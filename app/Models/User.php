<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'prenoms',
        'email',
        'password',
        'telephone',
        'role',
        'photo_profil',
        'is_verified',
        'is_valide',
        'statut_verification',
        'commentaire_admin',
        'profile_completed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_valide' => 'boolean',
            'profile_completed' => 'boolean',
        ];
    }

    public function documentsIdentite()
    {
        return $this->hasMany(DocumentIdentite::class);
    }

    public function profilEtudiant()
    {
        return $this->hasOne(ProfilEtudiant::class);
    }

    public function profilDemarcheur()
    {
        return $this->hasOne(ProfilDemarcheur::class);
    }

    public function profilBoutique()
    {
        return $this->hasOne(ProfilBoutique::class);
    }

    public function annonces()
    {
        return $this->hasMany(Annonce::class);
    }

    public function avis()
    {
        return $this->hasMany(Avis::class);
    }

    public function favoris()
    {
        return $this->hasMany(Favori::class);
    }

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }

    public function publicitesBoutiques()
    {
        return $this->hasMany(PubliciteBoutique::class);
    }

    public function portefeuillePublication()
    {
        return $this->hasOne(PortefeuillePublication::class);
    }

    public function transactionsCredits()
    {
        return $this->hasMany(TransactionCredit::class);
    }

    public function achatsForfaits()
    {
        return $this->hasMany(AchatForfait::class, 'user_id');
    }

    public function conversationsAsSender()
    {
        return $this->hasMany(Conversation::class, 'sender_id');
    }

    public function conversationsAsReceiver()
    {
        return $this->hasMany(Conversation::class, 'receiver_id');
    }

    public function sentConversationMessages()
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function activeAbonnement()
    {
        return $this->abonnements()
            ->with('plan')
            ->where('statut_paiement', 'paid')
            ->where('date_fin', '>=', now())
            ->latest('date_fin');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasValidatedIdentityDocument(): bool
    {
        if ($this->relationLoaded('documentsIdentite')) {
            return $this->documentsIdentite->contains(fn($document) => $document->statut_verification === 'valide');
        }

        return $this->documentsIdentite()->where('statut_verification', 'valide')->exists();
    }

    public function isKycValidated(): bool
    {
        return $this->role === 'admin' || $this->statut_verification === 'valide' || $this->hasValidatedIdentityDocument();
    }

    /**
     * The channels the user receives notification broadcasts on.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.' . $this->id;
    }
}
