<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'SmartIdle ERP',
        'version' => '1.0.0',
        'description' => 'Laravel 10 ERP System',
    ]);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
