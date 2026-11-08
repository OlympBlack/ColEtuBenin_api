<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AchatForfait;
use App\Models\Annonce;
use App\Models\User;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalUsers = User::count();
        $totalStudents = User::where('role', 'etudiant')->count();
        $totalDemarcheurs = User::where('role', 'demarcheur')->count();
        $totalBoutiques = User::where('role', 'boutique')->count();

        $totalAnnonces = Annonce::count();
        $annoncesDisponibles = Annonce::where('statut_disponibilite', 'available')->count();
        $kycEnAttente = User::where('role', '!=', 'admin')
            ->whereIn('statut_verification', ['pending_validation', 'en_attente'])
            ->count();
        $recettesTotales = (float) AchatForfait::where('statut_paiement', 'valide')->sum('montant');
        $signalementsEnAttente = \App\Models\Signalement::where('statut', 'EN_ATTENTE')->count();
        $contactsNouveaux = \App\Models\MessageContact::where('statut', 'NOUVEAU')->count();

        return response()->json([
            'data' => [
                'total_utilisateurs' => $totalUsers,
                'total_etudiants' => $totalStudents,
                'total_demarcheurs' => $totalDemarcheurs,
                'total_boutiques' => $totalBoutiques,
                'total_annonces' => $totalAnnonces,
                'annonces_disponibles' => $annoncesDisponibles,
                'kyc_en_attente' => $kycEnAttente,
                'recettes_totales_fcfa' => $recettesTotales,
                'signalements_en_attente' => $signalementsEnAttente,
                'contacts_nouveaux' => $contactsNouveaux,
            ],
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $search = $request->query('q');

        $query = User::query()
            ->with(['documentsIdentite', 'profilEtudiant', 'profilDemarcheur', 'profilBoutique'])
            ->where('role', '!=', 'admin');

        if ($role && in_array($role, ['etudiant', 'demarcheur', 'boutique'], true)) {
            $query->where('role', $role);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('prenoms', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(25);

        return response()->json([
            'data' => collect($users->items())->map(fn(User $u) => ApiData::user($u))->values(),
            'pagination' => [
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function annonces(Request $request): JsonResponse
    {
        $status = $request->query('statut');
        $search = $request->query('q');

        $query = Annonce::query()->with(['user', 'medias']);

        if ($status) {
            $query->where('statut_disponibilite', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('quartier', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $annonces = $query->latest()->paginate(25);

        return response()->json([
            'data' => collect($annonces->items())->map(fn(Annonce $a) => ApiData::annonce($a))->values(),
            'pagination' => [
                'total' => $annonces->total(),
                'current_page' => $annonces->currentPage(),
                'last_page' => $annonces->lastPage(),
            ],
        ]);
    }

    public function deleteAnnonce(Annonce $annonce): JsonResponse
    {
        foreach ($annonce->medias as $media) {
            if ($media->url_fichier && ! str_starts_with($media->url_fichier, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($media->url_fichier);
            }
        }

        $annonce->delete();

        return response()->json([
            'message' => 'L\'annonce a été supprimée par l\'administrateur.',
        ]);
    }

    public function toggleAnnonceStatus(Request $request, Annonce $annonce): JsonResponse
    {
        $statut = $request->input('statut_disponibilite', 'available');
        $annonce->update(['statut_disponibilite' => $statut]);

        return response()->json([
            'message' => 'Statut de l\'annonce mis à jour.',
            'data' => ApiData::annonce($annonce->fresh(['user', 'medias'])),
        ]);
    }

    /**
     * Moderation : activer / suspendre la publication d'une annonce.
     */
    public function updatePublicationStatus(Request $request, Annonce $annonce): JsonResponse
    {
        $validated = $request->validate([
            'statut_publication' => 'required|in:active,en_attente,refusee,expiree',
        ]);

        $annonce->update($validated);

        return response()->json([
            'message' => 'Statut de publication mis à jour.',
            'data' => ApiData::annonce($annonce->fresh(['user', 'medias'])),
        ]);
    }

    public function finances(): JsonResponse
    {
        $achats = AchatForfait::with('user')->latest()->paginate(25);

        return response()->json([
            'data' => collect($achats->items())->map(fn($af) => [
                'id' => $af->id,
                'user_nom' => $af->user ? ($af->user->name . ' ' . $af->user->prenoms) : 'Utilisateur',
                'user_email' => $af->user?->email,
                'user_role' => $af->user?->role,
                'montant' => (float) $af->montant,
                'nombre_publications' => $af->nombre_publications,
                'methode_paiement' => $af->methode_paiement ?? 'Mobile Money',
                'reference' => $af->reference_transaction,
                'statut' => $af->statut_paiement,
                'date' => $af->created_at->toISOString(),
            ])->values(),
            'total_recettes_fcfa' => (float) AchatForfait::where('statut_paiement', 'valide')->sum('montant'),
            'pagination' => [
                'total' => $achats->total(),
                'current_page' => $achats->currentPage(),
                'last_page' => $achats->lastPage(),
            ],
        ]);
    }

    /**
     * Activer / désactiver manuellement la validation d'un compte utilisateur par l'admin.
     */
    public function toggleUserValidation(User $user): JsonResponse
    {
        $user->is_valide = !$user->is_valide;

        if ($user->is_valide) {
            $user->is_verified = true;
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
            }
        } else {
            // Révocation immédiate des sessions actives
            $user->tokens()->delete();
        }

        $user->save();

        return response()->json([
            'message' => $user->is_valide ? 'Compte activé et validé avec succès.' : 'Compte désactivé et accès suspendu.',
            'data' => ApiData::user($user),
        ]);
    }

    /**
     * Supprimer définitivement un utilisateur par l'admin.
     */
    public function deleteUser(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte administrateur.',
            ], 400);
        }

        $user->tokens()->delete();

        if ($user->photo_profil && !str_starts_with($user->photo_profil, 'http')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->photo_profil);
        }

        $user->delete();

        return response()->json([
            'message' => 'Le compte utilisateur a été supprimé avec succès.',
        ]);
    }

    /**
     * Attribuer manuellement des crédits de publication à un utilisateur.
     */
    public function giveCredits(Request $request, User $user, \App\Services\ServicePortefeuillePublications $servicePortefeuille): JsonResponse
    {
        $validated = $request->validate([
            'credits' => ['required', 'integer', 'min:1', 'max:1000'],
            'motif' => ['nullable', 'string', 'max:500'],
        ]);

        $nombre = (int) $validated['credits'];
        $motif = $validated['motif'] ?? null;
        $admin = $request->user();

        $portefeuille = $servicePortefeuille->attribuerCreditsAdmin($user, $nombre, $motif, $admin);
        $nouveauTotal = (int) $portefeuille->credits_restants;

        // Notification envoyée directement à l'utilisateur (BD, Pusher Broadcast, WebPush)
        $user->notify(new \App\Notifications\CreditsAllouesNotification($nombre, $nouveauTotal, $motif));

        $userName = trim(($user->prenoms ?? '') . ' ' . ($user->name ?? '')) ?: $user->email;

        return response()->json([
            'message' => "{$nombre} crédit(s) attribué(s) avec succès à {$userName}. L'utilisateur a été notifié.",
            'data' => [
                'user_id' => $user->id,
                'credits_ajoutes' => $nombre,
                'nouveau_solde' => $nouveauTotal,
                'portefeuille' => $servicePortefeuille->obtenirResumePortefeuille($user),
            ],
        ]);
    }
}

