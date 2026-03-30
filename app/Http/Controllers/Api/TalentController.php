<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Talent;
use Illuminate\Support\Facades\Validator;

class TalentController extends Controller
{
    // List semua talent
    public function index()
    {
        return response()->json(Talent::all());
    }

    // Detail talent
    public function show($id)
    {
        $talent = Talent::find($id);
        if (!$talent) {
            return response()->json(['message' => 'Talent not found'], 404);
        }
        return response()->json($talent);
    }

    // Tampilkan form tambah model
    public function create()
    {
        return view('addmodel');
    }

    // Tambah talent
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_model' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'age' => 'required|integer',
            'height' => 'required|integer',
            'weight' => 'required|integer',
            'shoes_size' => 'nullable|integer',
            'bust' => 'nullable|integer',
            'waist' => 'nullable|integer',
            'size' => 'nullable|string|max:50',
            // 'experience' => 'nullable|string', // HAPUS/COMMENT
            'experience_value' => 'required|integer|min:0', // TAMBAH
            'experience_unit' => 'required|string|max:10',  // TAMBAH
            'categories' => 'required|in:kids,teens',
            'status' => 'required|in:available,unavailable',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            if ($request->isMethod('post') && !$request->expectsJson()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
            $data['photo'] = $photoPath;
        } else {
            // Jangan pernah simpan path img/ di database
            if (isset($data['photo']) && substr($data['photo'], 0, 4) === 'img/') {
                unset($data['photo']);
            }
        }

        $talent = Talent::create($data);

        if ($request->isMethod('post') && !$request->expectsJson()) {
            return redirect()->route('models.list')->with('success', 'Model berhasil ditambahkan!');
        }

