<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PubliciteBoutique extends Model
{
    protected $table = 'publicites_boutiques';

    protected $fillable = [
        'user_id',
        'titre_promo',
        'prix',
        'description',
        'categorie',
        'url_image_banniere',
        'lien_action',
        'quartier_cible',
        'vues_count',
        'clics_count',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'integer',
            'vues_count' => 'integer',
            'clics_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
