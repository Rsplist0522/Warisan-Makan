<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodTrailSuggestion;
use App\Models\HeritageShop;
use Illuminate\Http\Request;

class FoodTrailSuggestionController extends Controller
{
    public function index()
    {
        return view('admin.food-trails.index', ['suggestions' => FoodTrailSuggestion::withCount('restaurants')->latest()->get()]);
    }

    public function create()
    {
        return $this->form(new FoodTrailSuggestion());
    }

    public function store(Request $request)
    {
        $suggestion = FoodTrailSuggestion::create($this->validated($request)); $this->syncRestaurants($suggestion, $request->input('restaurant_ids', []));
        return redirect()->route('admin.food-trails.index')->with('status', 'Food trail suggestion added.');
    }

    public function edit(FoodTrailSuggestion $foodTrail)
    {
        return $this->form($foodTrail);
    }

    public function update(Request $request, FoodTrailSuggestion $foodTrail)
    {
        $foodTrail->update($this->validated($request)); $this->syncRestaurants($foodTrail, $request->input('restaurant_ids', []));
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
        $request->validate(['restaurant_ids' => ['required','array','min:1'], 'restaurant_ids.*' => ['integer','distinct','exists:heritage_shops,id']]);

        return $data;
    }

    private function locations(): array { return ['Kuala Lumpur', 'Penang', 'Melaka', 'Johor Bahru', 'Ipoh']; }
    private function categories(): array { return ['All', 'Street Food', 'Dessert', 'Seafood', 'Snacks']; }
    private function form(FoodTrailSuggestion $suggestion) { return view('admin.food-trails.form', ['suggestion' => $suggestion->load('restaurants'), 'locations' => $this->locations(), 'categories' => $this->categories(), 'restaurants' => HeritageShop::orderBy('shop_name')->get(['id','shop_name','primary_food_category','heritage_story','highlight','address','city','state'])]); }
    private function syncRestaurants(FoodTrailSuggestion $suggestion, array $ids): void { $suggestion->restaurants()->sync(collect($ids)->values()->mapWithKeys(fn ($id, $index) => [$id => ['stop_order' => $index + 1]])->all()); }
}
