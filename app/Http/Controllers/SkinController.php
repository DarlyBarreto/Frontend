<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use App\Models\Skin;
use App\Models\SkinReaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SkinController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userId = auth()->id();

            // Iniciar la consulta base
            $query = Skin::with(['user:id,name'])
                ->with('tags:id,name')
                ->with('category:id,name')
                ->with(['favorites' => function($query) use ($userId) {
                    $query->where('user_id', $userId);
                }])
                ->with(['reactions' => function($query) use ($userId) {
                    $query->where('user_id', $userId);
                }])
                ->where('is_active', true);

            // Aplicar filtros usando OR
            if ($request->has('category') || $request->has('tags')) {
                $query->where(function($q) use ($request) {
                    // Filtro de categoría
                    if ($request->has('category') && $request->category) {
                        $q->orWhere('category_id', $request->category);
                    }

                    // Filtro de tags
                    if ($request->has('tags') && !empty($request->tags)) {
                        $tagIds = is_array($request->tags) ? $request->tags : [$request->tags];
                        $q->orWhereHas('tags', function($query) use ($tagIds) {
                            $query->whereIn('tags.id', $tagIds);
                        });
                    }
                });
            }

            // Completar la consulta
            $skins = $query->select('id', 'name', 'image_path', 'user_id', 'category_id', 'likes', 'dislikes')  // Añadimos category_id
            ->withCount(['comments' => function($query) {
                $query->where('is_active', true);
            }])
                ->latest()
                ->get()
                ->map(function ($skin) use ($userId) {
                    $userReaction = $skin->reactions->first();

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
                        'category' => [  // Añadimos info de la categoría
                            'id' => $skin->category->id,
                            'name' => $skin->category->name
                        ],
                        'user_reaction' => $userReaction ? $userReaction->type : null,
                        'is_favorite' => $skin->favorites->isNotEmpty(),
                        'comments_count' => $skin->comments_count,
                        'comments' => [],
                        'tags' => $skin->tags->map(function($tag) {
                            return [
                                'id' => $tag->id,
                                'name' => $tag->name
                            ];
                        }),
                    ];
                });

            return response()->json($skins);

        } catch (\Exception $e) {
            Log::error('Error al listar skins:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'Error al cargar las skins',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $skin = Cache::remember('skin.' . $id, 300, function () use ($id) {
                return Skin::with(['user:id,name'])
                    ->where('is_active', true)
                    ->findOrFail($id);
            });

            return response()->json($skin);

        } catch (\Exception $e) {
            Log::error('Error al mostrar skin:', [
                'skin_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error al cargar la skin',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'image_path' => 'required|string',
                'user_id' => 'required|exists:usuarios,id',
                'tags' => 'array|max:2',  // Máximo 2 tags
                'tags.*' => 'exists:tags,id|distinct'
            ]);

            $skin = Skin::create([
                'name' => $validated['name'],
                'image_path' => $validated['image_path'],
                'user_id' => $validated['user_id'],
                'category_id' => $request->category_id ?? 1
            ]);

            // Añadir tags
            if ($request->has('tags')) {
                $skin->tags()->attach($request->tags);
            }

            Cache::forget('skins.all');

            return response()->json([
                'message' => 'Skin publicada correctamente',
                'skin' => $skin->load('tags:id,name')
            ]);

        } catch (\Exception $e) {
            Log::error('Error al crear skin:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Error al publicar la skin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleReaction($id, $type)
    {
        try {
            if (!in_array($type, ['like', 'dislike'])) {
                return response()->json([
                    'error' => 'Tipo de reacción inválido'
                ], 400);
            }

            DB::beginTransaction();
            $skin = Skin::where('id', $id)
                ->where('is_active', true)
                ->firstOrFail();
            $userId = auth()->id();

            // Buscar reacción existente
            $existingReaction = SkinReaction::where('user_id', $userId)
                ->where('skin_id', $id)
                ->first();

            if ($existingReaction) {
                if ($existingReaction->type === $type) {
                    // Si es la misma reacción, la quitamos
                    if ($type === 'like') {
                        $skin->likes = max(0, $skin->likes - 1);
                    } else {
                        $skin->dislikes = max(0, $skin->dislikes - 1);
                    }
                    $existingReaction->delete();
                } else {
                    // Si es diferente reacción, actualizamos
                    if ($type === 'like') {
                        $skin->likes = max(0, $skin->likes + 1);
                        $skin->dislikes = max(0, $skin->dislikes - 1);
                    } else {
                        $skin->dislikes = max(0, $skin->dislikes + 1);
                        $skin->likes = max(0, $skin->likes - 1);
                    }
                    $existingReaction->update(['type' => $type]);
                }
            } else {
                // Nueva reacción
                if ($type === 'like') {
                    $skin->likes++;
                } else {
                    $skin->dislikes++;
                }

                SkinReaction::create([
                    'user_id' => $userId,
                    'skin_id' => $id,
                    'type' => $type
                ]);
            }

            $skin->save();
            DB::commit();

            Cache::forget('skins.all');
            Cache::forget('skin.' . $id);

            // Obtener estado actual
            $currentReaction = SkinReaction::where('user_id', $userId)
                ->where('skin_id', $id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Reacción actualizada',
                'skin' => array_merge($skin->toArray(), [
                    'user_reaction' => $currentReaction ? $currentReaction->type : null
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en toggleReaction:', [
                'error' => $e->getMessage(),
                'skin_id' => $id,
                'type' => $type
            ]);

            return response()->json([
                'error' => 'Error al actualizar reacción'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $skin = Skin::findOrFail($id);

            if ($skin->user_id !== auth()->id()) {
                return response()->json(['message' => 'No autorizado'], 403);
            }

            $skin->delete();

            Cache::forget('skins.all');
            Cache::forget('skin.' . $id);

            return response()->json(['message' => 'Skin eliminada']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar la skin'
            ], 500);
        }
    }

    public function getImage($filename)
    {
        try {
            $path = storage_path('app/public/skins/' . $filename);

            if (!file_exists($path)) {
                return response()->json(['error' => 'Imagen no encontrada'], 404);
            }

            return response()->file($path, [
                'Content-Type' => 'image/png',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET',
                'Cross-Origin-Resource-Policy' => 'cross-origin',
                'Cache-Control' => 'public, max-age=31536000'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar la imagen',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
