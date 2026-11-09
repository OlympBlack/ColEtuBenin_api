<?php

namespace App\Support;

use App\Models\Annonce;
use App\Models\Avis;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\PubliciteBoutique;
use App\Models\Universite;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class ApiData
{
    public static function universite(Universite $universite): array
    {
        return [
            'id' => $universite->id,
            'code' => $universite->code,
            'nom' => $universite->nom,
            'ville' => $universite->ville,
            'departement' => $universite->departement,
            'commune' => $universite->commune,
            'accent' => (bool) $universite->accent,
            'quartiers_count' => $universite->relationLoaded('quartiers') ? $universite->quartiers->count() : null,
        ];
    }

    private static function universiteSafe(?Universite $universite): ?array
    {
        return $universite ? self::universite($universite) : null;
    }

    public static function user(User $user): array
    {
        $user->loadMissing(['profilEtudiant', 'profilDemarcheur', 'profilBoutique', 'documentsIdentite']);

        $completionPct = ProfileDataManager::completionPercentage($user);
        $isVerified = (bool) (
            $user->is_verified ||
            $user->statut_verification === 'valide' ||
            $user->statut_verification === 'verifie' ||
            ($user->is_valide ?? false)
        );
        $profileCompleted = (bool) (
            $user->profile_completed ||
            $completionPct >= 100 ||
            $isVerified ||
            $user->role === 'admin'
        );

        return [
            'id' => $user->id,
            'name' => $user->name,
            'prenoms' => $user->prenoms,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'role' => $user->role,
            'photo_profil' => self::fileUrl($user->photo_profil),
            'is_verified' => $isVerified,
            'is_valide' => (bool) (($user->is_valide ?? false) || $isVerified),
            'profile_completed' => $profileCompleted,
            'profile_completion_percentage' => $completionPct,
            'credits_restants' => (int) ($user->portefeuillePublication?->credits_restants ?? 0),
            'statut_verification' => $user->statut_verification,
            'commentaire_admin' => $user->commentaire_admin,
            'documents_identite' => $user->documentsIdentite
                ->map(fn($document) => [
                    'id' => $document->id,
                    'type_piece' => $document->type_piece,
                    'numero_piece' => $document->numero_piece,
                    'photo_piece_url' => self::fileUrl($document->photo_piece_url),
                    'statut_verification' => $document->statut_verification,
                    'commentaire_admin' => $document->commentaire_admin,
                ])
                ->values()
                ->all(),
            'profil' => self::profile($user),
        ];
    }

    public static function profile(User $user): array
    {
        return match ($user->role) {
            'etudiant' => [
                'etablissement' => $user->profilEtudiant?->etablissement,
                'universite_id' => $user->profilEtudiant?->universite_id,
                'universite' => self::universiteSafe($user->profilEtudiant?->universite),
                'filiere' => $user->profilEtudiant?->filiere,
                'niveau' => $user->profilEtudiant?->niveau,
                'sexe' => $user->profilEtudiant?->sexe,
                'budget_max' => $user->profilEtudiant?->budget_max,
                'preferences' => $user->profilEtudiant?->preferences ?? [],
            ],
            'demarcheur' => [
                'ifu' => $user->profilDemarcheur?->ifu,
                'nom_agence' => $user->profilDemarcheur?->nom_agence,
                'zone_activite' => $user->profilDemarcheur?->zone_activite,
            ],
            'boutique' => [
                'ifu_rccm' => $user->profilBoutique?->ifu_rccm,
                'nom_boutique' => $user->profilBoutique?->nom_boutique,
                'categorie' => $user->profilBoutique?->categorie,
                'adresse_physique' => $user->profilBoutique?->adresse_physique,
                'logo_url' => self::fileUrl($user->profilBoutique?->logo_url),
            ],
            default => [],
        };
    }

    public static function annonce(Annonce $annonce, array $extra = []): array
    {
        $annonce->loadMissing(['user.profilEtudiant.universite', 'user.profilDemarcheur', 'user.profilBoutique', 'medias', 'universite']);

        $medias = $annonce->medias
            ->map(fn($media) => [
                'id' => $media->id,
                'url_fichier' => self::fileUrl($media->url_fichier),
                'est_panoramique_3d' => (bool) $media->est_panoramique_3d,
            ])
            ->values();

        if ($medias->isEmpty() && $annonce->user?->photo_profil) {
            $medias = collect([
                [
                    'id' => 0,
                    'url_fichier' => self::fileUrl($annonce->user->photo_profil),
                    'est_panoramique_3d' => false,
                ]
            ]);
        }

        return array_merge([
            'id' => $annonce->id,
            'titre' => $annonce->titre,
            'description' => $annonce->description,
            'quartier' => $annonce->quartier,
            'universite_id' => $annonce->universite_id,
            'universite' => self::universiteSafe($annonce->universite),
            'loyer' => (int) $annonce->loyer,
            'caution' => (float) $annonce->caution,
            'source_eau' => $annonce->source_eau,
            'type_electricite' => $annonce->type_electricite,
            'lien_google_maps' => $annonce->lien_google_maps,
            'type_annonce' => $annonce->type_annonce,
            'type_chambre' => $annonce->type_chambre,
            'nombre_places' => $annonce->nombre_places ? (int) $annonce->nombre_places : ($annonce->type_annonce === 'PLACE_LIBRE_HOTE' ? 1 : null),
            'statut_disponibilite' => $annonce->statut_disponibilite,
            'statut_publication' => $annonce->statut_publication ?? 'active',
            'vues_count' => (int) ($annonce->vues_count ?? 0),
            'created_at' => $annonce->created_at?->toIso8601String(),
            'user' => [
                'id' => $annonce->user?->id,
                'name' => $annonce->user?->name,
                'prenoms' => $annonce->user?->prenoms,
                'role' => $annonce->user?->role,
                'telephone' => $annonce->user?->telephone,
                'photo_profil' => self::fileUrl($annonce->user?->photo_profil),
                'statut_verification' => $annonce->user?->statut_verification,
                'profil' => $annonce->user ? self::profile($annonce->user) : null,
            ],
            'medias' => $medias->all(),
        ], $extra);
    }

    public static function avis(Avis $avis, array $extra = []): array
    {
        $avis->loadMissing(['user.profilEtudiant', 'universite']);

        $student = $avis->user;

        return array_merge([
            'id' => $avis->id,
            'user_id' => $avis->user_id,
            'universite_id' => $avis->universite_id,
            'universite' => self::universiteSafe($avis->universite),
            'quartier' => $avis->quartier,
            'type_chambre' => $avis->type_chambre,
            'nombre_places' => $avis->nombre_places,
            'budget_min' => $avis->budget_min,
            'budget_max' => $avis->budget_max,
            'date_souhaitee' => $avis->date_souhaitee?->toDateString(),
            'description' => $avis->description,
            'statut' => $avis->statut,
            'created_at' => $avis->created_at?->toIso8601String(),
            'etudiant' => [
                'id' => $student?->id,
                'name' => $student?->name,
                'prenoms' => $student?->prenoms,
                'photo_profil' => self::fileUrl($student?->photo_profil),
                'statut_verification' => $student?->statut_verification,
                'is_verified' => (bool) ($student?->is_verified || $student?->statut_verification === 'valide'),
                'etablissement' => $student?->profilEtudiant?->etablissement,
                'filiere' => $student?->profilEtudiant?->filiere,
                'niveau' => $student?->profilEtudiant?->niveau,
                'universite_id' => $student?->profilEtudiant?->universite_id,
            ],
        ], $extra);
    }

    public static function notification(DatabaseNotification $notification): array
    {
        $meta = $notification->data['meta'] ?? [];

        if (isset($meta['sender']['photo_profil'])) {
            $meta['sender']['photo_profil'] = self::fileUrl($meta['sender']['photo_profil']);
        }

        return [
            'id' => $notification->id,
            'type' => $notification->data['type'] ?? $notification->type,
            'title' => $notification->data['title'] ?? 'Notification',
            'body' => $notification->data['body'] ?? '',
            'action_url' => $notification->data['action_url'] ?? null,
            'action_label' => $notification->data['action_label'] ?? null,
            'meta' => $meta,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }

    public static function conversation(Conversation $conversation): array
    {
        $conversation->loadMissing(['annonce.medias', 'avis.user', 'sender', 'receiver']);

        $userId = request()->user()?->id;
        $unreadCount = $conversation->messages()
            ->where('user_id', '!=', $userId)
            ->where('is_read', false)
            ->count();

        $annonceFirstMedia = $conversation->annonce?->medias?->first();

        return [
            'id' => $conversation->id,
            'annonce_id' => $conversation->annonce_id,
            'sender_id' => $conversation->sender_id,
            'receiver_id' => $conversation->receiver_id,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'unread_count' => $unreadCount,
            'annonce' => $conversation->annonce ? [
                'id' => $conversation->annonce->id,
                'titre' => $conversation->annonce->titre,
                'quartier' => $conversation->annonce->quartier,
                'loyer' => (int) $conversation->annonce->loyer,
                'photo' => $annonceFirstMedia?->url_fichier ? self::fileUrl($annonceFirstMedia->url_fichier) : null,
            ] : null,
            'avis' => $conversation->avis ? [
                'id' => $conversation->avis->id,
                'quartier' => $conversation->avis->quartier,
                'type_chambre' => $conversation->avis->type_chambre,
                'budget_min' => $conversation->avis->budget_min,
                'budget_max' => $conversation->avis->budget_max,
                'statut' => $conversation->avis->statut,
                'photo' => self::fileUrl($conversation->avis->user?->photo_profil),
                'etudiant_nom' => $conversation->avis->user
                    ? trim(($conversation->avis->user->prenoms ?? '') . ' ' . ($conversation->avis->user->name ?? ''))
                    : null,
            ] : null,
            'sender' => self::conversationParticipant($conversation->sender),
            'receiver' => self::conversationParticipant($conversation->receiver),
        ];
    }

    public static function conversationMessage(ConversationMessage $message): array
    {
        $message->loadMissing('user');

        $mediaUrl = $message->media_url;
        $type = $message->type ?? ($mediaUrl ? 'audio' : 'text');

        // Backwards-compatibility if audio column was not migrated yet on remote DB:
        if (!$mediaUrl && str_contains((string) $message->content, 'storage/vocaux/')) {
            if (preg_match('/(https?:\/\/[^\s]+storage\/vocaux\/[^\s]+)/', (string) $message->content, $matches)) {
                $mediaUrl = $matches[1];
                $type = 'audio';
            }
        }

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $message->user_id,
            'type' => $type,
            'content' => $message->content,
            'media_url' => self::fileUrl($mediaUrl),
            'duration' => $message->duration,
            'is_read' => (bool) $message->is_read,
            'created_at' => $message->created_at?->toIso8601String(),
            'user' => self::conversationParticipant($message->user),
        ];
    }

    public static function conversationMessages(iterable $messages): array
    {
        return collect($messages)
            ->map(fn($message) => self::conversationMessage($message))
            ->values()
            ->all();
    }

    private static function conversationParticipant(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'prenoms' => $user->prenoms,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'role' => $user->role,
            'photo_profil' => self::fileUrl($user->photo_profil),
            'statut_verification' => $user->statut_verification,
        ];
    }

    public static function publicite(PubliciteBoutique $publicite): array
    {
        $publicite->loadMissing('user.profilBoutique');

        return [
            'id' => $publicite->id,
            'titre_promo' => $publicite->titre_promo,
            'nom_produit' => $publicite->titre_promo,
            'prix' => $publicite->prix ? (int) $publicite->prix : null,
            'description' => $publicite->description,
            'categorie' => $publicite->categorie ?: ($publicite->user?->profilBoutique?->categorie ?: 'Équipements & Services'),
            'url_image_banniere' => self::fileUrl($publicite->url_image_banniere),
            'lien_action' => $publicite->lien_action,
            'quartier_cible' => $publicite->quartier_cible,
            'vues_count' => (int) $publicite->vues_count,
            'clics_count' => (int) $publicite->clics_count,
            'created_at' => $publicite->created_at?->toIso8601String(),
            'boutique' => [
                'user_id' => $publicite->user_id,
                'nom_boutique' => $publicite->user?->profilBoutique?->nom_boutique ?: ($publicite->user?->name ?: 'Boutique Partenaire'),
                'categorie' => $publicite->user?->profilBoutique?->categorie,
                'logo_url' => self::fileUrl($publicite->user?->profilBoutique?->logo_url) ?: self::fileUrl($publicite->user?->photo_profil),
                'telephone' => $publicite->user?->telephone,
                'adresse_physique' => $publicite->user?->profilBoutique?->adresse_physique,
                'is_verified' => (bool) ($publicite->user?->is_verified || $publicite->user?->statut_verification === 'verifie'),
            ],
        ];
    }

    public static function fileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '#/')) {
            return $path;
        }

        $relativePath = 'storage/' . ltrim($path, '/');
        $request = request();

        if ($request) {
            return $request->getSchemeAndHttpHost() . '/' . $relativePath;
        }

        return rtrim(config('app.url'), '/') . '/' . $relativePath;
    }
}
