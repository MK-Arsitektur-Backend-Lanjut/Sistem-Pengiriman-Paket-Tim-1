<?php
/**
 * Debug script: Jalankan via php artisan tinker < debug_warehouse.php
 * atau: php artisan eval di dalam container
 * 
 * Menguji apakah Warehouse::create dan ->update() benar-benar menyimpan ke DB.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== WAREHOUSE DEBUG SCRIPT ===" . PHP_EOL;
echo "DB Connection: " . config('database.default') . PHP_EOL;
echo "DB Database:   " . config('database.connections.mysql.database') . PHP_EOL;
echo "DB Host:       " . config('database.connections.mysql.host') . PHP_EOL;
echo PHP_EOL;

// Test 1: Cek koneksi DB
try {
    $count = DB::table('warehouses')->count();
    echo "[PASS] DB Connection OK. Jumlah warehouse: {$count}" . PHP_EOL;
} catch (Exception $e) {
    echo "[FAIL] DB Connection ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 2: Warehouse::create
echo PHP_EOL . "--- Test CREATE ---" . PHP_EOL;
try {
    $w = Warehouse::create([
        'warehouse_name' => 'DEBUG TEST WH ' . time(),
        'location'       => 'Debug Location',
        'capacity'       => 500,
        'current_load'   => 10,
        'status'         => 'active',
    ]);
    echo "[PASS] Warehouse::create() sukses. ID={$w->id}" . PHP_EOL;
    echo "       Data tersimpan: name={$w->warehouse_name}, capacity={$w->capacity}, load={$w->current_load}" . PHP_EOL;
    
    // Test 3: Update
    echo PHP_EOL . "--- Test UPDATE (ID={$w->id}) ---" . PHP_EOL;
    $result = $w->update([
        'capacity'     => 750,
        'current_load' => 25,
    ]);
    echo "[INFO] update() return value: " . var_export($result, true) . PHP_EOL;
    
    $w->refresh();
    echo "[CHECK] After refresh: capacity={$w->capacity}, current_load={$w->current_load}" . PHP_EOL;
    
    if ($w->capacity == 750 && $w->current_load == 25) {
        echo "[PASS] UPDATE berhasil tersimpan ke DB." . PHP_EOL;
    } else {
        echo "[FAIL] UPDATE GAGAL — data tidak berubah di DB!" . PHP_EOL;
    }
    
    // Test 4: Cek lewat DB langsung (bypass Eloquent)
    $raw = DB::table('warehouses')->find($w->id);
    echo PHP_EOL . "--- Verifikasi Raw DB Query ---" . PHP_EOL;
    echo "[DB RAW] capacity={$raw->capacity}, current_load={$raw->current_load}, status={$raw->status}" . PHP_EOL;
    
    // Cleanup
    $w->delete();
    echo PHP_EOL . "[CLEANUP] Test record dihapus." . PHP_EOL;
    
} catch (Exception $e) {
    echo "[FAIL] Exception: " . $e->getMessage() . PHP_EOL;
    echo "       Trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== SELESAI ===" . PHP_EOL;
