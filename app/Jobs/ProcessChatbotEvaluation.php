<?php

namespace App\Jobs;

use App\Http\Controllers\ChatbotController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Queue Job: jalankan evaluation.py (Python) dan simpan hasil ke Cache.
 * Queue name: chatbot-training (SAMA dengan ProcessChatbotTraining -- satu
 * worker, satu job dalam satu waktu; lihat evaluationStatus() di
 * ChatbotController untuk cara UI membedakan "menunggu training" vs
 * "worker tidak aktif").
 *
 * Root cause dipindah ke sini (bukan lagi jalan synchronous di dalam request
 * HTTP php artisan serve): proses python.exe yang di-spawn LANGSUNG dari
 * dalam request handler php artisan serve (dev server bawaan PHP, bukan
 * server produksi) terbukti KONSISTEN gagal dengan
 * `OSError: [WinError 10106] The requested service provider could not be
 * loaded or initialized` (WSAEPROVIDERFAILEDINIT) saat proses baru itu
 * meng-import asyncio -> asyncio.windows_events -> _overlapped. Proses YANG
 * SAMA PERSIS, di-spawn dari proses PHP CLI apa pun selain php artisan
 * serve's request handler (tinker, queue worker), terbukti tidak pernah
 * gagal dengan cara ini pada environment yang sama.
 *
 * Anti-overlap: ShouldBeUnique + WithoutOverlapping (satu evaluasi aktif),
 * pola PERSIS ProcessChatbotTraining.
 */
