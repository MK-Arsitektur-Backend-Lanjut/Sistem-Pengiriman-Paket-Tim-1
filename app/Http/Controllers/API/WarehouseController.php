<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Hub;
use App\Models\Warehouse;
use App\Services\CacheService;
use Illuminate\Support\Facades\Log;

class WarehouseController extends Controller
{
    /**
     * Display a listing of all warehouses.
     */
    public function index()
    {
        try {
            $warehouses = Warehouse::with(['packages', 'hub'])->get();

            return response()->json([
                'success' => true,
                'message' => 'Warehouse list retrieved successfully',
                'data' => $warehouses,
                'total' => $warehouses->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouse list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(StoreWarehouseRequest $request)
    {
        try {
            $payload = $request->validated();
            $payload['current_load'] = $payload['current_load'] ?? 0;

            // Use status provided from client, fallback to 'active'
            $payload['status'] = $payload['status'] ?? 'active';

            $warehouse = Warehouse::create($payload);

            $this->applyHubLoadChange($warehouse->hub_id, (int) $warehouse->current_load);
            $warehouse->load(['packages', 'hub']);

            // Invalidate frontend cache so UI updates instantly
            CacheService::flushTag(CacheService::TAG_WAREHOUSE);
            // Fallback for environments without Redis (like file cache driver)
            CacheService::forget(CacheService::keyWarehouseStats());
            CacheService::forget(CacheService::keyWarehouseList());

            return response()->json([
                'success' => true,
                'message' => 'Warehouse created successfully',
                'data' => $warehouse
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified warehouse.
     */
    public function show($id)
    {
        try {
            $warehouse = Warehouse::with(['packages', 'hub'])->findOrFail($id);

            // Convert to array for response
            $warehouseData = $warehouse->toArray();
            
            // Ensure 'id' exists
            if (!isset($warehouseData['id'])) {
                $warehouseData['id'] = $warehouse->id;
            }

            // Calculate warehouse usage percentage
            $usagePercentage = $warehouse->capacity > 0 
                ? round(($warehouse->current_load / $warehouse->capacity) * 100, 2) 
                : 0;

            $warehouseData['usage_percentage'] = $usagePercentage;

            return response()->json([
                'success' => true,
                'message' => 'Warehouse retrieved successfully',
                'data' => $warehouseData
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
                'error' => 'The warehouse with ID ' . $id . ' does not exist'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Warehouse show error: ' . $e->getMessage(), [
                'warehouse_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(UpdateWarehouseRequest $request, $id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);

            $oldHubId = $warehouse->hub_id;
            $oldCurrentLoad = (int) $warehouse->current_load;

            $payload = $request->validated();
            
            // Use status from payload if present, else keep existing
            if (!isset($payload['status'])) {
                $payload['status'] = $warehouse->status;
            }

            $warehouse->update($payload);
            
            // Refresh warehouse data
            $warehouse->refresh();
            $warehouse->load(['packages', 'hub']);

            $newHubId = $warehouse->hub_id;
            $newCurrentLoad = (int) $warehouse->current_load;

            if ($oldHubId === $newHubId) {
                $this->applyHubLoadChange($newHubId, $newCurrentLoad - $oldCurrentLoad);
            } else {
                $this->applyHubLoadChange($oldHubId, -$oldCurrentLoad);
                $this->applyHubLoadChange($newHubId, $newCurrentLoad);
            }

            // Invalidate frontend cache so UI updates instantly
            CacheService::flushTag(CacheService::TAG_WAREHOUSE);
            // Fallback for environments without Redis (like file cache driver)
            CacheService::forget(CacheService::keyWarehouseStats());
            CacheService::forget(CacheService::keyWarehouseList());
            
            // Convert to array for response
            $warehouseData = $warehouse->toArray();
            
            // Ensure 'id' exists
            if (!isset($warehouseData['id'])) {
                $warehouseData['id'] = $warehouse->id;
            }
            
            // Calculate usage percentage
            $usagePercentage = $warehouse->capacity > 0 
                ? round(($warehouse->current_load / $warehouse->capacity) * 100, 2) 
                : 0;
            $warehouseData['usage_percentage'] = $usagePercentage;

            return response()->json([
                'success' => true,
                'message' => 'Warehouse updated successfully',
                'data' => $warehouseData
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
                'error' => 'The warehouse with ID ' . $id . ' does not exist'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Warehouse update error: ' . $e->getMessage(), [
                'warehouse_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy($id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            $hubId = $warehouse->hub_id;
            $currentLoad = (int) $warehouse->current_load;

            // Check if warehouse has packages
            if ($warehouse->packages()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete warehouse',
                    'error' => 'Warehouse has ' . $warehouse->packages()->count() . ' packages. Please remove all packages first.'
                ], 422);
            }

            $warehouse->delete();
            $this->applyHubLoadChange($hubId, -$currentLoad);

            // Invalidate frontend cache so UI updates instantly
            CacheService::flushTag(CacheService::TAG_WAREHOUSE);
            // Fallback for environments without Redis (like file cache driver)
            CacheService::forget(CacheService::keyWarehouseStats());
            CacheService::forget(CacheService::keyWarehouseList());

            return response()->json([
                'success' => true,
                'message' => 'Warehouse deleted successfully',
                'data' => $warehouse
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Warehouse not found',
                'error' => 'The warehouse with ID ' . $id . ' does not exist'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete warehouse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function applyHubLoadChange(?int $hubId, int $delta): void
    {
        if (!$hubId || $delta === 0) {
            return;
        }

        $hub = Hub::find($hubId);

        if (!$hub) {
            return;
        }

        $hub->current_load = max(0, (int) $hub->current_load + $delta);
        $hub->save();
    }
}