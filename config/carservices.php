<?php

return [
    'child_seat' => [
        'key' => 'child_seat',
        'label_en' => 'Child Seat',
        'label_fa' => 'صندلی کودک',
        'icon' => 'fa-baby',
        'amount' => 20,
        'per_day' => true,
    ],
    'additional_driver' => [
        'key' => 'additional_driver',
        'label_en' => 'Additional Driver',
        'label_fa' => 'راننده اضافه',
        'icon' => 'fa-user-plus',
        'amount' => 20,
        'per_day' => false,
    ],
    'ldw_insurance' => [
        'key' => 'ldw_insurance',
        'label_en' => 'LDW Insurance',
        'label_fa' => 'بیمه LDW',
        'icon' => 'fa-car-burst',
        'amount' => null, // مقدار از مدل Car گرفته می‌شه
        'per_day' => true,
    ],
    'scdw_insurance' => [
        'key' => 'scdw_insurance',
        'label_en' => 'Full Coverage (SCDW)',
        'label_fa' => 'بیمه کامل SCDW',
        'icon' => 'fa-lock',
        'amount' => null, // مقدار از مدل Car گرفته می‌شه
        'per_day' => true,
    ],
];
