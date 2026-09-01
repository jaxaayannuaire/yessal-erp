<?php

namespace Tests\Feature\Subscription;

use App\Http\Middleware\EnsureSubscriptionActive;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnsureSubscriptionActiveTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizationWithUser(
        string $status = 'active',
        ?\DateTimeInterface $gracePeriodEndsAt = null
    ): array {
        $organization = Organization::factory()->create();

        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
            'grace_period_ends_at' => $gracePeriodEndsAt,
        ]);

        return [$organization, $user, $subscription];
    }

    private function requestFor(
        User $user,
        Organization $organization
    ): Request {
        Sanctum::actingAs($user);

        $request = Request::create(
            '/api/v1/test-subscription',
            'GET'
        );

        $request->setUserResolver(
            fn () => $user
        );

        $request->headers->set(
            'X-Organization-Id',
            (string) $organization->id
        );

        return $request;
    }

    private function executeMiddleware(Request $request)
    {
        return app(EnsureSubscriptionActive::class)->handle(
            $request,
            function (Request $request) {
                return response()->json([
                    'success' => true,
                ]);
            }
        );
    }

    public function test_un_abonnement_actif_autorise_l_acces(): void
    {
        [$organization, $user] =
            $this->createOrganizationWithUser('active');

        $request = $this->requestFor(
            $user,
            $organization
        );

        $response = $this->executeMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(
            $organization->id,
            $request->attributes
                ->get('currentOrganization')
                ->id
        );

        $this->assertNotNull(
            $request->attributes->get('currentSubscription')
        );
    }

    public function test_un_abonnement_past_due_pendant_la_grace_autorise_l_acces(): void
    {
        [$organization, $user] =
            $this->createOrganizationWithUser(
                'past_due',
                now()->addDays(2)
            );

        $request = $this->requestFor(
            $user,
            $organization
        );

        $response = $this->executeMiddleware($request);

        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(
            'past_due',
            $request->attributes
                ->get('currentSubscription')
                ->status
        );
    }

    public function test_un_abonnement_past_due_apres_la_grace_refuse_l_acces(): void
    {
        [$organization, $user] =
            $this->createOrganizationWithUser(
                'past_due',
                now()->subMinute()
            );

        $request = $this->requestFor(
            $user,
            $organization
        );

        $response = $this->executeMiddleware($request);
        $data = $response->getData(true);

        $this->assertSame(403, $response->getStatusCode());

        $this->assertSame(false, $data['success']);
        $this->assertSame(
            'subscription_inactive',
            $data['code']
        );
        $this->assertSame(
            'past_due',
            $data['subscription_status']
        );
    }

    public function test_un_abonnement_expire_refuse_l_acces(): void
    {
        [$organization, $user] =
            $this->createOrganizationWithUser('expired');

        $request = $this->requestFor(
            $user,
            $organization
        );

        $response = $this->executeMiddleware($request);
        $data = $response->getData(true);

        $this->assertSame(403, $response->getStatusCode());

        $this->assertSame(false, $data['success']);
        $this->assertSame(
            'subscription_inactive',
            $data['code']
        );
        $this->assertSame(
            'expired',
            $data['subscription_status']
        );
    }

    public function test_un_abonnement_pending_refuse_l_acces(): void
    {
        [$organization, $user] =
            $this->createOrganizationWithUser('pending');

        $request = $this->requestFor(
            $user,
            $organization
        );

        $response = $this->executeMiddleware($request);
        $data = $response->getData(true);

        $this->assertSame(403, $response->getStatusCode());

        $this->assertSame(false, $data['success']);
        $this->assertSame(
            'subscription_inactive',
            $data['code']
        );
        $this->assertSame(
            'pending',
            $data['subscription_status']
        );
    }

    public function test_une_organisation_sans_abonnement_refuse_l_acces(): void
    {
        $organization = Organization::factory()->create();

        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $request = $this->requestFor(
            $user,
            $organization
        );

        $response = $this->executeMiddleware($request);
        $data = $response->getData(true);

        $this->assertSame(403, $response->getStatusCode());

        $this->assertSame(false, $data['success']);
        $this->assertSame(
            'subscription_required',
            $data['code']
        );
    }

    public function test_un_utilisateur_non_authentifie_recoit_401(): void
    {
        $request = Request::create(
            '/api/v1/test-subscription',
            'GET'
        );

        $request->setUserResolver(
            fn () => null
        );

        $response = $this->executeMiddleware($request);
        $data = $response->getData(true);

        $this->assertSame(401, $response->getStatusCode());

        $this->assertSame(false, $data['success']);
        $this->assertSame(
            'authentication_failed',
            $data['code']
        );
    }
}