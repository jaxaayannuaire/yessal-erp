<?php

namespace Tests\Feature\Subscription;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizationWithUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        return [$organization, $user, $plan, $subscription];
    }

    private function authenticate(
        User $user,
        Organization $organization
    ): void {
        Sanctum::actingAs($user);

        $this->withHeaders([
            'X-Organization-Id' => (string) $organization->id,
        ]);
    }

    public function test_index_retourne_uniquement_les_souscriptions_du_tenant(): void
    {
        [$organizationA, $userA, $plan] =
            $this->createOrganizationWithUser();

        [$organizationB, ,] =
            $this->createOrganizationWithUser();

        Subscription::factory()->create([
            'organization_id' => $organizationA->id,
            'plan_id' => $plan->id,
        ]);

        Subscription::factory()->create([
            'organization_id' => $organizationB->id,
            'plan_id' => $plan->id,
        ]);

        $this->authenticate($userA, $organizationA);

        $response = $this->getJson('/api/v1/subscriptions');

        $response->assertOk();

        $subscriptions = $response->json('subscriptions');

        $this->assertIsArray($subscriptions);

        foreach ($subscriptions as $subscription) {
            $this->assertSame(
                $organizationA->id,
                $subscription['organization_id']
            );
        }
    }

    public function test_show_refuse_une_souscription_d_un_autre_tenant(): void
    {
        [$organizationA, $userA] =
            $this->createOrganizationWithUser();

        [$organizationB, , , $subscriptionB] =
            $this->createOrganizationWithUser();

        $this->authenticate($userA, $organizationA);

        $response = $this->getJson(
            "/api/v1/subscriptions/{$subscriptionB->id}"
        );

        $response->assertStatus(403);
    }

    public function test_update_refuse_une_souscription_d_un_autre_tenant(): void
    {
        [$organizationA, $userA] =
            $this->createOrganizationWithUser();

        [, , , $subscriptionB] =
            $this->createOrganizationWithUser();

        $this->authenticate($userA, $organizationA);

        $response = $this->putJson(
            "/api/v1/subscriptions/{$subscriptionB->id}",
            [
                'status' => 'cancelled',
            ]
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionB->id,
            'status' => 'active',
        ]);
    }

    public function test_activate_refuse_une_souscription_d_un_autre_tenant(): void
    {
        [$organizationA, $userA] =
            $this->createOrganizationWithUser();

        [, , , $subscriptionB] =
            $this->createOrganizationWithUser();

        $subscriptionB->update([
            'status' => 'past_due',
        ]);

        $this->authenticate($userA, $organizationA);

        $response = $this->postJson(
            "/api/v1/subscriptions/{$subscriptionB->id}/activate"
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionB->id,
            'status' => 'past_due',
        ]);
    }

    public function test_cancel_refuse_une_souscription_d_un_autre_tenant(): void
    {
        [$organizationA, $userA] =
            $this->createOrganizationWithUser();

        [, , , $subscriptionB] =
            $this->createOrganizationWithUser();

        $this->authenticate($userA, $organizationA);

        $response = $this->postJson(
            "/api/v1/subscriptions/{$subscriptionB->id}/cancel"
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionB->id,
            'status' => 'active',
        ]);
    }

    public function test_destroy_refuse_une_souscription_d_un_autre_tenant(): void
    {
        [$organizationA, $userA] =
            $this->createOrganizationWithUser();

        [, , , $subscriptionB] =
            $this->createOrganizationWithUser();

        $this->authenticate($userA, $organizationA);

        $response = $this->deleteJson(
            "/api/v1/subscriptions/{$subscriptionB->id}"
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscriptionB->id,
            'status' => 'active',
        ]);
    }

    public function test_store_utilise_l_organisation_du_contexte(): void
	{
		$organization = Organization::factory()->create();
		$user = User::factory()->create();

		$organization->users()->attach($user->id, [
			'role' => 'owner',
		]);

		$plan = Plan::factory()->create();

		$otherOrganization = Organization::factory()->create();

		$this->authenticate($user, $organization);

		$response = $this->postJson(
			'/api/v1/subscriptions',
			[
				'organization_id' => $otherOrganization->id,
				'plan_id' => $plan->id,
				'billing_cycle' => 'monthly',
			]
		);

		$response->assertStatus(201);

		$subscriptionId = $response->json('subscription.id');

		$this->assertDatabaseHas('subscriptions', [
			'id' => $subscriptionId,
			'organization_id' => $organization->id,
			'plan_id' => $plan->id,
		]);
	}

	public function test_store_ne_requiert_pas_organization_id(): void
	{
		$organization = Organization::factory()->create();
		$user = User::factory()->create();

		$organization->users()->attach($user->id, [
			'role' => 'owner',
		]);

		$plan = Plan::factory()->create();

		$this->authenticate($user, $organization);

		$response = $this->postJson(
			'/api/v1/subscriptions',
			[
				'plan_id' => $plan->id,
				'billing_cycle' => 'monthly',
			]
		);

		$response->assertStatus(201);

		$this->assertDatabaseHas('subscriptions', [
			'id' => $response->json('subscription.id'),
			'organization_id' => $organization->id,
		]);
	}
}