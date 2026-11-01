<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Les administrateurs ne sont jamais soumis à la validation d'email
            if ($user->role === 'admin') {
                if (!$user->is_valide) {
                    $user->is_valide = true;
                    $user->save();
                }
            } elseif (!$user->is_valide) {
                Auth::logout();

                return response()->json([
                    'message' => 'Votre compte n\'a pas encore été validé. Veuillez cliquer sur le lien envoyé dans votre boîte mail ou contacter un administrateur.',
                    'validation_required' => true,
                    'is_valide' => false,
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => ApiData::user($user),
            ], 200);
        }

        return response()->json(['message' => 'Identifiants invalides'], 401);
    }

    /**
     * Terminer la session de l'utilisateur.
     */
    public function logout(): JsonResponse
    {
        Auth::user()->tokens()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }
}
