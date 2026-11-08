<?php

namespace App\Notifications;

use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CreditsAllouesNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $nombre,
        private readonly int $nouveauTotal,
        private readonly ?string $motif = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => '🎁 Crédits accordés !',
            'body' => "L'administrateur vous a attribué {$this->nombre} crédit(s) de publication. Nouveau solde : {$this->nouveauTotal} crédit(s).",
            'icon' => '/logo.png',
            'tag' => 'colcampus-credits-' . time(),
            'url' => '/#/forfaits-publications',
            'type' => 'credits_alloues',
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification' => $this->toArray($notifiable),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        $motifText = $this->motif ? " Motif : {$this->motif}." : '';

        return [
            'type' => 'credits_alloues',
            'title' => '🎁 Crédits accordés par l\'administrateur',
            'body' => "L'administrateur vous a attribué {$this->nombre} crédit(s) de publication.{$motifText} Vous disposez désormais de {$this->nouveauTotal} crédit(s) pour publier vos annonces.",
            'action_url' => '#/forfaits-publications',
            'action_label' => 'Voir mon portefeuille',
            'meta' => [
                'credits_ajoutes' => $this->nombre,
                'nouveau_total' => $this->nouveauTotal,
                'motif' => $this->motif,
            ],
        ];
    }
}

