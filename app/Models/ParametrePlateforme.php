<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametrePlateforme extends Model
{
    protected $table = 'parametres_plateforme';

    protected $fillable = [
        'nom_plateforme',
        'slogan',
        'description',
        'logo_url',
        'campus_localisation',
        'telephone',
        'whatsapp',
        'email_contact',
        'email_support',
        'signature_auteur',
        'signature_url',
        'signature_texte',
        'copyright_texte',
    ];

    /**
     * Récupère la ligne unique de configuration ou l'initialise
     */
    public static function getSettings(): self
    {
        $settings = static::first();
        if (!$settings) {
            $settings = static::create([
                'nom_plateforme' => 'ColEtuBenin',
                'slogan' => 'La plateforme de logement et de colocation étudiante autour des campus du Bénin',
                'description' => 'ColEtuBenin met en relation étudiants, bailleurs et démarcheurs autour des quatre grandes universités publiques du Bénin : UAC, UNA, UNSTIM et UP. Trouvez ou proposez facilement une chambre ou une colocation près de votre campus.',
                'logo_url' => '/assets/images/logo_colcampus.png',
                'campus_localisation' => 'Campus UAC, UNA, UNSTIM & UP • Bénin',
                'telephone' => '+229 01 54 47 00 02',
                'whatsapp' => '+229 01 54 47 00 02',
                'email_contact' => 'contact@coletubenin.com',
                'email_support' => 'support@coletubenin.com',
                'signature_auteur' => 'GBASSI Jules-christ',
                'signature_url' => 'https://juleschrist-portfolio.vercel.app/',
                'signature_texte' => 'Conçu & développé par',
                'copyright_texte' => 'ColEtuBenin. Tous droits réservés.',
            ]);
        }
        return $settings;
    }

    /**
     * Formatage de l'URL du logo pour le frontend
     */
    public function getFormattedLogoUrlAttribute(): string
    {
        if (empty($this->logo_url)) {
            return '/assets/images/logo_colcampus.png';
        }

        if (str_starts_with($this->logo_url, 'http://') || str_starts_with($this->logo_url, 'https://') || str_starts_with($this->logo_url, '/')) {
            return $this->logo_url;
        }

        return asset('storage/' . $this->logo_url);
    }

    /**
     * Sérialisation publique propre
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'nom_plateforme' => $this->nom_plateforme,
            'slogan' => $this->slogan,
            'description' => $this->description,
            'logo_url' => $this->formatted_logo_url,
            'campus_localisation' => $this->campus_localisation,
            'telephone' => $this->telephone,
            'whatsapp' => $this->whatsapp,
            'email_contact' => $this->email_contact,
            'email_support' => $this->email_support,
            'signature_auteur' => $this->signature_auteur,
            'signature_url' => $this->signature_url,
            'signature_texte' => $this->signature_texte,
            'copyright_texte' => $this->copyright_texte,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
