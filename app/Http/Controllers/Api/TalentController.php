<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Career;
use App\Models\Portfolio;
use App\Models\Talent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TalentController extends Controller
{
    public function index()
    {
        return response()->json(Talent::all());
    }

    public function show($id)
    {
        $talent = Talent::find($id);
        if (!$talent) {
            return response()->json(['message' => 'Talent not found'], 404);
        }

        return response()->json($talent);
    }

    public function create()
    {
        return view('addmodel');
    }

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
            'experience_value' => 'required|integer|min:0',
            'experience_unit' => 'required|string|max:10',
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
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        } elseif (isset($data['photo']) && str_starts_with((string) $data['photo'], 'img/')) {
            unset($data['photo']);
        }

        $talent = Talent::create($data);

        if ($request->isMethod('post') && !$request->expectsJson()) {
            return redirect()->route('models.list')->with('success', 'Model berhasil ditambahkan!');
        }

        return response()->json(['message' => 'Talent created', 'talent' => $talent], 201);
    }

    public function update(Request $request, $id_model)
    {
        $model = Talent::findOrFail($id_model);

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
            'experience_value' => 'sometimes|required|integer|min:0',
            'experience_unit' => 'sometimes|required|string|max:10',
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
            if ($model->photo && !str_starts_with((string) $model->photo, 'img/') && Storage::disk('public')->exists($model->photo)) {
                Storage::disk('public')->delete($model->photo);
            }

            $photo = $request->file('photo');
            $photoName = time() . '_' . $photo->getClientOriginalName();
            $photo->move(public_path('storage/photos'), $photoName);
            $data['photo'] = 'photos/' . $photoName;
        } elseif (isset($data['photo']) && str_starts_with((string) $data['photo'], 'img/')) {
            unset($data['photo']);
        }

        $model->update($data);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Talent updated', 'talent' => $model]);
        }

        return redirect()->route('portofolio.detail', $id_model)->with('success', 'Model berhasil diupdate!');
    }

    public function destroy($id_model)
    {
        $talent = Talent::find($id_model);
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
        $models = Talent::inRandomOrder()->get();

        return view('model', compact('models'));
    }

    public function newModels()
    {
        return Talent::orderByDesc('id_model')->take(8)->get();
    }

    public function portofolio()
    {
        $models = Talent::all();
        $selected = $models->first();
        $portfolio = $selected ? Portfolio::where('id_model', $selected->id_model)->get() : collect();
        $portfolioSlots = [];
        foreach (range(1, 10) as $slot) {
            $portfolioSlots[$slot] = $portfolio->firstWhere('description', 'slot_' . $slot);
        }

        $career = $selected ? Career::where('id_model', $selected->id_model)->get() : collect();
        $careerSlots = [];
        foreach (range(1, 6) as $slot) {
            $careerSlots[$slot] = $career->get($slot - 1);
        }

        $award = $selected ? Award::where('id_model', $selected->id_model)->get() : collect();
        $awardSlots = [];
        foreach (range(1, 6) as $slot) {
            $awardSlots[$slot] = $award->get($slot - 1);
        }

        return view('portofolio', compact('models', 'portfolioSlots', 'selected', 'careerSlots', 'awardSlots'));
    }

    public function portofolioDetail($id_model)
    {
        $user = auth()->user();
        $model = Talent::findOrFail($id_model);
        $models = Talent::all();
        $isOwnerModel = $user
            && $user->role === 'model'
            && trim(strtolower((string) $user->name)) === trim(strtolower((string) $model->nama_model));

        $portfolio = Portfolio::where('id_model', $id_model)->get();
        $portfolioSlots = [];
        foreach (range(1, 10) as $slot) {
            $portfolioSlots[$slot] = $portfolio->firstWhere('description', 'slot_' . $slot);
        }

        $career = Career::where('id_model', $id_model)->get();
        $careerSlots = [];
        foreach (range(1, 6) as $slot) {
            $careerSlots[$slot] = $career->get($slot - 1);
        }

        $award = Award::where('id_model', $id_model)->get();
        $awardSlots = [];
        foreach (range(1, 6) as $slot) {
            $awardSlots[$slot] = $award->get($slot - 1);
        }

        if ($user && ($user->role === 'admin' || $isOwnerModel)) {
            return view('portofolio', compact('model', 'models', 'portfolioSlots', 'careerSlots', 'awardSlots'));
        }

        $portfolioSizes = [];
        foreach ($portfolioSlots as $slot => $item) {
            if ($item && $item->photo) {
                $path = storage_path('app/public/' . $item->photo);
                if (file_exists($path)) {
                    [$width, $height] = getimagesize($path);
                    if ($width < 1000 || $height < 800) {
                        $scale = min(1200 / $width, 1200 / $height);
                        $width = (int) ($width * $scale);
                        $height = (int) ($height * $scale);
                    }
                    $portfolioSizes[$slot] = ['width' => $width, 'height' => $height];
                } else {
                    $portfolioSizes[$slot] = ['width' => 1200, 'height' => 900];
                }
            }
        }

        return view('portofolioasli', compact('model', 'models', 'portfolioSlots', 'careerSlots', 'awardSlots', 'portfolioSizes'));
    }

    public function edit($id_model)
    {
        $model = Talent::findOrFail($id_model);

        return view('editmodel', compact('model'));
    }

    public function uploadPortfolio(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio((int) $id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $photo = $request->file('photo');
        $photoName = time() . '_' . $photo->getClientOriginalName();
        $photo->move(public_path('storage/portfolio'), $photoName);
        $photoPath = 'portfolio/' . $photoName;

        $model = Talent::findOrFail($id_model);
        Portfolio::updateOrCreate(
            [
                'id_model' => $id_model,
                'description' => 'slot_' . $slot,
            ],
            [
                'nama_model' => $model->nama_model,
                'photo' => $photoPath,
            ]
        );

        return back()->with('success', 'Foto portofolio berhasil diupload!');
    }

    public function updatePortfolio(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio((int) $id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $portfolio = Portfolio::where('id_model', $id_model)->where('description', 'slot_' . $slot)->first();
        if ($portfolio && $portfolio->photo && Storage::disk('public')->exists($portfolio->photo)) {
            Storage::disk('public')->delete($portfolio->photo);
        }

        $photoPath = $request->file('photo')->store('portfolio', 'public');
        if ($portfolio) {
            $portfolio->update(['photo' => $photoPath]);
        }

        return back()->with('success', 'Foto portofolio berhasil diupdate!');
    }

    public function deletePortfolio(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio((int) $id_model);
        $portfolio = Portfolio::where('id_model', $id_model)->where('description', 'slot_' . $slot)->first();
        if ($portfolio) {
            if ($portfolio->photo && Storage::disk('public')->exists($portfolio->photo)) {
                Storage::disk('public')->delete($portfolio->photo);
            }
            $portfolio->delete();
        }

        return back()->with('success', 'Foto portofolio berhasil dihapus!');
    }

    public function uploadCareer(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio((int) $id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'event_name' => 'required|string|max:255',
            'year' => 'required|string|max:10',
        ]);

        $photoPath = $request->file('photo')->store('career', 'public');
        Career::updateOrCreate(
            [
                'id_model' => $id_model,
                'event_name' => $request->event_name,
                'year' => $request->year,
            ],
            [
                'photo' => $photoPath,
            ]
        );

        return back()->with('success', 'Career berhasil diupload!');
    }

    public function updateCareer(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio((int) $id_model);
        $request->validate([
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'event_name' => 'required|string|max:255',
            'year' => 'required|string|max:10',
        ]);

        $career = Career::where('id_model', $id_model)->skip($slot - 1)->first();
        if ($career) {
            if ($request->hasFile('photo')) {
                if ($career->photo && Storage::disk('public')->exists($career->photo)) {
                    Storage::disk('public')->delete($career->photo);
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
        $this->authorizePortfolio((int) $id_model);
        $career = Career::where('id_model', $id_model)->skip($slot - 1)->first();
        if ($career) {
            if ($career->photo && Storage::disk('public')->exists($career->photo)) {
                Storage::disk('public')->delete($career->photo);
            }
            $career->delete();
        }

        return back()->with('success', 'Career berhasil dihapus!');
    }

    public function uploadAward(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio((int) $id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $photoPath = $request->file('photo')->store('award', 'public');
        Award::updateOrCreate(
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
        $this->authorizePortfolio((int) $id_model);
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $award = Award::where('id_model', $id_model)->skip($slot - 1)->first();
        if ($award) {
            if ($award->photo && Storage::disk('public')->exists($award->photo)) {
                Storage::disk('public')->delete($award->photo);
            }
            $award->photo = $request->file('photo')->store('award', 'public');
            $award->save();
        }

        return back()->with('success', 'Award berhasil diupdate!');
    }

    public function deleteAward(Request $request, $id_model, $slot)
    {
        $this->authorizePortfolio((int) $id_model);
        $award = Award::where('id_model', $id_model)->skip($slot - 1)->first();
        if ($award) {
            if ($award->photo && Storage::disk('public')->exists($award->photo)) {
                Storage::disk('public')->delete($award->photo);
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
            return;
        }

        if ($user->role === 'model') {
            $model = Talent::findOrFail($id_model);
            if (trim(strtolower((string) $user->name)) === trim(strtolower((string) $model->nama_model))) {
                return;
            }
        }

        abort(403, 'Anda tidak memiliki akses untuk mengubah portofolio ini');
    }
}
