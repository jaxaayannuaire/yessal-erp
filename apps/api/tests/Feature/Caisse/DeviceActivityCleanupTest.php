<?php

namespace Tests\Feature\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\DeviceActivityLog;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeviceActivityCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function deviceFor(Organization $organization): Device
    {
        return Device::factory()->create([
            'organization_id' => $organization->id,
        ]);
    }

    private function logFor(
        Organization $organization,
        Device $device,
        Carbon $createdAt
    ): DeviceActivityLog {
        return DeviceActivityLog::create([
            'organization_id' => $organization->id,
            'device_id' => $device->id,
            'event_type' => 'connected',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'YessalTest/1.0',
            'app_version' => '0.1.0',
            'metadata' => [
                'platform' => 'android',
            ],
            'created_at' => $createdAt,
        ]);
    }

    public function test_logs_older_than_three_months_are_deleted(): void
    {
        $organization = Organization::factory()->create();
        $device = $this->deviceFor($organization);

        $oldLog = $this->logFor(
            $organization,
            $device,
            now()->subMonths(3)->subDay()
        );

        $this->artisan('device-activity:cleanup')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('device_activity_logs', [
            'id' => $oldLog->id,
        ]);
    }

    public function test_recent_logs_are_preserved(): void
    {
        $organization = Organization::factory()->create();
        $device = $this->deviceFor($organization);

        $recentLog = $this->logFor(
            $organization,
            $device,
            now()->subMonths(2)
        );

        $this->artisan('device-activity:cleanup')
            ->assertExitCode(0);

        $this->assertDatabaseHas('device_activity_logs', [
            'id' => $recentLog->id,
        ]);
    }

    public function test_old_and_recent_logs_are_handled_correctly(): void
    {
        $organization = Organization::factory()->create();
        $device = $this->deviceFor($organization);

        $oldLog = $this->logFor(
            $organization,
            $device,
            now()->subMonths(4)
        );

        $recentLog = $this->logFor(
            $organization,
            $device,
            now()->subMonth()
        );

        $this->artisan('device-activity:cleanup')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('device_activity_logs', [
            'id' => $oldLog->id,
        ]);

        $this->assertDatabaseHas('device_activity_logs', [
            'id' => $recentLog->id,
        ]);
    }

    public function test_cleanup_processes_multiple_organizations(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();

        $deviceA = $this->deviceFor($organizationA);
        $deviceB = $this->deviceFor($organizationB);

        $oldA = $this->logFor(
            $organizationA,
            $deviceA,
            now()->subMonths(5)
        );

        $oldB = $this->logFor(
            $organizationB,
            $deviceB,
            now()->subMonths(6)
        );

        $this->artisan('device-activity:cleanup')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('device_activity_logs', [
            'id' => $oldA->id,
        ]);

        $this->assertDatabaseMissing('device_activity_logs', [
            'id' => $oldB->id,
        ]);
    }

    public function test_cleanup_succeeds_when_no_old_logs_exist(): void
    {
        $organization = Organization::factory()->create();
        $device = $this->deviceFor($organization);

        $this->logFor(
            $organization,
            $device,
            now()->subDays(10)
        );

        $this->artisan('device-activity:cleanup')
            ->assertExitCode(0);

        $this->assertDatabaseCount('device_activity_logs', 1);
    }
}