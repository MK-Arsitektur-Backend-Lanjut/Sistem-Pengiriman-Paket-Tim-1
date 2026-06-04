<?php

namespace Tests\Feature;

use App\Models\Hub;
use App\Models\Package;
use App\Models\PackageHistory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTrackingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_package_history_successfully()
    {
        $user = User::factory()->create();
        $hub = Hub::factory()->create([
            'name' => 'Hub Jakarta',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);
        $warehouse = Warehouse::factory()->create([
            'warehouse_name' => 'Gudang Jakarta',
            'location' => 'Jakarta',
            'capacity' => 500,
            'current_load' => 0,
            'status' => 'available',
            'hub_id' => $hub->id,
        ]);
        $package = Package::factory()->create([
            'tracking_number' => 'PKG-TEST-HIST',
            'sender_name' => 'John Doe',
            'receiver_name' => 'Jane Smith',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'weight' => 2.5,
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'volume' => 1000,
            'warehouse_id' => $warehouse->id,
            'package_status' => 'registered'
        ]);

        // Create some histories manually
        PackageHistory::create([
            'package_id' => $package->id,
            'status' => 'registered',
            'hub_id' => $hub->id,
            'notes' => 'Terdaftar',
            'recorded_at' => now()->subHours(2)
        ]);

        PackageHistory::create([
            'package_id' => $package->id,
            'status' => 'picked_up',
            'notes' => 'Armada menjemput paket',
            'recorded_at' => now()->subHours(1)
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/v1/tracking/{$package->tracking_number}/history");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'tracking_number',
            'current_status',
            'history' => [
                '*' => [
                    'id',
                    'package_id',
                    'status',
                    'status_label',
                    'notes',
                    'recorded_at'
                ]
            ]
        ]);

        $data = $response->json('history');
        $this->assertCount(2, $data);
        $this->assertEquals('registered', $data[0]['status']);
        $this->assertEquals('picked_up', $data[1]['status']);
    }

    public function test_update_package_status_records_history()
    {
        $user = User::factory()->create();
        $hub = Hub::factory()->create([
            'name' => 'Hub Jakarta',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);
        $warehouse = Warehouse::factory()->create([
            'warehouse_name' => 'Gudang Jakarta',
            'location' => 'Jakarta',
            'capacity' => 500,
            'current_load' => 0,
            'status' => 'available',
            'hub_id' => $hub->id,
        ]);
        $package = Package::factory()->create([
            'tracking_number' => 'PKG-TEST-UPD',
            'sender_name' => 'John Doe',
            'receiver_name' => 'Jane Smith',
            'origin' => 'Jakarta',
            'destination' => 'Surabaya',
            'weight' => 2.5,
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'volume' => 1000,
            'warehouse_id' => $warehouse->id,
            'package_status' => 'registered'
        ]);

        $response = $this->actingAs($user, 'api')
            ->patchJson("/api/v1/tracking/{$package->tracking_number}/status", [
                'status' => 'picked_up',
                'notes' => 'Paket diambil oleh kurir JNE',
                'recorded_at' => now()->toDateTimeString()
            ]);

        $response->assertStatus(200);

        // Assert database package_status is updated
        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'package_status' => 'picked_up'
        ]);

        // Assert package_histories records the action
        $this->assertDatabaseHas('package_histories', [
            'package_id' => $package->id,
            'status' => 'picked_up',
            'notes' => 'Paket diambil oleh kurir JNE'
        ]);
    }
}
