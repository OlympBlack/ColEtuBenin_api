<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fusionne les conversations existantes en doublon pour une meme paire d'interlocuteurs.
     */
    public function up(): void
    {
        if (!Schema::hasTable('conversations') || !Schema::hasTable('messages')) {
            return;
        }

        // Recuperer toutes les conversations
        $conversations = DB::table('conversations')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        // Grouper par paire canonique (user_min, user_max)
        $grouped = [];
        foreach ($conversations as $c) {
            $u1 = min((int) $c->sender_id, (int) $c->receiver_id);
            $u2 = max((int) $c->sender_id, (int) $c->receiver_id);
            $key = "{$u1}_{$u2}";

            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $c;
        }

        // Pour chaque groupe ayant plus d'une conversation, fusionner vers la principale
        foreach ($grouped as $key => $groupList) {
            if (count($groupList) <= 1) {
                continue;
            }

            // La premiere est la principale (la plus recente)
            $primary = $groupList[0];
            $duplicateIds = [];

            for ($i = 1; $i < count($groupList); $i++) {
                $duplicateIds[] = $groupList[$i]->id;
            }

            if (!empty($duplicateIds)) {
                // 1. Deplacer tous les messages des conversations doublons vers la principale
                DB::table('messages')
                    ->whereIn('conversation_id', $duplicateIds)
                    ->update(['conversation_id' => $primary->id]);

                // 2. Mettre a jour last_message_at de la principale si un message plus recent existe
                $latestMessage = DB::table('messages')
                    ->where('conversation_id', $primary->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestMessage && $latestMessage->created_at) {
                    DB::table('conversations')
                        ->where('id', $primary->id)
                        ->update(['last_message_at' => $latestMessage->created_at]);
                }

                // 3. Supprimer les conversations doublons
                DB::table('conversations')
                    ->whereIn('id', $duplicateIds)
                    ->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas d'operation inverse (la fusion est definitive)
    }
};
