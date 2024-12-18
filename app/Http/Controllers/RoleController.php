<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    private function checkAdmin()
    {
        if (!auth()->check() || auth()->user()->role_id === null || !auth()->user()->role->is_admin) {
            return false;
        }
        return true;
    }

    public function index()
    {
        if (!$this->checkAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $roles = Role::with('permissions')->get();
            return response()->json($roles);
        } catch (\Exception $e) {
            Log::error('Error al obtener roles:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al obtener roles'], 500);
        }
    }

    public function store(Request $request)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:roles',
                'description' => 'nullable|string',
                'is_admin' => 'boolean',
                'permissions' => 'array'
            ]);

            $role = Role::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'is_admin' => $validated['is_admin'] ?? false,
                'is_active' => true
            ]);

            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            return response()->json([
                'message' => 'Rol creado correctamente',
                'role' => $role->load('permissions')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear rol:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al crear rol'], 500);
        }
    }

    public function updatePermissions(Request $request, $id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $role = Role::findOrFail($id);
            $permissions = $request->validate(['permissions' => 'required|array'])['permissions'];

            $role->permissions()->sync($permissions);

            return response()->json([
                'message' => 'Permisos actualizados',
                'role' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar permisos:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al actualizar permisos'], 500);
        }
    }

    public function getPermissions()
    {
        if (!$this->checkAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $permissions = Permission::all();
            return response()->json($permissions);
        } catch (\Exception $e) {
            Log::error('Error al obtener permisos:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al obtener permisos'], 500);
        }
    }
    public function destroy($id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $role = Role::findOrFail($id);

            // Evitar eliminar el rol de administrador
            if ($role->is_admin) {
                return response()->json([
                    'error' => 'No se puede eliminar el rol de administrador'
                ], 403);
            }

            // Verificar si hay usuarios usando este rol
            if ($role->users()->count() > 0) {
                return response()->json([
                    'error' => 'No se puede eliminar un rol que está siendo utilizado'
                ], 403);
            }

            $role->delete();

            return response()->json([
                'message' => 'Rol eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al eliminar rol:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al eliminar rol'], 500);
        }
    }
    public function update(Request $request, $id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:roles,name,' . $id,
                'description' => 'nullable|string',
                'is_admin' => 'boolean',
                'permissions' => 'array'
            ]);

            $role = Role::findOrFail($id);

            // Actualizar los campos básicos
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'is_admin' => $validated['is_admin'] ?? false
            ]);

            // Actualizar permisos si se proporcionaron
            if (isset($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            return response()->json([
                'message' => 'Rol actualizado correctamente',
                'role' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar rol:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error al actualizar rol'], 500);
        }
    }
}
