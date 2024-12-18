<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
use App\Models\Skin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    public function toggle($skinId)
    {
        try {
            // Primero verificar que la skin existe y esta activa
            $skin = Skin::where('id', $skinId)
                ->where('is_active', true)
                ->firstOrFail();

            $userId = auth()->id();

            // Verificar si ya existe el favorito
            $favorite = Favorite::where('user_id', $userId)
                ->where('skin_id', $skinId)
                ->first();

            $isFavorite = false;

            if ($favorite) {
                $favorite->delete();
            } else {
                Favorite::create([
                    'user_id' => $userId,
                    'skin_id' => $skinId
                ]);
                $isFavorite = true;
            }

            // Limpiar caché específico
            Cache::forget("skins.all.user.{$userId}");

            return response()->json([
                'success' => true,
                'is_favorite' => $isFavorite
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Skin no encontrada:', [
                'skin_id' => $skinId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Skin no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error en toggle favorite:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'skin_id' => $skinId,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar favoritos: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {
            $userId = auth()->id();

            return Skin::whereHas('favorites', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->where('is_active', true)
                ->with(['user:id,name'])
                ->select('id', 'name', 'image_path', 'user_id', 'likes', 'dislikes')
                ->latest()
                ->get()
                ->map(function ($skin) {
                    return [
                        'id' => $skin->id,
                        'name' => $skin->name,
                        'image_path' => $skin->image_path,
                        'likes' => (int)$skin->likes,
                        'dislikes' => (int)$skin->dislikes,
                        'user' => [
                            'id' => $skin->user->id,
                            'name' => $skin->user->name
                        ],
                        'is_favorite' => true
                    ];
                });

        } catch (\Exception $e) {
            Log::error('Error al obtener favoritos:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'error' => 'Error al obtener favoritos: ' . $e->getMessage()
            ], 500);
        }
    }
}
