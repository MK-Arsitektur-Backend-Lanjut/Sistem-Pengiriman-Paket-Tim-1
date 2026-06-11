<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\FleetRepositoryInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    public function __construct(protected FleetRepositoryInterface $fleetRepo) {}

    public function index(Request $request): JsonResponse
    {
        return $this->successResponse($this->fleetRepo->getAllFleets(
            $request->query('search'),
            $request->query('status'),
            $request->query('hub_id')
        ));
    }

    public function show(int $id): JsonResponse
    {
        return $this->successResponse($this->fleetRepo->getFleetById($id));
    }

    public function getTransitDuration(int $id): JsonResponse
    {
        return $this->successResponse($this->fleetRepo->calculateTransitDuration($id));
    }

    public function calculateLoadPlan(Request $request, int $id): JsonResponse
    {
        $validated = $this->validateLoadPlanRequest($request);
        $packageIds = $validated['package_ids'] ?? [];
        $packageQuantities = $this->normalizePackageQuantities($validated['package_quantities'] ?? []);

        $this->assertAllPackageQuantitiesExist($packageQuantities);

        $plan = $this->fleetRepo->calculateLoadPlan(
            $id,
            $packageIds,
            $packageQuantities,
            $validated['strategy'] ?? 'maximize_count',
            (bool) ($validated['include_package_breakdown'] ?? false)
        );

        return $this->successResponse($plan, 'Simulasi kapasitas armada berhasil dihitung.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plate_number' => 'required|string|unique:fleets,plate_number',
            'type' => 'required|in:motorcycle,van,truck',
            'capacity' => 'required|integer|min:1',
            'status' => 'required|in:idle,in_transit,maintenance',
            'current_hub_id' => 'required|exists:hubs,id'
        ]);

        $fleet = $this->fleetRepo->storeFleet($data);

        return $this->successResponse($fleet, 'Armada baru berhasil didaftarkan!', 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:idle,in_transit,maintenance'
        ]);

        $fleet = $this->fleetRepo->updateFleetStatus($id, $request->status);

        return $this->successResponse($fleet, 'Status armada berhasil diperbarui!');
    }

    public function relocate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'new_hub_id' => 'required|exists:hubs,id'
        ]);

        $fleet = $this->fleetRepo->relocateFleet($id, $request->new_hub_id);

        return $this->successResponse(
            $fleet,
            'Armada berhasil direlokasi! Gudang lama dan baru telah diperbarui kapasitasnya.'
        );
    }

    private function validateLoadPlanRequest(Request $request): array
    {
        return $request->validate([
            'package_ids' => 'nullable|array|min:1|required_without:package_quantities',
            'package_ids.*' => 'required_with:package_ids|integer|distinct|exists:packages,id',
            'package_quantities' => 'nullable|array|min:1|required_without:package_ids',
            'package_quantities.*' => 'required|integer|min:1|max:100000',
            'strategy' => 'nullable|in:keep_order,maximize_count',
            'include_package_breakdown' => 'nullable|boolean',
        ]);
    }

    private function normalizePackageQuantities(array $rawQuantities): array
    {
        $normalized = [];

        foreach ($rawQuantities as $packageId => $quantity) {
            if (!is_numeric($packageId) || (int) $packageId <= 0) {
                $this->throwLoadPlanValidationError(
                    'Format package_quantities tidak valid. Gunakan key ID paket numerik.'
                );
            }

            $normalized[(int) $packageId] = (int) $quantity;
        }

        return $normalized;
    }

    private function assertAllPackageQuantitiesExist(array $packageQuantities): void
    {
        if (empty($packageQuantities)) {
            return;
        }

        $missingPackageIds = $this->fleetRepo->getMissingPackageIds(array_keys($packageQuantities));

        if (!empty($missingPackageIds)) {
            $this->throwLoadPlanValidationError(
                'Terdapat package_id pada package_quantities yang tidak ditemukan.'
            );
        }
    }

    private function successResponse(mixed $data, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
            'data' => $data,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }

    private function throwLoadPlanValidationError(string $message): never
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $message,
        ], 422));
    }
}
