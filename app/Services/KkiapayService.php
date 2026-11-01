<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KkiapayService
{
    protected string $publicKey;
    protected string $secretKey;
    protected string $privateKey;
    protected bool $sandbox;
    protected string $baseUrl;

    public function __construct()
    {
        // Lecture directe depuis .env via config/services.php
        $this->publicKey  = (string) config('services.kkiapay.public_key', '');
        $this->secretKey  = (string) config('services.kkiapay.secret_key', '');
        $this->privateKey = (string) config('services.kkiapay.private_key', '');
        $this->sandbox    = filter_var(config('services.kkiapay.sandbox', true), FILTER_VALIDATE_BOOLEAN);

        $this->baseUrl = $this->sandbox
            ? 'https://api-sandbox.kkiapay.me'
            : 'https://api.kkiapay.me';

        Log::info('KkiapayService initialisé', [
            'sandbox'        => $this->sandbox,
            'baseUrl'        => $this->baseUrl,
            'public_key_set' => !empty($this->publicKey),
            'private_key_set'=> !empty($this->privateKey),
            'secret_key_set' => !empty($this->secretKey),
        ]);
    }

    public function verifyTransaction(string $transactionId, int $maxAttempts = 4): ?array
    {
        $url = "{$this->baseUrl}/api/v1/transactions/status";

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'X-API-KEY'     => $this->publicKey,
                    'X-PRIVATE-KEY' => $this->privateKey,
                    'X-SECRET-KEY'  => $this->secretKey,
                    'Accept'        => 'application/json',
                ])->post($url, [
                    'transactionId' => $transactionId,
                ]);

                Log::info("Kkiapay verify response (tentative {$attempt}/{$maxAttempts})", [
                    'transactionId' => $transactionId,
                    'url'           => $url,
                    'sandbox'       => $this->sandbox,
                    'status'        => $response->status(),
                    'body'          => $response->json(),
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $status = $data['status'] ?? null;

                    if ($status === 'SUCCESS') {
                        return $data;
                    }

                    // Si la transaction Mobile Money est encore en cours de confirmation, on réessaie
                    if ($status === 'PENDING' && $attempt < $maxAttempts) {
                        Log::info("Kkiapay transaction {$transactionId} encore PENDING, nouvelle vérification dans 2s...");
                        sleep(2);
                        continue;
                    }
                }

                if ($attempt === $maxAttempts) {
                    Log::error('Kkiapay verification failed after max attempts', [
                        'transactionId' => $transactionId,
                        'httpStatus'    => $response->status(),
                        'response'      => $response->json(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Kkiapay exception tentative {$attempt}: " . $e->getMessage());
                if ($attempt < $maxAttempts) {
                    sleep(1);
                    continue;
                }
            }
        }

        return null;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }
}