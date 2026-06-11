<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sentAt = fake()->dateTimeBetween('-30 days', 'now');
        $deliveredAt = fake()->randomElement([null, fake()->dateTimeBetween($sentAt, 'now')]);
        $originHubId = fake()->numberBetween(1, 50);
        $destinationHubId = fake()->numberBetween(1, 50);

        return [
            'tracking_number' => 'TRK' . fake()->unique()->numerify('###########'),
            'customer_id' => fake()->numberBetween(1, 100),              // M3: required customer
            'package_id' => fake()->numberBetween(1, 500),              // M1: required package (Option B)
            // Data (sender_name, receiver_name, weight, dimensions) fetched from package relationship
            'origin_hub_id' => $originHubId,
            'destination_hub_id' => $destinationHubId,
            'current_hub_id' => fake()->randomElement([$originHubId, $destinationHubId]),  // M4: current location
            'fleet_id' => fake()->randomElement([null, fake()->numberBetween(1, 100)]),  // M4: optional fleet assignment
            'status' => fake()->randomElement(['pending', 'in_transit', 'in_hub', 'on_delivery', 'delivered', 'failed']),
            'sent_at' => $sentAt,
            'delivered_at' => $deliveredAt,
        ];
    }

    /**
     * Indicate that the shipment is pending
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'sent_at' => null,
                'delivered_at' => null,
            ];
        });
    }

    /**
     * Indicate that the shipment is delivered
     */
    public function delivered()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'delivered',
                'sent_at' => fake()->dateTimeBetween('-30 days', '-1 days'),
                'delivered_at' => fake()->dateTimeBetween('-1 days', 'now'),
            ];
        });
    }
}
