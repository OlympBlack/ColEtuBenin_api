<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreAnnonceRequest;
use App\Services\ServicePublicationAnnonce;
use App\Models\Annonce;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;

class ControleurAnnonce extends ManagedAnnonceController
{
    protected $servicePublication;

    public function __construct(ServicePublicationAnnonce $servicePublication)
    {
        $this->servicePublication = $servicePublication;
    }

    /**
     * Publier une nouvelle annonce (avec gestion des crédits pour les démarcheurs)
     */
    public function store(StoreAnnonceRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // 1. Vérification des règles de rôle (réutilisation de ManagedAnnonceController)
        if ($roleGuard = $this->guardAnnouncementRules($user->role, $validated)) {
            return $roleGuard;
        }

        try {
            // 2. Publication via le service de crédits
            // On prépare le payload. Le service s'occupe de vérifier les crédits et de créer l'annonce.
            $payload = [
                'titre' => $validated['titre'],
                'description' => $validated['description'],
                'quartier' => $validated['quartier'],
                'universite_id' => $request->universiteId(),
                'loyer' => $validated['loyer'],
                'caution' => $validated['caution'],
                'source_eau' => $validated['source_eau'],
                'type_electricite' => $validated['type_electricite'],
                'lien_google_maps' => $validated['lien_google_maps'] ?? null,
                'type_annonce' => $validated['type_annonce'],
                'type_chambre' => $validated['type_chambre'] ?? null,
                'nombre_places' => isset($validated['nombre_places']) ? (int) $validated['nombre_places'] : ($validated['type_annonce'] === 'PLACE_LIBRE_HOTE' ? 1 : null),
                'statut_disponibilite' => $validated['statut_disponibilite'] ?? 'available',
            ];

            $annonce = $this->servicePublication->publierAvecCredits($user, $payload);

            // 3. Gestion des médias
            if ($request->hasFile('medias')) {
                $this->storeAnnouncementMedia($annonce, $request->file('medias'));
            }

            return response()->json([
                'message' => 'Annonce publiée avec succès.',
                'data' => ApiData::annonce($annonce->fresh(['user', 'medias'])),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 403);
        }
    }
}
