<?php

namespace App\Repositories\Contracts;

interface FleetRepositoryInterface
{
    public function getAllFleets($search = null, $status = null, $hubId = null);
    public function getFleetById($id);
    public function getMissingPackageIds(array $packageIds): array;
    public function calculateTransitDuration($fleetId);
    public function calculateLoadPlan(int $fleetId, array $packageIds = [], array $packageQuantities = [], string $strategy = 'maximize_count', bool $includeBreakdown = false): array;
    public function storeFleet(array $data);
    public function updateFleetStatus($id, $status);
    public function relocateFleet($id, $newHubId);
}
