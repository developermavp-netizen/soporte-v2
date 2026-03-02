<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Traits\HandlesImages;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    use HandlesImages;

    public function __construct()
    {
        // Solo ADMIN puede gestionar usuarios
        $this->middleware('role:ADMIN');
    }

    public function index(Request $request)
    {
        try {
            $users = User::with('role')
                ->when($request->has('role'), function($q) use ($request) {
                    $q->whereHas('role', function($r) use ($request) {
                        $r->where('name', $request->role);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json($users, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users|alpha_dash',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', Password::min(8)],
                'phone' => 'nullable|string|max:20',
                'role_id' => 'required|exists:roles,id',
                'is_active' => 'boolean',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'role_id' => $validated['role_id'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            if ($request->hasFile('avatar')) {
                $imageData = $this->uploadImage($request->file('avatar'), 'avatars');
                $userData['avatar'] = $imageData['url'];
                $userData['cloudinary_avatar_id'] = $imageData['public_id'];
            }

            $user = User::create($userData);
            
            return response()->json($user->load('role'), Response::HTTP_CREATED);

        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with('role')->find($id);
            
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
            
            return response()->json($user, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'username' => 'sometimes|string|max:255|unique:users,username,' . $id . '|alpha_dash',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'password' => ['sometimes', Password::min(8)],
                'phone' => 'nullable|string|max:20',
                'role_id' => 'sometimes|exists:roles,id',
                'is_active' => 'sometimes|boolean',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            $userData = $validated;

            if (isset($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }

            if ($request->hasFile('avatar')) {
                if ($user->cloudinary_avatar_id) {
                    $this->deleteImage($user->cloudinary_avatar_id);
                }
                
                $imageData = $this->uploadImage($request->file('avatar'), 'avatars');
                $userData['avatar'] = $imageData['url'];
                $userData['cloudinary_avatar_id'] = $imageData['public_id'];
            }

            $user->update($userData);
            
            return response()->json($user->load('role'), Response::HTTP_OK);

        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            if ($user->id === auth()->id()) {
                return response()->json(['error' => 'No puedes eliminarte a ti mismo'], 400);
            }

            if ($user->cloudinary_avatar_id) {
                $this->deleteImage($user->cloudinary_avatar_id);
            }

            $user->delete();
            
            return response()->json(['message' => 'Usuario eliminado correctamente'], Response::HTTP_OK);

        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
}