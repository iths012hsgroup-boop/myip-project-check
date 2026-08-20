<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IpCheckController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route untuk cek IP
Route::get('/check-ip/{ip?}', [IpCheckController::class, 'checkIp']);
