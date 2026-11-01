<?php

namespace App\Services;

use App\Models\User;
use App\Models\PortefeuillePublication;
use App\Models\TransactionCredit;
use Illuminate\Support\Facades\DB;

class ServicePortefeuillePublications
{
    /**
     * Initialiser un portefeuille pour un nouvel utilisateur (démarcheur)
     */
    public function initialiserPortefeuille(User $user)
    {
        return DB::transaction(function () use ($user) {
            $existant = PortefeuillePublication::where('user_id', $user->id)->first();
            if ($existant) {
                return $existant;
            }

            $portefeuille = PortefeuillePublication::create([
                'user_id' => $user->id,
                'credits_gratuits' => 2,
                'credits_achetes' => 0,
                'credits_utilises' => 0,
            ]);

            // Enregistrer la transaction initiale
            TransactionCredit::create([
                'user_id' => $user->id,
                'type' => 'GRATUIT',
                'montant' => 2,
                'description' => 'Crédits de bienvenue à l\'inscription',
            ]);

            return $portefeuille;
        });
    }

    /**
     * Obtenir le portefeuille ou l'initialiser proprement s'il n'existe pas
     */
    public function obtenirOuInitialiserPortefeuille(User $user): PortefeuillePublication
    {
        $portefeuille = $user->portefeuillePublication;
        if (!$portefeuille) {
            $portefeuille = $this->initialiserPortefeuille($user);
        }
        return $portefeuille;
    }

    /**
     * Vérifier si l'utilisateur a des crédits disponibles
     */
    public function aDesCreditsDisponibles(User $user): bool
    {
        $portefeuille = $this->obtenirOuInitialiserPortefeuille($user);
        return $portefeuille->credits_restants > 0;
    }

    /**
     * Utiliser un crédit pour une publication d'annonce (démarcheur)
     */
    public function utiliserCredit(User $user, $annonceId)
    {
        return DB::transaction(function () use ($user, $annonceId) {
            $portefeuille = $this->obtenirOuInitialiserPortefeuille($user);

            if ($portefeuille->credits_restants <= 0) {
                throw new \Exception("Crédits insuffisants pour publier cette annonce.");
            }

            $portefeuille->increment('credits_utilises');

            TransactionCredit::create([
                'user_id' => $user->id,
                'type' => 'UTILISATION',
                'montant' => -1,
                'source_type' => 'Annonce',
                'source_id' => $annonceId,
                'description' => 'Publication d\'une nouvelle annonce de logement',
            ]);

            return true;
        });
    }

    /**
     * Utiliser un crédit pour une publicité/campagne (boutique)
     */
    public function utiliserCreditBoutique(User $user, $publiciteId, ?string $titre = null)
    {
        return DB::transaction(function () use ($user, $publiciteId, $titre) {
            $portefeuille = $this->obtenirOuInitialiserPortefeuille($user);

            if ($portefeuille->credits_restants <= 0) {
                throw new \Exception("Crédits insuffisants pour lancer cette campagne publicitaire.");
            }

            $portefeuille->increment('credits_utilises');

            TransactionCredit::create([
                'user_id' => $user->id,
                'type' => 'UTILISATION',
                'montant' => -1,
                'source_type' => 'PubliciteBoutique',
                'source_id' => $publiciteId,
                'description' => 'Diffusion campagne bannière' . ($titre ? " : {$titre}" : ''),
            ]);

            return true;
        });
    }

    /**
     * Ajouter des crédits achetés
     */
    public function ajouterCreditsAchetes(User $user, int $nombre, $achatId)
    {
        return DB::transaction(function () use ($user, $nombre, $achatId) {
            $portefeuille = $this->obtenirOuInitialiserPortefeuille($user);

            $portefeuille->increment('credits_achetes', $nombre);

            TransactionCredit::create([
                'user_id' => $user->id,
                'type' => 'ACHAT',
                'montant' => $nombre,
                'source_type' => 'AchatForfait',
                'source_id' => $achatId,
                'description' => "Achat d'un pack de {$nombre} publications",
            ]);

            return true;
        });
    }

    /**
     * Résumé complet et calculs transparents du portefeuille
     */
    public function obtenirResumePortefeuille(User $user): array
    {
        $portefeuille = $this->obtenirOuInitialiserPortefeuille($user);
        $portefeuille->refresh();

        $totalCredits = (int) $portefeuille->credits_gratuits + (int) $portefeuille->credits_achetes;
        $creditsUtilises = (int) $portefeuille->credits_utilises;
        $creditsRestants = max(0, $totalCredits - $creditsUtilises);

        return [
            'credits_restants' => $creditsRestants,
            'credits_gratuits' => (int) $portefeuille->credits_gratuits,
            'credits_achetes' => (int) $portefeuille->credits_achetes,
            'credits_utilises' => $creditsUtilises,
            'total_credits' => $totalCredits,
        ];
    }
}
