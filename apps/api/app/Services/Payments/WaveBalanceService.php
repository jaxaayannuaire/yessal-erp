<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WaveBalanceService
{
    private string $baseUrl;
	private ?string $apiKey;
    private ?string $signingSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.wave.api_url', 'https://api.wave.com/v1'),
            '/'
        );

        $this->apiKey = config('services.wave_balance.api_key');
        $this->signingSecret = config(
            'services.wave_balance.signing_secret'
        );
    }

    public function getBalance(bool $includeSubaccounts = false): array
    {
        $query = [];

        if ($includeSubaccounts) {
            $query['include_subaccounts'] = 'true';
        }

        return $this->get('/balance', $query);
    }

    public function getTransactions(
        ?string $date = null,
        ?string $after = null,
        bool $includeSubaccounts = false
    ): array {
        $query = [];

        if ($date !== null) {
            $query['date'] = $date;
        }

        if ($after !== null) {
            $query['after'] = $after;
        }

        if ($includeSubaccounts) {
            $query['include_subaccounts'] = 'true';
        }

        return $this->get('/transactions', $query);
    }

    private function get(string $path, array $query = []): array
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

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get($this->baseUrl . $path, $query);

            if ($response->failed()) {
                Log::error('Wave Balance API error', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                throw new RuntimeException(
                    'Impossible d’exécuter la requête Wave Balance.'
                );
            }

            return $response->json();
        } catch (\Throwable $exception) {
            Log::error('Wave Balance API exception', [
                'path' => $path,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}