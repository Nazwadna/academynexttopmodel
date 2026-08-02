<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeaturedModelsController extends Controller
{
    public function welcome()
    {
        try {
            // Nama tabel mengikuti academyntm.sql (hasil import phpMyAdmin)
            $featuredTalents = DB::table('featured_talents')
                ->leftJoin('models', 'featured_talents.id_model', '=', 'models.id_model')
                ->orderBy('featured_talents.order')
                ->limit(8)
                ->select(
                    'featured_talents.*',
                    'models.nama_model',
                    'models.photo',
                    'models.categories',
                    'models.id_model'
                )
                ->get();

            // Hanya tampilkan gambar yang benar-benar ada & tidak kosong (hindari broken image).
            $ourTalents = $this->validPublicImages(
                DB::table('ourtalents')->orderBy('order')->get(),
                8
            );
            $popularTalents = $this->validPublicImages(
                DB::table('popular_talents')->orderBy('order')->get(),
                8
            );
            $hireModels = DB::table('models')->inRandomOrder()->limit(10)->get();
        } catch (\Throwable $e) {
            // Fallback aman jika MySQL down — halaman welcome tetap bisa dibuka
            \Log::warning('FeaturedModelsController::welcome DB unavailable: '.$e->getMessage());
            $featuredTalents = collect();
            $ourTalents = collect();
            $popularTalents = collect();
            $hireModels = collect();
        }

        return view('welcome', compact('featuredTalents', 'ourTalents', 'popularTalents', 'hireModels'));
    }

    /**
     * Filter baris yang punya file storage publik valid (ada & size > 0).
     */
    private function validPublicImages($rows, int $limit = 8)
    {
        return collect($rows)
            ->filter(function ($row) {
                $rel = ltrim((string) ($row->image ?? ''), '/');
                if ($rel === '') {
                    return false;
                }
                $path = storage_path('app/public/' . $rel);
                return is_file($path) && filesize($path) > 0;
            })
            ->take($limit)
            ->values();
    }

    public function saveFeaturedTalents(Request $request)
    {
        $modelIds = $request->input('model_ids', []);

        DB::table('featured_talents')->truncate();

        foreach (range(0, 7) as $index) {
            DB::table('featured_talents')->insert([
                'id_model' => $modelIds[$index] ?? null,
                'order' => $index,
            ]);
        }

        return redirect()->back()->with('success', 'Featured talents berhasil disimpan.');
    }
}
