<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * Module 3: Customer Authentication Controller (JWT)
 *
 * Terintegrasi dengan Module 1 (Package/Warehouse), Module 2 (Tracking),
 * dan Module 4 (Fleet) melalui auth middleware 'auth:api'.
 */
class CustomerAuthController extends Controller
{
    /**
     * Register pelanggan baru dan dapatkan JWT token.
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address'  => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name'        => $payload['name'],
            'email'       => $payload['email'],
            'password'    => Hash::make($payload['password']),
            'phone'       => $payload['phone'] ?? null,
            'address'     => $payload['address'] ?? null,
            'is_customer' => true,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Registrasi pelanggan berhasil.',
            'data' => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => [
                    'id'      => $user->id,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'phone'   => $payload['phone'] ?? null,
                    'address' => $payload['address'] ?? null,
                ],
            ],
        ], 201);
    }

    /**
     * Login pelanggan dan dapatkan JWT token.
     * POST /api/v1/auth/login
     *
     * FIX: Setelah JWTAuth::attempt(), auth('api')->user() bisa null
     * karena guard belum di-resolve dalam siklus request yang sama.
     * Solusi: fetch user langsung dari DB setelah attempt() sukses.
     */
    public function login(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $token = JWTAuth::attempt([
                'email'    => $payload['email'],
                'password' => $payload['password'],
            ]);

            if (!$token) {
                return response()->json([
                    'message' => 'Email atau password tidak valid.',
                    'errors'  => ['email' => ['Email atau password tidak valid.']],
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Gagal membuat token. Silakan coba lagi.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        // Fetch user dari DB — lebih reliable dibanding auth('api')->user()
        // yang kadang null setelah attempt() dalam request yang sama.
        $user = User::where('email', $payload['email'])->where('is_customer', true)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan atau bukan pelanggan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Login berhasil.',
            'data' => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    /**
     * Logout pelanggan dan invalidasi JWT token.
     * POST /api/v1/auth/logout
     * PROTECTED: Requires auth:api (JWT)
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'message' => 'Logout berhasil.',
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Logout berhasil (token sudah tidak aktif).',
            ]);
        }
    }

    /**
     * Refresh JWT token.
     * POST /api/v1/auth/refresh
     * PROTECTED: Requires auth:api (JWT)
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'message' => 'Token berhasil diperbarui.',
                'data' => [
                    'token'      => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token tidak valid atau sudah kadaluarsa. Silakan login ulang.',
            ], 401);
        }
    }

    /**
     * Profil pelanggan yang sedang login.
     * GET /api/v1/auth/me
     * PROTECTED: Requires auth:api (JWT)
     */
    public function me(Request $request): JsonResponse
    {
        try {
            // Mengambil payload tanpa hit database untuk mendapatkan User ID
            $payload = JWTAuth::parseToken()->getPayload();
            $userId = $payload->get('sub');

            // Menyimpan & mengambil profil dari Redis Cache selama 1 jam (3600 detik)
            $profile = \Illuminate\Support\Facades\Cache::remember('user_profile_' . $userId, 3600, function () use ($userId) {
                $user = User::find($userId);
                return $user ? [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'created_at' => $user->created_at,
                ] : null;
            });

            if (!$profile) {
                return response()->json([
                    'message' => 'Unauthorized. Silakan login terlebih dahulu.',
                ], 401);
            }

            return response()->json([
                'message' => 'Data profil berhasil diambil.',
                'data' => $profile,
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Token tidak valid.',
            ], 401);
        }
    }
}
