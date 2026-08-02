<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Talent;

class ChatbotModelController extends Controller
{
    public function index()
    {
        $models = Talent::select(
                'id_model',
                'nama_model',
                'categories',
                'status',
                'city',
                'age',
                'height',
                'photo'
            )
            ->orderBy('nama_model')
            ->get();

        return response()->json([
            'success' => true,
            'count' => $models->count(),
            'models' => $models
        ]);
    }

    public function search(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (!$q) {
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'Query kosong'
            ], 400);
        }

        $query = Talent::select(
            'id_model',
            'nama_model',
            'categories',
            'status',
            'city',
            'age',
            'height',
            'photo'
        );

        // 1. Full search
        $model = (clone $query)
            ->where('nama_model', 'like', '%' . $q . '%')
            ->orderBy('nama_model')
            ->first();

        // 2. Fallback per kata
        if (!$model) {
            $words = preg_split('/\s+/', $q);

            foreach ($words as $word) {
                if (!$word) continue;

                $model = (clone $query)
                    ->where('nama_model', 'like', '%' . $word . '%')
                    ->orderBy('nama_model')
                    ->first();

                if ($model) {
                    break;
                }
            }
        }

        // 3. Fallback startswith kata pertama
        if (!$model) {
            $firstWord = explode(' ', $q)[0];

            $model = (clone $query)
                ->where('nama_model', 'like', $firstWord . '%')
                ->orderBy('nama_model')
                ->first();
        }

        if (!$model) {
            return response()->json([
                'success' => true,
                'found' => false,
                'message' => 'Model tidak ditemukan'
            ]);
        }

        return response()->json([
            'success' => true,
            'found' => true,
            'model' => $model
        ]);
    }

    public function count()
    {
        $count = Talent::count();
        $available = Talent::where('status', 'available')->count();

        return response()->json([
            'success' => true,
            'count' => $count,
            'available_count' => $available,
        ]);
    }
}
