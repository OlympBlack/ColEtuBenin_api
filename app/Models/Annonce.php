<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Annonce extends Model
{
    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'quartier',
        'universite_id',
        'loyer',
        'caution',
        'source_eau',
        'type_electricite',
        'lien_google_maps',
        'type_annonce',
        'type_chambre',
        'nombre_places',
        'statut_disponibilite',
        'statut_publication',
        'vues_count',
    ];

    protected function casts(): array
    {
        return [
            'loyer' => 'integer',
            'caution' => 'decimal:2',
            'nombre_places' => 'integer',
            'vues_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function universite()
    {
        return $this->belongsTo(Universite::class);
    }

    public function medias()
    {
        return $this->hasMany(MediaAnnonce::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
