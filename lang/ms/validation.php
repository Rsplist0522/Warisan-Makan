<?php

return [
    'required' => ':attribute diperlukan.',
    'email' => ':attribute mesti alamat e-mel yang sah.',
    'image' => ':attribute mesti berupa imej.',
    'mimes' => ':attribute mesti berupa fail jenis: :values.',
    'max' => [
        'string' => ':attribute tidak boleh melebihi :max aksara.',
        'file' => ':attribute tidak boleh melebihi :max kilobait.',
        'array' => ':attribute tidak boleh mempunyai lebih daripada :max item.',
    ],
    'integer' => ':attribute mesti nombor bulat.', 'numeric' => ':attribute mesti nombor.',
    'url' => ':attribute mesti URL yang sah.', 'in' => 'Pilihan :attribute tidak sah.',
    'regex' => 'Format :attribute tidak sah.',
    'attributes' => ['name' => 'nama', 'email' => 'e-mel', 'phone' => 'telefon', 'city' => 'bandar', 'bio' => 'biodata', 'language' => 'bahasa', 'shop_name' => 'nama kedai', 'contribution_title' => 'tajuk sumbangan', 'field_name' => 'medan', 'suggested_value' => 'nilai cadangan', 'reason' => 'sebab', 'additional_information' => 'maklumat tambahan'],
];