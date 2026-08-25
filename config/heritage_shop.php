<?php

return [
    // HeritageShop images must live on shared object storage in deployment.
    // Set HERITAGE_SHOP_IMAGE_DISK=public only for local development or tests.
    'image_disk' => env('HERITAGE_SHOP_IMAGE_DISK', 'r2'),
    'image_directory' => env('HERITAGE_SHOP_IMAGE_DIRECTORY', 'heritage-shops'),
];
