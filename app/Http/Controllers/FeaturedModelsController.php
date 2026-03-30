<?php

namespace App\Http\Controllers;

use App\Models\PopularTalent;
use Illuminate\Http\Request;
use App\Models\FeaturedTalent;
use App\Models\OurTalent;

class FeaturedModelsController extends Controller
{
    public function welcome()
    {
        $featuredTalents = FeaturedTalent::with('talent')->orderBy('order')->get();
        $ourTalents = OurTalent::orderBy('order')->take(8)->get();
        $popularTalents = PopularTalent::orderBy('order')->take(8)->get();
        $hireModels = \App\Models\Talent::inRandomOrder()->take(10)->get();
    
        return view('welcome', compact('featuredTalents', 'ourTalents', 'popularTalents', 'hireModels'));
    }

    public function saveFeaturedTalents(Request $request)
{
    $modelIds = $request->input('model_ids', []);

    FeaturedTalent::truncate();

    foreach (range(0, 7) as $index) {
        FeaturedTalent::create([
            'id_model' => $modelIds[$index] ?? null,
            'order' => $index,
        ]);
    }

    return redirect()->back()->with('success', 'Featured talents berhasil disimpan.');
}
}


