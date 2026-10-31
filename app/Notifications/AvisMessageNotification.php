<?php

namespace App\Notifications;

use App\Models\Avis;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Support\ApiData;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AvisMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Avis $avis,
        private readonly User $sender,
        private readonly Conversation $conversation,
        private readonly ConversationMessage $conversationMessage,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    public function toWebPush(object $notifiable): array
    {
        $senderName = trim(($this->sender->prenoms ?? '') . ' ' . ($this->sender->name ?? '')) ?: 'Nouveau message';
        $isAudio = $this->conversationMessage->type === 'audio' || (bool) $this->conversationMessage->media_url;

        return [
            'title' => $senderName . ' sur ColEtuBenin',
            'body' => $isAudio
                ? '🎤 Nouveau message vocal'
                : ($this->conversationMessage->content ?: 'Vous a envoyé un message'),
            'icon' => ApiData::fileUrl($this->sender->photo_profil),
            'tag' => 'colcampus-msg-' . $this->conversation->id,
            'conversationId' => $this->conversation->id,
            'url' => '/#/?conversation=' . $this->conversation->id,
            'type' => 'avis_message',
        ];
    }

    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'notification' => $this->toArray($notifiable),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        $senderName = trim(($this->sender->prenoms ?? '') . ' ' . ($this->sender->name ?? '')) ?: 'Un demarcheur';

        return [
            'type' => 'avis_message',
            'title' => 'Nouveau message sur votre avis',
            'body' => $senderName . ' vous a ecrit au sujet de votre recherche de chambre a "' . $this->avis->quartier . '".',
            'action_url' => '#/avis',
            'action_label' => 'Voir mon avis',
            'meta' => [
                'avis_id' => $this->avis->id,
                'avis_quartier' => $this->avis->quartier,
                'conversation_id' => $this->conversation->id,
                'message_id' => $this->conversationMessage->id,
                'sender' => [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'prenoms' => $this->sender->prenoms,
                    'email' => $this->sender->email,
                    'telephone' => $this->sender->telephone,
                    'photo_profil' => $this->sender->photo_profil,
                ],
                'message' => $this->conversationMessage->content,
            ],
        ];
    }
}
