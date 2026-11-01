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

    public function verifyTransaction(string $transactionId): ?array
    {
        $url = "{$this->baseUrl}/api/v1/transactions/status";

        try {
            $response = Http::withHeaders([
                'X-API-KEY'     => $this->publicKey,
                'X-PRIVATE-KEY' => $this->privateKey,
                'X-SECRET-KEY'  => $this->secretKey,
                'Accept'        => 'application/json',
            ])->post($url, [
                'transactionId' => $transactionId,
            ]);

            Log::info('Kkiapay verify response', [
                'transactionId' => $transactionId,
                'url'           => $url,
                'sandbox'       => $this->sandbox,
                'status'        => $response->status(),
                'body'          => $response->json(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'SUCCESS') {
                    return $data;
                }
            }

            Log::error('Kkiapay verification failed', [
                'transactionId' => $transactionId,
                'httpStatus'    => $response->status(),
                'response'      => $response->json(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Kkiapay exception: ' . $e->getMessage());
            return null;
        }
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