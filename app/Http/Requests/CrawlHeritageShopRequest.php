<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrawlHeritageShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
        ];
    }
}
