<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDiscoveredHeritageShopsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'list_url' => ['required', 'url:http,https', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:'.(int) config('heritage_shop.max_list_discovery_items', 10)],
            'items.*' => ['required', 'array'],
        ];
    }
}
