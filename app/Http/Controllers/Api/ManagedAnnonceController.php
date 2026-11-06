<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAnnonceRequest;
use App\Http\Requests\Api\UpdateAnnonceStatusRequest;
use App\Models\Annonce;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManagedAnnonceController extends Controller
{
    public function store(StoreAnnonceRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($roleGuard = $this->guardAnnouncementRules($user->role, $validated)) {
            return $roleGuard;
        }

        if ($user->role === 'demarcheur') {
            $activeAbonnement = $user->activeAbonnement()->first();
            $quotaTotal = $activeAbonnement?->plan?->limite_annonces ?? 2;

            if ($user->annonces()->count() >= $quotaTotal) {
                return response()->json([
                    'message' => 'Votre quota d annonces est atteint. Passez au Pack Pro ou archivez une annonce existante.',
                ], 403);
            }
        }

        $annonce = $user->annonces()->create($this->announcementPayload($validated, $request->universiteId()));
        $this->storeAnnouncementMedia($annonce, $request->file('medias', []), $request->file('panoramicMedias', []));

        return response()->json([
            'message' => 'Annonce publiee avec succes.',
            'data' => ApiData::annonce($annonce->fresh(['user', 'medias'])),
        ], 201);
    }

    public function update(StoreAnnonceRequest $request, Annonce $annonce): JsonResponse
    {
        if ($request->user()->id !== $annonce->user_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier cette annonce.'], 403);
        }

        $validated = $request->validated();

        if ($roleGuard = $this->guardAnnouncementRules($request->user()->role, $validated)) {
            return $roleGuard;
        }

        $annonce->update($this->announcementPayload($validated, $request->universiteId()));

        if ($request->hasFile('medias')) {
            $this->replaceAnnouncementMedia($annonce, $request->file('medias', []), $request->file('panoramicMedias', []));
        } elseif ($request->hasFile('panoramicMedias')) {
            $this->storeAnnouncementMedia($annonce, [], $request->file('panoramicMedias', []));
        }

        return response()->json([
            'message' => 'Annonce mise a jour avec succes.',
            'data' => ApiData::annonce($annonce->fresh(['user', 'medias'])),
        ]);
    }

    public function destroy(Request $request, Annonce $annonce): JsonResponse
    {
        if ($request->user()->id !== $annonce->user_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer cette annonce.'], 403);
        }

        foreach ($annonce->medias as $media) {
            if ($media->url_fichier) {
                Storage::disk('public')->delete($media->url_fichier);
            }
        }

        $annonce->delete();

        return response()->json([
            'message' => 'Annonce supprimee avec succes.',
        ]);
    }

    public function updateStatus(UpdateAnnonceStatusRequest $request, Annonce $annonce): JsonResponse
    {
        if ($request->user()->id !== $annonce->user_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier cette annonce.'], 403);
        }

        $annonce->update([
            'statut_disponibilite' => $request->validated()['statut_disponibilite'],
        ]);

        return response()->json([
            'message' => 'Statut de l annonce mis a jour.',
            'data' => ApiData::annonce($annonce->fresh(['user', 'medias'])),
        ]);
    }

    protected function guardAnnouncementRules(string $role, array $validated): ?JsonResponse
    {
        if ($role === 'boutique') {
            return response()->json(['message' => 'Une boutique ne peut pas publier d annonces de chambre.'], 403);
        }

        if ($role === 'demarcheur' && $validated['type_annonce'] !== 'CHAMBRE_VIDE') {
            return response()->json(['message' => 'Un demarcheur publie uniquement des chambres vides.'], 422);
        }

        if ($role === 'etudiant' && $validated['type_annonce'] === 'CHAMBRE_VIDE') {
            return response()->json(['message' => 'Un etudiant peut publier une place hote ou une recherche de binome.'], 422);
        }

        return null;
    }

    private function announcementPayload(array $validated, ?int $universiteId = null): array
    {
        return [
            'titre' => $validated['titre'],
            'description' => $validated['description'],
            'quartier' => $validated['quartier'],
            'universite_id' => $universiteId,
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
    }

    protected function storeAnnouncementMedia(Annonce $annonce, array $medias, array $panoramicMedias = []): void
    {
        foreach ($medias as $media) {
            $annonce->medias()->create([
                'url_fichier' => $media->store('annonces', 'public'),
                'est_panoramique_3d' => false,
            ]);
        }

        foreach ($panoramicMedias as $media) {
            $annonce->medias()->create([
                'url_fichier' => $media->store('annonces', 'public'),
                'est_panoramique_3d' => true,
            ]);
        }
    }

    private function replaceAnnouncementMedia(Annonce $annonce, array $medias, array $panoramicMedias = []): void
    {
        foreach ($annonce->medias as $media) {
            if ($media->url_fichier) {
                Storage::disk('public')->delete($media->url_fichier);
            }
        }

        $annonce->medias()->delete();
        $this->storeAnnouncementMedia($annonce, $medias, $panoramicMedias);
    }
}
