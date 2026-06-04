<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ShippingProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $profiles = $this->readProfiles();
        $profile = $profiles[(string) $user->id] ?? null;

        return response()->json([
            'message' => 'Profil pengiriman pelanggan.',
            'data' => $profile,
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_phone' => ['required', 'string', 'max:30'],
            'default_pickup_address' => ['required', 'string', 'max:255'],
            'default_origin_city' => ['required', 'string', 'max:100'],
            'default_origin_postal_code' => ['required', 'string', 'max:12'],
            'preferred_service_type' => ['required', 'in:regular,express,same_day'],
            'preferred_package_type' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth('api')->user();
        $profiles = $this->readProfiles();
        $profiles[(string) $user->id] = $payload;
        Storage::disk('local')->put('module3/shipping_profiles.json', json_encode($profiles, JSON_PRETTY_PRINT));

        return response()->json([
            'message' => 'Profil pengiriman berhasil disimpan.',
            'data' => $payload,
        ]);
    }

    private function readProfiles(): array
    {
        if (!Storage::disk('local')->exists('module3/shipping_profiles.json')) {
            return [];
        }

        $decoded = json_decode((string) Storage::disk('local')->get('module3/shipping_profiles.json'), true);

        return is_array($decoded) ? $decoded : [];
    }
}
