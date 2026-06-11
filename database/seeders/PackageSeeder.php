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
        $warehouses   = Warehouse::all()->keyBy('id');
        $hubIds       = DB::table('hubs')->pluck('id')->toArray();
        $fleetIds     = DB::table('fleets')->pluck('id')->toArray();

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

        // Disable FK checks to safely truncate and insert
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('packages')->truncate();
        DB::table('package_histories')->truncate();

        $packages = [];
        $histories = [];
        $now      = now();
        $total    = 25000;
        $batchSize = 1000;

        for ($i = 1; $i <= $total; $i++) {
            [$length, $width, $height] = $this->randomDimension();
            $volume = $length * $width * $height;
            $weight = round(($volume / 5000) * 10 + rand(1, 5) + rand(0, 9) / 10, 2);

            $statuses = ['registered', 'picked_up', 'in_transit', 'arrived_at_hub', 'out_for_delivery', 'delivered', 'failed', 'returned'];
            $status = $statuses[array_rand($statuses)];

            $warehouseId = $warehouseIds[($i - 1) % count($warehouseIds)];
            $warehouse = $warehouses[$warehouseId] ?? null;
            $originHubId = $warehouse ? $warehouse->hub_id : null;

            $hubId = null;
            $fleetId = null;

            if ($status === 'registered') {
                $hubId = $originHubId;
            } elseif (in_array($status, ['picked_up', 'in_transit', 'out_for_delivery'])) {
                $fleetId = !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null;
                $hubId = $originHubId;
            } elseif ($status === 'arrived_at_hub') {
                $hubId = !empty($hubIds) ? $hubIds[array_rand($hubIds)] : null;
            }

            $createdAt = now()->subDays(rand(7, 90));

            $packages[] = [
                'tracking_number' => 'PKG-2026-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'sender_name'     => $senders[array_rand($senders)],
                'receiver_name'   => $receivers[array_rand($receivers)],
                'origin'          => $cities[array_rand($cities)],
                'destination'     => $cities[array_rand($cities)],
                'weight'          => $weight,
                'length'          => $length,
                'width'           => $width,
                'height'          => $height,
                'volume'          => $volume,
                'warehouse_id'    => $warehouseId,
                'hub_id'          => $hubId,
                'fleet_id'        => $fleetId,
                'package_status'  => $status,
                'created_at'      => $createdAt,
                'updated_at'      => $now,
            ];

            // Generate sequence of logs leading to final status
            $sequence = [];
            switch ($status) {
                case 'registered':
                    $sequence = ['registered'];
                    break;
                case 'picked_up':
                    $sequence = ['registered', 'picked_up'];
                    break;
                case 'in_transit':
                    $sequence = ['registered', 'picked_up', 'in_transit'];
                    break;
                case 'arrived_at_hub':
                    $sequence = ['registered', 'picked_up', 'in_transit', 'arrived_at_hub'];
                    break;
                case 'out_for_delivery':
                    $sequence = ['registered', 'picked_up', 'in_transit', 'arrived_at_hub', 'out_for_delivery'];
                    break;
                case 'delivered':
                    $sequence = ['registered', 'picked_up', 'in_transit', 'arrived_at_hub', 'out_for_delivery', 'delivered'];
                    break;
                case 'failed':
                    $sequence = ['registered', 'picked_up', 'in_transit', 'arrived_at_hub', 'out_for_delivery', 'failed'];
                    break;
                case 'returned':
                    $sequence = ['registered', 'picked_up', 'in_transit', 'arrived_at_hub', 'out_for_delivery', 'failed', 'returned'];
                    break;
            }

            $currentTime = clone $createdAt;
            foreach ($sequence as $stepIndex => $stepStatus) {
                if ($stepIndex > 0) {
                    $currentTime->addHours(rand(2, 12));
                }

                $stepHubId = null;
                $stepFleetId = null;
                $stepNotes = '';

                switch ($stepStatus) {
                    case 'registered':
                        $stepHubId = $originHubId;
                        $stepNotes = 'Paket terdaftar di sistem pengiriman.';
                        break;
                    case 'picked_up':
                        $stepFleetId = !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null;
                        $stepNotes = 'Paket telah dijemput oleh armada.';
                        break;
                    case 'in_transit':
                        $stepFleetId = !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null;
                        $stepNotes = 'Paket sedang dalam perjalanan.';
                        break;
                    case 'arrived_at_hub':
                        $stepHubId = !empty($hubIds) ? $hubIds[array_rand($hubIds)] : null;
                        $stepNotes = 'Paket tiba di hub transit.';
                        break;
                    case 'out_for_delivery':
                        $stepFleetId = !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null;
                        $stepNotes = 'Paket sedang diantar ke alamat penerima.';
                        break;
                    case 'delivered':
                        $stepNotes = 'Paket berhasil diterima oleh yang bersangkutan.';
                        break;
                    case 'failed':
                        $stepNotes = 'Gagal mengirimkan paket. Penerima tidak berada di tempat.';
                        break;
                    case 'returned':
                        $stepNotes = 'Paket dikembalikan ke pengirim.';
                        break;
                }

                $histories[] = [
                    'package_id'  => $i,
                    'status'      => $stepStatus,
                    'hub_id'      => $stepHubId,
                    'fleet_id'    => $stepFleetId,
                    'notes'       => $stepNotes,
                    'recorded_at' => $currentTime->toDateTimeString(),
                    'created_at'  => $currentTime->toDateTimeString(),
                    'updated_at'  => $currentTime->toDateTimeString(),
                ];
            }

            // Bulk insert setiap batchSize
            if ($i % $batchSize === 0) {
                DB::table('packages')->insert($packages);
                DB::table('package_histories')->insert($histories);
                $this->command->info("  ✓ {$i} packages and histories inserted...");
                $packages = [];
                $histories = [];
            }
        }

        // Insert sisa
        if (!empty($packages)) {
            DB::table('packages')->insert($packages);
        }
        if (!empty($histories)) {
            DB::table('package_histories')->insert($histories);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

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
