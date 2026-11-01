<?php

namespace Database\Seeders;

use App\Models\ProfilBoutique;
use App\Models\ProfilDemarcheur;
use App\Models\ProfilEtudiant;
use App\Models\Universite;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfilesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $etudiants = [
            ['name' => 'TOSSOU', 'prenoms' => 'Jean', 'email' => 'jean.tossou@etudiant.uac.bj', 'filiere' => 'Genie Logiciel', 'etablissement' => 'IFRI', 'universite' => 'UAC', 'sexe' => 'M', 'niveau' => 'L3'],
            ['name' => 'ADAM', 'prenoms' => 'Sika', 'email' => 'sika.adam@etudiant.uac.bj', 'filiere' => 'Agronomie', 'etablissement' => 'FSA', 'universite' => 'UAC', 'sexe' => 'F', 'niveau' => 'M1'],
            ['name' => 'SESSOU', 'prenoms' => 'Luc', 'email' => 'luc.sessou@etudiant.uac.bj', 'filiere' => 'Droit', 'etablissement' => 'FADESP', 'universite' => 'UAC', 'sexe' => 'M', 'niveau' => 'L1'],
            ['name' => 'GOMEZ', 'prenoms' => 'Marie', 'email' => 'marie.gomez@etudiant.uac.bj', 'filiere' => 'Securite Informatique', 'etablissement' => 'IFRI', 'universite' => 'UAC', 'sexe' => 'F', 'niveau' => 'L3'],
            ['name' => 'ZINSOU', 'prenoms' => 'Rita', 'email' => 'rita.zinsou@etudiant.una.bj', 'filiere' => 'Agronomie Tropicale', 'etablissement' => 'ISA', 'universite' => 'UNA', 'sexe' => 'F', 'niveau' => 'L2'],
            ['name' => 'HOUNGBO', 'prenoms' => 'Koffi', 'email' => 'koffi.houngbo@etudiant.unstim.bj', 'filiere' => 'Comptabilite', 'etablissement' => 'FAST', 'universite' => 'UNSTIM', 'sexe' => 'M', 'niveau' => 'L2'],
            ['name' => 'DOSSOU', 'prenoms' => 'Pascal', 'email' => 'pascal.dossou@etudiant.unstim.bj', 'filiere' => 'Mathematiques', 'etablissement' => 'FAST', 'universite' => 'UNSTIM', 'sexe' => 'M', 'niveau' => 'L3'],
            ['name' => 'ALLOGBO', 'prenoms' => 'Clarisse', 'email' => 'clarisse.allogbo@etudiant.up.bj', 'filiere' => 'Informatique', 'etablissement' => 'FAST', 'universite' => 'UP', 'sexe' => 'F', 'niveau' => 'M1'],
        ];

        $prefOptions = ['Non-fumeur', 'Calme', 'Travailleur', 'Social', 'Sportif', 'Cuisinier'];

        $universiteIds = Universite::pluck('id', 'code')->all();

        foreach ($etudiants as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'prenoms' => $data['prenoms'],
                    'password' => Hash::make('password123'),
                    'telephone' => '+229' . rand(60000000, 99999999),
                    'role' => 'etudiant',
                    'photo_profil' => 'https://ui-avatars.com/api/?name=' . urlencode($data['prenoms'] . ' ' . $data['name']) . '&background=0f4c81&color=fff',
                    'is_verified' => true,
                    'is_valide' => true,
                    'statut_verification' => 'valide',
                    'commentaire_admin' => null,
                    'profile_completed' => true,
                ]
            );

            $preferences = array_values(
                array_intersect_key(
                    $prefOptions,
                    array_flip((array) array_rand($prefOptions, rand(2, 4)))
                )
            );

            $studentProfileData = [
                'etablissement' => $data['etablissement'],
                'universite_id' => $universiteIds[$data['universite']] ?? null,
                'filiere' => $data['filiere'],
                'niveau' => $data['niveau'],
                'sexe' => $data['sexe'],
                'preferences' => $preferences,
            ];

            ProfilEtudiant::updateOrCreate(
                ['user_id' => $user->id],
                $studentProfileData
            );
        }

        $demarcheurs = [
            ['name' => 'AGOSSOU', 'prenoms' => 'Marc', 'email' => 'marc.agossou@immo.bj', 'agence' => 'Marc Immo', 'zone' => 'Zogbadje'],
            ['name' => 'KPADONOU', 'prenoms' => 'Benoit', 'email' => 'benoit.k@agence.bj', 'agence' => 'Benoit Services', 'zone' => 'Calavi Centre'],
        ];

        foreach ($demarcheurs as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'prenoms' => $data['prenoms'],
                    'password' => Hash::make('password123'),
                    'telephone' => '+229' . rand(60000000, 99999999),
                    'role' => 'demarcheur',
                    'photo_profil' => 'https://ui-avatars.com/api/?name=' . urlencode($data['prenoms'] . ' ' . $data['name']) . '&background=2f855a&color=fff',
                    'is_verified' => true,
                    'is_valide' => true,
                    'statut_verification' => 'valide',
                    'commentaire_admin' => null,
                    'profile_completed' => true,
                ]
            );

            ProfilDemarcheur::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'ifu' => 'IFU' . rand(1000000000, 9999999999),
                    'nom_agence' => $data['agence'],
                    'zone_activite' => $data['zone'],
                    'adresse_bureau' => 'Abomey-Calavi, Quartier ' . $data['zone'],
                ]
            );
        }

        $boutiques = [
            ['name' => 'DOSSOU', 'prenoms' => 'Alice', 'email' => 'alice.meubles@shop.bj', 'boutique' => 'Alice Meubles', 'cat' => 'Meubles'],
            ['name' => 'BIO', 'prenoms' => 'Saliou', 'email' => 'saliou.info@shop.bj', 'boutique' => 'Saliou Informatique', 'cat' => 'Informatique'],
        ];

        foreach ($boutiques as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'prenoms' => $data['prenoms'],
                    'password' => Hash::make('password123'),
                    'telephone' => '+229' . rand(60000000, 99999999),
                    'role' => 'boutique',
                    'photo_profil' => 'https://ui-avatars.com/api/?name=' . urlencode($data['prenoms'] . ' ' . $data['name']) . '&background=975a16&color=fff',
                    'is_verified' => true,
                    'is_valide' => true,
                    'statut_verification' => 'valide',
                    'commentaire_admin' => null,
                    'profile_completed' => true,
                ]
            );

            ProfilBoutique::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'ifu_rccm' => 'RCCM' . rand(1000, 9999),
                    'nom_boutique' => $data['boutique'],
                    'categorie' => $data['cat'],
                    'adresse_physique' => 'Abomey-Calavi, Entree principale UAC',
                    'logo_url' => 'https://via.placeholder.com/150?text=' . urlencode($data['boutique']),
                ]
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@colcampus.bj'],
            [
                'name' => 'Admin',
                'prenoms' => 'Plateforme',
                'password' => Hash::make('password123'),
                'telephone' => '+22997000000',
                'role' => 'admin',
                'photo_profil' => 'https://ui-avatars.com/api/?name=Admin+Plateforme&background=111827&color=fff',
                'is_verified' => true,
                'is_valide' => true,
                'statut_verification' => 'valide',
                'commentaire_admin' => null,
                'profile_completed' => true,
            ]
        );
    }
}
