<?php

namespace App\Support;

use App\Models\Universite;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileDataManager
{
    public static function sync(User $user, array $validated, Request $request): void
    {
        if ($request->hasFile('photo_profil')) {
            $user->photo_profil = $request->file('photo_profil')->store('profiles', 'public');
        }

        match ($user->role) {
            'etudiant' => $user->profilEtudiant()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'etablissement' => $validated['etablissement'],
                    'universite_id' => self::resolveUniversiteId($validated),
                    'filiere' => $validated['filiere'],
                    'niveau' => $validated['niveau'],
                    'sexe' => $validated['sexe'],
                    'preferences' => array_values($validated['preferences'] ?? []),
                    'budget_max' => $validated['budget_max'] ?? null,
                ]
            ),
            'demarcheur' => $user->profilDemarcheur()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'ifu' => $validated['ifu'],
                    'nom_agence' => $validated['nom_agence'],
                    'zone_activite' => $validated['zone_activite'],
                ]
            ),
            'boutique' => $user->profilBoutique()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'ifu_rccm' => $validated['ifu_rccm'],
                    'nom_boutique' => $validated['nom_boutique'],
                    'categorie' => $validated['categorie'],
                    'adresse_physique' => $validated['adresse_physique'],
                    'logo_url' => $request->hasFile('logo_boutique')
                        ? $request->file('logo_boutique')->store('boutiques/logos', 'public')
                        : $user->profilBoutique?->logo_url,
                ]
            ),
            default => null,
        };

        $user->profile_completed = self::completionPercentage($user->fresh(['profilEtudiant', 'profilDemarcheur', 'profilBoutique', 'documentsIdentite'])) >= 100;
    }

    public static function completionPercentage(User $user): int
    {
        if (
            $user->role === 'admin' ||
            (bool) $user->is_verified ||
            (bool) ($user->is_valide ?? false) ||
            $user->statut_verification === 'valide' ||
            $user->statut_verification === 'verifie'
        ) {
            return 100;
        }

        $checks = [
            filled($user->name),
            filled($user->prenoms),
            filled($user->email),
            filled($user->telephone),
            filled($user->photo_profil),
        ];

        $checks = array_merge($checks, match ($user->role) {
            'etudiant' => [
                filled($user->profilEtudiant?->etablissement),
                filled($user->profilEtudiant?->filiere),
                filled($user->profilEtudiant?->niveau),
                filled($user->profilEtudiant?->sexe),
                !empty($user->profilEtudiant?->preferences ?? []),
            ],
            'demarcheur' => [
                filled($user->profilDemarcheur?->ifu),
                filled($user->profilDemarcheur?->nom_agence),
                filled($user->profilDemarcheur?->zone_activite),
            ],
            'boutique' => [
                filled($user->profilBoutique?->ifu_rccm),
                filled($user->profilBoutique?->nom_boutique),
                filled($user->profilBoutique?->categorie),
                filled($user->profilBoutique?->adresse_physique),
            ],
            default => [],
        });

        $total = count($checks);
        $completed = count(array_filter($checks));

        return $total === 0 ? 0 : (int) round(($completed / $total) * 100);
    }

    private static function resolveUniversiteId(array $validated): ?int
    {
        if (!empty($validated['universite_id'])) {
            return (int) $validated['universite_id'];
        }

        if (!empty($validated['universite'])) {
            return Universite::where('code', strtoupper($validated['universite']))->value('id');
        }

        return null;
    }
}
