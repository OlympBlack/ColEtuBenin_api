<?php

namespace App\Services;

use App\Models\ForfaitPublication;
use App\Models\AchatForfait;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ServiceForfaitPublications
{
    protected KkiapayService $kkiapay;

    // ← Injection via constructor
    public function __construct(KkiapayService $kkiapay)
    {
        $this->kkiapay = $kkiapay;
    }

    public function listerForfaitsActifs()
    {
        return ForfaitPublication::where('est_actif', true)
            ->orderBy('ordre_affichage')
            ->get();
    }

    public function acheterForfait(User $user, ForfaitPublication $forfait, ?string $reference = null, ?string $methode = null)
    {
        return DB::transaction(function () use ($user, $forfait, $reference, $methode) {
            $achat = AchatForfait::create([
                'user_id'                => $user->id,
                'forfait_id'             => $forfait->id,
                'nombre_publications'    => $forfait->nombre_publications,
                'montant'                => $forfait->prix,
                'statut_paiement'        => 'valide',
                'methode_paiement'       => $methode,
                'reference_transaction'  => $reference,
            ]);

            $servicePortefeuille = new ServicePortefeuillePublications();
            $servicePortefeuille->ajouterCreditsAchetes($user, $forfait->nombre_publications, $achat->id);

            return $achat;
        });
    }

    public function validerAchatKkiapay(User $user, ForfaitPublication $forfait, string $transactionId)
    {
        // 1. Idempotence : si la transaction a déjà été enregistrée et validée, on la retourne directement
        $achatExistant = AchatForfait::where('reference_transaction', $transactionId)
            ->where('statut_paiement', 'valide')
            ->first();

        if ($achatExistant) {
            return $achatExistant->load('forfait');
        }

        // 2. Vérification auprès de l'API Kkiapay (via les clés dynamiques de la plateforme)
        $details = $this->kkiapay->verifyTransaction($transactionId);

        if (!$details || ($details['status'] ?? '') !== 'SUCCESS') {
            throw new \Exception("La transaction Kkiapay n'a pas pu être vérifiée ou a échoué. Veuillez vérifier auprès de votre opérateur.");
        }

        // 3. Vérification de sécurité critique : le montant payé doit correspondre au prix du pack
        $montantPaye = isset($details['amount']) ? (int) $details['amount'] : 0;
        if ($montantPaye < (int) $forfait->prix) {
            throw new \Exception("Le montant de la transaction ({$montantPaye} FCFA) est inférieur au montant requis ({$forfait->prix} FCFA).");
        }

        // 4. Enregistrement de l'achat et mise à jour transparente du portefeuille
        return $this->acheterForfait($user, $forfait, $transactionId, 'kkiapay');
    }
}