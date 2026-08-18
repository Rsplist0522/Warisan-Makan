<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodTrailSuggestion;
use Illuminate\Http\Request;

class FoodTrailSuggestionController extends Controller
{
    public function index()
    {
        return view('admin.food-trails.index', ['suggestions' => FoodTrailSuggestion::latest()->get()]);
    }

    public function create()
    {
        return view('admin.food-trails.form', ['suggestion' => new FoodTrailSuggestion(), 'locations' => $this->locations(), 'categories' => $this->categories()]);
    }

    public function store(Request $request)
    {
        FoodTrailSuggestion::create($this->validated($request));
        return redirect()->route('admin.food-trails.index')->with('status', 'Food trail suggestion added.');
    }

    public function edit(FoodTrailSuggestion $foodTrail)
    {
        return view('admin.food-trails.form', ['suggestion' => $foodTrail, 'locations' => $this->locations(), 'categories' => $this->categories()]);
    }

    public function update(Request $request, FoodTrailSuggestion $foodTrail)
    {
        $foodTrail->update($this->validated($request));
        return redirect()->route('admin.food-trails.index')->with('status', 'Food trail suggestion updated.');
    }

    public function destroy(FoodTrailSuggestion $foodTrail)
    {
        $foodTrail->delete();
        return redirect()->route('admin.food-trails.index')->with('status', 'Food trail suggestion removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:160'],
            'summary' => ['required', 'string', 'max:1000'],
            'location' => ['required', 'in:Kuala Lumpur,Penang,Melaka,Johor Bahru,Ipoh'],
            'category' => ['required', 'in:All,Street Food,Dessert,Seafood,Snacks'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function locations(): array { return ['Kuala Lumpur', 'Penang', 'Melaka', 'Johor Bahru', 'Ipoh']; }
    private function categories(): array { return ['All', 'Street Food', 'Dessert', 'Seafood', 'Snacks']; }
}
