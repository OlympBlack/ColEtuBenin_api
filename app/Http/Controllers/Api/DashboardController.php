<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Support\ApiData;
use App\Support\ProfileDataManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'profilEtudiant',
            'profilDemarcheur',
            'profilBoutique',
            'documentsIdentite',
            'annonces.medias',
            'favoris.annonce.user',
            'favoris.annonce.medias',
            'publicitesBoutiques',
            'abonnements.plan',
        ]);

        $conversations = clone $user->conversationsAsSender;
        $conversations = $conversations->merge($user->conversationsAsReceiver)
            ->load([
                'annonce',
                'avis.user',
                'sender',
                'receiver',
                'messages' => function ($query) {
                    $query->latest()->limit(1);
                }
            ])
            ->sortByDesc('last_message_at')
            ->values();

        // Garantir strictement une seule discussion par interlocuteur (un seul contact dans la liste)
        $uniqueConversations = collect();
        $seenPartnerIds = [];
        foreach ($conversations as $c) {
            $partnerId = (int) $c->sender_id === (int) $user->id ? (int) $c->receiver_id : (int) $c->sender_id;
            if ($partnerId > 0 && !in_array($partnerId, $seenPartnerIds, true)) {
                $seenPartnerIds[] = $partnerId;
                $uniqueConversations->push($c);
            }
        }
        $conversations = $uniqueConversations;

        $baseData = [
            'user' => ApiData::user($user),
            'profile_progress' => [
                'percentage' => ProfileDataManager::completionPercentage($user),
                'completed' => (bool) $user->profile_completed,
            ],
            'verification' => [
                'statut' => $user->statut_verification,
                'commentaire_admin' => $user->commentaire_admin,
                'documents_count' => $user->documentsIdentite->count(),
                'can_contact' => $user->isKycValidated(),
                'can_publish' => $user->isKycValidated(),
            ],
            'mes_conversations' => $conversations->map(fn($c) => [
                'conversation' => ApiData::conversation($c),
                'last_message' => $c->messages->first() ? ApiData::conversationMessage($c->messages->first()) : null,
            ])->toArray(),
            'unread_messages_count' => $conversations->filter(fn($c) => (ApiData::conversation($c)['unread_count'] ?? 0) > 0)->count(),
        ];

        $roleData = match ($user->role) {
            'etudiant' => $this->studentDashboard($user),
            'demarcheur' => $this->demarcheurDashboard($user),
            'boutique' => $this->boutiqueDashboard($user),
            'admin' => $this->adminDashboard($user),
            default => [],
        };

        return response()->json([
            'data' => array_merge($baseData, $roleData),
        ]);
    }

    private function adminDashboard($user): array
    {
        $totalUsers = \App\Models\User::count();
        $totalStudents = \App\Models\User::where('role', 'etudiant')->count();
        $totalDemarcheurs = \App\Models\User::where('role', 'demarcheur')->count();
        $totalBoutiques = \App\Models\User::where('role', 'boutique')->count();

        $annonces = Annonce::with(['user', 'medias'])->latest()->get();
        $totalAnnonces = $annonces->count();
        $annoncesActives = $annonces->where('statut_disponibilite', '!=', 'taken')->count();

        $kycPendingUsers = \App\Models\User::where('role', '!=', 'admin')
            ->whereIn('statut_verification', ['pending_validation', 'en_attente'])
            ->with(['documentsIdentite', 'profilEtudiant', 'profilDemarcheur'])
            ->latest()
            ->get();

        $recentUsers = \App\Models\User::where('role', '!=', 'admin')
            ->latest()
            ->take(15)
            ->get();

        $recettesTotal = (float) \App\Models\AchatForfait::where('statut_paiement', 'valide')->sum('montant');
        $achatsRecents = \App\Models\AchatForfait::with('user')->latest()->take(15)->get();
        $signalementsEnAttente = \App\Models\Signalement::where('statut', 'EN_ATTENTE')->count();
        $contactsNouveaux = \App\Models\MessageContact::where('statut', 'NOUVEAU')->count();

        return [
            'admin' => [
                'statistiques' => [
                    'total_utilisateurs' => $totalUsers,
                    'total_etudiants' => $totalStudents,
                    'total_demarcheurs' => $totalDemarcheurs,
                    'total_boutiques' => $totalBoutiques,
                    'total_annonces' => $totalAnnonces,
                    'annonces_actives' => $annoncesActives,
                    'kyc_en_attente' => $kycPendingUsers->count(),
                    'recettes_totales_fcfa' => $recettesTotal,
                    'signalements_en_attente' => $signalementsEnAttente,
                    'contacts_nouveaux' => $contactsNouveaux,
                ],
                'kyc_en_attente_liste' => $kycPendingUsers->map(fn($u) => ApiData::user($u))->values(),
                'annonces_recentes' => $annonces->take(20)->map(fn(Annonce $a) => ApiData::annonce($a))->values(),
                'utilisateurs_recents' => $recentUsers->map(fn($u) => ApiData::user($u))->values(),
                'achats_recents' => $achatsRecents->map(fn($af) => [
                    'id' => $af->id,
                    'user_id' => $af->user_id,
                    'user_nom' => $af->user ? ($af->user->name . ' ' . $af->user->prenoms) : 'Utilisateur',
                    'user_email' => $af->user?->email,
                    'user_role' => $af->user?->role,
                    'montant' => (float) $af->montant,
                    'nombre_publications' => $af->nombre_publications,
                    'methode_paiement' => $af->methode_paiement ?? 'Mobile Money',
                    'reference' => $af->reference_transaction,
                    'statut' => $af->statut_paiement,
                    'date' => $af->created_at->toISOString(),
                ])->values(),
            ],
        ];
    }

    private function studentDashboard($user): array
    {
        $myAnnouncements = $user->annonces()->with(['user', 'medias'])->latest()->get();
        $favorites = $user->favoris->pluck('annonce')->filter();

        $suggested = Annonce::query()
            ->with(['user.profilEtudiant', 'medias'])
            ->where('user_id', '!=', $user->id)
            ->where('statut_disponibilite', '!=', 'taken')
            ->get()
            ->sortByDesc(function (Annonce $annonce) use ($user) {
                $score = 0;

                if (in_array($annonce->type_annonce, ['PLACE_LIBRE_HOTE', 'RECHERCHE_BINOME'], true)) {
                    $score += 40;
                }

                if ($annonce->user?->role === 'etudiant') {
                    $score += 20;
                }

                if ($annonce->user?->profilEtudiant?->sexe && $annonce->user->profilEtudiant->sexe === $user->profilEtudiant?->sexe) {
                    $score += 25;
                }

                if ((int) $annonce->loyer <= 50000) {
                    $score += 10;
                }

                return $score;
            })
            ->take(6)
            ->values();

        return [
            'student' => [
                'preferences' => $user->profilEtudiant?->preferences ?? [],
                'mes_annonces' => $myAnnouncements->map(fn(Annonce $annonce) => ApiData::annonce($annonce))->values(),
                'favoris' => $favorites->map(fn(Annonce $annonce) => ApiData::annonce($annonce, ['est_favori' => true]))->values(),
                'annonces_suggerees' => $suggested->map(fn(Annonce $annonce) => ApiData::annonce($annonce))->values(),
            ],
        ];
    }

    private function demarcheurDashboard($user): array
    {
        $annonces = $user->annonces()->with(['user', 'medias'])->latest()->get();
        $servicePortefeuille = new \App\Services\ServicePortefeuillePublications();
        $portefeuille = $servicePortefeuille->obtenirOuInitialiserPortefeuille($user);
        $activeAbonnement = $user->activeAbonnement()->first();

        return [
            'demarcheur' => [
                'mes_annonces' => $annonces->map(fn(Annonce $annonce) => ApiData::annonce($annonce))->values(),
                'statistiques' => [
                    'annonces_publiees' => $annonces->count(),
                    'annonces_actives' => $annonces->where('statut_publication', 'active')->count(),
                    'annonces_en_attente' => $annonces->where('statut_publication', 'en_attente')->count(),
                    'annonces_refusees' => $annonces->where('statut_publication', 'refusee')->count(),
                    'total_vues' => $annonces->sum('vues_count'),
                    'total_messages' => $user->conversationsAsReceiver->count(),
                    'credits_restants' => $portefeuille->credits_restants,
                ],
                'stock' => [
                    'disponible' => $annonces->where('statut_disponibilite', 'available')->count(),
                    'occupe' => $annonces->where('statut_disponibilite', 'taken')->count(),
                    'en_attente' => $annonces->where('statut_disponibilite', 'pending')->count(),
                ],
                'portefeuille' => [
                    'credits_gratuits' => (int) $portefeuille->credits_gratuits,
                    'credits_achetes' => (int) $portefeuille->credits_achetes,
                    'credits_utilises' => (int) $portefeuille->credits_utilises,
                    'credits_restants' => (int) $portefeuille->credits_restants,
                    'transactions_recentes' => $user->transactionsCredits()->latest()->limit(5)->get()->map(fn($t) => [
                        'id' => $t->id,
                        'type' => $t->type,
                        'montant' => $t->montant,
                        'description' => $t->description,
                        'created_at' => $t->created_at->toISOString(),
                    ]),
                    'historique' => $user->transactionsCredits()->latest()->limit(20)->get()->map(fn($t) => [
                        'id' => $t->id,
                        'type' => $t->type,
                        'montant' => $t->montant,
                        'description' => $t->description,
                        'date' => $t->created_at->toISOString(),
                    ]),
                    'achats_recents' => $user->achatsForfaits()->with('forfait')->latest()->limit(10)->get()->map(fn($a) => [
                        'id' => $a->id,
                        'forfait_titre' => $a->forfait?->titre ?? 'Pack',
                        'nombre_publications' => $a->nombre_publications,
                        'montant' => $a->montant,
                        'reference_transaction' => $a->reference_transaction,
                        'date' => $a->created_at->toISOString(),
                    ]),
                ],
                'abonnement' => [
                    'nom_plan' => $activeAbonnement?->plan?->nom_plan ?? 'Gratuit',
                    'statut' => $activeAbonnement ? 'Pack Pro' : 'Gratuit',
                    'quota_total' => $portefeuille->credits_gratuits + $portefeuille->credits_achetes,
                    'quota_restante' => $portefeuille->credits_restants,
                ],
            ],
        ];
    }

    private function boutiqueDashboard($user): array
    {
        $publicites = $user->publicitesBoutiques()->latest()->get();
        $servicePortefeuille = new \App\Services\ServicePortefeuillePublications();
        $portefeuille = $servicePortefeuille->obtenirOuInitialiserPortefeuille($user);

        return [
            'boutique' => [
                'mes_publicites' => $publicites->map(fn($publicite) => ApiData::publicite($publicite))->values(),
                'statistiques' => [
                    'vues_total' => $publicites->sum('vues_count'),
                    'clics_total' => $publicites->sum('clics_count'),
                    'credits_restants' => $portefeuille->credits_restants,
                ],
                'portefeuille' => [
                    'credits_gratuits' => (int) $portefeuille->credits_gratuits,
                    'credits_achetes' => (int) $portefeuille->credits_achetes,
                    'credits_utilises' => (int) $portefeuille->credits_utilises,
                    'credits_restants' => (int) $portefeuille->credits_restants,
                    'transactions_recentes' => $user->transactionsCredits()->latest()->limit(5)->get()->map(fn($t) => [
                        'id' => $t->id,
                        'type' => $t->type,
                        'montant' => $t->montant,
                        'description' => $t->description,
                        'created_at' => $t->created_at->toISOString(),
                    ]),
                    'historique' => $user->transactionsCredits()->latest()->limit(20)->get()->map(fn($t) => [
                        'id' => $t->id,
                        'type' => $t->type,
                        'montant' => $t->montant,
                        'description' => $t->description,
                        'date' => $t->created_at->toISOString(),
                    ]),
                    'achats_recents' => $user->achatsForfaits()->with('forfait')->latest()->limit(10)->get()->map(fn($a) => [
                        'id' => $a->id,
                        'forfait_titre' => $a->forfait?->titre ?? 'Pack',
                        'nombre_publications' => $a->nombre_publications,
                        'montant' => $a->montant,
                        'reference_transaction' => $a->reference_transaction,
                        'date' => $a->created_at->toISOString(),
                    ]),
                ],
            ],
        ];
    }
}

