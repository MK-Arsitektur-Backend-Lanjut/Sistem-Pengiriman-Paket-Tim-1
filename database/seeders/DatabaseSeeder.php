<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Urutan eksekusi PENTING karena ada FK dependencies:
     * 1. Users        (M3 dependency)
     * 2. Hubs         (M4 — dibutuhkan Warehouses & Fleets)
     * 3. Fleets       (M4 — dibutuhkan ShipmentLogSeeder)
     * 4. FleetLogs    (M4 — 5.000 log armada per requirement)
     * 5. Module1      (Warehouse & Packages awal)
     * 6. PackageSeeder (1.000 packages — M2 dependency)
     * 7. ShipmentLogSeeder (25.000 log tracking — M2 core requirement)
     */
    public function run(): void
    {
        // M3: Buat users terlebih dahulu
        User::factory(100)->create();

        User::factory()->create([
            'name'        => 'Test User',
            'email'       => 'test@example.com',
            'phone'       => '081234567890',
            'address'     => 'Jl. Contoh No. 1, Jakarta',
            'is_customer' => true,
        ]);

        $this->call([
            HubSeeder::class,           // M4: Hub (dibutuhkan oleh Warehouse & Fleet)
            FleetSeeder::class,         // M4: Armada
            FleetLogSeeder::class,      // M4: 5.000+ log armada (requirement teknis)
            Module1Seeder::class,       // M1: Warehouse
            PackageSeeder::class,       // M1: 1.000 packages (untuk M2)
            ShipmentLogSeeder::class,   // M2: 25.000 log tracking (requirement teknis)
        ]);
    }
}
