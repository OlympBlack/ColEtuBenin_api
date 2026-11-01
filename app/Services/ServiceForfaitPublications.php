<?php

namespace App\Services;

use App\Models\ForfaitPublication;
use App\Models\AchatForfait;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceForfaitPublications
{
    protected KkiapayService $kkiapay;

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

    /**
     * Enregistre l'achat et crédite le portefeuille (sans vérification Kkiapay).
     */
    public function acheterForfait(User $user, ForfaitPublication $forfait, ?string $reference = null, ?string $methode = null)
    {
        return DB::transaction(function () use ($user, $forfait, $reference, $methode) {
            $achat = AchatForfait::create([
                'user_id'               => $user->id,
                'forfait_id'            => $forfait->id,
                'nombre_publications'   => $forfait->nombre_publications,
                'montant'               => $forfait->prix,
                'statut_paiement'       => 'valide',
                'methode_paiement'      => $methode,
                'reference_transaction' => $reference,
            ]);

            $servicePortefeuille = new ServicePortefeuillePublications();
            $servicePortefeuille->ajouterCreditsAchetes($user, $forfait->nombre_publications, $achat->id);

            Log::info('Achat forfait validé et crédits ajoutés', [
                'user_id'     => $user->id,
                'forfait_id'  => $forfait->id,
                'credits'     => $forfait->nombre_publications,
                'reference'   => $reference,
            ]);

            return $achat;
        });
    }

    /**
     * Valider un achat via Kkiapay : vérifie la transaction puis crédite.
     */
    public function validerAchatKkiapay(User $user, ForfaitPublication $forfait, string $transactionId)
    {
        // 1. Idempotence : transaction déjà traitée ?
        $achatExistant = AchatForfait::where('reference_transaction', $transactionId)
            ->where('statut_paiement', 'valide')
            ->first();

        if ($achatExistant) {
            Log::info('Transaction déjà traitée (idempotence)', ['transaction_id' => $transactionId]);
            return $achatExistant->load('forfait');
        }

        // 2. Vérification auprès de l'API Kkiapay
        $details = $this->kkiapay->verifyTransaction($transactionId);

        if (!$details || ($details['status'] ?? '') !== 'SUCCESS') {
            Log::error('Échec vérification Kkiapay', [
                'transaction_id' => $transactionId,
                'details'        => $details,
            ]);
            throw new \Exception(
                "La transaction Kkiapay n'a pas pu être vérifiée. " .
                "Veuillez contacter le support avec votre référence de paiement : {$transactionId}"
            );
        }

        // 3. Vérification de sécurité : montant payé >= prix du pack
        $montantPaye = isset($details['amount']) ? (int) $details['amount'] : 0;
        if ($montantPaye < (int) $forfait->prix) {
            Log::error('Montant insuffisant', [
                'montant_paye' => $montantPaye,
                'prix_forfait' => $forfait->prix,
            ]);
            throw new \Exception(
                "Le montant payé ({$montantPaye} FCFA) est inférieur au prix du pack ({$forfait->prix} FCFA)."
            );
        }

        // 4. Enregistrement et crédit du portefeuille
        return $this->acheterForfait($user, $forfait, $transactionId, 'kkiapay');
    }
}