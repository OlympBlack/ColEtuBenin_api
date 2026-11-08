<?php

namespace Database\Seeders;

use App\Models\PubliciteBoutique;
use App\Models\User;
use Illuminate\Database\Seeder;

class PubliciteBoutiqueSeeder extends Seeder
{
    public function run(): void
    {
        PubliciteBoutique::query()->delete();

        $alice = User::where('email', 'alice.meubles@shop.bj')->firstOrFail();
        $saliou = User::where('email', 'saliou.info@shop.bj')->firstOrFail();

        $publicites = [
            [
                'user_id' => $saliou->id,
                'titre_promo' => 'MacBook Pro M3 Max 36GB - Configuration Pro Étudiant',
                'prix' => 1850000,
                'description' => 'Le tout nouveau MacBook Pro avec puce M3 Max. Jusqu\'à 22h d\'autonomie, écran Liquid Retina XDR 16", idéal pour les étudiants en informatique, architecture et graphisme.',
                'categorie' => 'Informatique',
                'url_image_banniere' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1200&q=80',
                'lien_action' => '#/boutiques',
                'quartier_cible' => 'Tankpe',
            ],
            [
                'user_id' => $alice->id,
                'titre_promo' => 'Pack Lit Mousse Haute Densité + Table de Travail Étudiant',
                'prix' => 65000,
                'description' => 'Pack complet pour emménagement rapide en chambre universitaire. Matelas 1.5 place mousse ferme, table d\'étude compacte en bois traité et rangements intégrés.',
                'categorie' => 'Meubles & Literie',
                'url_image_banniere' => 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                'lien_action' => '#/boutiques',
                'quartier_cible' => 'Zogbadje',
            ],
            [
                'user_id' => $saliou->id,
                'titre_promo' => 'Routeur Haute Vitesse WiFi 6 Mesh TP-Link Gigabit',
                'prix' => 45000,
                'description' => 'Idéal pour colocation étudiante. Couverture jusqu\'à 200m², gestion multi-appareils sans ralentissement, double bande 2.4/5GHz avec contrôle parental et invité.',
                'categorie' => 'Électronique & Réseau',
                'url_image_banniere' => 'https://images.unsplash.com/photo-1544158141-866ab8eafeeb?auto=format&fit=crop&w=1200&q=80',
                'lien_action' => '#/boutiques',
                'quartier_cible' => 'Bidossessi',
            ],
            [
                'user_id' => $alice->id,
                'titre_promo' => 'Bureau Compact en Bois d\'Iroko & Chaise Ergonomique Aérée',
                'prix' => 85000,
                'description' => 'Conçu pour les longues sessions de révision. Plateau anti-rayures, pieds métalliques renforcés et dossier lombaire respirant pour préserver votre posture.',
                'categorie' => 'Meubles & Literie',
                'url_image_banniere' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1200&q=80',
                'lien_action' => '#/boutiques',
                'quartier_cible' => 'Houeto',
            ],
            [
                'user_id' => $saliou->id,
                'titre_promo' => 'Imprimante Laser Multifonction HP LaserJet Wifi & Scanner',
                'prix' => 120000,
                'description' => 'Impression rapide noir et blanc, coût par page ultra-économique pour mémoires et polycopies de cours. Connexion Wifi directe depuis smartphone et PC portable.',
                'categorie' => 'Bureautique',
                'url_image_banniere' => 'https://images.unsplash.com/photo-1612815154858-60aa4c59eaa6?auto=format&fit=crop&w=1200&q=80',
                'lien_action' => '#/boutiques',
                'quartier_cible' => null,
            ],
            [
                'user_id' => $saliou->id,
                'titre_promo' => 'Setup Clavier Ergonomique RGB Custom & Souris Sans Fil',
                'prix' => 35000,
                'description' => 'Ensemble clavier mécanique silencieux et souris optique ergonomique. Connexion Bluetooth multipoints, batterie longue durée rechargeable USB-C.',
                'categorie' => 'Informatique',
                'url_image_banniere' => 'https://images.unsplash.com/photo-1595225476474-87563907a212?auto=format&fit=crop&w=1200&q=80',
                'lien_action' => '#/boutiques',
                'quartier_cible' => 'Banikanni',
            ],
            [
                'user_id' => $alice->id,
                'titre_promo' => 'Armoire Penderie 2 Battants avec Miroir et Tiroirs Sécurisés',
                'prix' => 95000,
                'description' => 'Penderie en bois mélaminé haute résistance, idéale pour studio ou chambre en colocation. Espace étagères modulables et serrure à clé.',
                'categorie' => 'Meubles & Literie',
                'url_image_banniere' => 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=1200&q=80',
                'lien_action' => '#/boutiques',
                'quartier_cible' => 'Oganla Nord',
            ],
            [
                'user_id' => $saliou->id,
                'titre_promo' => 'Hub USB-C 7-en-1 Anker HDMI 4K + Charge Rapide 100W',
                'prix' => 25000,
                'description' => 'Connectez facilement votre ordinateur portable à un écran externe, clés USB, carte SD et réseau filaire ethernet RJ45.',
                'categorie' => 'Informatique',
                'url_image_banniere' => 'https://images.unsplash.com/photo-1588694073351-419b7ce96313?auto=format&fit=crop&w=1200&q=80',
                'lien_action' => '#/boutiques',
                'quartier_cible' => null,
            ],
        ];

        foreach ($publicites as $publicite) {
            PubliciteBoutique::create($publicite);
        }
    }
}