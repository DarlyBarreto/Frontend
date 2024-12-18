<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Skin;

class CommentController extends Controller
{
    public function index($skinId)
    {
        try {
            $comments = Comment::where('skin_id', $skinId)
                ->where('is_active', true)  // Solo mostrar comentarios activos
                ->with('user:id,name')
                ->latest()
                ->get()
                ->map(function ($comment) {
                    $userId = auth()->id();
                    return [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'user' => $comment->user->name,
                        'user_id' => $comment->user_id,
                        'created_at' => $comment->created_at->diffForHumans(),
                        'is_active' => $comment->is_active
                    ];
                });

            return response()->json($comments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cargar comentarios'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'skin_id' => 'required|exists:skins,id',
        ]);

        // Verificar que la skin esté activa
        $skin = Skin::where('id', $request->skin_id)
            ->where('is_active', true)
            ->first();

        if (!$skin) {
            return response()->json([
                'error' => 'La skin no está disponible'
            ], 404);
        }

        // Verificar límite de comentarios
        $commentCount = Comment::where('skin_id', $request->skin_id)
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->count();

        if ($commentCount >= 3) {
            return response()->json([
                'error' => 'Has alcanzado el límite de comentarios para esta skin'
            ], 403);
        }

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'skin_id' => $request->input('skin_id'),
            'content' => $request->input('content'),
            'is_active' => true
        ]);

        return response()->json([
            'id' => $comment->id,
            'content' => $comment->content,
            'user' => auth()->user()->name,
            'user_id' => $comment->user_id,
            'created_at' => $comment->created_at->diffForHumans(),
            'is_active' => true
        ]);
    }

    public function destroy($id)
    {
        try {
            $comment = Comment::findOrFail($id);

            if ($comment->user_id !== auth()->id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // En lugar de eliminar, actualizamos el estado
            $comment->update(['is_active' => false]);

            return response()->json([
                'message' => 'Comentario eliminado',
                'comment_id' => $id,
                'is_active' => false
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar el comentario'], 500);
        }
    }
}
