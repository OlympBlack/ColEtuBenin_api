<?php

namespace Database\Seeders;

use App\Models\Annonce;
use App\Models\MediaAnnonce;
use App\Models\Universite;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnonceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MediaAnnonce::query()->delete();
        Annonce::query()->delete();

        $demarcheurMarc = User::where('email', 'marc.agossou@immo.bj')->firstOrFail();
        $demarcheurBenoit = User::where('email', 'benoit.k@agence.bj')->firstOrFail();
        $etudianteSika = User::where('email', 'sika.adam@etudiant.uac.bj')->firstOrFail();
        $etudianteMarie = User::where('email', 'marie.gomez@etudiant.uac.bj')->firstOrFail();
        $etudiantLuc = User::where('email', 'luc.sessou@etudiant.uac.bj')->firstOrFail();
        $etudiantJean = User::where('email', 'jean.tossou@etudiant.uac.bj')->firstOrFail();
        $etudianteRita = User::where('email', 'rita.zinsou@etudiant.una.bj')->firstOrFail();
        $etudiantKoffi = User::where('email', 'koffi.houngbo@etudiant.unstim.bj')->firstOrFail();
        $etudianteClarisse = User::where('email', 'clarisse.allogbo@etudiant.up.bj')->firstOrFail();

        $universiteIds = Universite::pluck('id', 'code')->all();

        $annonces = [
            [
                'user_id' => $demarcheurMarc->id,
                'titre' => 'Chambre carree avec douche interne a Zogbadje',
                'description' => 'Chambre propre et bien aeree a 12 minutes du campus, cour fermee, compteur carte, acces facile pour taxi-moto et superette a deux ruelles.',
                'quartier' => 'Zogbadje',
                'loyer' => 28000,
                'caution' => 84000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Zogbadje+Abomey-Calavi',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'ENTREE_COUCHEE_SANITAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    [
                        'url_fichier' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                        'est_panoramique_3d' => false,
                    ],
                    [
                        'url_fichier' => 'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
                        'est_panoramique_3d' => true,
                    ],
                    [
                        'url_fichier' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
                        'est_panoramique_3d' => false,
                    ],
                ],
            ],
            [
                'user_id' => $demarcheurBenoit->id,
                'titre' => 'Entree couchee meublable a Tankpe proche voie pavee',
                'description' => 'Piece spacieuse avec faux plafond, petite terrasse, forage disponible, environnement calme pour etudiant ou jeune travailleur.',
                'quartier' => 'Tankpe',
                'loyer' => 32000,
                'caution' => 96000,
                'source_eau' => 'Forage',
                'type_electricite' => 'SBEE_CONVENTIONNEL',
                'lien_google_maps' => 'https://maps.google.com/?q=Tankpe+Abomey-Calavi',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'ENTREE_COUCHEE_ORDINAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    [
                        'url_fichier' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                        'est_panoramique_3d' => true,
                    ],
                    [
                        'url_fichier' => 'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80',
                        'est_panoramique_3d' => false,
                    ],
                    [
                        'url_fichier' => 'https://images.unsplash.com/photo-1460317442991-0ec209397118?auto=format&fit=crop&w=1200&q=80',
                        'est_panoramique_3d' => false,
                    ],
                ],
            ],
            [
                'user_id' => $demarcheurMarc->id,
                'titre' => 'Studio simple a Houeto avec cour securisee',
                'description' => 'Logement bien eclaire dans une maison neuve, douche carree, SONEB et boutique juste en face. Ideal pour une installation rapide.',
                'quartier' => 'Houeto',
                'loyer' => 26000,
                'caution' => 78000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Houeto+Abomey-Calavi',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'STUDIO_MEUBLE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1486304873000-235643847519?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudianteSika->id,
                'titre' => 'Place libre pour etudiante serieuse a Zogbadje',
                'description' => 'Je partage deja une chambre propre avec douche interne. Je cherche une colocataire calme, non-fumeuse et respectueuse des heures de repos.',
                'quartier' => 'Zogbadje',
                'loyer' => 14000,
                'caution' => 28000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Zogbadje+Abomey-Calavi',
                'type_annonce' => 'PLACE_LIBRE_HOTE',
                'type_chambre' => 'PLACE_COLOCATION',
                'nombre_places' => 1,
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudianteMarie->id,
                'titre' => 'Colocation feminine a Togoudo dans chambre de deux',
                'description' => 'Une place disponible immediatement, ambiance studieuse, cuisine commune propre, eau reguliere et acces rapide a la voie principale.',
                'quartier' => 'Godomey-Togoudo',
                'loyer' => 16500,
                'caution' => 33000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Togoudo+Abomey-Calavi',
                'type_annonce' => 'PLACE_LIBRE_HOTE',
                'type_chambre' => 'PLACE_COLOCATION',
                'nombre_places' => 1,
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudiantJean->id,
                'titre' => 'Place libre pour etudiant a Bidossessi dans chambre ventilee',
                'description' => 'Je cherche un colocataire propre pour partager les charges. La chambre est deja equipee de rideaux, ventilateur et rangement simple.',
                'quartier' => 'Bidossessi',
                'loyer' => 15000,
                'caution' => 30000,
                'source_eau' => 'Forage',
                'type_electricite' => 'SBEE_CONVENTIONNEL',
                'lien_google_maps' => 'https://maps.google.com/?q=Bidossessi+Abomey-Calavi',
                'type_annonce' => 'PLACE_LIBRE_HOTE',
                'type_chambre' => 'PLACE_COLOCATION',
                'nombre_places' => 2,
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudiantLuc->id,
                'titre' => 'Recherche binome pour prendre un deux chambres salon a Arconville',
                'description' => 'Je veux signer cette semaine pour un appartement propre et bien situe. Je cherche quelqu un de serieux pour partager loyer, caution et frais d agence.',
                'quartier' => 'Arconville',
                'loyer' => 45000,
                'caution' => 135000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Arconville+Abomey-Calavi',
                'type_annonce' => 'RECHERCHE_BINOME',
                'type_chambre' => 'DEUX_CHAMBRES_SALON',
                'statut_disponibilite' => 'pending',
                'medias' => [
                    'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudiantJean->id,
                'titre' => 'Cherche colocataire pour appartement au rez-de-chaussee a Ouedo',
                'description' => 'Appartement aere avec salon, compteur carte, eau de forage et petite cour. Je privilegie un etudiant calme et organise.',
                'quartier' => 'Ouedo Centre',
                'loyer' => 38000,
                'caution' => 114000,
                'source_eau' => 'Forage',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Ouedo+Abomey-Calavi',
                'type_annonce' => 'RECHERCHE_BINOME',
                'type_chambre' => 'CHAMBRE_SALON_SANITAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [],
            ],
            [
                'user_id' => $demarcheurMarc->id,
                'titre' => 'Deux chambres salon sanitaire neuves a Kpota',
                'description' => 'Logement neuf entierement cartele, acces direct voie goudronnee, garage 2 motos, forage privatif et compteur a carte personnel.',
                'quartier' => 'Djekpota',
                'loyer' => 70000,
                'caution' => 210000,
                'source_eau' => 'Forage',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Kpota+Abomey-Calavi',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'DEUX_CHAMBRES_SALON',
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudianteMarie->id,
                'titre' => 'Place libre pour etudiante a Tankpe dans grand 2 chambres salon',
                'description' => 'J occupe une chambre dans un logement bien securise a Tankpe. Je cherche une etudiante calme (IFRI, FSA, EPAC...) pour occuper la deuxieme chambre autonome.',
                'quartier' => 'Tankpe',
                'loyer' => 22000,
                'caution' => 44000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Tankpe+Abomey-Calavi',
                'type_annonce' => 'PLACE_LIBRE_HOTE',
                'type_chambre' => 'PLACE_COLOCATION',
                'nombre_places' => 1,
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudiantJean->id,
                'titre' => 'Etudiant IFRI cherche binome pour louer entree couchee a Zogbadje',
                'description' => 'Etudiant en 3eme annee genie logiciel, je cherche un camarade serieux et studieux pour louer une chambre sanitaire a 2 pas du campus UAC a la rentree.',
                'quartier' => 'Zogbadje',
                'loyer' => 15000,
                'caution' => 45000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Zogbadje+Abomey-Calavi',
                'type_annonce' => 'RECHERCHE_BINOME',
                'type_chambre' => 'ENTREE_COUCHEE_SANITAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [],
            ],
            [
                'user_id' => $etudianteSika->id,
                'titre' => 'Etudiante FSA cherche binome pour appartement a Arconville',
                'description' => 'Je cherche une etudiante non-fumeuse, respectueuse et ordonnee pour prospecter ensemble et signer un logement F2 ou F3 proche de l universite.',
                'quartier' => 'Arconville',
                'loyer' => 20000,
                'caution' => 60000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Arconville+Abomey-Calavi',
                'type_annonce' => 'RECHERCHE_BINOME',
                'type_chambre' => 'CHAMBRE_SALON_SANITAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [],
            ],
            [
                'user_id' => $demarcheurMarc->id,
                'titre' => 'Chambre salon ordinaire aérée à Tankpé proche pharmacie',
                'description' => 'Deux pièces distinctes propres avec grand salon, chambre à coucher séparée, sanitaires externes bien entretenus dans une cour fermée et sécurisée.',
                'quartier' => 'Tankpe',
                'loyer' => 35000,
                'caution' => 105000,
                'source_eau' => 'Forage',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Tankpe+Abomey-Calavi',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'CHAMBRE_SALON_ORDINAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1494526585095-c41746248156?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $demarcheurBenoit->id,
                'titre' => 'Chambre salon ordinaire carrelée à Zogbadje',
                'description' => 'Logement F2 économique à quelques minutes à pied du campus UAC, salon spacieux et chambre séparée, compteur personnel et eau régulière.',
                'quartier' => 'Zogbadje',
                'loyer' => 32000,
                'caution' => 96000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Zogbadje+Abomey-Calavi',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'CHAMBRE_SALON_ORDINAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $demarcheurMarc->id,
                'universite' => 'UNA',
                'titre' => 'Chambre carrelée à Oganla Nord proche de l UNA',
                'description' => 'Logement étudiant à quelques minutes du campus de l Université Nationale d Agriculture à Porto-Novo, cour pavée, compteur individuel et eau SONEB.',
                'quartier' => 'Oganla Nord',
                'loyer' => 25000,
                'caution' => 50000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Oganla+Nord+Porto-Novo',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'ENTREE_COUCHEE_SANITAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudianteRita->id,
                'universite' => 'UNA',
                'titre' => 'Place libre en colocation à Adjina Nord proche ISA',
                'description' => 'Chambre partagée dans une maison familiale calme, proche des résidences estudiantines de l UNA. Compteurs partagés, cuisine équipée.',
                'quartier' => 'Adjina Nord',
                'loyer' => 15000,
                'caution' => 15000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CONVENTIONNEL',
                'lien_google_maps' => 'https://maps.google.com/?q=Adjina+Nord+Porto-Novo',
                'type_annonce' => 'PLACE_LIBRE_HOTE',
                'type_chambre' => 'CHAMBRE_SALON_ORDINAIRE',
                'nombre_places' => 1,
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $demarcheurBenoit->id,
                'universite' => 'UNSTIM',
                'titre' => 'Chambre salon à Djègbè à deux pas de l UNSTIM',
                'description' => 'F2 lumineux dans le quartier de Djègbé à Abomey, proche des amphithéâtres de l UNSTIM. Cour fermée, compteur individuel, forage.',
                'quartier' => 'Djegbe',
                'loyer' => 22000,
                'caution' => 44000,
                'source_eau' => 'Forage',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Djegbe+Abomey',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'CHAMBRE_SALON_ORDINAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudiantKoffi->id,
                'universite' => 'UNSTIM',
                'titre' => 'Recherche binôme à Agblomè près du campus UNSTIM',
                'description' => 'Étudiant en comptabilité cherche colocataire pour une chambre à Agblomè. Loyer à partager équitablement, proche des amphis.',
                'quartier' => 'Agblome',
                'loyer' => 12000,
                'caution' => 12000,
                'source_eau' => 'Forage',
                'type_electricite' => 'SBEE_CONVENTIONNEL',
                'lien_google_maps' => 'https://maps.google.com/?q=Agblome+Abomey',
                'type_annonce' => 'RECHERCHE_BINOME',
                'type_chambre' => 'ENTREE_COUCHEE_ORDINAIRE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $demarcheurMarc->id,
                'universite' => 'UP',
                'titre' => 'Chambre meublée à Banikanni proche de l UP',
                'description' => 'Chambre meublée dans une résidence calme à Banikanni, à 10 minutes à moto du campus de l Université de Parakou. Eau et compteur inclus.',
                'quartier' => 'Banikanni',
                'loyer' => 20000,
                'caution' => 40000,
                'source_eau' => 'SONEB',
                'type_electricite' => 'SBEE_CARTE',
                'lien_google_maps' => 'https://maps.google.com/?q=Banikanni+Parakou',
                'type_annonce' => 'CHAMBRE_VIDE',
                'type_chambre' => 'STUDIO_MEUBLE',
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'user_id' => $etudianteClarisse->id,
                'universite' => 'UP',
                'titre' => 'Place libre en colocation à Albarika (Parakou)',
                'description' => 'Colocation étudiante au cœur de Parakou, quartier Albarika. Chambre spacieuse, cuisine commune, proche des boutiques et du marché.',
                'quartier' => 'Albarika',
                'loyer' => 18000,
                'caution' => 18000,
                'source_eau' => 'Forage',
                'type_electricite' => 'SBEE_CONVENTIONNEL',
                'lien_google_maps' => 'https://maps.google.com/?q=Albarika+Parakou',
                'type_annonce' => 'PLACE_LIBRE_HOTE',
                'type_chambre' => 'CHAMBRE_SALON_ORDINAIRE',
                'nombre_places' => 2,
                'statut_disponibilite' => 'available',
                'medias' => [
                    'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
        ];

        foreach ($annonces as $annonceData) {
            $medias = $annonceData['medias'];
            unset($annonceData['medias']);

            $codeUniversite = $annonceData['universite'] ?? 'UAC';
            unset($annonceData['universite']);
            $annonceData['universite_id'] = $universiteIds[$codeUniversite] ?? ($universiteIds['UAC'] ?? null);

            // Donnees de demonstration : annonces publiees directement
            $annonceData['statut_publication'] = 'active';

            $annonce = Annonce::create($annonceData);

            foreach ($medias as $url) {
                $annonce->medias()->create([
                    'url_fichier' => $url,
                    'est_panoramique_3d' => false,
                ]);
            }
        }
    }
}
