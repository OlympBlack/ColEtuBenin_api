<?php

namespace Tests\Feature;

use App\Models\Annonce;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_can_send_text_and_audio_messages_even_if_unverified(): void
    {
        Storage::fake('public');

        $user1 = User::factory()->create([
            'prenoms' => 'Alice',
            'telephone' => '+22997111111',
            'role' => 'etudiant',
            'statut_verification' => 'en_attente', // not validated yet
        ]);

        $user2 = User::factory()->create([
            'prenoms' => 'Bob',
            'telephone' => '+22997222222',
            'role' => 'etudiant',
            'statut_verification' => 'non_rempli',
        ]);

        $conversation = Conversation::create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'last_message_at' => now(),
        ]);

        // Alice sends a text message
        Sanctum::actingAs($user1);
        $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'message' => 'Salut Bob !',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Votre message a ete envoye.')
            ->assertJsonPath('data.message.content', 'Salut Bob !')
            ->assertJsonPath('data.message.type', 'text');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $user1->id,
            'content' => 'Salut Bob !',
        ]);

        // Bob responds with an audio message
        Sanctum::actingAs($user2);
        $audioFile = UploadedFile::fake()->create('vocal.webm', 150, 'audio/webm');
        $audioResponse = $this->post("/api/conversations/{$conversation->id}/messages", [
            'audio' => $audioFile,
            'duration' => 12,
        ]);

        $audioResponse->assertOk()
            ->assertJsonPath('message', 'Votre message a ete envoye.')
            ->assertJsonPath('data.message.type', 'audio')
            ->assertJsonPath('data.message.duration', 12);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $user2->id,
            'duration' => 12,
        ]);

        // A third user cannot send messages to this conversation
        $user3 = User::factory()->create([
            'prenoms' => 'Intrus',
            'telephone' => '+22997333333',
            'role' => 'etudiant',
        ]);
        Sanctum::actingAs($user3);
        $forbiddenResponse = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'message' => 'Je m incruste',
        ]);
        $forbiddenResponse->assertForbidden();
    }

    public function test_same_interlocutors_share_single_unique_conversation_across_different_annonces(): void
    {
        $landlord = User::factory()->create([
            'prenoms' => 'Plateforme Admin',
            'telephone' => '+22997000001',
            'role' => 'demarcheur',
            'statut_verification' => 'valide',
        ]);

        $student = User::factory()->create([
            'prenoms' => 'Etudiant Test',
            'telephone' => '+22997000002',
            'role' => 'etudiant',
            'statut_verification' => 'valide',
        ]);

        $annonce1 = Annonce::create([
            'user_id' => $landlord->id,
            'titre' => 'Chambre 1',
            'description' => 'Description chambre 1',
            'quartier' => 'Zogbadje',
            'loyer' => 25000,
            'caution' => 50000,
            'source_eau' => 'forage',
            'type_electricite' => 'sbee_carte',
            'type_annonce' => 'CHAMBRE_COMPLETE',
            'statut_disponibilite' => 'DISPONIBLE',
            'statut_publication' => 'VALIDEE',
        ]);

        $annonce2 = Annonce::create([
            'user_id' => $landlord->id,
            'titre' => 'Chambre 2',
            'description' => 'Description chambre 2',
            'quartier' => 'Calavi Kpota',
            'loyer' => 30000,
            'caution' => 60000,
            'source_eau' => 'forage',
            'type_electricite' => 'sbee_carte',
            'type_annonce' => 'CHAMBRE_COMPLETE',
            'statut_disponibilite' => 'DISPONIBLE',
            'statut_publication' => 'VALIDEE',
        ]);

        Sanctum::actingAs($student);

        // 1. L'etudiant contacte le proprietaire sur l'annonce 1
        $res1 = $this->postJson("/api/annonces/{$annonce1->id}/messages", [
            'message' => 'Bonjour pour la chambre 1',
        ]);
        $res1->assertOk();
        $convId1 = $res1->json('data.conversation.id');

        // 2. L'etudiant contacte le meme proprietaire sur l'annonce 2
        $res2 = $this->postJson("/api/annonces/{$annonce2->id}/messages", [
            'message' => 'Bonjour aussi pour la chambre 2',
        ]);
        $res2->assertOk();
        $convId2 = $res2->json('data.conversation.id');

        // Ils doivent partager la MEME conversation unique
        $this->assertEquals($convId1, $convId2, 'Deux annonces pour un meme interlocuteur doivent reutiliser la meme conversation unique');

        // Verifier qu'il n'y a qu'une seule conversation en base entre ces 2 utilisateurs
        $conversationsCount = Conversation::where(function ($q) use ($student, $landlord) {
            $q->where('sender_id', $student->id)->where('receiver_id', $landlord->id)
              ->orWhere('sender_id', $landlord->id)->where('receiver_id', $student->id);
        })->count();
        $this->assertEquals(1, $conversationsCount);

        // Verifier que la liste du Dashboard ne renvoie qu'une seule fois l'interlocuteur
        $dashResponse = $this->getJson('/api/dashboard');
        $dashResponse->assertOk();
        $mesConvs = $dashResponse->json('data.mes_conversations');
        $this->assertCount(1, $mesConvs);
    }
}
