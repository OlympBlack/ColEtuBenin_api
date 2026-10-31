<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MessageContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Envoi d'un message de contact / assistance
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|min:2|max:120',
            'email' => 'required|email|max:150',
            'telephone' => 'nullable|string|max:40',
            'sujet' => 'required|string|min:3|max:180',
            'message' => 'required|string|min:10|max:4000',
        ], [
            'nom.required' => 'Votre nom est requis.',
            'email.required' => 'Votre adresse email est requise.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
            'sujet.required' => 'Veuillez préciser l’objet de votre message.',
            'message.required' => 'Veuillez rédiger votre message.',
            'message.min' => 'Votre message doit contenir au moins 10 caractères.',
        ]);

        $user = $request->user('sanctum');

        $contact = MessageContact::create([
            'user_id' => $user?->id,
            'nom' => $validated['nom'],
            'email' => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'sujet' => $validated['sujet'],
            'message' => $validated['message'],
            'statut' => 'NOUVEAU',
        ]);

        return response()->json([
            'message' => 'Votre message a bien été envoyé à l’équipe ColEtuBenin. Nous vous répondrons dans les plus brefs délais.',
            'data' => $contact,
        ], 201);
    }

    /**
     * Liste des messages de contact reçus (Admin)
     */
    public function index(Request $request): JsonResponse
    {
        $statut = $request->query('statut');
        $search = $request->query('q');

        $query = MessageContact::with(['user', 'traitePar'])->latest();

        if ($statut && $statut !== 'all') {
            $query->where('statut', $statut);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telephone', 'like', "%{$search}%")
                    ->orWhere('sujet', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate(25);

        $counts = [
            'total' => MessageContact::count(),
            'nouveau' => MessageContact::where('statut', 'NOUVEAU')->count(),
            'traite' => MessageContact::where('statut', 'TRAITE')->count(),
        ];

        return response()->json([
            'data' => collect($messages->items())->map(function (MessageContact $m) {
                return [
                    'id' => $m->id,
                    'user_id' => $m->user_id,
                    'nom' => $m->nom,
                    'email' => $m->email,
                    'telephone' => $m->telephone,
                    'sujet' => $m->sujet,
                    'message' => $m->message,
                    'statut' => $m->statut,
                    'reponse_admin' => $m->reponse_admin,
                    'created_at' => $m->created_at->toISOString(),
                    'traite_le' => $m->traite_le?->toISOString(),
                    'traite_par' => $m->traitePar ? [
                        'id' => $m->traitePar->id,
                        'nom' => $m->traitePar->prenoms . ' ' . $m->traitePar->name,
                    ] : null,
                ];
            })->values(),
            'counts' => $counts,
            'pagination' => [
                'total' => $messages->total(),
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * Mettre à jour le statut d'un message de contact (Admin)
     */
    public function update(Request $request, $id): JsonResponse
    {
        $contact = MessageContact::findOrFail($id);

        $validated = $request->validate([
            'statut' => 'required|string|in:NOUVEAU,EN_COURS,TRAITE,ARCHIVE',
            'reponse_admin' => 'nullable|string|max:3000',
        ]);

        $contact->update([
            'statut' => $validated['statut'],
            'reponse_admin' => $validated['reponse_admin'] ?? $contact->reponse_admin,
            'traite_par_user_id' => $request->user()->id,
            'traite_le' => now(),
        ]);

        return response()->json([
            'message' => 'Le message a été mis à jour avec succès.',
            'data' => $contact->fresh(['user', 'traitePar']),
        ]);
    }

    /**
     * Supprimer un message de contact
     */
    public function destroy($id): JsonResponse
    {
        $contact = MessageContact::findOrFail($id);
        $contact->delete();

        return response()->json([
            'message' => 'Message supprimé avec succès.',
        ]);
    }
}
