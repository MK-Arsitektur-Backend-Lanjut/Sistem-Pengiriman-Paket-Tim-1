<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;

/**
 * WarehouseWebController - Handle web form submission (not API)
 * Returns redirect dengan flash session messages, bukan JSON response
 */
class WarehouseWebController extends Controller
{
    /**
     * Store a newly created warehouse from form submission.
     * Redirect back dengan flash success message.
     */
    public function store(StoreWarehouseRequest $request)
    {
        try {
            $payload = $request->validated();
            $payload['current_load'] = $payload['current_load'] ?? 0;

            $warehouse = Warehouse::create($payload);

            // Log untuk audit trail (optional)
            Log::info('Warehouse created via web form', [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->warehouse_name
            ]);

            return redirect()->back()->with(
                'success',
                "Gudang '{$warehouse->warehouse_name}' berhasil ditambahkan!"
            );
        } catch (\Exception $e) {
            Log::error('Warehouse store error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'Terjadi kesalahan saat menambah gudang: ' . $e->getMessage()
                );
        }
    }

    /**
     * Update the specified warehouse.
     * Redirect back dengan flash update message.
     */
    public function update(UpdateWarehouseRequest $request, $id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            $oldName = $warehouse->warehouse_name;

            $warehouse->update($request->validated());

            Log::info('Warehouse updated via web form', [
                'warehouse_id' => $warehouse->id,
                'old_name' => $oldName,
                'new_name' => $warehouse->warehouse_name
            ]);

            return redirect()->back()->with(
                'success',
                "Gudang '{$warehouse->warehouse_name}' berhasil diperbarui!"
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with(
                'error',
                'Gudang tidak ditemukan.'
            );
        } catch (\Exception $e) {
            Log::error('Warehouse update error', [
                'warehouse_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'Terjadi kesalahan saat memperbarui gudang.'
                );
        }
    }

    /**
     * Delete the specified warehouse.
     * Redirect back dengan flash delete message.
     */
    public function destroy($id)
    {
        try {
            $warehouse = Warehouse::findOrFail($id);
            $warehouseName = $warehouse->warehouse_name;

            $warehouse->delete();

            Log::info('Warehouse deleted via web form', [
                'warehouse_id' => $id,
                'warehouse_name' => $warehouseName
            ]);

            return redirect()->back()->with(
                'success',
                "Gudang '{$warehouseName}' berhasil dihapus."
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with(
                'error',
                'Gudang tidak ditemukan.'
            );
        } catch (\Exception $e) {
            Log::error('Warehouse delete error', [
                'warehouse_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                'error',
                'Terjadi kesalahan saat menghapus gudang.'
            );
        }
    }
}
