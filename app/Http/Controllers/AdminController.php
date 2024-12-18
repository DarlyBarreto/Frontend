<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Role;
use App\Models\Skin;
use App\Models\User;
use App\Models\Tag;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Verifica si el usuario actual tiene permisos de administrador.
     *
     * @return bool
     */
    private function checkAdmin()
    {
        if (!auth()->check() ||
            auth()->user()->role_id === null ||
            !auth()->user()->role->is_admin) {
            return false;
        }
        return true;
    }

    /**
     * Verifica si el usuario tiene un permiso específico
     */
    private function hasPermission($permissionName)
    {
        if (!auth()->check() || auth()->user()->role_id === null) {
            return false;
        }

        return auth()->user()->role->permissions()
            ->where('name', $permissionName)
            ->exists();
    }

    /**
     * Obtiene la lista de usuarios para el panel de administración.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersList()
    {
        if (!$this->hasPermission('manage_users')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $users = User::with('role:id,name')
                ->select('id', 'name', 'email', 'role_id', 'user_active')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios:', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Error al obtener usuarios'], 500);
        }
    }

    /**
     * Actualiza el rol de un usuario.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserRole(Request $request, $id)
    {
        // Solo los administradores pueden actualizar roles
        if (!$this->checkAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $request->validate([
                'role_id' => 'nullable|exists:roles,id'  // Cambiado de 'required' a 'nullable'
            ]);

            $user = User::findOrFail($id);

            // Si role_id es null, simplemente quitamos el rol
            if ($request->role_id === null) {
                // Evitar que un admin se quite su propio rol
                if (auth()->id() === $user->id && $user->role->is_admin) {
                    return response()->json(['error' => 'No puedes quitarte tus propios privilegios de administrador'], 403);
                }

                $user->role_id = null;
                $user->save();

                return response()->json([
                    'message' => 'Rol removido correctamente',
                    'user' => $user->load('role:id,name')
                ]);
            }

            // Si hay un nuevo rol, verificamos las reglas existentes
            $newRole = Role::findOrFail($request->role_id);

            // Solo permitir asignar rol de admin vía base de datos
            if ($newRole->is_admin) {
                return response()->json([
                    'error' => 'No se puede asignar el rol de administrador desde la interfaz'
                ], 403);
            }

            // Evitar que un admin se quite sus propios privilegios
            if (auth()->id() === $user->id && $user->role->is_admin && !$newRole->is_admin) {
                return response()->json(['error' => 'No puedes quitarte tus propios privilegios de administrador'], 403);
            }

            $user->role_id = $request->role_id;
            $user->save();

            return response()->json([
                'message' => 'Rol actualizado correctamente',
                'user' => $user->load('role:id,name')
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar rol:', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);
            return response()->json(['error' => 'Error al actualizar rol'], 500);
        }
    }

    /**
     * Obtiene la lista de tags.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagsList()
    {
        if (!$this->hasPermission('manage_tags')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $tags = Tag::select('id', 'name', 'is_active')
                ->orderBy('name')
                ->get();

            return response()->json($tags);
        } catch (\Exception $e) {
            Log::error('Error al obtener tags:', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error al obtener tags'], 500);
        }
    }

    /**
     * Crea un nuevo tag.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTag(Request $request)
    {
        if (!$this->hasPermission('manage_tags')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $request->validate([
                'name' => 'required|string|max:50|unique:tags,name'
            ]);

            $tag = Tag::create([
                'name' => $request->name,
                'is_active' => true
            ]);

            return response()->json([
                'message' => 'Tag creado correctamente',
                'tag' => $tag
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear tag:', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Error al crear tag'], 500);
        }
    }

    /**
     * Activa o desactiva un tag.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleTagStatus($id)
    {
        if (!$this->hasPermission('manage_tags')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $tag = Tag::findOrFail($id);
            $tag->is_active = !$tag->is_active;
            $tag->save();

            return response()->json([
                'message' => $tag->is_active ? 'Tag activado' : 'Tag desactivado',
                'tag' => $tag
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del tag:', [
                'error' => $e->getMessage(),
                'tag_id' => $id
            ]);

            return response()->json(['error' => 'Error al cambiar estado del tag'], 500);
        }
    }

    /**
     * Activa o desactiva un usuario.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleUserStatus($id)
    {
        if (!$this->hasPermission('manage_users')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $user = User::findOrFail($id);
            $currentUser = auth()->user();

            // Si el usuario actual es admin, tiene control total excepto sobre sí mismo
            if ($currentUser->role->is_admin) {
                if (auth()->id() === $user->id) {
                    return response()->json([
                        'error' => 'No puedes desactivar tu propia cuenta'
                    ], 403);
                }
            }
            // Si no es admin, aplicar restricciones
            else {
                // No puede desactivar usuarios con rol
                if ($user->role_id !== null) {
                    return response()->json([
                        'error' => 'No tienes permisos para gestionar usuarios con roles asignados'
                    ], 403);
                }

                // No puede desactivar administradores (doble verificación)
                if ($user->role && $user->role->is_admin) {
                    return response()->json([
                        'error' => 'No puedes gestionar cuentas de administradores'
                    ], 403);
                }
            }

            $user->user_active = !$user->user_active;
            $user->save();

            return response()->json([
                'message' => $user->user_active ? 'Usuario activado' : 'Usuario desactivado',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del usuario:', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);

            return response()->json(['error' => 'Error al cambiar estado del usuario'], 500);
        }
    }

    /**
     * Obtiene la lista de categorías.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoriesList()
    {
        if (!$this->hasPermission('manage_categories')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $categories = Category::select('id', 'name', 'is_active')
                ->orderBy('name')
                ->get();

            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Error al obtener categorías:', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error al obtener categorías'], 500);
        }
    }

    /**
     * Crea una nueva categoría.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createCategory(Request $request)
    {
        if (!$this->hasPermission('manage_categories')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $request->validate([
                'name' => 'required|string|max:50|unique:categories,name'
            ]);

            $category = Category::create([
                'name' => $request->name,
                'is_active' => true
            ]);

            return response()->json([
                'message' => 'Categoría creada correctamente',
                'category' => $category
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear categoría:', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json(['error' => 'Error al crear categoría'], 500);
        }
    }

    /**
     * Activa o desactiva una categoría.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleCategoryStatus($id)
    {
        if (!$this->hasPermission('manage_categories')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $category = Category::findOrFail($id);
            $category->is_active = !$category->is_active;
            $category->save();

            return response()->json([
                'message' => $category->is_active ? 'Categoría activada' : 'Categoría desactivada',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de la categoría:', [
                'error' => $e->getMessage(),
                'category_id' => $id
            ]);

            return response()->json(['error' => 'Error al cambiar estado de la categoría'], 500);
        }
    }

    /**
     * Obtiene la lista de skins para el panel de administración.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function skinsList()
    {
        if (!$this->hasPermission('manage_skins')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $skins = Skin::with(['user:id,name', 'category:id,name', 'tags:id,name'])
                ->select('id', 'name', 'user_id', 'category_id', 'is_active', 'likes', 'dislikes', 'created_at')
                ->withCount(['comments', 'favorites'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($skins);
        } catch (\Exception $e) {
            Log::error('Error al obtener skins:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Error al obtener skins'], 500);
        }
    }

    /**
     * Obtiene los detalles de una skin específica.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function skinDetails($id)
    {
        if (!$this->hasPermission('manage_skins')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $skin = Skin::with([
                'user:id,name',
                'category:id,name',
                'tags:id,name',
                'comments.user:id,name'
            ])
                ->withCount(['comments', 'favorites'])
                ->findOrFail($id);

            return response()->json($skin);
        } catch (\Exception $e) {
            Log::error('Error al obtener detalles de skin:', [
                'error' => $e->getMessage(),
                'skin_id' => $id
            ]);

            return response()->json(['error' => 'Error al obtener detalles de skin'], 500);
        }
    }

    /**
     * Activa o desactiva una skin.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleSkinStatus($id)
    {
        if (!$this->hasPermission('manage_skins')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $skin = Skin::with(['user:id,name', 'category:id,name', 'tags:id,name'])
                ->withCount(['comments', 'favorites'])
                ->findOrFail($id);

            $skin->is_active = !$skin->is_active;
            $skin->save();

            return response()->json([
                'message' => $skin->is_active ? 'Skin activada' : 'Skin desactivada',
                'skin' => $skin
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado de skin:', [
                'error' => $e->getMessage(),
                'skin_id' => $id
            ]);

            return response()->json(['error' => 'Error al cambiar estado de skin'], 500);
        }
    }

    /**
     * Obtiene estadísticas generales de las skins.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function skinsStats()
    {
        if (!$this->hasPermission('manage_skins')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $stats = [
                'total' => Skin::count(),
                'active' => Skin::where('is_active', true)->count(),
                'inactive' => Skin::where('is_active', false)->count(),
                'total_comments' => Comment::count(),
                'total_favorites' => Favorite::count(),
                'by_category' => Skin::selectRaw('category_id, count(*) as count')
                    ->groupBy('category_id')
                    ->with('category:id,name')
                    ->get(),
                'most_commented' => Skin::withCount('comments')
                    ->orderBy('comments_count', 'desc')
                    ->limit(5)
                    ->get(),
                'most_favorited' => Skin::withCount('favorites')
                    ->orderBy('favorites_count', 'desc')
                    ->limit(5)
                    ->get()
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas:', [
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error al obtener estadísticas'], 500);
        }
    }

    /**
     * Obtiene la lista de comentarios para el panel de administración.
     */
    public function commentsList()
    {
        if (!$this->hasPermission('manage_comments')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $comments = Comment::with(['user:id,name', 'skin:id,name'])
                ->select('id', 'content', 'user_id', 'skin_id', 'is_active', 'created_at')
                ->latest()
                ->get();

            return response()->json($comments);
        } catch (\Exception $e) {
            Log::error('Error al obtener comentarios:', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Error al obtener comentarios'], 500);
        }
    }

    /**
     * Obtiene el historial de comentarios de un usuario específico.
     */
    public function userComments($userId)
    {
        if (!$this->hasPermission('manage_comments')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $comments = Comment::with(['skin:id,name'])
                ->where('user_id', $userId)
                ->select('id', 'content', 'skin_id', 'is_active', 'created_at')
                ->latest()
                ->get();

            return response()->json($comments);
        } catch (\Exception $e) {
            Log::error('Error al obtener comentarios del usuario:', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return response()->json(['error' => 'Error al obtener comentarios'], 500);
        }
    }

    /**
     * Activa o desactiva un comentario.
     */
    public function toggleCommentStatus($id)
    {
        if (!$this->hasPermission('manage_comments')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $comment = Comment::with(['user:id,name', 'skin:id,name'])
                ->findOrFail($id);

            $comment->is_active = !$comment->is_active;
            $comment->save();

            return response()->json([
                'message' => $comment->is_active ? 'Comentario activado' : 'Comentario desactivado',
                'comment' => $comment
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del comentario:', [
                'error' => $e->getMessage(),
                'comment_id' => $id
            ]);
            return response()->json(['error' => 'Error al cambiar estado del comentario'], 500);
        }
    }
    public function availableRoles()
    {
        if (!$this->hasPermission('manage_users')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            // Si el usuario es admin, ve todos los roles
            if (auth()->user()->role->is_admin) {
                $roles = Role::where('is_active', true)->get();
            } else {
                // Si no es admin, solo ve roles no administrativos
                $roles = Role::where('is_active', true)
                    ->where('is_admin', false)
                    ->get();
            }

            return response()->json($roles);
        } catch (\Exception $e) {
            Log::error('Error al obtener roles:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al obtener roles'], 500);
        }
    }
}
