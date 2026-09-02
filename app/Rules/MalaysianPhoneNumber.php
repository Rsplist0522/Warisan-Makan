<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MalaysianPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = is_string($value) ? trim($value) : '';

        if ($phone === '') {
            return;
        }

        $compact = preg_replace('/[\s().-]/', '', $phone);

        if (! is_string($compact) || preg_match('/^(?:\+60|0)\d+$/', $compact) !== 1) {
            $fail(__('The :attribute must be a valid Malaysian phone number.'));

            return;
        }

        $digits = preg_replace('/\D/', '', $compact);
        if (! is_string($digits)) {
            $fail(__('The :attribute must be a valid Malaysian phone number.'));

            return;
        }

        $nationalNumber = str_starts_with($compact, '+60')
            ? substr($digits, 2)
            : substr($digits, 1);

        if (preg_match('/^(?:1\d{8,9}|[3-9]\d{7,8})$/', $nationalNumber) !== 1) {
            $fail(__('The :attribute must be a valid Malaysian phone number.'));
        }
    }
}
