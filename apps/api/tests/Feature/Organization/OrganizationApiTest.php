<?php

namespace Tests\Feature\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(
        User $user,
        Organization $organization
    ): void {
        Sanctum::actingAs($user);

        $this->withHeaders([
            'X-Organization-Id' => (string) $organization->id,
        ]);
    }

    private function createMember(
        Organization $organization,
        string $role = 'member'
    ): User {
        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => $role,
        ]);

        return $user;
    }

    public function test_user_can_list_their_organizations(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createMember($organization);

        $otherOrganization = Organization::factory()->create();
        $this->createMember($otherOrganization);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/organizations');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $organization->id,
            ])
            ->assertJsonMissing([
                'id' => $otherOrganization->id,
            ]);
    }

    public function test_member_can_show_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createMember($organization);

        $this->authenticate($user, $organization);

        $response = $this->getJson(
            "/api/v1/organizations/{$organization->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'organization.id',
                $organization->id
            );
    }

    public function test_non_member_cannot_show_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $this->authenticate($user, $organization);

        $response = $this->getJson(
            "/api/v1/organizations/{$organization->id}"
        );

        $response->assertForbidden();
    }

    public function test_owner_can_update_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createMember($organization, 'owner');

        $this->authenticate($user, $organization);

        $response = $this->patchJson(
            "/api/v1/organizations/{$organization->id}",
            [
                'name' => 'Organisation modifiée',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'organization.name',
                'Organisation modifiée'
            );
    }

    public function test_member_cannot_update_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createMember($organization, 'member');

        $this->authenticate($user, $organization);

        $response = $this->patchJson(
            "/api/v1/organizations/{$organization->id}",
            [
                'name' => 'Tentative interdite',
            ]
        );

        $response->assertForbidden();
    }

    public function test_admin_cannot_update_organization_yet(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createMember($organization, 'admin');

        $this->authenticate($user, $organization);

        $response = $this->patchJson(
            "/api/v1/organizations/{$organization->id}",
            [
                'name' => 'Modification admin',
            ]
        );

        $response->assertForbidden();
    }

    public function test_owner_can_delete_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createMember($organization, 'owner');

        $this->authenticate($user, $organization);

        $response = $this->deleteJson(
            "/api/v1/organizations/{$organization->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('organizations', [
            'id' => $organization->id,
        ]);
    }

    public function test_admin_cannot_delete_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createMember($organization, 'admin');

        $this->authenticate($user, $organization);

        $response = $this->deleteJson(
            "/api/v1/organizations/{$organization->id}"
        );

        $response->assertForbidden();
    }

    public function test_member_cannot_delete_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createMember($organization, 'member');

        $this->authenticate($user, $organization);

        $response = $this->deleteJson(
            "/api/v1/organizations/{$organization->id}"
        );

        $response->assertForbidden();
    }

    public function test_user_cannot_access_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $user = $this->createMember($organization);

        $this->authenticate($user, $organization);

        $response = $this->getJson(
            "/api/v1/organizations/{$otherOrganization->id}"
        );

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_list_organizations(): void
    {
        $response = $this->getJson('/api/v1/organizations');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_organization(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/organizations', [
            'name' => 'Nouvelle organisation',
            'country' => 'SN',
            'currency' => 'XOF',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'organization.name',
                'Nouvelle organisation'
            );

        $organizationId = $response->json('organization.id');

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }
	
	public function test_user_cannot_show_organization_different_from_current_context(): void
{
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $user = $this->createMember($organizationA);
    $organizationB->users()->attach($user->id, [
        'role' => 'member',
    ]);

    // Le contexte sélectionne A, mais la route cible B.
    $this->authenticate($user, $organizationA);

    $response = $this->getJson(
        "/api/v1/organizations/{$organizationB->id}"
    );

    $response->assertForbidden();
}

public function test_user_cannot_update_organization_different_from_current_context(): void
{
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $user = $this->createMember($organizationA, 'owner');

    $organizationB->users()->attach($user->id, [
        'role' => 'owner',
    ]);

    $this->authenticate($user, $organizationA);

    $response = $this->patchJson(
        "/api/v1/organizations/{$organizationB->id}",
        [
            'name' => 'Modification interdite',
        ]
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('organizations', [
        'id' => $organizationB->id,
        'name' => $organizationB->name,
    ]);
}

	public function test_user_cannot_delete_organization_different_from_current_context(): void
	{
		$organizationA = Organization::factory()->create();
		$organizationB = Organization::factory()->create();

		$user = $this->createMember($organizationA, 'owner');

		$organizationB->users()->attach($user->id, [
			'role' => 'owner',
		]);

		$this->authenticate($user, $organizationA);

		$response = $this->deleteJson(
			"/api/v1/organizations/{$organizationB->id}"
		);

		$response->assertForbidden();

		$this->assertDatabaseHas('organizations', [
			'id' => $organizationB->id,
		]);
	}
}