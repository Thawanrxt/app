<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$row = Illuminate\Support\Facades\DB::table('users')
    ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
    ->leftJoin('farmer_registrations as registrations', 'registrations.profile_id', '=', 'profiles.id')
    ->leftJoin('provinces as provinces', 'provinces.id', '=', 'profiles.province_id')
    ->leftJoin('districts as districts', 'districts.id', '=', 'profiles.district_id')
    ->leftJoin('provinces as district_provinces', 'district_provinces.id', '=', 'districts.province_id')
    ->select([
        'users.id',
        'users.username',
        'users.phone',
        'users.role',
        'profiles.full_name',
        'profiles.address',
        'profiles.province_id',
        'profiles.district_id',
        'profiles.subdistrict',
        'profiles.postcode',
        'provinces.name_th as province_name',
        'districts.name_th as district_name',
        'district_provinces.name_th as district_province_name',
    ])
    ->where('users.username', 'wiraya01')
    ->first();

echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
