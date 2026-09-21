<?php

return [
    'name' => 'Warung Kaure',
    'tagline' => 'POS & Management System',
    'env' => 'development',
    'debug' => true,
    'timezone' => 'Asia/Jakarta',
    'currency' => 'IDR',
    'currency_symbol' => 'Rp',
    'date_format' => 'd/m/Y',
    'datetime_format' => 'd/m/Y H:i',
    'session_lifetime' => 7200, // 2 jam timeout
    'regular_work_hours' => 8, // 480 menit
    'default_overtime_rate' => 5000, // Rp5.000 / jam
    'payroll_cycle_days' => 14, // 2 minggu
    'max_upload_size' => 2097152, // 2MB
    'allowed_image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'allowed_image_exts' => ['jpg', 'jpeg', 'png', 'webp'],
];
