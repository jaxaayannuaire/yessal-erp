<?php

namespace Tests\Feature\Caisse;

use App\Services\Payments\WaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WaveBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private function queryParameters($request): array
    {
        $query = parse_url($request->url(), PHP_URL_QUERY);

        if (!$query) {
            return [];
        }

        parse_str($query, $parameters);

        return $parameters;
    }

    public function test_le_solde_wave_peut_etre_recupere(): void
    {
        Http::fake([
            'https://api.wave.com/v1/balance' => Http::response([
                'amount' => '150000.00',
                'currency' => 'XOF',
            ], 200),
        ]);

        $result = app(WaveBalanceService::class)->getBalance();

        $this->assertSame('150000.00', $result['amount']);
        $this->assertSame('XOF', $result['currency']);

    }

    public function test_le_solde_wave_peut_inclure_les_sous_comptes(): void
    {
        Http::fake([
            'https://api.wave.com/v1/balance*' => Http::response([
                'amount' => '250000.00',
                'currency' => 'XOF',
            ], 200),
        ]);

        app(WaveBalanceService::class)->getBalance(true);

        Http::assertSent(function ($request) {
            $parameters = $this->queryParameters($request);

            return $parameters['include_subaccounts'] ?? null === 'true';
        });
    }

    public function test_les_transactions_wave_peuvent_etre_recuperees(): void
    {
        Http::fake([
            'https://api.wave.com/v1/transactions*' => Http::response([
                'items' => [
                    [
                        'id' => 'txn-test-001',
                        'amount' => '5000.00',
                        'currency' => 'XOF',
                    ],
                ],
                'page_info' => [
                    'has_next_page' => false,
                    'end_cursor' => null,
                ],
            ], 200),
        ]);

        $result = app(WaveBalanceService::class)->getTransactions();

        $this->assertCount(1, $result['items']);
        $this->assertSame(
            'txn-test-001',
            $result['items'][0]['id']
        );
        $this->assertSame(
            '5000.00',
            $result['items'][0]['amount']
        );
    }

    public function test_les_parametres_des_transactions_wave_sont_transmis(): void
    {
        Http::fake([
            'https://api.wave.com/v1/transactions*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        app(WaveBalanceService::class)->getTransactions(
            '2026-08-31',
            'cursor-test-001',
            true
        );

        Http::assertSent(function ($request) {
            $parameters = $this->queryParameters($request);

            return ($parameters['date'] ?? null) === '2026-08-31'
                && ($parameters['after'] ?? null) === 'cursor-test-001'
                && ($parameters['include_subaccounts'] ?? null) === 'true';
        });
    }

    public function test_la_signature_wave_balance_est_generee_correctement(): void
    {
        config([
            'services.wave_balance.api_key' => 'test-api-key',
            'services.wave_balance.signing_secret' => 'test-signing-secret',
        ]);

        Http::fake([
            'https://api.wave.com/v1/balance' => Http::response([
                'amount' => '10000.00',
                'currency' => 'XOF',
            ], 200),
        ]);

        app(WaveBalanceService::class)->getBalance();

        Http::assertSent(function ($request) {
            $signatureHeader = $request->header('Wave-Signature')[0] ?? '';

            if (!preg_match(
                '/^t=(\d+),v1=([a-f0-9]{64})$/',
                $signatureHeader,
                $matches
            )) {
                return false;
            }

            $expectedSignature = hash_hmac(
                'sha256',
                $matches[1],
                'test-signing-secret'
            );

            return hash_equals(
                $expectedSignature,
                $matches[2]
            );
        });
    }

    public function test_la_requete_wave_balance_est_authentifiee_avec_la_cle_api(): void
    {
        config([
            'services.wave_balance.api_key' => 'test-api-key',
        ]);

        Http::fake([
            'https://api.wave.com/v1/balance' => Http::response([
                'amount' => '10000.00',
                'currency' => 'XOF',
            ], 200),
        ]);

        app(WaveBalanceService::class)->getBalance();

        Http::assertSent(function ($request) {
            return $request->header('Authorization') === [
                'Bearer test-api-key',
            ];
        });
    }

    public function test_l_absence_de_cle_api_wave_balance_declenche_une_erreur(): void
    {
        config([
            'services.wave_balance.api_key' => null,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'WAVE_BALANCE_API_KEY non configurée.'
        );

        app(WaveBalanceService::class)->getBalance();
    }

    public function test_une_erreur_http_wave_balance_declenche_une_erreur(): void
    {
        Http::fake([
            'https://api.wave.com/v1/balance' => Http::response([
                'code' => 'invalid-api-key',
                'message' => 'Invalid API key',
            ], 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Impossible d’exécuter la requête Wave Balance.'
        );

        app(WaveBalanceService::class)->getBalance();
    }

    public function test_une_erreur_http_des_transactions_wave_declenche_une_erreur(): void
    {
        Http::fake([
            'https://api.wave.com/v1/transactions*' => Http::response([
                'code' => 'provider-error',
            ], 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Impossible d’exécuter la requête Wave Balance.'
        );

        app(WaveBalanceService::class)->getTransactions();
    }

    public function test_la_signature_n_est_pas_envoyee_sans_secret_de_signature(): void
    {
        config([
            'services.wave_balance.api_key' => 'test-api-key',
            'services.wave_balance.signing_secret' => null,
        ]);

        Http::fake([
            'https://api.wave.com/v1/balance' => Http::response([
                'amount' => '10000.00',
                'currency' => 'XOF',
            ], 200),
        ]);

        app(WaveBalanceService::class)->getBalance();

        Http::assertSent(function ($request) {
            return empty($request->header('Wave-Signature'));
        });
    }

    public function test_les_transactions_wave_peuvent_etre_filtrees_par_date(): void
    {
        Http::fake([
            'https://api.wave.com/v1/transactions*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        app(WaveBalanceService::class)->getTransactions(
            '2026-08-30'
        );

        Http::assertSent(function ($request) {
            $parameters = $this->queryParameters($request);

            return ($parameters['date'] ?? null) === '2026-08-30'
                && !isset($parameters['after'])
                && !isset($parameters['include_subaccounts']);
        });
    }

    public function test_les_transactions_wave_peuvent_etre_reprises_avec_un_cursor(): void
    {
        Http::fake([
            'https://api.wave.com/v1/transactions*' => Http::response([
                'items' => [],
                'page_info' => [
                    'has_next_page' => true,
                    'end_cursor' => 'cursor-test-002',
                ],
            ], 200),
        ]);

        $result = app(WaveBalanceService::class)->getTransactions(
            null,
            'cursor-test-001'
        );

        $this->assertTrue($result['page_info']['has_next_page']);
        $this->assertSame(
            'cursor-test-002',
            $result['page_info']['end_cursor']
        );

        Http::assertSent(function ($request) {
            $parameters = $this->queryParameters($request);

            return ($parameters['after'] ?? null) === 'cursor-test-001';
        });
    }
}