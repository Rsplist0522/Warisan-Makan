<?php

return [
    // HeritageShop images must live on shared object storage in deployment.
    // Set HERITAGE_SHOP_IMAGE_DISK=public only for local development or tests.
    'image_disk' => env('HERITAGE_SHOP_IMAGE_DISK', 'r2'),
    'image_directory' => env('HERITAGE_SHOP_IMAGE_DIRECTORY', 'heritage-shops'),
    'max_image_kb' => (int) env('HERITAGE_SHOP_MAX_IMAGE_KB', 1024),
    'max_image_bytes' => (int) env('HERITAGE_SHOP_MAX_IMAGE_KB', 1024) * 1024,
    'max_list_discovery_items' => 10,

    // Real-world passport check-in radius used for every shop.
    'checkin_radius_meters' => (int) env('HERITAGE_CHECKIN_RADIUS_METERS', 150),
];
