<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PopularTalent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PopularTalentController extends Controller
{
    public function index()
    {
        $items = PopularTalent::orderBy('order')->get()->values();
        return view('admin.populartalent.index', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $uploaded = $request->file('images');
        $files = is_array($uploaded) ? $uploaded : [];
        $files = array_values(array_filter($files, static function ($file) {
            return $file instanceof UploadedFile
                && $file->isValid()
                && (int) $file->getSize() > 0;
        }));

        // Tolak sebelum delete/truncate: empty/[null]/0-byte tidak boleh lanjut.
        if (count($files) < 1) {
            throw ValidationException::withMessages([
                'images' => ['Pilih minimal satu gambar yang valid (tidak kosong).'],
            ]);
        }

        DB::transaction(function () use ($files) {
            PopularTalent::query()->delete();

            $order = 0;
            foreach ($files as $image) {
                $path = $image->store('popular_talents', 'public');
                $full = storage_path('app/public/' . ltrim($path, '/'));
                if (!is_file($full) || filesize($full) <= 0) {
                    if (is_file($full)) {
                        @unlink($full);
                    }
                    continue;
                }

                PopularTalent::create([
                    'image' => $path,
                    'order' => $order,
                ]);
                $order++;
            }

            if ($order < 1) {
                throw ValidationException::withMessages([
                    'images' => ['Gagal menyimpan gambar. Pastikan file tidak rusak.'],
                ]);
            }
        });

        return redirect()->back()->with('success', 'Popular Talents berhasil disimpan.');
    }
}
