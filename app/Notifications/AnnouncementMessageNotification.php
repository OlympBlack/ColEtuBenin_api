<?php

namespace App\Notifications;

use App\Models\Annonce;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Support\ApiData;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AnnouncementMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Annonce $annonce,
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
            'title' => $senderName . ' sur ColCampus',
            'body' => $isAudio
                ? '🎤 Nouveau message vocal'
                : ($this->conversationMessage->content ?: 'Vous a envoyé un message'),
            'icon' => ApiData::fileUrl($this->sender->photo_profil),
            'tag' => 'colcampus-msg-' . $this->conversation->id,
            'conversationId' => $this->conversation->id,
            'url' => '/#/?conversation=' . $this->conversation->id,
            'type' => 'annonce_message',
        ];
    }

    public function toBroadcast(object $notifiable): \Illuminate\Notifications\Messages\BroadcastMessage
    {
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'notification' => $this->toArray($notifiable)
        ]);
    }

    public function toArray(object $notifiable): array
    {
        $senderName = trim(($this->sender->prenoms ?? '') . ' ' . ($this->sender->name ?? '')) ?: 'Un utilisateur';

        return [
            'type' => 'annonce_message',
            'title' => 'Nouveau message sur votre annonce',
            'body' => $senderName . ' vous a ecrit a propos de "' . $this->annonce->titre . '".',
            'action_url' => '#/annonces/' . $this->annonce->id,
            'action_label' => 'Voir l annonce',
            'meta' => [
                'annonce_id' => $this->annonce->id,
                'annonce_titre' => $this->annonce->titre,
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
