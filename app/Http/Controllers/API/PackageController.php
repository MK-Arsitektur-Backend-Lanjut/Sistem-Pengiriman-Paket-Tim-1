<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Package;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    /**
     * Display a listing of all packages.
     */
    public function index()
    {
        try {
            $packages = Package::with('warehouse')->get();

            // Add dimension category to each package
            $packages = $packages->map(function ($package) {
                return [
                    ...$package->toArray(),
                    'dimension_category'  => $package->getDimensionCategory(),
                    'volumetric_weight'   => $this->calcVolumetricWeight($package),
                    'effective_weight'    => $this->calcEffectiveWeight($package),
                    'weight_basis'        => $this->calcEffectiveWeight($package) > $package->weight ? 'volumetric' : 'actual',
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Package list retrieved successfully',
                'data' => $packages,
                'total' => $packages->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve package list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created package in storage.
     */
    public function store(StorePackageRequest $request)
    {
        try {
            // Auto calculate volume: length × width × height
            $volume = $request->length * $request->width * $request->height;

            $package = Package::create([
                ...$request->validated(),
                'volume' => $volume
            ]);

            // ── Integrasi Modul 1 ↔ Modul 4 ──
            // Ketika paket didaftarkan masuk ke gudang, sinkronkan current_load
            // pada Hub yang menaungi gudang tersebut.
            $package->load('warehouse.hub');
            if ($package->warehouse) {
                \App\Models\Warehouse::where('id', $package->warehouse_id)
                    ->increment('current_load', 1);

                if ($package->warehouse->hub_id) {
                    \App\Models\Hub::where('id', $package->warehouse->hub_id)
                        ->increment('current_load', 1);
                }
            }

            // Convert to array for response
            $packageData = $package->toArray();
            
            // Ensure 'id' exists
            if (!isset($packageData['id'])) {
                $packageData['id'] = $package->id;
            }
            
            $packageData['dimension_category'] = $package->getDimensionCategory();
            $packageData['volumetric_weight']  = $this->calcVolumetricWeight($package);
            $packageData['effective_weight']   = $this->calcEffectiveWeight($package);
            $packageData['weight_basis']       = $this->calcEffectiveWeight($package) > $package->weight ? 'volumetric' : 'actual';

            // Invalidasi cache paket
            CacheService::flushTag(CacheService::TAG_PACKAGE, CacheService::TAG_STATS);

            return response()->json([
                'success' => true,
                'message' => 'Package registered successfully',
                'data'    => $packageData
            ], 201);
        } catch (\Exception $e) {
            Log::error('Package store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to register package',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified package.
     */
    public function show($id)
    {
        try {
            $package = Package::with('warehouse')->findOrFail($id);

            // Convert to array for response
            $packageData = $package->toArray();
            
            // Ensure 'id' exists
            if (!isset($packageData['id'])) {
                $packageData['id'] = $package->id;
            }

            $packageData['dimension_category'] = $package->getDimensionCategory();
            $packageData['volumetric_weight']  = $this->calcVolumetricWeight($package);
            $packageData['effective_weight']   = $this->calcEffectiveWeight($package);
            $packageData['weight_basis']       = $this->calcEffectiveWeight($package) > $package->weight ? 'volumetric' : 'actual';

            return response()->json([
                'success' => true,
                'message' => 'Package retrieved successfully',
                'data' => $packageData
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
                'error' => 'The package with ID ' . $id . ' does not exist'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Package show error: ' . $e->getMessage(), [
                'package_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve package',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified package in storage.
     */
    public function update(UpdatePackageRequest $request, $id)
    {
        try {
            $package = Package::with('warehouse')->findOrFail($id);
            $oldWarehouseId = $package->warehouse_id;
            $oldHubId = $package->warehouse?->hub_id;

            $data = $request->validated();

            // Recalculate volume if any dimension is updated
            if ($request->has('length') || $request->has('width') || $request->has('height')) {
                $length = $data['length'] ?? $package->length ?? 0;
                $width = $data['width'] ?? $package->width ?? 0;
                $height = $data['height'] ?? $package->height ?? 0;
                $data['volume'] = $length * $width * $height;
            }

            $package->update($data);

            if (array_key_exists('warehouse_id', $data) && $oldWarehouseId !== (int) $data['warehouse_id']) {
                \App\Models\Warehouse::where('id', $oldWarehouseId)
                    ->where('current_load', '>', 0)
                    ->decrement('current_load', 1);

                if ($oldHubId) {
                    \App\Models\Hub::where('id', $oldHubId)
                        ->where('current_load', '>', 0)
                        ->decrement('current_load', 1);
                }

                $newWarehouse = \App\Models\Warehouse::with('hub')->find((int) $data['warehouse_id']);

                if ($newWarehouse) {
                    \App\Models\Warehouse::where('id', $newWarehouse->id)
                        ->increment('current_load', 1);

                    if ($newWarehouse->hub_id) {
                        \App\Models\Hub::where('id', $newWarehouse->hub_id)
                            ->increment('current_load', 1);
                    }
                }
            }
            
            // Refresh package data
            $package->refresh();
            
            // Convert to array for response
            $packageData = $package->toArray();
            
            // Ensure 'id' exists
            if (!isset($packageData['id'])) {
                $packageData['id'] = $package->id;
            }
            
            $packageData['dimension_category'] = $package->getDimensionCategory();
            $packageData['volumetric_weight']  = $this->calcVolumetricWeight($package);
            $packageData['effective_weight']   = $this->calcEffectiveWeight($package);
            $packageData['weight_basis']       = $this->calcEffectiveWeight($package) > $package->weight ? 'volumetric' : 'actual';

            // Invalidasi cache paket
            CacheService::forget(CacheService::keyPackageById($id));
            CacheService::flushTag(CacheService::TAG_PACKAGE, CacheService::TAG_STATS);

            return response()->json([
                'success' => true,
                'message' => 'Package updated successfully',
                'data'    => $packageData
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
                'error' => 'The package with ID ' . $id . ' does not exist'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Package update error: ' . $e->getMessage(), [
                'package_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update package',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified package from storage.
     */
    public function destroy($id)
    {
        try {
            $package = Package::with('warehouse')->findOrFail($id);

            // ── Integrasi Modul 1 ↔ Modul 4 ──
            // Ketika paket keluar/dihapus dari gudang, sinkronkan current_load hub.
            if ($package->warehouse) {
                \App\Models\Warehouse::where('id', $package->warehouse_id)
                    ->where('current_load', '>', 0)
                    ->decrement('current_load', 1);

                if ($package->warehouse->hub_id) {
                    \App\Models\Hub::where('id', $package->warehouse->hub_id)
                        ->where('current_load', '>', 0)
                        ->decrement('current_load', 1);
                }
            }

            $package->delete();

            // Invalidasi cache paket
            CacheService::forget(CacheService::keyPackageById($id));
            CacheService::flushTag(CacheService::TAG_PACKAGE, CacheService::TAG_STATS);

            return response()->json([
                'success' => true,
                'message' => 'Package deleted successfully',
                'data'    => $package
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
                'error' => 'The package with ID ' . $id . ' does not exist'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete package',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dimension details and category of a package.
     */
    public function getDimension($id)
    {
        try {
            $package = Package::findOrFail($id);

            $dimensionCategory = $package->getDimensionCategory();

            return response()->json([
                'success' => true,
                'message' => 'Package dimension retrieved successfully',
                'data' => [
                    'id' => $package->id,
                    'tracking_number' => $package->tracking_number,
                    'length' => $package->length,
                    'width' => $package->width,
                    'height' => $package->height,
                    'volume' => $package->volume,
                    'dimension_category' => $dimensionCategory,
                    'category_description' => $this->getCategoryDescription($dimensionCategory)
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Package not found',
                'error' => 'The package with ID ' . $id . ' does not exist'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dimension',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get description for dimension category.
     */
    private function getCategoryDescription($category)
    {
        $descriptions = [
            'small'  => 'Volume ≤ 1000 cm³',
            'medium' => 'Volume 1000 - 5000 cm³',
            'large'  => 'Volume > 5000 cm³'
        ];

        return $descriptions[$category] ?? 'Unknown category';
    }

    /**
     * Hitung berat volumetrik paket.
     * Formula standar industri: (P × L × T) / 5000  → satuan kg
     */
    private function calcVolumetricWeight(Package $package): float
    {
        $l = (float) ($package->length ?? 0);
        $w = (float) ($package->width  ?? 0);
        $h = (float) ($package->height ?? 0);

        return round(($l * $w * $h) / 5000, 2);
    }

    /**
     * Berat efektif = max(berat_asli, berat_volumetrik).
     * Inilah berat yang dipakai untuk cek muat kapasitas kendaraan.
     */
    private function calcEffectiveWeight(Package $package): float
    {
        $actual     = (float) ($package->weight ?? 0);
        $volumetric = $this->calcVolumetricWeight($package);

        return max($actual, $volumetric);
    }
}