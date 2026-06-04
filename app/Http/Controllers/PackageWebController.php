<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Package;
use App\Models\Warehouse;
use App\Models\Hub;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

/**
 * PackageWebController - Handle web form submission (not API)
 * Returns redirect dengan flash session messages, bukan JSON response
 */
class PackageWebController extends Controller
{
    /**
     * Store a newly created package from form submission.
     * Redirect back dengan flash success message.
     */
    public function store(StorePackageRequest $request)
    {
        try {
            // Hitung volume: length × width × height
            $volume = $request->length * $request->width * $request->height;

            $package = Package::create([
                ...$request->validated(),
                'volume' => $volume
            ]);

            // ── Integrasi Modul 1 ↔ Modul 4 ──
            // Sinkronkan current_load pada Warehouse dan Hub
            $package->load('warehouse.hub');
            if ($package->warehouse) {
                Warehouse::where('id', $package->warehouse_id)
                    ->increment('current_load', 1);

                if ($package->warehouse->hub_id) {
                    Hub::where('id', $package->warehouse->hub_id)
                        ->increment('current_load', 1);
                }
            }

            // Invalidasi cache
            CacheService::flushTag(
                CacheService::TAG_PACKAGE,
                CacheService::TAG_STATS
            );

            Log::info('Package created via web form', [
                'package_id' => $package->id,
                'tracking_number' => $package->tracking_number,
                'warehouse_id' => $package->warehouse_id
            ]);

            return redirect()->back()->with(
                'success',
                "Paket '{$package->tracking_number}' berhasil didaftarkan!"
            );
        } catch (\Exception $e) {
            Log::error('Package store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'Terjadi kesalahan saat mendaftarkan paket: ' . $e->getMessage()
                );
        }
    }

    /**
     * Update the specified package.
     * Redirect back dengan flash update message.
     */
    public function update(UpdatePackageRequest $request, $id)
    {
        try {
            $package = Package::findOrFail($id);
            $oldTrackingNumber = $package->tracking_number;

            // Jika dimensi berubah, hitung ulang volume
            if (
                $request->has('length') ||
                $request->has('width') ||
                $request->has('height')
            ) {
                $validated = $request->validated();
                $length = $validated['length'] ?? $package->length;
                $width = $validated['width'] ?? $package->width;
                $height = $validated['height'] ?? $package->height;
                $validated['volume'] = $length * $width * $height;
                $package->update($validated);
            } else {
                $package->update($request->validated());
            }

            // Invalidasi cache
            CacheService::flushTag(
                CacheService::TAG_PACKAGE,
                CacheService::TAG_STATS
            );

            Log::info('Package updated via web form', [
                'package_id' => $package->id,
                'old_tracking_number' => $oldTrackingNumber,
                'new_tracking_number' => $package->tracking_number
            ]);

            return redirect()->back()->with(
                'success',
                "Paket '{$package->tracking_number}' berhasil diperbarui!"
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with(
                'error',
                'Paket tidak ditemukan.'
            );
        } catch (\Exception $e) {
            Log::error('Package update error', [
                'package_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'Terjadi kesalahan saat memperbarui paket.'
                );
        }
    }

    /**
     * Delete the specified package.
     * Redirect back dengan flash delete message.
     */
    public function destroy($id)
    {
        try {
            $package = Package::findOrFail($id);
            $trackingNumber = $package->tracking_number;
            $warehouseId = $package->warehouse_id;

            // Sinkronkan current_load saat paket dihapus
            if ($warehouseId) {
                Warehouse::where('id', $warehouseId)
                    ->decrement('current_load', 1);

                $warehouse = Warehouse::find($warehouseId);
                if ($warehouse && $warehouse->hub_id) {
                    Hub::where('id', $warehouse->hub_id)
                        ->decrement('current_load', 1);
                }
            }

            $package->delete();

            // Invalidasi cache
            CacheService::flushTag(
                CacheService::TAG_PACKAGE,
                CacheService::TAG_STATS
            );

            Log::info('Package deleted via web form', [
                'package_id' => $id,
                'tracking_number' => $trackingNumber
            ]);

            return redirect()->back()->with(
                'success',
                "Paket '{$trackingNumber}' berhasil dihapus."
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with(
                'error',
                'Paket tidak ditemukan.'
            );
        } catch (\Exception $e) {
            Log::error('Package delete error', [
                'package_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                'error',
                'Terjadi kesalahan saat menghapus paket.'
            );
        }
    }
}
