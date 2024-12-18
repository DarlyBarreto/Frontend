<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class SkinUploadController extends Controller
{
    public function store(Request $request)
    {
        if (!$request->hasFile('skin')) {
            return response()->json([
                'success' => false,
                'error' => 'No se encontró ningún archivo'
            ], 400);
        }

        $file = $request->file('skin');

        if ($file->getClientMimeType() !== 'image/png') {
            return response()->json([
                'success' => false,
                'error' => 'El archivo debe ser una imagen PNG'
            ], 400);
        }

        try {
            $path = $file->store('skins', 'public');

            // Limpiar caché cuando se sube una nueva skin
            Cache::forget('skins_list');

            return response()->json([
                'success' => true,
                'skin_url' => asset('storage/' . $path)
            ])->withHeaders([
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
                'Cache-Control' => 'no-cache' // No cachear la respuesta de subida
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al guardar el archivo'
            ], 500);
        }
    }

    public function show($filename)
    {
        $path = storage_path('app/public/skins/' . $filename);

        if (!File::exists($path)) {
            return response()->json([
                'error' => 'Archivo no encontrado'
            ], 404);
        }

        try {
            // Cachear la respuesta de la imagen
            return Cache::remember('skin_file_' . $filename, 3600, function () use ($path) {
                return response()->file($path, [
                    'Content-Type' => 'image/png',
                    'Access-Control-Allow-Origin' => 'http://localhost:3000',
                    'Access-Control-Allow-Credentials' => 'true',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
                    'Cache-Control' => 'public, max-age=31536000, immutable' // Cache por 1 año
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al leer el archivo'
            ], 500);
        }
    }
}
