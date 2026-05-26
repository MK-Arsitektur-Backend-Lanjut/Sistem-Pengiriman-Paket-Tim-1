<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingCalculatorController extends Controller
{
    public function calculate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'tracking_number' => ['nullable', 'string', 'exists:packages,tracking_number'],
            'weight_kg' => ['required_without_all:package_id,tracking_number', 'nullable', 'numeric', 'min:0.1'],
            'distance_km' => ['required_without_all:package_id,tracking_number', 'nullable', 'numeric', 'min:1'],
            'service_type' => ['required', 'in:regular,express,same_day'],
            'is_fragile' => ['nullable', 'boolean'],
            'use_insurance' => ['nullable', 'boolean'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $package = null;
        if (!empty($payload['package_id'])) {
            $package = \App\Models\Package::find($payload['package_id']);
        } elseif (!empty($payload['tracking_number'])) {
            $package = \App\Models\Package::where('tracking_number', $payload['tracking_number'])->first();
        }

        if ($package) {
            $length = (float) ($package->length ?? 0);
            $width = (float) ($package->width ?? 0);
            $height = (float) ($package->height ?? 0);
            
            // Calculate volumetric weight: (L * W * H) / 5000
            $volumetricWeight = ($length * $width * $height) / 5000;
            // Use maximum of actual weight and volumetric weight (effective weight)
            $weightKg = max((float) $package->weight, (float) $volumetricWeight);
            
            // Calculate distance based on origin and destination
            $distanceKm = $this->calculateDistance($package->origin, $package->destination);
            
            $actualWeight = (float) $package->weight;
            $origin = $package->origin;
            $destination = $package->destination;
        } else {
            $weightKg = (float) $payload['weight_kg'];
            $distanceKm = (float) $payload['distance_km'];
            
            $actualWeight = $weightKg;
            $volumetricWeight = 0.0;
            $length = 0.0;
            $width = 0.0;
            $height = 0.0;
            $origin = null;
            $destination = null;
        }

        $declaredValue = (float) ($payload['declared_value'] ?? 0);
        $isFragile = (bool) ($payload['is_fragile'] ?? false);
        $useInsurance = (bool) ($payload['use_insurance'] ?? false);

        $serviceMultiplier = match ($payload['service_type']) {
            'express' => 1.4,
            'same_day' => 1.8,
            default => 1.0,
        };

        $baseCost = 8000.0;
        $distanceCost = $distanceKm * 1200.0;
        $weightCost = $weightKg * 2500.0;
        $fuelSurcharge = ($baseCost + $distanceCost) * 0.08;
        $fragileSurcharge = $isFragile ? 5000.0 : 0.0;
        $insuranceCost = $useInsurance ? ($declaredValue * 0.0025) : 0.0;

        $subtotal = ($baseCost + $distanceCost + $weightCost + $fuelSurcharge + $fragileSurcharge + $insuranceCost);
        $totalCost = round($subtotal * $serviceMultiplier, 2);

        $estimatedSlaDays = match ($payload['service_type']) {
            'same_day' => 1,
            'express' => 2,
            default => 3,
        };

        return response()->json([
            'message' => 'Perhitungan ongkir berhasil.',
            'data' => [
                'total_cost' => $totalCost,
                'estimated_sla_days' => $estimatedSlaDays,
                'package_details' => $package ? [
                    'tracking_number' => $package->tracking_number,
                    'origin' => $origin,
                    'destination' => $destination,
                    'actual_weight' => $actualWeight,
                    'volumetric_weight' => round($volumetricWeight, 2),
                    'effective_weight' => round($weightKg, 2),
                    'dimensions' => "{$length}x{$width}x{$height} cm",
                    'calculated_distance_km' => $distanceKm,
                ] : null,
                'cost_breakdown' => [
                    'base_cost' => round($baseCost, 2),
                    'distance_cost' => round($distanceCost, 2),
                    'weight_cost' => round($weightCost, 2),
                    'fuel_surcharge' => round($fuelSurcharge, 2),
                    'fragile_surcharge' => round($fragileSurcharge, 2),
                    'insurance_cost' => round($insuranceCost, 2),
                    'service_multiplier' => $serviceMultiplier,
                ],
            ],
        ]);
    }

    private function calculateDistance(string $origin, string $destination): float
    {
        $origin = strtolower(trim($origin));
        $destination = strtolower(trim($destination));

        if ($origin === $destination) {
            return 10.0;
        }

        $coordinates = [
            'jakarta' => ['lat' => -6.2088, 'lon' => 106.8456],
            'bandung' => ['lat' => -6.9175, 'lon' => 107.6191],
            'surabaya' => ['lat' => -7.2575, 'lon' => 112.7521],
            'medan' => ['lat' => 3.5952, 'lon' => 98.6722],
            'makassar' => ['lat' => -5.1476, 'lon' => 119.4327],
            'semarang' => ['lat' => -6.9667, 'lon' => 110.4167],
            'yogyakarta' => ['lat' => -7.7956, 'lon' => 110.3695],
            'denpasar' => ['lat' => -8.6705, 'lon' => 115.2126],
            'palembang' => ['lat' => -2.9909, 'lon' => 104.7566],
            'balikpapan' => ['lat' => -1.2654, 'lon' => 116.8312],
        ];

        $origCoord = null;
        $destCoord = null;

        foreach ($coordinates as $city => $coord) {
            if (str_contains($origin, $city)) {
                $origCoord = $coord;
            }
            if (str_contains($destination, $city)) {
                $destCoord = $coord;
            }
        }

        if ($origCoord && $destCoord) {
            $earthRadius = 6371; // km
            $latDiff = deg2rad($destCoord['lat'] - $origCoord['lat']);
            $lonDiff = deg2rad($destCoord['lon'] - $origCoord['lon']);

            $a = sin($latDiff / 2) * sin($latDiff / 2) +
                 cos(deg2rad($origCoord['lat'])) * cos(deg2rad($destCoord['lat'])) *
                 sin($lonDiff / 2) * sin($lonDiff / 2);
            
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            return max(15.0, round($earthRadius * $c, 1));
        }

        $hashVal = abs(crc32($origin . ' -> ' . $destination));
        return max(20.0, ($hashVal % 500) + 50.0);
    }
}
