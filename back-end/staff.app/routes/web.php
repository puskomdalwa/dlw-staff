<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // dd(public_path('asdasd'));
    return view('welcome');
});


Route::get('/testing', [\App\Http\Controllers\TestingController::class, 'index']);
Route::get('/verify/sk6/{token}', [\App\Http\Controllers\Api\Sk6SignatureController::class, 'verifyPage']);
Route::get('/verify/form-pengambilan-ijazah/{token}', [\App\Http\Controllers\Api\PengambilanIjazahSignatureController::class, 'verifyPage']);

Route::get('/login', function () {
    return response()->json([
        'status' => false,
        'message' => 'Unauthenticated. Silakan login atau gunakan rute publik /api/public/...'
    ], 401);
})->name('login');







