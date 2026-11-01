<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KkiapayService
{
    protected $publicKey;
    protected $secretKey;
    protected $privateKey;
    protected $sandbox;
    protected $baseUrl;

    public function __construct()
    {
        try {
            $settings = \App\Models\ParametrePlateforme::getSettings();
            $this->publicKey  = $settings->getEffectiveKkiapayPublicKey();
            $this->secretKey  = $settings->getEffectiveKkiapaySecretKey();
            $this->privateKey = $settings->getEffectiveKkiapayPrivateKey();
            $this->sandbox    = $settings->isEffectiveKkiapaySandbox();
        } catch (\Throwable $e) {
            $this->publicKey  = (string) config('services.kkiapay.public_key', 'aa4b4f9052d511f18d492b2bdfbe5c62');
            $this->secretKey  = (string) config('services.kkiapay.secret_key', '');
            $this->privateKey = (string) config('services.kkiapay.private_key', '');
            $this->sandbox    = (bool) config('services.kkiapay.sandbox', true);
        }

        // URL définie dynamiquement selon le mode configuré par l'administrateur
        $this->baseUrl = $this->sandbox
            ? 'https://api-sandbox.kkiapay.me'
            : 'https://api.kkiapay.me';
    }

    public function verifyTransaction(string $transactionId)
    {
        $url = "{$this->baseUrl}/api/v1/transactions/{$transactionId}/status";

        try {
            $response = Http::withHeaders([
                'x-private-key' => $this->privateKey,
                'Accept'        => 'application/json',
            ])->get($url);

            Log::info('Kkiapay verify response', [
                'transactionId' => $transactionId,
                'url'           => $url,           // ← pour confirmer l'URL utilisée
                'sandbox'       => $this->sandbox, // ← pour confirmer le mode
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
                'response'      => $response->json(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Kkiapay exception: ' . $e->getMessage());
            return null;
        }
    }
}