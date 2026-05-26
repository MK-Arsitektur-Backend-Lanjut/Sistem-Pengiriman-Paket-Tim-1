<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackageSeeder extends Seeder
{
    /**
     * Seed 1.000 packages distributed across existing warehouses.
     *
     * Dinaikkan dari 100 ke 1.000 agar ShipmentLogSeeder bisa
     * menghasilkan minimal 25.000 log tracking (rata-rata 25 log/paket).
     *
     * After inserting all packages, warehouse current_load and status
     * are recalculated automatically via Warehouse::recalculateLoad().
     */
    public function run(): void
    {
        $warehouseIds = Warehouse::pluck('id')->toArray();

        if (empty($warehouseIds)) {
            $this->command->warn('No warehouses found. Run WarehouseSeeder first.');
            return;
        }

        $senders = [
            'PT Tokopedia Indonesia', 'Shopee Express', 'Lazada Logistics',
            'JD.id', 'Blibli Commerce', 'Bukalapak', 'PT GoSend Indonesia',
            'Grab Express', 'SiCepat Ekspres', 'JNE Logistics',
            'PT TIKI', 'Anteraja', 'Wahana Express', 'Ninja Xpress',
            'PT Pos Indonesia', 'Lion Parcel', 'RPX Holding',
            'J&T Express', 'SAP Express', 'Paxel',
        ];

        $receivers = [
            'Ahmad Fauzi', 'Siti Rahma', 'Budi Santoso', 'Dewi Lestari',
            'Rizky Pratama', 'Nurul Hidayah', 'Eko Prasetyo', 'Fitri Andriani',
            'Hendra Gunawan', 'Indah Permata', 'Joko Widodo', 'Kartini Sari',
            'Luki Hakim', 'Maya Putri', 'Nanang Suryadi', 'Olivia Tan',
            'Putra Alamsyah', 'Qonita Azzahra', 'Rendra Kusuma', 'Sari Dewi',
            'Toni Hartono', 'Umi Kalsum', 'Vira Septiani', 'Wira Kusuma',
            'Xena Maharani', 'Yoga Pranata', 'Zahra Amelia', 'Arif Budiman',
            'Bintang Ramadhan', 'Citra Ningrum',
        ];

        $cities = [
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Makassar',
            'Palembang', 'Semarang', 'Yogyakarta', 'Denpasar', 'Pontianak',
            'Banjarmasin', 'Balikpapan', 'Samarinda', 'Pekanbaru', 'Padang',
            'Manado', 'Kupang', 'Jayapura', 'Mataram', 'Ambon',
            'Batam', 'Jambi', 'Bengkulu', 'Lampung', 'Cirebon',
            'Bogor', 'Malang', 'Solo', 'Tasikmalaya', 'Kediri',
        ];

        $packages = [];
        $now      = now();
        $total    = 1000;

        for ($i = 1; $i <= $total; $i++) {
            [$length, $width, $height] = $this->randomDimension();
            $volume = $length * $width * $height;
            $weight = round(($volume / 5000) * 10 + rand(1, 5) + rand(0, 9) / 10, 2);

            // Status awal selalu 'registered' — akan diupdate oleh ShipmentLogSeeder
            $packages[] = [
                'tracking_number' => 'PKG-2026-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'sender_name'     => $senders[array_rand($senders)],
                'receiver_name'   => $receivers[array_rand($receivers)],
                'origin'          => $cities[array_rand($cities)],
                'destination'     => $cities[array_rand($cities)],
                'weight'          => $weight,
                'length'          => $length,
                'width'           => $width,
                'height'          => $height,
                'volume'          => $volume,
                'warehouse_id'    => $warehouseIds[($i - 1) % count($warehouseIds)],
                'package_status'  => 'registered', // akan diupdate ShipmentLogSeeder
                'created_at'      => now()->subDays(rand(7, 90)),
                'updated_at'      => $now,
            ];

            // Bulk insert setiap 200
            if ($i % 200 === 0) {
                DB::table('packages')->insert($packages);
                $this->command->info("  ✓ {$i} packages inserted...");
                $packages = [];
            }
        }

        // Insert sisa
        if (!empty($packages)) {
            DB::table('packages')->insert($packages);
        }

        // Recalculate warehouse loads
        $this->command->info('Recalculating warehouse loads...');
        foreach (Warehouse::all() as $warehouse) {
            $warehouse->recalculateLoad();
        }

        // Statistik
        $small  = Package::where('volume', '<=', 1000)->count();
        $medium = Package::whereBetween('volume', [1001, 5000])->count();
        $large  = Package::where('volume', '>', 5000)->count();

        $this->command->info('✅ PackageSeeder completed!');
        $this->command->line("📦 {$total} packages inserted");
        $this->command->line("  - Small  (≤ 1.000 cm³) : {$small}");
        $this->command->line("  - Medium (1.001–5.000)  : {$medium}");
        $this->command->line("  - Large  (> 5.000 cm³)  : {$large}");
    }

    /**
     * Generate dimensi paket secara random dengan distribusi realistis
     * ~30% small, ~40% medium, ~30% large
     */
    private function randomDimension(): array
    {
        $type = rand(1, 100);

        if ($type <= 30) {
            // Small: volume ≤ 1.000 cm³
            return [rand(8, 20), rand(8, 15), rand(5, 10)];
        } elseif ($type <= 70) {
            // Medium: volume 1.001–5.000 cm³
            return [rand(20, 35), rand(15, 25), rand(8, 15)];
        } else {
            // Large: volume > 5.000 cm³
            return [rand(40, 80), rand(30, 60), rand(20, 40)];
        }
    }
}
