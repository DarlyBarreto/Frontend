<?php

use App\Http\Controllers\SkinUploadController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'OPTIONS'], '/storage/skins/{filename}', [SkinUploadController::class, 'show'])
    ->where('filename', '.*')
    ->middleware(['cors']);

// Ruta de respaldo para acceso directo a las imágenes
Route::get('/storage/{path}', function($path) {
    $filePath = storage_path('app/public/' . $path);

    if (!file_exists($filePath)) {
        abort(404);
    }

    return response()->file($filePath, [
        'Content-Type' => 'image/png',
        'Access-Control-Allow-Origin' => 'http://localhost:3000',
        'Access-Control-Allow-Methods' => '*',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Allow-Headers' => '*'
    ]);
})->where('path', '.*')->middleware('cors');//a
