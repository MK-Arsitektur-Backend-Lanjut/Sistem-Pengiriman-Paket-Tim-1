<?php

namespace App\Repositories\Contracts;

interface WarehouseRepositoryInterface
{
    public function getAllWarehouses($filters = []);
    public function getWarehouseById($id);
    public function createWarehouse($data);
    public function updateWarehouse($id, $data);
    public function deleteWarehouse($id);
    public function hasPackages($id);
    public function getStatistics();
    public function getWarehouseCount();
    public function getLimitWarehouses($limit);
    public function calculateUsagePercentage($id);
}