class ProcessChatbotEvaluation implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const LOCK_KEY = 'chatbot:evaluation:active';

    /** Hasil evaluasi terakhir (success/failed + metrik), dibaca endpoint polling /admin/chatbot-evaluation/status. */
    public const RESULT_KEY = 'chatbot:evaluation:last_result';

    public int $timeout = 900;

    public int $tries = 1;

    /** Unique lock bertahan maksimal 15 menit. */
    public int $uniqueFor = 900;

    public function __construct()
    {
        $this->onQueue('chatbot-training');
    }

    public function uniqueId(): string
    {
        return 'chatbot-evaluation';
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('chatbot-evaluation'))
                ->expireAfter(900)
                ->dontRelease(),
        ];
    }

    public function handle(): void
    {
        $cwd = base_path('chatbot_hybrid/nlp_pipeline');
        $script = $cwd . DIRECTORY_SEPARATOR . 'evaluation.py';
        $resultPath = $cwd . DIRECTORY_SEPARATOR . 'evaluation_result.json';

        Cache::put(self::LOCK_KEY, [
            'status' => 'running',
            'started_at' => now()->toDateTimeString(),
        ], now()->addSeconds(900));

        try {
            if (!is_file($script)) {
                $this->storeFailure('evaluation.py tidak ditemukan.');
                return;
            }

            clearstatcache(true, $resultPath);
            $beforeMtime = is_file($resultPath) ? (int) filemtime($resultPath) : 0;
            $beforeGeneratedAt = null;
            $beforeReport = ChatbotController::loadEvaluationResult();
            if (is_array($beforeReport) && !empty($beforeReport['generated_at'])) {
                $beforeGeneratedAt = (string) $beforeReport['generated_at'];
            }

            $commands = ChatbotController::evaluationPythonCommands();
            $ranOk = false;
            $outputParts = [];
            $exitCode = 1;
            $usedCommand = null;
            $chunk = '';

            foreach ($commands as $cmd) {
                $runCmd = $cmd;
                // Di Windows, spawn lewat cmd sering lebih stabil untuk interpreter absolut.
                if (PHP_OS_FAMILY === 'Windows' && isset($cmd[0]) && str_ends_with(strtolower($cmd[0]), '.exe')) {
                    $runCmd = array_merge(['cmd', '/c'], $cmd);
                }

                // Retry singkat KHUSUS untuk startup crash asyncio/_overlapped (native Windows
                // extension loading yang terbukti rapuh secara acak -- dikonfirmasi terjadi
                // di proses APAPUN yang import sklearn/joblib, bukan bug logic evaluation.py).
                // TIDAK memicu retry untuk kegagalan evaluasi biasa (logic error dll).
                $maxAttempts = 2;
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $process = Process::path($cwd)
                        ->timeout(300)
                        ->run($runCmd);

                    $chunk = trim($process->output() . "\n" . $process->errorOutput());
                    $exitCode = $process->exitCode() ?? 1;

                    $isStartupCrash = $exitCode !== 0
                        && str_contains($chunk, 'asyncio')
                        && (str_contains($chunk, '_overlapped') || str_contains($chunk, '_winapi'));

                    if ($exitCode === 0 || !$isStartupCrash || $attempt >= $maxAttempts) {
                        break;
                    }

                    Log::warning('[ProcessChatbotEvaluation] startup crash terdeteksi (asyncio/_overlapped), retry', [
                        'command' => implode(' ', $runCmd),
                        'attempt' => $attempt,
                    ]);
                    usleep(1500000);
                }

                $outputParts[] = '$ ' . implode(' ', $runCmd) . " [exit={$exitCode}]\n" . $chunk;

                // Hanya anggap jalan jika exit code berhasil (jangan andalkan file lama).
                if ($exitCode === 0) {
                    $ranOk = true;
                    $usedCommand = implode(' ', $runCmd);
                    break;
                }
            }

            $output = trim(implode("\n---\n", $outputParts));

            if (!$ranOk) {
                $this->storeFailure(
                    'Evaluasi gagal dijalankan (exit code tidak berhasil).',
                    mb_substr($output, -4000),
                    $exitCode
                );
                return;
            }

            clearstatcache(true, $resultPath);
            $report = ChatbotController::loadEvaluationResult();
            if (!$report || !is_file($resultPath)) {
                $this->storeFailure(
                    'Evaluasi selesai tapi hasil tidak tersimpan.',
                    mb_substr($output, -4000),
                    $exitCode
                );
                return;
            }

            $afterMtime = (int) filemtime($resultPath);
            $afterGeneratedAt = isset($report['generated_at']) ? (string) $report['generated_at'] : null;
            $fileUpdated = $afterMtime > $beforeMtime
                || ($afterGeneratedAt !== null && $afterGeneratedAt !== $beforeGeneratedAt);

            if (!$fileUpdated) {
                $this->storeFailure(
                    'Evaluasi tidak menghasilkan file hasil yang baru (timestamp/generated_at tidak berubah).',
                    mb_substr($output, -4000),
                    $exitCode
                );
                return;
            }

            Cache::put(self::RESULT_KEY, [
                'status' => 'success',
                'metrics' => $report['metrics'] ?? [],
                'generated_at' => $afterGeneratedAt,
                'finished_at' => now()->toDateTimeString(),
            ], now()->addMinutes(15));

            Log::info('[ProcessChatbotEvaluation] Evaluasi model selesai', [
                'accuracy' => $report['metrics']['accuracy'] ?? null,
                'command' => $usedCommand,
                'generated_at' => $afterGeneratedAt,
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProcessChatbotEvaluation] ' . $e->getMessage());
            $this->storeFailure('Gagal menjalankan evaluasi: ' . $e->getMessage());
        } finally {
            Cache::forget(self::LOCK_KEY);
        }
    }

    private function storeFailure(string $error, ?string $detail = null, ?int $exitCode = null): void
    {
        Cache::put(self::RESULT_KEY, [
            'status' => 'failed',
            'error' => $error,
            'detail' => $detail,
            'exit_code' => $exitCode,
            'finished_at' => now()->toDateTimeString(),
        ], now()->addMinutes(15));

        Log::warning('[ProcessChatbotEvaluation] Evaluasi gagal', [
            'error' => $error,
            'exit_code' => $exitCode,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Cache::forget(self::LOCK_KEY);

        Cache::put(self::RESULT_KEY, [
            'status' => 'failed',
            'error' => $exception?->getMessage() ?? 'Job gagal total (timeout/unknown).',
            'finished_at' => now()->toDateTimeString(),
        ], now()->addMinutes(15));

        Log::error('[ProcessChatbotEvaluation] Job gagal total', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
