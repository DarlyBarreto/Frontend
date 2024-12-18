<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SkinUploadController;
use App\Http\Controllers\SkinController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas: no requieren autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Ruta para subir la skin
Route::match(['POST', 'OPTIONS'], '/upload-skin', [SkinUploadController::class, 'store']);

// Ruta pública para obtener tags
Route::get('/tags', [TagController::class, 'index']);

// Ruta pública para obtener las categorias
Route::get('/categories', [CategoryController::class, 'index']);

// Ruta pública para ver comentarios
Route::get('/skins/{id}/comments', [CommentController::class, 'index']);

// Rutas protegidas: solo accesibles para usuarios autenticados
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Favoritos (movidas arriba pero manteniendo la ruta original)
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::get('/skins/favorites', [FavoriteController::class, 'index']);
    Route::post('/skins/{id}/toggle-favorite', [FavoriteController::class, 'toggle']);

    // Ruta para toggle de reacciones
    Route::post('/skins/{id}/toggle-reaction/{type}', [SkinController::class, 'toggleReaction']);

    // Skins
    Route::post('/skins', [SkinController::class, 'store']);
    Route::post('/skins/{id}/like', [SkinController::class, 'like']);
    Route::post('/skins/{id}/dislike', [SkinController::class, 'dislike']);
    Route::delete('/skins/{id}', [SkinController::class, 'destroy']);

    // Comentarios
    Route::post('/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
});

// Rutas públicas de skins
Route::get('/skins', [SkinController::class, 'index']);
Route::get('/skins/{id}', [SkinController::class, 'show']);
Route::get('/skin-image/{filename}', [SkinController::class, 'getImage']);

// Rutas de administración
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Gestión de usuarios
    Route::get('/users', [AdminController::class, 'usersList']);
    Route::put('/users/{id}/role', [AdminController::class, 'updateUserRole']);
    Route::put('/users/{id}/toggle', [AdminController::class, 'toggleUserStatus']);
    Route::get('/users/available-roles', [AdminController::class, 'availableRoles']);

    // Gestión de tags
    Route::get('/tags', [AdminController::class, 'tagsList']);
    Route::post('/tags', [AdminController::class, 'createTag']);
    Route::put('/tags/{id}/toggle', [AdminController::class, 'toggleTagStatus']);

    // Gestión de categorías
    Route::get('/categories', [AdminController::class, 'categoriesList']);
    Route::post('/categories', [AdminController::class, 'createCategory']);
    Route::put('/categories/{id}/toggle', [AdminController::class, 'toggleCategoryStatus']);

    // Gestión de skins
    Route::get('/skins', [AdminController::class, 'skinsList']);
    Route::get('/skins/stats', [AdminController::class, 'skinsStats']);
    Route::get('/skins/{id}', [AdminController::class, 'skinDetails']);
    Route::put('/skins/{id}/toggle', [AdminController::class, 'toggleSkinStatus']);

    // Gestión de comentarios
    Route::get('/comments', [AdminController::class, 'commentsList']);
    Route::get('/users/{id}/comments', [AdminController::class, 'userComments']);
    Route::put('/comments/{id}/toggle', [AdminController::class, 'toggleCommentStatus']);

    // Roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    // Permisos de roles
    Route::post('/roles/{id}/permissions', [RoleController::class, 'updatePermissions']);
    Route::get('/permissions', [RoleController::class, 'getPermissions']);

});
