<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Traits\HandlesImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    use HandlesImages;

    /**
     * POST /api/auth/register
     * Registro de nuevo usuario (público)
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users|alpha_dash',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Password::min(8)],
                'phone' => 'nullable|string|max:20',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            // Por defecto asigna rol TECNICO
            $tecnicoRole = Role::where('name', 'TECNICO')->first();
            
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role_id' => $tecnicoRole->id,
                'is_active' => true,
            ];

            if ($request->hasFile('avatar')) {
                $imageData = $this->uploadImage($request->file('avatar'), 'avatars');
                $userData['avatar'] = $imageData['url'];
                $userData['cloudinary_avatar_id'] = $imageData['public_id'];
            }

            $user = User::create($userData);
            
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'user' => $user->load('role'),
                'token' => $token,
                'token_type' => 'Bearer',
            ], Response::HTTP_CREATED);

        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/auth/login
     * Login de usuario (público)
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::with('role')->where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'error' => 'Credenciales inválidas'
                ], Response::HTTP_UNAUTHORIZED);
            }

            if (!$user->is_active) {
                return response()->json([
                    'error' => 'Usuario inactivo. Contacta al administrador.'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Actualizar último login
            $user->last_login = now();
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar' => $user->avatar,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'last_login' => $user->last_login,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], Response::HTTP_OK);

        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/auth/me
     * Obtener usuario actual (protegido)
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user()->load('role');
            
            return response()->json([
                'user' => $user
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/auth/logout
     * Cerrar sesión (protegido)
     */
    public function logout(Request $request)
    {
        try {
            // Eliminar token actual
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'message' => 'Sesión cerrada exitosamente'
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/auth/change-password
     * Cambiar contraseña (protegido)
     */
    public function changePassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'current_password' => 'required',
                'new_password' => ['required', 'confirmed', Password::min(8)]
            ]);

            $user = $request->user();

            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'error' => 'Contraseña actual incorrecta'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return response()->json([
                'message' => 'Contraseña actualizada exitosamente'
            ], Response::HTTP_OK);

        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /api/auth/refresh-token
     * Refrescar token (opcional)
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            
            // Revocar token actual
            $user->currentAccessToken()->delete();
            
            // Crear nuevo token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json([
                'error' => $error->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}