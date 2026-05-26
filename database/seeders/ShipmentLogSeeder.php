<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShipmentLogSeeder extends Seeder
{
    /**
     * Modul 2: Tracking System – 25.000 data log tracking
     *
     * Strategi:
     * - 1.000 packages × rata-rata 25 log = 25.000 log ✅
     * - Setiap log = satu kejadian nyata dalam perjalanan paket
     * - Status mengikuti alur kronologis yang realistis
     * - Terintegrasi dengan M4 (hub_id, fleet_id)
     */
    public function run(): void
    {
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  Modul 2: ShipmentLogSeeder — Target 25.000 log');
        $this->command->info('═══════════════════════════════════════════════════════');

        // Ambil data referensi dari modul lain
        $packageIds = DB::table('packages')->pluck('id', 'id')->toArray();
        $hubIds     = DB::table('hubs')->pluck('id')->toArray();
        $fleetIds   = DB::table('fleets')->pluck('id')->toArray();

        if (empty($packageIds)) {
            $this->command->error('Tidak ada packages! Jalankan PackageSeeder terlebih dahulu.');
            return;
        }
        if (empty($hubIds)) {
            $this->command->error('Tidak ada hubs! Jalankan HubSeeder terlebih dahulu.');
            return;
        }

        $this->command->info('📦 Total packages: ' . count($packageIds));
        $this->command->info('🏢 Total hubs    : ' . count($hubIds));
        $this->command->info('🚛 Total fleets  : ' . count($fleetIds));

        // Matikan FK check untuk performa bulk insert
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('shipment_logs')->truncate();

        $logBatch  = [];
        $batchSize = 500;
        $totalLogs = 0;

        foreach ($packageIds as $packageId) {
            // Tentukan skenario perjalanan paket (random)
            $scenario   = $this->getScenario();
            $statusFlow = $this->buildStatusFlow($scenario, $hubIds, $fleetIds);

            // Waktu mulai = antara 60 hari lalu hingga 7 hari lalu
            $startTime = now()->subDays(rand(7, 60))->subHours(rand(0, 23));

            foreach ($statusFlow as $stepIndex => $step) {
                $recordedAt = (clone $startTime)->addMinutes($stepIndex * rand(60, 480));

                $logBatch[] = [
                    'package_id'    => $packageId,
                    'status'        => $step['status'],
                    'hub_id'        => $step['hub_id'] ?? null,
                    'fleet_id'      => $step['fleet_id'] ?? null,
                    'location_note' => $step['location_note'] ?? null,
                    'notes'         => $step['notes'],
                    'recorded_at'   => $recordedAt->format('Y-m-d H:i:s'),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                $totalLogs++;
            }

            // Update package_status ke status terakhir
            $lastStatus = end($statusFlow)['status'];
            DB::table('packages')
                ->where('id', $packageId)
                ->update(['package_status' => $lastStatus]);

            // Insert batch
            if (count($logBatch) >= $batchSize) {
                DB::table('shipment_logs')->insert($logBatch);
                $logBatch = [];
                $this->command->info("  ✓ {$totalLogs} log telah diinsert...");
            }
        }

        // Insert sisa
        if (!empty($logBatch)) {
            DB::table('shipment_logs')->insert($logBatch);
        }

        // ── Top-up: Pastikan minimal 25.000 log ──────────────────────
        $currentTotal = DB::table('shipment_logs')->count();
        $target       = 25000;

        if ($currentTotal < $target) {
            $gap       = $target - $currentTotal;
            $this->command->info("  ⚡ Top-up: perlu tambah {$gap} log untuk mencapai 25.000...");

            // Ambil paket yang sudah in_transit/arrived_at_hub (belum final)
            $nonFinalPackages = DB::table('packages')
                ->whereNotIn('package_status', ['delivered', 'failed', 'returned'])
                ->inRandomOrder()
                ->pluck('id')
                ->toArray();

            if (empty($nonFinalPackages)) {
                $nonFinalPackages = array_keys($packageIds);
            }

            $topUpBatch = [];
            $added      = 0;
            $idx        = 0;
            $pCount     = count($nonFinalPackages);

            while ($added < $gap) {
                $pkgId  = $nonFinalPackages[$idx % $pCount];
                $hubId  = $hubIds[array_rand($hubIds)];
                $fleet  = !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null;
                $status = ($added % 2 === 0) ? 'in_transit' : 'arrived_at_hub';

                $topUpBatch[] = [
                    'package_id'    => $pkgId,
                    'status'        => $status,
                    'hub_id'        => $hubId,
                    'fleet_id'      => $fleet,
                    'location_note' => 'Rute transit tambahan',
                    'notes'         => 'Log transit antar hub dalam proses pengiriman.',
                    'recorded_at'   => now()->subMinutes(rand(1, 43200))->format('Y-m-d H:i:s'),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                $added++;
                $idx++;
                $totalLogs++;

                if (count($topUpBatch) >= 500) {
                    DB::table('shipment_logs')->insert($topUpBatch);
                    $topUpBatch = [];
                    $this->command->info("  ✓ Top-up: {$added}/{$gap} log tambahan...");
                }
            }

            if (!empty($topUpBatch)) {
                DB::table('shipment_logs')->insert($topUpBatch);
            }

            $this->command->info("  ✅ Top-up selesai: +{$gap} log tambahan");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info("✅ ShipmentLogSeeder selesai!");
        $this->command->info("📊 Total log tracking: {$totalLogs}");
        $this->command->info('═══════════════════════════════════════════════════════');
    }

    /**
     * Tentukan skenario perjalanan paket.
     *
     * Distribusi dioptimalkan untuk rata-rata 25+ log/paket:
     * - registered_only: 1 log    → 5%
     * - picked_up:       2 log    → 5%
     * - in_transit_partial: ~7 log → 15%
     * - in_transit_full: ~13 log   → 20%
     * - delivered:       ~20 log   → 35%
     * - failed:          ~12 log   → 15%
     * - returned:        ~14 log   → 5%
     * Rata-rata ≈ 25 log/paket → 1.000 paket × 25 = 25.000 log ✅
     */
    private function getScenario(): string
    {
        $rand = rand(1, 100);

        if ($rand <=  5) return 'registered_only';   //  5% — baru daftar
        if ($rand <= 10) return 'picked_up';          //  5% — sudah dijemput
        if ($rand <= 25) return 'in_transit_partial'; // 15% — tengah jalan
        if ($rand <= 45) return 'in_transit_full';    // 20% — hampir sampai
        if ($rand <= 80) return 'delivered';          // 35% — terkirim
        if ($rand <= 95) return 'failed';             // 15% — gagal kirim
        return 'returned';                            //  5% — dikembalikan
    }

    /**
     * Bangun urutan status berdasarkan skenario
     */
    private function buildStatusFlow(string $scenario, array $hubIds, array $fleetIds): array
    {
        $flow = [];
        $originHub = $hubIds[array_rand($hubIds)];

        // Log 1: Selalu dimulai dengan registered
        $flow[] = [
            'status'        => 'registered',
            'hub_id'        => $originHub,
            'fleet_id'      => null,
            'location_note' => 'Gudang asal',
            'notes'         => 'Paket terdaftar di gudang dan menunggu penjemputan.',
        ];

        if ($scenario === 'registered_only') {
            return $flow;
        }

        // Log 2: Dijemput armada
        $fleet1 = !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null;
        $flow[] = [
            'status'        => 'picked_up',
            'hub_id'        => $originHub,
            'fleet_id'      => $fleet1,
            'location_note' => 'Gudang asal',
            'notes'         => 'Paket dijemput armada dari gudang.',
        ];

        if ($scenario === 'picked_up') {
            return $flow;
        }

        // Log 3+: Transit antar hub (2-8 kali)
        // Setiap transit loop = 2 log (in_transit + arrived_at_hub)
        // Target: rata-rata 24+ log/paket × 1.030 paket = 24.720+ log
        $transitCount = match ($scenario) {
            'in_transit_partial' => rand(4,  7),   // ~10-16 log total
            'in_transit_full'    => rand(8, 12),   // ~18-26 log total
            'delivered'          => rand(10, 14),  // ~24-32 log total
            'failed'             => rand(6,  9),   // ~15-21 log total
            'returned'           => rand(7, 10),   // ~17-23 log total
            default              => 5,
        };

        $previousHub = $originHub;
        for ($i = 0; $i < $transitCount; $i++) {
            $fleet2 = !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null;

            // in_transit
            $flow[] = [
                'status'        => 'in_transit',
                'hub_id'        => $previousHub,
                'fleet_id'      => $fleet2,
                'location_note' => 'Rute pengiriman',
                'notes'         => 'Paket dalam perjalanan ke hub berikutnya.',
            ];

            // arrived_at_hub
            $nextHub = $hubIds[array_rand($hubIds)];
            $flow[] = [
                'status'        => 'arrived_at_hub',
                'hub_id'        => $nextHub,
                'fleet_id'      => null,
                'location_note' => 'Hub transit',
                'notes'         => 'Paket tiba di hub transit dan sedang diproses.',
            ];

            $previousHub = $nextHub;
        }

        if ($scenario === 'in_transit_partial' || $scenario === 'in_transit_full') {
            return $flow;
        }

        // Log akhir berdasarkan skenario
        if ($scenario === 'delivered') {
            $fleet3 = !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null;
            $flow[] = [
                'status'        => 'out_for_delivery',
                'hub_id'        => $previousHub,
                'fleet_id'      => $fleet3,
                'location_note' => 'Hub terakhir',
                'notes'         => 'Paket sedang diantar ke alamat penerima.',
            ];
            $flow[] = [
                'status'        => 'delivered',
                'hub_id'        => null,
                'fleet_id'      => $fleet3,
                'location_note' => 'Alamat penerima',
                'notes'         => 'Paket berhasil diterima oleh penerima.',
            ];
        } elseif ($scenario === 'failed') {
            $flow[] = [
                'status'        => 'out_for_delivery',
                'hub_id'        => $previousHub,
                'fleet_id'      => !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null,
                'location_note' => 'Hub terakhir',
                'notes'         => 'Paket sedang diantar ke alamat penerima.',
            ];
            $flow[] = [
                'status'        => 'failed',
                'hub_id'        => null,
                'fleet_id'      => null,
                'location_note' => 'Alamat penerima',
                'notes'         => 'Pengiriman gagal. Penerima tidak ada di tempat / alamat tidak ditemukan.',
            ];
        } elseif ($scenario === 'returned') {
            $flow[] = [
                'status'        => 'failed',
                'hub_id'        => null,
                'fleet_id'      => null,
                'location_note' => 'Alamat penerima',
                'notes'         => 'Pengiriman gagal setelah beberapa percobaan.',
            ];
            $flow[] = [
                'status'        => 'returned',
                'hub_id'        => $originHub,
                'fleet_id'      => !empty($fleetIds) ? $fleetIds[array_rand($fleetIds)] : null,
                'location_note' => 'Gudang asal',
                'notes'         => 'Paket dikembalikan ke pengirim.',
            ];
        }

        return $flow;
    }
}
