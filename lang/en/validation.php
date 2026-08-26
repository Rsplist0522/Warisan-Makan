<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'image' => 'The :attribute must be an image.',
    'mimes' => 'The :attribute must be a file of type: :values.',
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'integer' => 'The :attribute must be an integer.',
    'numeric' => 'The :attribute must be a number.',
    'url' => 'The :attribute must be a valid URL.',
    'in' => 'The selected :attribute is invalid.',
    'regex' => 'The :attribute format is invalid.',
    'attributes' => [
        'name' => 'name', 'email' => 'email', 'phone' => 'phone', 'city' => 'city',
        'bio' => 'bio', 'language' => 'language', 'shop_name' => 'shop name',
        'contribution_title' => 'contribution title', 'field_name' => 'field',
        'suggested_value' => 'suggested value', 'reason' => 'reason',
        'additional_information' => 'additional information',
    ],
];