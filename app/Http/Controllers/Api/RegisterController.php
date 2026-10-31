<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Mail\SendOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Support\ApiData;
use App\Support\ProfileDataManager;
use App\Services\ServicePortefeuillePublications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class RegisterController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'prenoms' => $validated['prenoms'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'telephone' => $validated['telephone'],
                'role' => $validated['role'],
                'is_verified' => false,
                'is_valide' => false,
                'statut_verification' => 'quarantaine',
                'commentaire_admin' => null,
                'profile_completed' => false,
            ]);

            ProfileDataManager::sync($user, $validated, $request);
            $user->save();

            if ($user->role === 'demarcheur') {
                app(ServicePortefeuillePublications::class)->initialiserPortefeuille($user);
            }

            $this->generateAndSendOtpAndLink($user);

            return $user->fresh(['profilEtudiant', 'profilDemarcheur', 'profilBoutique', 'documentsIdentite']);
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription enregistrée ! Un email avec un lien de validation et un code vous a été envoyé.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => ApiData::user($user),
        ], 201);
    }

    /**
     * Validation du compte via le clic sur le lien dans l'email.
     */
    public function validateAccountFromEmail(Request $request, $id, $hash)
    {
        $loginUrl = config('app.frontend_url', 'https://coletubenin.lavoixdabouloussi.org') . '/#/connexion';

        $user = User::find($id);

        if (!$user || !hash_equals((string) $hash, sha1($user->email))) {
            return response()->view('auth.validation_result', [
                'success' => false,
                'message' => 'Lien de validation non conforme ou utilisateur introuvable.',
                'loginUrl' => $loginUrl,
            ], 404);
        }

        // Si la signature a expiré ou n'est plus valide mais le hash correspond
        if (!$request->hasValidSignature() && $user->email_verified_at === null && $user->created_at->lt(now()->subDays(7))) {
            return response()->view('auth.validation_result', [
                'success' => false,
                'message' => 'Ce lien de validation a expiré. Veuillez demander un nouveau lien ou contacter l\'administrateur.',
                'loginUrl' => $loginUrl,
            ], 403);
        }

        $user->update([
            'is_valide' => true,
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);

        // Nettoyage des OTPs en attente
        EmailOtp::where('email', $user->email)->delete();

        return response()->view('auth.validation_result', [
            'success' => true,
            'user' => $user,
            'message' => 'Félicitations ' . $user->prenoms . ' ! Votre compte a été validé avec succès.',
            'loginUrl' => $loginUrl . '?validated=1',
        ]);
    }

    /**
     * Generate 6-digit OTP and signed email validation URL, then send email.
     */
    public function generateAndSendOtpAndLink(User $user): void
    {
        $otp = (string) random_int(100000, 999999);

        EmailOtp::where('email', $user->email)->delete();

        EmailOtp::create([
            'email' => $user->email,
            'otp' => $otp,
            'expires_at' => now()->addHours(48),
        ]);

        $validationUrl = null;
        try {
            $validationUrl = URL::temporarySignedRoute(
                'auth.validation',
                now()->addDays(2),
                [
                    'id' => $user->id,
                    'hash' => sha1($user->email),
                ]
            );
        } catch (\Throwable $e1) {
            try {
                $validationUrl = URL::temporarySignedRoute(
                    'auth.validate-account',
                    now()->addDays(2),
                    [
                        'id' => $user->id,
                        'hash' => sha1($user->email),
                    ]
                );
            } catch (\Throwable $e2) {
                $validationUrl = url('/auth/validation/' . $user->id . '/' . sha1($user->email));
            }
        }

        try {
            Mail::to($user->email)->send(new SendOtpMail($otp, $validationUrl, $user->prenoms));
        } catch (\Throwable $mailErr) {
            \Illuminate\Support\Facades\Log::error("Erreur envoi email validation: " . $mailErr->getMessage());
        }
    }
}
