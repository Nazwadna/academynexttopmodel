<?php

namespace App\Http\Controllers;

use App\Models\OurTalent;
use Illuminate\Http\Request;

class OurTalentController extends Controller
{
    public function index()
    {
        $items = OurTalent::orderBy('order')->get();
        return view('admin.ourtalent.index', compact('items'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'images.*' => 'image|max:2048',
        ]);

        OurTalent::truncate(); // remove old ones

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('ourtalents', 'public');

            OurTalent::create([
                'image' => $path,
                'order' => $index,
            ]);
        }

        return redirect()->back()->with('success', 'Gambar berhasil disimpan.');
    }
}
