<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscoverHeritageShopsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'url' => ['bail', 'required', 'string', 'max:2048', 'url:http,https'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'Please enter a permitted list-page URL.',
            'url.url' => 'Please enter a valid URL starting with http:// or https://.',
        ];
    }
}
