<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Queue Job: latih ulang chatbot lewat Flask NLP Service.
 * Queue name: chatbot-training
 *
 * Default: Naive Bayes + TF-IDF (POST /train/naive-bayes)
 * Opsional: TensorFlow jika payload berisi training_data + mode=tensorflow
 *
 * Anti-overlap: ShouldBeUnique + WithoutOverlapping (satu training aktif).
 */
class ProcessChatbotTraining implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const LOCK_KEY = 'chatbot:training:active';

    public int $timeout = 320;

    public int $tries = 1;

    /** Unique lock bertahan maksimal 10 menit. */
    public int $uniqueFor = 600;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload)
    {
        $this->onQueue('chatbot-training');
    }

    public function uniqueId(): string
    {
        return 'chatbot-training';
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('chatbot-training'))
                ->expireAfter(600)
                ->dontRelease(),
        ];
    }

    public function handle(): void
    {
        $baseUrl = rtrim((string) env('CHATBOT_API_URL', 'http://127.0.0.1:5000'), '/');
        $mode = strtolower((string) ($this->payload['mode'] ?? 'naive_bayes'));

        $url = ($mode === 'tensorflow' && !empty($this->payload['training_data']))
            ? $baseUrl . '/train'
            : $baseUrl . '/train/naive-bayes';

        Cache::put(self::LOCK_KEY, [
            'status' => 'running',
            'mode' => $mode,
            'started_at' => now()->toDateTimeString(),
        ], now()->addSeconds(600));

        Log::info('[ProcessChatbotTraining] Mengirim request ke Flask', [
            'url' => $url,
            'mode' => $mode,
        ]);

        try {
            $response = Http::timeout(300)->post($url, $this->payload);
        } catch (ConnectionException $e) {
            Log::error('[ProcessChatbotTraining] Flask offline / tidak dapat terhubung', [
                'url' => $url,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);

            Cache::forget(self::LOCK_KEY);

            $this->fail(new \RuntimeException(
                'Flask NLP Service offline atau tidak dapat dihubungi di ' . $url,
                0,
                $e
            ));

            return;
        } catch (\Throwable $e) {
            Log::error('[ProcessChatbotTraining] Training error', [
                'url' => $url,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);

            Cache::forget(self::LOCK_KEY);

            $this->fail($e instanceof \Exception
                ? $e
                : new \RuntimeException($e->getMessage(), 0, $e)
            );

            return;
        }

        if (!$response->successful()) {
            Log::error('[ProcessChatbotTraining] Training gagal', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            Cache::forget(self::LOCK_KEY);

            $this->fail(new \RuntimeException(
                'Flask training gagal dengan status HTTP ' . $response->status()
            ));

            return;
        }

        Cache::forget(self::LOCK_KEY);

        Log::info('[ProcessChatbotTraining] Training selesai', [
            'status' => $response->status(),
            'result' => $response->json(),
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Cache::forget(self::LOCK_KEY);

        Log::error('[ProcessChatbotTraining] Job gagal total', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
