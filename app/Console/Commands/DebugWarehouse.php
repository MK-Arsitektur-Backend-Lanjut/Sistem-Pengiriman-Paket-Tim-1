<?php

namespace App\Console\Commands;

use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugWarehouse extends Command
{
    protected $signature = 'debug:warehouse';
    protected $description = 'Debug warehouse store and update operations';

    public function handle(): int
    {
        $this->info('=== WAREHOUSE DEBUG COMMAND ===');
        $this->info('DB Connection : ' . config('database.default'));
        $this->info('DB Database   : ' . config('database.connections.mysql.database'));
        $this->info('DB Host       : ' . config('database.connections.mysql.host'));
        $this->newLine();

        // Test 1: Koneksi DB
        try {
            $count = DB::table('warehouses')->count();
            $this->info("[PASS] DB Connection OK. Total warehouse: {$count}");
        } catch (\Exception $e) {
            $this->error("[FAIL] DB Connection ERROR: " . $e->getMessage());
            return 1;
        }

        // Test 2: $fillable check
        $this->newLine();
        $this->info('--- Cek $fillable di Model ---');
        $dummy = new Warehouse();
        $fillable = $dummy->getFillable();
        $this->info('$fillable: ' . implode(', ', $fillable));
        $hasCapacity    = in_array('capacity', $fillable);
        $hasCurrentLoad = in_array('current_load', $fillable);
        $this->line('[' . ($hasCapacity    ? 'PASS' : 'FAIL') . '] capacity     ada di $fillable');
        $this->line('[' . ($hasCurrentLoad ? 'PASS' : 'FAIL') . '] current_load ada di $fillable');

        // Test 3: Warehouse::create
        $this->newLine();
        $this->info('--- Test CREATE ---');
        try {
            $before = DB::table('warehouses')->count();

            $w = Warehouse::create([
                'warehouse_name' => 'DEBUG WH ' . now()->format('His'),
                'location'       => 'Debug Location Test',
                'capacity'       => 999,
                'current_load'   => 11,
                'status'         => 'active',
            ]);

            $after = DB::table('warehouses')->count();

            $this->info("[INFO] Warehouse::create() selesai. ID={$w->id}");
            $this->info("[INFO] Jumlah baris sebelum: {$before}, sesudah: {$after}");

            if ($after > $before) {
                $this->info('[PASS] CREATE berhasil tersimpan ke DB.');
            } else {
                $this->error('[FAIL] CREATE GAGAL — jumlah baris tidak bertambah!');
            }

            // Verifikasi langsung dari DB (bypass Eloquent)
            $raw = DB::table('warehouses')->find($w->id);
            $this->info("[DB RAW] capacity={$raw->capacity}, current_load={$raw->current_load}, status={$raw->status}");

        } catch (\Exception $e) {
            $this->error("[FAIL] CREATE Exception: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }

        // Test 4: Update
        $this->newLine();
        $this->info("--- Test UPDATE (ID={$w->id}) ---");
        try {
            $result = $w->update([
                'capacity'     => 888,
                'current_load' => 55,
            ]);

            $this->info("[INFO] \$warehouse->update() return: " . var_export($result, true));

            $w->refresh();
            $raw2 = DB::table('warehouses')->find($w->id);

            $this->info("[Eloquent] capacity={$w->capacity}, current_load={$w->current_load}");
            $this->info("[DB RAW  ] capacity={$raw2->capacity}, current_load={$raw2->current_load}");

            if ($raw2->capacity == 888 && $raw2->current_load == 55) {
                $this->info('[PASS] UPDATE berhasil tersimpan ke DB.');
            } else {
                $this->error('[FAIL] UPDATE GAGAL — data tidak berubah di DB!');
                $this->error("       Expected: capacity=888, current_load=55");
                $this->error("       Got:      capacity={$raw2->capacity}, current_load={$raw2->current_load}");
            }

        } catch (\Exception $e) {
            $this->error("[FAIL] UPDATE Exception: " . $e->getMessage());
        }

        // Cleanup
        $this->newLine();
        $w->forceDelete();
        $this->info('[CLEANUP] Test record dihapus.');

        // Test 5: Cek apakah ada DB transaction terbuka yang tidak di-commit
        $this->newLine();
        $this->info('--- Cek Transaction Level ---');
        $transLevel = DB::transactionLevel();
        if ($transLevel === 0) {
            $this->info("[PASS] Tidak ada transaksi DB yang menggantung (level=0).");
        } else {
            $this->error("[FAIL] Ada transaksi DB yang BELUM DI-COMMIT! Level={$transLevel}");
        }

        $this->newLine();
        $this->info('=== SELESAI ===');
        return 0;
    }
}
