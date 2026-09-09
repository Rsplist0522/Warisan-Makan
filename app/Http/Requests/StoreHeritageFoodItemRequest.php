<?php

namespace App\Http\Requests;

use App\Models\HeritageFoodItem;
use App\Models\HeritageShop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHeritageFoodItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $fields = ['name', 'category', 'availability', 'price'];
        $normalized = [];
        foreach ($fields as $field) {
            if (is_string($this->input($field))) {
                $normalized[$field] = trim(preg_replace('/\s+/u', ' ', $this->input($field)) ?? $this->input($field));
            }
        }
        $this->merge($normalized);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('name')) {
                return;
            }

            $shop = $this->route('heritageShop');
            $foodItem = $this->route('foodItem');
            if (! $shop instanceof HeritageShop) {
                return;
            }

            $duplicate = $shop->foodItems()
                ->where('normalized_name', HeritageFoodItem::normalizeName($this->input('name')))
                ->when($foodItem instanceof HeritageFoodItem, fn ($query) => $query->whereKeyNot($foodItem->getKey()))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('name', 'A food item with the same name already exists for this shop.');
            }
        });
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:120'],
            'heritage_significance' => ['nullable', 'string', 'max:3000'],
            'availability' => ['nullable', 'string', 'max:120'],
            'price' => ['nullable', 'string', 'max:80'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 2048)],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
