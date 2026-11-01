<?php

namespace Tests\Feature;

use App\Models\AchatForfait;
use App\Models\ForfaitPublication;
use App\Models\ParametrePlateforme;
use App\Models\User;
use App\Services\ServiceForfaitPublications;
use App\Services\ServicePortefeuillePublications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KkiapaySettingsAndForfaitTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settings_expose_only_public_kkiapay_key_and_sandbox(): void
    {
        $settings = ParametrePlateforme::getSettings();
        $settings->update([
            'kkiapay_public_key' => 'pk_test_public_123',
            'kkiapay_private_key' => 'sk_test_private_456',
            'kkiapay_secret_key' => 'secret_key_789',
            'kkiapay_sandbox' => true,
        ]);

        $response = $this->getJson('/api/settings');
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'kkiapay_public_key' => 'pk_test_public_123',
                'kkiapay_sandbox' => true,
            ],
        ]);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('kkiapay_private_key', $data);
        $this->assertArrayNotHasKey('kkiapay_secret_key', $data);
    }

    public function test_admin_can_read_and_update_kkiapay_keys(): void
    {
        $admin = User::factory()->create([
            'prenoms' => 'Super',
            'name' => 'Admin',
            'role' => 'admin',
            'email' => 'admin@coletubenin.com',
            'telephone' => '+22901000000',
            'is_valide' => true,
        ]);

        // 1. Admin can read all keys
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/settings');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('kkiapay_public_key', $data);
        $this->assertArrayHasKey('kkiapay_private_key', $data);
        $this->assertArrayHasKey('kkiapay_secret_key', $data);
        $this->assertArrayHasKey('kkiapay_sandbox', $data);

        // 2. Admin can update keys without code editing
        $updateResponse = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/settings', [
            'kkiapay_public_key' => 'pk_live_custom_abc',
            'kkiapay_private_key' => 'prv_live_custom_def',
            'kkiapay_secret_key' => 'sec_live_custom_ghi',
            'kkiapay_sandbox' => false,
        ]);

        $updateResponse->assertStatus(200);
        $this->assertDatabaseHas('parametres_plateforme', [
            'kkiapay_public_key' => 'pk_live_custom_abc',
            'kkiapay_private_key' => 'prv_live_custom_def',
            'kkiapay_secret_key' => 'sec_live_custom_ghi',
            'kkiapay_sandbox' => false,
        ]);
    }

    public function test_wallet_calculations_and_idempotency_are_accurate_for_demarcheur_and_boutique(): void
    {
        $boutique = User::factory()->create([
            'prenoms' => 'Boutique',
            'name' => 'Campus Pro',
            'role' => 'boutique',
            'email' => 'boutique@test.com',
            'telephone' => '+22901111111',
            'is_valide' => true,
        ]);

        $servicePortefeuille = new ServicePortefeuillePublications();
        $resumeInitial = $servicePortefeuille->obtenirResumePortefeuille($boutique);

        // Welcome credits: 2 gratuit, 0 acheté, 0 utilisé, 2 restants
        $this->assertEquals(2, $resumeInitial['credits_gratuits']);
        $this->assertEquals(0, $resumeInitial['credits_achetes']);
        $this->assertEquals(0, $resumeInitial['credits_utilises']);
        $this->assertEquals(2, $resumeInitial['credits_restants']);

        // Create forfait
        $forfait = ForfaitPublication::create([
            'titre' => 'Pack Pro 10',
            'description' => '10 publications',
            'nombre_publications' => 10,
            'prix' => 5000,
            'est_actif' => true,
            'est_populaire' => true,
            'ordre_affichage' => 1,
        ]);

        $mockKkiapay = \Mockery::mock(\App\Services\KkiapayService::class);
        $mockKkiapay->shouldReceive('verifyTransaction')
            ->with('tx_kkiapay_unique_999')
            ->andReturn([
                'status' => 'SUCCESS',
                'amount' => 5000,
            ]);

        $serviceForfait = new ServiceForfaitPublications($mockKkiapay);

        // Buy pack
        $achat = $serviceForfait->validerAchatKkiapay($boutique, $forfait, 'tx_kkiapay_unique_999');
        $this->assertNotNull($achat);
        $this->assertEquals(10, $achat->nombre_publications);

        // Check wallet calculation: 2 gratuit + 10 achetés - 0 utilisé = 12 restants
        $resumeApresAchat = $servicePortefeuille->obtenirResumePortefeuille($boutique);
        $this->assertEquals(2, $resumeApresAchat['credits_gratuits']);
        $this->assertEquals(10, $resumeApresAchat['credits_achetes']);
        $this->assertEquals(0, $resumeApresAchat['credits_utilises']);
        $this->assertEquals(12, $resumeApresAchat['credits_restants']);

        // Idempotency: re-running with same transactionId should NOT double credit
        $achatReplay = $serviceForfait->validerAchatKkiapay($boutique, $forfait, 'tx_kkiapay_unique_999');
        $this->assertEquals($achat->id, $achatReplay->id);

        $resumeApresReplay = $servicePortefeuille->obtenirResumePortefeuille($boutique);
        $this->assertEquals(12, $resumeApresReplay['credits_restants'], 'Idempotency prevented duplicate credits');

        // Use 1 credit
        $servicePortefeuille->utiliserCreditBoutique($boutique, 123, 'Nouvelle collection rentrée');
        $resumeApresUtilisation = $servicePortefeuille->obtenirResumePortefeuille($boutique);
        $this->assertEquals(1, $resumeApresUtilisation['credits_utilises']);
        $this->assertEquals(11, $resumeApresUtilisation['credits_restants']);
    }
}
