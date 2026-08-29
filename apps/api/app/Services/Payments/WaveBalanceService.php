<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WaveBalanceService
{
    private string $baseUrl;
    private string $apiKey;
    private ?string $signingSecret;

    public function __construct()
    {
        $this->baseUrl = 'https://api.wave.com';
        $this->apiKey = config('services.wave_balance.api_key');
        $this->signingSecret = config(
            'services.wave_balance.signing_secret'
        );
    }

    public function getBalance(bool $includeSubaccounts = false): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException(
                'WAVE_BALANCE_API_KEY non configurée.'
            );
        }

        $timestamp = time();

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ];

        if (!empty($this->signingSecret)) {
            $signature = hash_hmac(
                'sha256',
                (string) $timestamp,
                $this->signingSecret
            );

            $headers['Wave-Signature'] =
                "t={$timestamp},v1={$signature}";
        }

        $query = [];

        if ($includeSubaccounts) {
            $query['include_subaccounts'] = 'true';
        }

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->get(
                $this->baseUrl . '/v1/balance',
                $query
            );

        if ($response->failed()) {
            Log::error('Wave Balance API error', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException(
                'Impossible de récupérer le solde Wave.'
            );
        }

        Log::info('Wave balance retrieved', [
            'balance' => $response->json(),
        ]);

        return $response->json();
    }
}