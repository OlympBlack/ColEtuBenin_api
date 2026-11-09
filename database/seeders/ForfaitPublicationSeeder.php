<?php

namespace Database\Seeders;

use App\Models\ForfaitPublication;
use Illuminate\Database\Seeder;

class ForfaitPublicationSeeder extends Seeder
{
    public function run(): void
    {
        ForfaitPublication::updateOrCreate(
            ['titre' => 'Pack Standard'],
            [
                'description' => 'Idéal pour débuter votre activité.',
                'prix' => 5000,
                'nombre_publications' => 5,
                'est_actif' => true,
                'est_populaire' => false,
                'ordre_affichage' => 1,
            ]
        );

        ForfaitPublication::updateOrCreate(
            ['titre' => 'Pack Pro'],
            [
                'description' => 'Notre pack le plus populaire pour les agents actifs.',
                'prix' => 12000,
                'nombre_publications' => 15,
                'est_actif' => true,
                'est_populaire' => true,
                'ordre_affichage' => 2,
            ]
        );

        ForfaitPublication::updateOrCreate(
            ['titre' => 'Pack Premium'],
            [
                'description' => 'Pour une visibilité maximale et un gros volume.',
                'prix' => 25000,
                'nombre_publications' => 40,
                'est_actif' => true,
                'est_populaire' => false,
                'ordre_affichage' => 3,
            ]
        );
    }
}
