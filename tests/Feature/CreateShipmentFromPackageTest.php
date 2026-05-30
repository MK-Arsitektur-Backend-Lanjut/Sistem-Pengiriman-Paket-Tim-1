<?php

namespace Tests\Feature;

use App\Models\Hub;
use App\Models\Package;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateShipmentFromPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_shipment_from_package_successfully()
    {
        // 1. Create a user (customer)
        $user = User::factory()->create();

        // 2. Create origin and destination hubs
        $originHub = Hub::factory()->create([
            'name' => 'Origin Hub Jakarta',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);
        $destinationHub = Hub::factory()->create([
            'name' => 'Destination Hub Surabaya',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);

        // 3. Create warehouse linked to origin hub
        $warehouse = Warehouse::factory()->create([
            'warehouse_name' => 'Gudang Jakarta',
            'location' => 'Jakarta',
            'capacity' => 500,
            'current_load' => 0,
            'status' => 'available',
            'hub_id' => $originHub->id,
        ]);

        // 4. Create package in the warehouse
        $package = Package::factory()->create([
            'tracking_number' => 'PKG-TEST-12345',
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
            'package_status' => 'registered',
        ]);

        // 5. Post to API with authentication
        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/shipment/from-package/{$package->id}", [
                'destination_hub_id' => $destinationHub->id,
            ]);

        // 6. Assert response
        $response->assertStatus(201);
        $response->assertJson([
            'status'  => 'success',
            'message' => 'Pengiriman berhasil dibuat dari paket!',
            'data'    => [
                'tracking_number' => $package->tracking_number,
            ]
        ]);

        // 7. Assert package has correct transit fields
        $this->assertDatabaseHas('packages', [
            'id'             => $package->id,
            'package_status' => 'registered',
            'hub_id'         => $originHub->id,
        ]);

        // 8. Assert package status is updated to registered (or whatever it already was/is synced)
        $package->refresh();
        $this->assertEquals('registered', $package->package_status);
    }

    public function test_create_shipment_fails_if_unauthorized()
    {
        // Create hubs and warehouse explicitly
        $originHub = Hub::factory()->create([
            'name' => 'Origin Hub Jakarta',
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
            'hub_id' => $originHub->id,
        ]);
        $package = Package::factory()->create([
            'tracking_number' => 'PKG-TEST-12345',
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
            'package_status' => 'registered',
        ]);
        $destinationHub = Hub::factory()->create([
            'name' => 'Destination Hub Surabaya',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);

        $response = $this->postJson("/api/v1/shipment/from-package/{$package->id}", [
            'destination_hub_id' => $destinationHub->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_create_shipment_fails_if_same_hub()
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
            'tracking_number' => 'PKG-TEST-12345',
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
            'package_status' => 'registered',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/shipment/from-package/{$package->id}", [
                'destination_hub_id' => $hub->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'status',
            'message',
            'code',
            'origin_hub',
            'available_destination_hubs',
        ]);
    }
}
