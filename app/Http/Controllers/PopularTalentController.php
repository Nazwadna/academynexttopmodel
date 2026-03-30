<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PopularTalent;

class PopularTalentController extends Controller
{
    public function index()
{
    $items = PopularTalent::orderBy('order')->get()->values(); // 👈 ini penting
    return view('admin.populartalent.index', compact('items'));
}


     public function store(Request $request)
    {
        $request->validate([
            'images.*' => 'image|max:2048',
        ]);

        PopularTalent::truncate();

        foreach ($request->file('images') as $index => $image) {
            if (!$image) continue;

            $path = $image->store('popular_talents', 'public');

            PopularTalent::create([
                'image' => $path,
                'order' => $index,
            ]);
        }

        return redirect()->back()->with('success', 'Popular Talents berhasil disimpan.');
    }
}

