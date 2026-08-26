<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHeritageFoodItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
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
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('heritage_shop.max_image_kb', 1024)],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
