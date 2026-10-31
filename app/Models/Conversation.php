<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'annonce_id',
        'avis_id',
        'sender_id',
        'receiver_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function annonce()
    {
        return $this->belongsTo(Annonce::class);
    }

    public function avis()
    {
        return $this->belongsTo(Avis::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function messages()
    {
        return $this->hasMany(ConversationMessage::class)->oldest();
    }

    /**
     * Recherche la conversation unique entre deux utilisateurs, quel que soit l'ordre (expediteur / destinataire).
     */
    public static function findBetween(int $userId1, int $userId2): ?self
    {
        return static::query()
            ->where(function ($query) use ($userId1, $userId2) {
                $query->where(function ($q) use ($userId1, $userId2) {
                    $q->where('sender_id', $userId1)->where('receiver_id', $userId2);
                })->orWhere(function ($q) use ($userId1, $userId2) {
                    $q->where('sender_id', $userId2)->where('receiver_id', $userId1);
                });
            })
            ->orderByDesc('last_message_at')
            ->first();
    }

    /**
     * Renvoie le modèle User correspondant a l'interlocuteur de l'utilisateur donne.
     */
    public function getPartner(int $userId): ?User
    {
        return (int) $this->sender_id === (int) $userId ? $this->receiver : $this->sender;
    }
}
