<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\Conversation;
use App\Models\Universite;
use App\Support\ApiData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvisController extends Controller
{
    /**
     * Liste publique des avis "je cherche une chambre".
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'universite' => ['nullable', 'string', 'max:20'],
            'universite_id' => ['nullable', 'integer', 'exists:universites,id'],
            'quartier' => ['nullable', 'string', 'max:120'],
            'type_chambre' => ['nullable', 'string', 'max:60'],
            'budget_max' => ['nullable', 'integer', 'min:0'],
            'q' => ['nullable', 'string', 'max:120'],
            'statut' => ['nullable', 'in:ouverte,pourvue,toutes'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $statut = $validated['statut'] ?? 'ouverte';
        $perPage = (int) ($validated['per_page'] ?? 9);

        $query = Avis::query()
            ->with(['user.profilEtudiant', 'universite'])
            ->when($statut !== 'toutes', fn ($q) => $q->where('statut', $statut))
            ->when(!empty($validated['universite_id']), function ($q) use ($validated) {
                $q->where('universite_id', $validated['universite_id']);
            })
            ->when(empty($validated['universite_id']) && !empty($validated['universite']), function ($q) use ($validated) {
                $q->whereHas('universite', function ($sub) use ($validated) {
                    $sub->where('code', strtoupper($validated['universite']));
                });
            })
            ->when(!empty($validated['quartier']), function ($q) use ($validated) {
                $q->where('quartier', 'like', '%' . $validated['quartier'] . '%');
            })
            ->when(!empty($validated['type_chambre']), function ($q) use ($validated) {
                $q->where('type_chambre', $validated['type_chambre']);
            })
            ->when(isset($validated['budget_max']), function ($q) use ($validated) {
                $q->where('budget_max', '<=', $validated['budget_max']);
            })
            ->when(!empty($validated['q']), function ($q) use ($validated) {
                $search = $validated['q'];
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'like', "%{$search}%")
                        ->orWhere('quartier', 'like', "%{$search}%");
                });
            })
            ->latest();

        $paginator = $query->paginate($perPage);

        $conversationMap = $this->conversationMapForUser($request->user('sanctum'), $paginator->items());

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (Avis $avis) => ApiData::avis($avis, [
                    'conversation_id' => $conversationMap[$avis->id] ?? null,
                ]))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => [
                'total_ouvertes' => Avis::where('statut', 'ouverte')->count(),
            ],
        ]);
    }

    public function show(Request $request, Avis $avis): JsonResponse
    {
        $conversationMap = $this->conversationMapForUser($request->user('sanctum'), [$avis]);

        return response()->json([
            'data' => ApiData::avis($avis, [
                'conversation_id' => $conversationMap[$avis->id] ?? null,
            ]),
        ]);
    }

    /**
     * Avis de l'utilisateur connecte (tous statuts confondus).
     */
    public function mine(Request $request): JsonResponse
    {
        $avis = Avis::query()
            ->with(['user.profilEtudiant', 'universite'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data' => $avis->map(fn (Avis $item) => ApiData::avis($item))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'etudiant') {
            return response()->json([
                'message' => 'Seuls les etudiants peuvent publier un avis de recherche de chambre.',
            ], 403);
        }

        $alreadyOpen = Avis::query()
            ->where('user_id', $user->id)
            ->where('statut', 'ouverte')
            ->first();

        if ($alreadyOpen) {
            return response()->json([
                'message' => 'Vous avez deja un avis ouvert. Modifiez-le ou cloturez-le avant d\'en publier un nouveau.',
                'data' => ApiData::avis($alreadyOpen, ['conversation_id' => null]),
            ], 422);
        }

        $validated = $this->validateAvis($request);

        $avis = Avis::create([
            'user_id' => $user->id,
            'universite_id' => $this->resolveUniversiteId($validated),
            'quartier' => $validated['quartier'],
            'type_chambre' => $validated['type_chambre'] ?? null,
            'nombre_places' => $validated['nombre_places'] ?? null,
            'budget_min' => $validated['budget_min'] ?? null,
            'budget_max' => $validated['budget_max'],
            'date_souhaitee' => $validated['date_souhaitee'] ?? null,
            'description' => $validated['description'],
            'statut' => 'ouverte',
        ]);

        return response()->json([
            'message' => 'Votre avis est en ligne. Les demarcheurs peuvent desormais vous contacter.',
            'data' => ApiData::avis($avis->fresh(['user.profilEtudiant', 'universite'])),
        ], 201);
    }

    public function update(Request $request, Avis $avis): JsonResponse
    {
        $user = $request->user();

        if ((int) $avis->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Vous ne pouvez modifier que vos propres avis.'], 403);
        }

        $validated = $this->validateAvis($request, true);

        $avis->update([
            'universite_id' => $this->resolveUniversiteId($validated, $avis->universite_id),
            'quartier' => $validated['quartier'],
            'type_chambre' => $validated['type_chambre'] ?? null,
            'nombre_places' => $validated['nombre_places'] ?? null,
            'budget_min' => $validated['budget_min'] ?? null,
            'budget_max' => $validated['budget_max'],
            'date_souhaitee' => $validated['date_souhaitee'] ?? null,
            'description' => $validated['description'],
            'statut' => $validated['statut'] ?? $avis->statut,
        ]);

        return response()->json([
            'message' => 'Votre avis a ete mis a jour.',
            'data' => ApiData::avis($avis->fresh(['user.profilEtudiant', 'universite'])),
        ]);
    }

    public function destroy(Request $request, Avis $avis): JsonResponse
    {
        if ((int) $avis->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez supprimer que vos propres avis.'], 403);
        }

        $avis->delete();

        return response()->json(['message' => 'Votre avis a ete supprime.']);
    }

    /**
     * Ouvre (ou retrouve) la discussion interne entre un demarcheur et l'etudiant
     * auteur de l'avis.
     */
    public function contact(Request $request, Avis $avis): JsonResponse
    {
        $user = $request->user();

        if ((int) $avis->user_id === (int) $user->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas contacter votre propre avis.',
            ], 422);
        }

        if ($avis->statut !== 'ouverte') {
            return response()->json([
                'message' => 'Cet avis est cloture : l\'etudiant a deja trouve son logement.',
            ], 422);
        }

        $conversation = Conversation::findBetween($user->id, (int) $avis->user_id);

        if ($conversation) {
            if ((int) $conversation->avis_id !== (int) $avis->id || $conversation->annonce_id !== null) {
                $conversation->update([
                    'avis_id' => $avis->id,
                    'annonce_id' => null,
                ]);
            }
        } else {
            $conversation = Conversation::create([
                'annonce_id' => null,
                'avis_id' => $avis->id,
                'sender_id' => $user->id,
                'receiver_id' => $avis->user_id,
                'last_message_at' => now(),
            ]);
        }

        $conversation->loadMissing(['annonce', 'avis.user', 'sender', 'receiver', 'messages.user']);

        return response()->json([
            'message' => 'Discussion ouverte. Presentez votre offre a l\'etudiant.',
            'data' => [
                'conversation' => ApiData::conversation($conversation),
            ],
        ]);
    }

    private function validateAvis(Request $request, bool $withStatut = false): array
    {
        $rules = [
            'universite' => ['nullable', 'string', 'max:20'],
            'universite_id' => ['nullable', 'integer', 'exists:universites,id'],
            'quartier' => ['required', 'string', 'min:2', 'max:120'],
            'type_chambre' => ['nullable', 'string', 'max:60'],
            'nombre_places' => ['nullable', 'integer', 'min:1', 'max:20'],
            'budget_min' => ['nullable', 'integer', 'min:0', 'max:100000000', 'lte:budget_max'],
            'budget_max' => ['required', 'integer', 'min:0', 'max:100000000'],
            'date_souhaitee' => ['nullable', 'date'],
            'description' => ['required', 'string', 'min:30', 'max:3000'],
        ];

        if ($withStatut) {
            $rules['statut'] = ['nullable', 'in:ouverte,pourvue,archivee'];
        }

        return $request->validate($rules, [
            'quartier.required' => 'Indiquez le quartier ou la zone recherchee.',
            'quartier.min' => 'Le quartier doit contenir au moins 2 caracteres.',
            'budget_max.required' => 'Indiquez votre budget maximum par mois.',
            'budget_min.lte' => 'Le budget minimum ne peut pas depasser le budget maximum.',
            'description.required' => 'Decrivez ce que vous recherchez.',
            'description.min' => 'Votre description doit contenir au moins 30 caracteres pour aider les demarcheurs.',
        ]);
    }

    private function resolveUniversiteId(array $validated, ?int $fallback = null): ?int
    {
        if (!empty($validated['universite_id'])) {
            return (int) $validated['universite_id'];
        }

        if (!empty($validated['universite'])) {
            return Universite::where('code', strtoupper($validated['universite']))->value('id');
        }

        return $fallback;
    }

    /**
     * @param  array<int, Avis>  $avis
     * @return array<int, int>
     */
    private function conversationMapForUser(?object $user, array $avis): array
    {
        if (!$user || empty($avis)) {
            return [];
        }

        $avisIds = collect($avis)->pluck('id')->filter()->values()->all();

        if (empty($avisIds)) {
            return [];
        }

        return Conversation::query()
            ->whereIn('avis_id', $avisIds)
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->pluck('id', 'avis_id')
            ->all();
    }
}
