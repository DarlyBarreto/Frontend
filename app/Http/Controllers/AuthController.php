<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Método para registrar un usuario
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usuarios',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 201);
    }
    /**
     * Método para iniciar sesión de un usuario.
     *
     * Este método valida las credenciales proporcionadas (email y contraseña).
     * Si las credenciales son correctas, se genera un token de acceso utilizando
     * Sanctum y se retorna junto con los datos del usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Verificar si el usuario está activo
        if (!$user->user_active) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Por favor, contacta al administrador.'
            ], 403);
        }

        // Eliminar tokens anteriores
        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Ingreso exitoso',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role()->with('permissions')->first(),
                'user_active' => $user->user_active
            ]
        ]);
    }


    /**
     * Método para cerrar sesión de un usuario.
     *
     * Este método elimina el token de acceso actual, cerrando la sesión del usuario.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Eliminar el token de acceso actual para cerrar sesión
        $request->user()->currentAccessToken()->delete();

        // Retornar una respuesta confirmando que la sesión ha sido cerrada
        return response()->json(['message' => 'Sesión cerrada'], 200);
    }

    /**
     * Método para obtener el perfil del usuario autenticado.
     *
     * Este método retorna los datos del usuario autenticado que realiza la petición.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        // Verificar si el usuario está activo
        if (!$user->user_active) {
            // Si el usuario está inactivo, eliminar sus tokens
            $user->tokens()->delete();
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Por favor, contacta al administrador.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role()->with('permissions')->first(),
                'user_active' => $user->user_active
            ]
        ]);
    }
}
