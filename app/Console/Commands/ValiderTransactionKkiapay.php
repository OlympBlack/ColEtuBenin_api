<?php

namespace App\Console\Commands;

use App\Models\ForfaitPublication;
use App\Models\User;
use App\Services\KkiapayService;
use App\Services\ServiceForfaitPublications;
use App\Services\ServicePortefeuillePublications;
use Illuminate\Console\Command;

class ValiderTransactionKkiapay extends Command
{
    protected $signature = 'kkiapay:valider {transactionId : L\'identifiant de transaction Kkiapay} {email? : L\'email de l\'utilisateur (optionnel, déduit de la transaction)} {--forfait= : L\'identifiant du forfait si ambigu}';

    protected $description = 'Vérifie et valide manuellement une transaction Kkiapay pour créditer les publications d\'un utilisateur';

    public function handle(
        KkiapayService $kkiapayService,
        ServiceForfaitPublications $serviceForfait,
        ServicePortefeuillePublications $servicePortefeuille
    ): int {
        $transactionId = (string) $this->argument('transactionId');
        $this->info("🔍 Vérification de la transaction Kkiapay : {$transactionId}...");

        $details = $kkiapayService->verifyTransaction($transactionId);

        if (!$details || ($details['status'] ?? '') !== 'SUCCESS') {
            $this->error("❌ La transaction n'est pas valide ou n'a pas été confirmée par Kkiapay.");
            if ($details) {
                $this->line(json_encode($details, JSON_PRETTY_PRINT));
            }
            return self::FAILURE;
        }

        $amount = (int) ($details['amount'] ?? 0);
        $clientEmail = $this->argument('email') ?: ($details['client']['email'] ?? null);

        $this->line("✅ Transaction confirmée par Kkiapay :");
        $this->line("   - Montant : {$amount} FCFA");
        $this->line("   - Date : " . ($details['performed_at'] ?? 'inconnue'));
        $this->line("   - Client Kkiapay : " . ($details['client']['fullname'] ?? 'Anonyme') . " ({$clientEmail})");

        if (!$clientEmail) {
            $this->error("❌ Aucun email fourni et aucun email trouvé dans la transaction Kkiapay.");
            return self::FAILURE;
        }

        $user = User::where('email', $clientEmail)->first();
        if (!$user) {
            $this->error("❌ Aucun compte utilisateur trouvé dans la base avec l'email : {$clientEmail}");
            return self::FAILURE;
        }

        // Trouver le forfait correspondant
        $forfaitId = $this->option('forfait');
        if ($forfaitId) {
            $forfait = ForfaitPublication::find($forfaitId);
        } else {
            $forfait = ForfaitPublication::where('prix', $amount)
                ->where('est_actif', true)
                ->first();
            
            if (!$forfait) {
                // Fallback sans le filtre est_actif au cas où
                $forfait = ForfaitPublication::where('prix', $amount)->first();
            }
        }

        if (!$forfait) {
            $this->error("❌ Aucun pack trouvé pour le montant de {$amount} FCFA.");
            $this->line("Packs disponibles :");
            foreach (ForfaitPublication::all() as $f) {
                $this->line("   - ID {$f->id} : {$f->titre} ({$f->prix} FCFA, {$f->nombre_publications} pubs)");
            }
            $this->line("Utilisez l'option --forfait=<ID> pour spécifier le forfait.");
            return self::FAILURE;
        }

        $this->info("📦 Pack sélectionné : {$forfait->titre} (+{$forfait->nombre_publications} publications)");

        try {
            $achat = $serviceForfait->validerAchatKkiapay($user, $forfait, $transactionId);
            $resume = $servicePortefeuille->obtenirResumePortefeuille($user);

            $this->info("🎉 Succès ! Le pack a été crédité à l'utilisateur : {$user->name} ({$user->email})");
            $this->table(
                ['Crédits Gratuits', 'Crédits Achetés', 'Crédits Utilisés', 'Solde Restant'],
                [[
                    $resume['credits_gratuits'],
                    $resume['credits_achetes'],
                    $resume['credits_utilises'],
                    $resume['credits_restants'],
                ]]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du crédit : " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
