<?php

namespace Tests\Feature;

use App\Models\Annonce;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnnonceViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_annonce_detail_increments_views_and_returns_count(): void
    {
        $user = User::factory()->create([
            'prenoms' => 'Marc',
            'telephone' => '+22997100001',
            'role' => 'demarcheur',
            'is_verified' => true,
            'statut_verification' => 'valide',
        ]);

        $annonce = Annonce::create([
            'user_id' => $user->id,
            'titre' => 'Chambre moderne Calavi',
            'description' => 'Superbe chambre à proximité du campus',
            'quartier' => 'Zogbadjè',
            'loyer' => 25000,
            'caution' => 50000,
            'source_eau' => 'Forage',
            'type_electricite' => 'SBEE_CARTE',
            'type_annonce' => 'CHAMBRE_VIDE',
            'statut_disponibilite' => 'available',
            'statut_publication' => 'active',
            'vues_count' => 0,
        ]);

        $this->assertSame(0, $annonce->fresh()->vues_count);

        // Premier clic / consultation de la fiche annonce
        $response1 = $this->getJson("/api/annonces/{$annonce->id}");
        $response1->assertOk()
            ->assertJsonPath('data.id', $annonce->id)
            ->assertJsonPath('data.vues_count', 1);

        $this->assertSame(1, $annonce->fresh()->vues_count);

        // Deuxième clic / consultation
        $response2 = $this->getJson("/api/annonces/{$annonce->id}");
        $response2->assertOk()
            ->assertJsonPath('data.vues_count', 2);

        $this->assertSame(2, $annonce->fresh()->vues_count);
    }

    public function test_demarcheur_dashboard_reflects_annonce_views(): void
    {
        $user = User::factory()->create([
            'prenoms' => 'Jean',
            'telephone' => '+22997100002',
            'role' => 'demarcheur',
            'is_verified' => true,
            'statut_verification' => 'valide',
        ]);

        $annonce = Annonce::create([
            'user_id' => $user->id,
            'titre' => 'Studio indépendant',
            'description' => 'Studio avec cuisine privée',
            'quartier' => 'Agla',
            'loyer' => 35000,
            'caution' => 70000,
            'source_eau' => 'ONEB',
            'type_electricite' => 'SBEE_CONVENTIONNEL',
            'type_annonce' => 'CHAMBRE_VIDE',
            'statut_disponibilite' => 'available',
            'statut_publication' => 'active',
            'vues_count' => 5,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard');
        $response->assertOk()
            ->assertJsonPath('data.demarcheur.statistiques.total_vues', 5)
            ->assertJsonPath('data.demarcheur.mes_annonces.0.vues_count', 5);
    }
}
