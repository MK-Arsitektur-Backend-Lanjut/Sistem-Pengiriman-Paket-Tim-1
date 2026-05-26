<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Package;
use App\Models\Hub;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ShippingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'calculator@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    public function test_calculate_with_manual_inputs_successfully()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/customer/shipping-cost/calculate', [
            'weight_kg'     => 5,
            'distance_km'   => 150,
            'service_type'  => 'express',
            'is_fragile'    => true,
            'use_insurance' => true,
            'declared_value' => 500000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'total_cost',
                'estimated_sla_days',
                'cost_breakdown' => [
                    'base_cost',
                    'distance_cost',
                    'weight_cost',
                    'fuel_surcharge',
                    'fragile_surcharge',
                    'insurance_cost',
                    'service_multiplier',
                ],
            ],
        ]);
    }

    public function test_calculate_using_package_db_successfully()
    {
        $hub = Hub::factory()->create([
            'name' => 'Jakarta Hub',
            'capacity' => 1000,
            'current_load' => 0,
            'status' => 'available',
        ]);

        $warehouse = Warehouse::factory()->create([
            'warehouse_name' => 'Gudang Utama',
            'location'       => 'Jakarta Barat',
            'capacity'       => 100,
            'hub_id'         => $hub->id,
        ]);

        // Volumetric weight of this package: (100 * 50 * 20) / 5000 = 20 kg
        // Actual weight: 10 kg
        // Effective weight should be 20 kg
        $package = Package::factory()->create([
            'tracking_number' => 'PKG-CALC-12345',
            'warehouse_id' => $warehouse->id,
            'sender_name' => 'Alice',
            'receiver_name' => 'Bob',
            'origin' => 'Jakarta Barat',
            'destination' => 'Bandung Kota',
            'weight' => 10,
            'length' => 100,
            'width' => 50,
            'height' => 20,
            'volume' => 100000,
            'package_status' => 'registered',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/v1/customer/shipping-cost/calculate', [
            'package_id'    => $package->id,
            'service_type'  => 'regular',
            'is_fragile'    => false,
            'use_insurance' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Perhitungan ongkir berhasil.',
        ]);

        // Verify that package details were returned and calculated correctly
        $response->assertJsonPath('data.package_details.tracking_number', 'PKG-CALC-12345');
        
        // Volumetric weight should be 20
        $response->assertJsonPath('data.package_details.volumetric_weight', 20);
        
        // Effective weight is max(10, 20) = 20
        $response->assertJsonPath('data.package_details.effective_weight', 20);
        
        // The distance between Jakarta and Bandung coordinates dictionary should be resolved to around ~119 km
        $distance = $response->json('data.package_details.calculated_distance_km');
        $this->assertGreaterThan(100, $distance);
        $this->assertLessThan(150, $distance);
    }
}
