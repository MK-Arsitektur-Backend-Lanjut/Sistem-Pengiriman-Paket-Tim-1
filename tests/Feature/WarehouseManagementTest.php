<?php

namespace Tests\Feature;

use App\Models\Hub;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_warehouse_successfully()
    {
        $hub = Hub::factory()->create([
            'name' => 'Test Hub',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);

        $response = $this->postJson('/api/v1/warehouse', [
            'warehouse_name' => 'New Test Warehouse',
            'location'       => 'Jakarta Timur',
            'capacity'       => 200,
            'current_load'   => 50,
            'hub_id'         => $hub->id,
            'status'         => 'available',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Warehouse created successfully',
        ]);

        $this->assertDatabaseHas('warehouses', [
            'warehouse_name' => 'New Test Warehouse',
            'capacity'       => 200,
            'current_load'   => 50,
            'status'         => 'available', // Status matches the load (<90%)
        ]);
    }

    public function test_warehouse_status_calculated_correctly_on_creation()
    {
        $hub = Hub::factory()->create([
            'name' => 'Test Hub',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);

        // 1. Available status (<90% usage)
        $response1 = $this->postJson('/api/v1/warehouse', [
            'warehouse_name' => 'Warehouse Available',
            'location'       => 'Jakarta',
            'capacity'       => 100,
            'current_load'   => 89,
            'hub_id'         => $hub->id,
        ]);
        $response1->assertStatus(201);
        $this->assertDatabaseHas('warehouses', [
            'warehouse_name' => 'Warehouse Available',
            'status'         => 'available',
        ]);

        // 2. Full status (>=90% and <100% usage)
        $response2 = $this->postJson('/api/v1/warehouse', [
            'warehouse_name' => 'Warehouse Full',
            'location'       => 'Jakarta',
            'capacity'       => 100,
            'current_load'   => 90,
            'hub_id'         => $hub->id,
        ]);
        $response2->assertStatus(201);
        $this->assertDatabaseHas('warehouses', [
            'warehouse_name' => 'Warehouse Full',
            'status'         => 'full',
        ]);

        // 3. Overload status (>=100% usage)
        $response3 = $this->postJson('/api/v1/warehouse', [
            'warehouse_name' => 'Warehouse Overload',
            'location'       => 'Jakarta',
            'capacity'       => 100,
            'current_load'   => 100,
            'hub_id'         => $hub->id,
        ]);
        $response3->assertStatus(201);
        $this->assertDatabaseHas('warehouses', [
            'warehouse_name' => 'Warehouse Overload',
            'status'         => 'overload',
        ]);
    }

    public function test_can_update_warehouse_successfully()
    {
        $hub = Hub::factory()->create([
            'name' => 'Test Hub',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);

        $warehouse = Warehouse::factory()->create([
            'warehouse_name' => 'Old Warehouse Name',
            'location'       => 'Old Location',
            'capacity'       => 100,
            'current_load'   => 10,
            'status'         => 'available',
            'hub_id'         => $hub->id,
        ]);

        $response = $this->putJson("/api/v1/warehouse/{$warehouse->id}", [
            'warehouse_name' => 'Updated Warehouse Name',
            'current_load'   => 95, // Updates usage to 95%, status should change to 'full'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Warehouse updated successfully',
        ]);

        $this->assertDatabaseHas('warehouses', [
            'id'             => $warehouse->id,
            'warehouse_name' => 'Updated Warehouse Name',
            'current_load'   => 95,
            'status'         => 'full',
        ]);
    }
}
