<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function sendToUser(User $user, array $payload): void
    {
        $publicKey = (string) config('webpush.public_key');
        $privateKey = (string) config('webpush.private_key');

        if ($publicKey === '' || $privateKey === '') {
            Log::warning('WebPushService: VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY are not configured.');

            return;
        }

        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush(
            [
                'VAPID' => [
                    'subject' => (string) config('webpush.subject'),
                    'publicKey' => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ],
            [
                'TTL' => (int) config('webpush.ttl'),
                'urgency' => (string) config('webpush.urgency'),
            ]
        );

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encodedPayload === false) {
            return;
        }

        foreach ($subscriptions as $subscription) {
            if (!$subscription->public_key || !$subscription->auth_token) {
                continue;
            }

            try {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                    ]),
                    $encodedPayload
                );
            } catch (\Throwable $e) {
                Log::warning('WebPushService: unable to queue notification: ' . $e->getMessage());
            }
        }

        try {
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    continue;
                }

                if ($report->isSubscriptionExpired()) {
                    PushSubscription::query()->where('endpoint', $report->getEndpoint())->delete();
                    continue;
                }

                Log::warning('WebPushService: delivery failed: ' . $report->getReason());
            }
        } catch (\Throwable $e) {
            Log::warning('WebPushService: flush failed: ' . $e->getMessage());
        }
    }
}