        return response()->json(['message' => 'Talent created', 'talent' => $talent], 201);
    }


    // Update talent (API & Web)
    public function update(Request $request, $id_model)
    {
        $model = \App\Models\Talent::findOrFail($id_model);
        $validator = Validator::make($request->all(), [
            'nama_model' => 'sometimes|required|string|max:100',
            'city' => 'sometimes|required|string|max:100',
            'age' => 'sometimes|required|integer',
            'height' => 'sometimes|required|integer',
            'weight' => 'sometimes|required|integer',
            'shoes_size' => 'nullable|integer',
            'bust' => 'nullable|integer',
            'waist' => 'nullable|integer',
            'size' => 'nullable|string|max:50',
            // 'experience' => 'nullable|string', // HAPUS/COMMENT
            'experience_value' => 'sometimes|required|integer|min:0', // TAMBAH
            'experience_unit' => 'sometimes|required|string|max:10',  // TAMBAH
            'categories' => 'sometimes|required|in:kids,teens',
            'status' => 'sometimes|required|in:available,unavailable',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        if ($request->hasFile('photo')) {
            // Hapus file lama jika ada dan bukan dari img/
            if ($model->photo && substr($model->photo, 0, 4) !== 'img/' && \Storage::disk('public')->exists($model->photo)) {
                \Storage::disk('public')->delete($model->photo);
            }
            $photo = $request->file('photo');
$photoName = time() . '_' . $photo->getClientOriginalName();
$photo->move(public_path('storage/photos'), $photoName);

// Simpan path relatifnya ke DB
$data['photo'] = 'photos/' . $photoName;

        } else {
            // Jangan pernah simpan path img/ di database
            if (isset($data['photo']) && substr($data['photo'], 0, 4) === 'img/') {
                unset($data['photo']);
            }
        }
        $model->update($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Talent updated', 'talent' => $model]);
        }
        return redirect()->route('portofolio.detail', $id_model)->with('success', 'Model berhasil diupdate!');
    }

    // Hapus talent (API & Web)
    public function destroy($id_model)
    {
        $talent = \App\Models\Talent::find($id_model);
        if (!$talent) {
            if (request()->expectsJson()) {
            return response()->json(['message' => 'Talent not found'], 404);
            }
            return redirect()->route('models.list')->with('error', 'Model tidak ditemukan!');
        }
        $talent->delete();
        if (request()->expectsJson()) {
        return response()->json(['message' => 'Talent deleted']);
        }
        return redirect()->route('models.list')->with('success', 'Model berhasil dihapus!');
    }

public function list()
{
    $models = \App\Models\Talent::inRandomOrder()->get();
    return view('model', compact('models'));
}

public function newModels()
{
    $models = \App\Models\Talent::orderByDesc('id_model')->take(8)->get();
    return $models;
}

public function portofolio()
{
    $models = \App\Models\Talent::all();
    $selected = $models->first();
    $portfolio = $selected ? \App\Models\Portfolio::where('id_model', $selected->id_model)->get() : collect();
    $portfolioSlots = [];
    foreach (range(1,10) as $slot) {
        $portfolioSlots[$slot] = $portfolio->firstWhere('description', 'slot_' . $slot);
    }
    // Career
    $career = $selected ? \App\Models\Career::where('id_model', $selected->id_model)->get() : collect();
    $careerSlots = [];
    foreach (range(1,6) as $slot) {
        $careerSlots[$slot] = $career->get($slot-1);
    }
    // Award
    $award = $selected ? \App\Models\Award::where('id_model', $selected->id_model)->get() : collect();
    $awardSlots = [];
    foreach (range(1,6) as $slot) {
        $awardSlots[$slot] = $award->get($slot-1);
    }
    return view('portofolio', compact('models', 'portfolioSlots', 'selected', 'careerSlots', 'awardSlots'));
}

public function portofolioDetail($id_model)
{
    $user = auth()->user();
    $model = \App\Models\Talent::findOrFail($id_model);
    $models = \App\Models\Talent::all();
    $isOwnerModel = $user && $user->role === 'model' && trim(strtolower($user->name)) === trim(strtolower($model->nama_model));
    if ($user && ($user->role === 'admin' || $isOwnerModel)) {
        $portfolio = \App\Models\Portfolio::where('id_model', $id_model)->get();
        $portfolioSlots = [];
        foreach (range(1,10) as $slot) {
            $portfolioSlots[$slot] = $portfolio->firstWhere('description', 'slot_' . $slot);
        }
        $career = \App\Models\Career::where('id_model', $id_model)->get();
        $careerSlots = [];
        foreach (range(1,6) as $slot) {
            $careerSlots[$slot] = $career->get($slot-1);
        }
        $award = \App\Models\Award::where('id_model', $id_model)->get();
        $awardSlots = [];
        foreach (range(1,6) as $slot) {
            $awardSlots[$slot] = $award->get($slot-1);
        }
        return view('portofolio', compact('model', 'models', 'portfolioSlots', 'careerSlots', 'awardSlots'));
    } else {
        $portfolio = \App\Models\Portfolio::where('id_model', $id_model)->get();
        $portfolioSlots = [];
        foreach (range(1,10) as $slot) {
            $portfolioSlots[$slot] = $portfolio->firstWhere('description', 'slot_' . $slot);
        }
    
        $portfolioSizes = [];
foreach ($portfolioSlots as $slot => $item) {
    if ($item && $item->photo) {
        $path = storage_path('app/public/' . $item->photo);
        if (file_exists($path)) {
            [$width, $height] = getimagesize($path);

            // Scale up jika terlalu kecil
            if ($width < 1000 || $height < 800) {
                $scale = min(1200 / $width, 1200 / $height);
                $width = intval($width * $scale);
                $height = intval($height * $scale);
            }

            $portfolioSizes[$slot] = ['width' => $width, 'height' => $height];
        } else {
            $portfolioSizes[$slot] = ['width' => 1200, 'height' => 900]; // fallback
        }
    }
}

        $career = \App\Models\Career::where('id_model', $id_model)->get();
        $careerSlots = [];
        foreach (range(1,6) as $slot) {
            $careerSlots[$slot] = $career->get($slot-1);
        }
        $award = \App\Models\Award::where('id_model', $id_model)->get();
        $awardSlots = [];
        foreach (range(1,6) as $slot) {
            $awardSlots[$slot] = $award->get($slot-1);
        }
        return view('portofolioasli', compact('model', 'models', 'portfolioSlots', 'careerSlots', 'awardSlots', 'portfolioSizes'));
    }
}

public function edit($id_model)
{
    $model = \App\Models\Talent::findOrFail($id_model);
    return view('editmodel', compact('model'));
}

    public function uploadPortfolio(Request $request, $id_model, $slot)
    {
        // Otorisasi: hanya admin atau model pemilik nama yang sama
        $this->authorizePortfolio($id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        $photo = $request->file('photo');
$photoName = time() . '_' . $photo->getClientOriginalName();
$photo->move(public_path('storage/portfolio'), $photoName);

$photoPath = 'portfolio/' . $photoName; // path relatif yang akan disimpan ke DB

$model = \App\Models\Talent::findOrFail($id_model);
$portfolio = \App\Models\Portfolio::updateOrCreate(
    [
        'id_model' => $id_model,
        'description' => 'slot_' . $slot
    ],
    [
        'nama_model' => $model->nama_model,
        'photo' => $photoPath
    ]
);

        return back()->with('success', 'Foto portofolio berhasil diupload!');
    }

    public function updatePortfolio(Request $request, $id_model, $slot)
    {
        // Otorisasi: hanya admin atau model pemilik nama yang sama
        $this->authorizePortfolio($id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        $portfolio = \App\Models\Portfolio::where('id_model', $id_model)->where('description', 'slot_' . $slot)->first();
        if ($portfolio && $portfolio->photo && \Storage::disk('public')->exists($portfolio->photo)) {
            \Storage::disk('public')->delete($portfolio->photo);
        }
        $photoPath = $request->file('photo')->store('portfolio', 'public');
        if ($portfolio) {
            $portfolio->update(['photo' => $photoPath]);
        }
        return back()->with('success', 'Foto portofolio berhasil diupdate!');
    }

    public function deletePortfolio(Request $request, $id_model, $slot)
    {
        // Otorisasi: hanya admin atau model pemilik nama yang sama
        $this->authorizePortfolio($id_model);
        $portfolio = \App\Models\Portfolio::where('id_model', $id_model)->where('description', 'slot_' . $slot)->first();
        if ($portfolio) {
            if ($portfolio->photo && \Storage::disk('public')->exists($portfolio->photo)) {
                \Storage::disk('public')->delete($portfolio->photo);
            }
            $portfolio->delete();
        }
        return back()->with('success', 'Foto portofolio berhasil dihapus!');
    }

    // CAREER
    public function uploadCareer(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio($id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'event_name' => 'required|string|max:255',
            'year' => 'required|string|max:10',
        ]);
        $photoPath = $request->file('photo')->store('career', 'public');
        \App\Models\Career::updateOrCreate(
            [
                'id_model' => $id_model,
                'event_name' => $request->event_name,
                'year' => $request->year,
            ],
            [
                'photo' => $photoPath
            ]
        );
        return back()->with('success', 'Career berhasil diupload!');
    }
    public function updateCareer(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio($id_model);
        $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'event_name' => 'required|string|max:255',
            'year' => 'required|string|max:10',
        ]);
        $career = \App\Models\Career::where('id_model', $id_model)->skip($slot-1)->first();
        if ($career) {
            if ($request->hasFile('photo')) {
                if ($career->photo && \Storage::disk('public')->exists($career->photo)) {
                    \Storage::disk('public')->delete($career->photo);
                }
                $career->photo = $request->file('photo')->store('career', 'public');
            }
            $career->event_name = $request->event_name;
            $career->year = $request->year;
            $career->save();
        }
        return back()->with('success', 'Career berhasil diupdate!');
    }
    public function deleteCareer(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio($id_model);
        $career = \App\Models\Career::where('id_model', $id_model)->skip($slot-1)->first();
        if ($career) {
            if ($career->photo && \Storage::disk('public')->exists($career->photo)) {
                \Storage::disk('public')->delete($career->photo);
            }
            $career->delete();
        }
        return back()->with('success', 'Career berhasil dihapus!');
    }
    // AWARD
    public function uploadAward(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio($id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        $photoPath = $request->file('photo')->store('award', 'public');
        \App\Models\Award::updateOrCreate(
            [
                'id_model' => $id_model,
                'photo' => $photoPath,
            ],
            []
        );
        return back()->with('success', 'Award berhasil diupload!');
    }
    public function updateAward(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio($id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        $award = \App\Models\Award::where('id_model', $id_model)->skip($slot-1)->first();
        if ($award) {
            if ($award->photo && \Storage::disk('public')->exists($award->photo)) {
                \Storage::disk('public')->delete($award->photo);
            }
            $award->photo = $request->file('photo')->store('award', 'public');
            $award->save();
        }
        return back()->with('success', 'Award berhasil diupdate!');
    }
    public function deleteAward(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio($id_model);
        $award = \App\Models\Award::where('id_model', $id_model)->skip($slot-1)->first();
        if ($award) {
            if ($award->photo && \Storage::disk('public')->exists($award->photo)) {
                \Storage::disk('public')->delete($award->photo);
            }
            $award->delete();
        }
        return back()->with('success', 'Award berhasil dihapus!');
    }

    private function authorizePortfolio(int $id_model): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        if ($user->role === 'admin') {
            return; // admin selalu boleh
        }
        if ($user->role === 'model') {
            $model = \App\Models\Talent::findOrFail($id_model);
            // Cocokkan berdasarkan nama
            if (trim(strtolower($user->name)) === trim(strtolower($model->nama_model))) {
                return;
            }
        }
        abort(403, 'Anda tidak memiliki akses untuk mengubah portofolio ini');
    }
}
