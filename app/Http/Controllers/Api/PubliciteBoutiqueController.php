<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PubliciteBoutique;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PubliciteBoutiqueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quartier' => ['nullable', 'string', 'max:255'],
            'categorie' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'boutique_id' => ['nullable', 'integer'],
            'sort' => ['nullable', 'string', 'in:recent,price_asc,price_desc,popular'],
        ]);

        $query = PubliciteBoutique::query()
            ->with(['user.profilBoutique']);

        if (!empty($validated['quartier'])) {
            $quartier = $validated['quartier'];
            $query->where(function ($innerQuery) use ($quartier) {
                $innerQuery
                    ->whereNull('quartier_cible')
                    ->orWhere('quartier_cible', $quartier);
            });
        }

        if (!empty($validated['categorie']) && $validated['categorie'] !== 'Tous' && $validated['categorie'] !== 'Tous les produits') {
            $cat = $validated['categorie'];
            $query->where(function ($innerQuery) use ($cat) {
                $innerQuery->where('categorie', $cat)
                    ->orWhereHas('user.profilBoutique', function ($q) use ($cat) {
                        $q->where('categorie', $cat);
                    });
            });
        }

        if (!empty($validated['boutique_id'])) {
            $query->where('user_id', $validated['boutique_id']);
        }

        if (!empty($validated['search'])) {
            $search = '%' . $validated['search'] . '%';
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('titre_promo', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhereHas('user.profilBoutique', function ($q) use ($search) {
                        $q->where('nom_boutique', 'like', $search);
                    });
            });
        }

        $sort = $validated['sort'] ?? 'recent';
        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw('CASE WHEN prix IS NULL THEN 1 ELSE 0 END, prix ASC');
                break;
            case 'price_desc':
                $query->orderByDesc('prix');
                break;
            case 'popular':
                $query->orderByDesc('vues_count')->orderByDesc('clics_count');
                break;
            case 'recent':
            default:
                $query->latest();
                break;
        }

        $publicites = $query->get();

        if ($publicites->isNotEmpty()) {
            PubliciteBoutique::query()
                ->whereIn('id', $publicites->pluck('id'))
                ->increment('vues_count');

            $publicites->each(function (PubliciteBoutique $publicite) {
                $publicite->vues_count++;
            });
        }

        return response()->json([
            'data' => $publicites->map(fn (PubliciteBoutique $publicite) => ApiData::publicite($publicite))->values(),
        ]);
    }

    public function show(PubliciteBoutique $publicite): JsonResponse
    {
        $publicite->increment('vues_count');
        $publicite->loadMissing(['user.profilBoutique']);

        return response()->json([
            'data' => ApiData::publicite($publicite),
        ]);
    }

    public function contact(Request $request, PubliciteBoutique $publicite): JsonResponse
    {
        $user = $request->user();

        if ((int) $publicite->user_id === (int) $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas initier une discussion avec votre propre boutique.',
            ], 422);
        }

        $conversation = \App\Models\Conversation::findBetween($user->id, (int) $publicite->user_id);

        if (!$conversation) {
            $conversation = \App\Models\Conversation::create([
                'sender_id' => $user->id,
                'receiver_id' => $publicite->user_id,
                'last_message_at' => now(),
            ]);
        }

        $conversation->loadMissing(['sender', 'receiver', 'messages.user']);

        return response()->json([
            'message' => 'Discussion avec la boutique prête.',
            'data' => [
                'conversation' => ApiData::conversation($conversation),
            ],
        ]);
    }

    public function click(PubliciteBoutique $publicite): JsonResponse
    {
        $publicite->increment('clics_count');

        return response()->json([
            'message' => 'Clic publicitaire enregistre.',
            'data' => ApiData::publicite($publicite->fresh(['user.profilBoutique'])),
        ]);
    }
}
