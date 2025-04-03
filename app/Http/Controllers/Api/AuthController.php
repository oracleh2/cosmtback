<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'skin_type' => 'nullable|string|max:50',
            'skin_concerns' => 'nullable|array',
            'skin_concerns.*' => 'string|max:50',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'skin_type' => $request->skin_type,
            'skin_concerns' => $request->skin_concerns,
        ];

        // Создаем пользователя
        $user = User::create($userData);

        // Обработка аватара, если он предоставлен
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('public/avatars/' . $user->id);
            $user->avatar = $path;
            $user->save();
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $user->append('avatar_url');

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url,
                'skin_type' => $user->skin_type,
                'skin_concerns' => $user->skin_concerns ?? [],
                'created_at' => $user->created_at->toIso8601String()
            ],
            'token' => $token
        ], 201);
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = $request->user();
        $token = $user->createToken('auth-token')->plainTextToken;
        $user->append('avatar_url');

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url,
                'skin_type' => $user->skin_type,
                'skin_concerns' => $user->skin_concerns ?? [],
                'created_at' => $user->created_at->toIso8601String()
            ],
            'token' => $token
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated user
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->append('avatar_url');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url,
                'skin_type' => $user->skin_type,
                'skin_concerns' => $user->skin_concerns ?? [],
                'created_at' => $user->created_at->toIso8601String()
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'skin_type' => 'sometimes|string|max:50',
            'skin_concerns' => 'sometimes|array',
            'skin_concerns.*' => 'string|max:50',
            'avatar' => 'sometimes|image|max:2048',
        ]);

        $user = $request->user();

        // Обновление текстовых полей
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('skin_type')) {
            $user->skin_type = $request->skin_type;
        }

        if ($request->has('skin_concerns')) {
            $user->skin_concerns = $request->skin_concerns;
        }

        // Обработка загрузки аватара
        if ($request->hasFile('avatar')) {
            // Удаляем старый аватар, если он есть и это локальный файл
            if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                Storage::delete($user->avatar);
            }

            // Сохраняем новый аватар
            $path = $request->file('avatar')->store('public/avatars/' . $user->id);
            $user->avatar = $path;
        }

        $user->save();
        $user->append('avatar_url');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar_url,
                'skin_type' => $user->skin_type,
                'skin_concerns' => $user->skin_concerns ?? [],
                'created_at' => $user->created_at->toIso8601String()
            ]
        ]);
    }
}
