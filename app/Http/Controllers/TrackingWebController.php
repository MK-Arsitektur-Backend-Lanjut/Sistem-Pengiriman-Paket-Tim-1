<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\PackageRepositoryInterface;
use App\Services\CacheService;
use App\Models\Hub;
use App\Models\Fleet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingWebController extends Controller
{
    protected PackageRepositoryInterface $packageRepo;

    public function __construct(PackageRepositoryInterface $packageRepo)
    {
        $this->packageRepo = $packageRepo;
    }

    /**
     * Dashboard tracking - tampilkan statistik dan list paket
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        try {
            $packages = $this->packageRepo->getAllPackagesPaginated([
                'search' => $search,
                'status' => $status
            ], 15);

            // Statistik dari packages.package_status
            $stats = CacheService::remember(
                CacheService::keyDashboardStats(),
                function () {
                    $rows = DB::table('packages')
                        ->selectRaw('package_status as status, COUNT(*) as total')
                        ->groupBy('package_status')
                        ->pluck('total', 'status');

                    return [
                        'total'            => $rows->sum(),
                        'registered'       => (int) ($rows['registered']       ?? 0),
                        'in_transit'       => (int) ($rows['in_transit']       ?? 0),
                        'delivered'        => (int) ($rows['delivered']        ?? 0),
                        'failed'           => (int) ($rows['failed']           ?? 0),
                        'out_for_delivery' => (int) ($rows['out_for_delivery'] ?? 0),
                    ];
                },
                CacheService::TTL_SHORT,
                [CacheService::TAG_STATS, CacheService::TAG_PACKAGE]
            );

            return view('tracking.index', compact('packages', 'stats', 'status', 'search'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memuat data: ' . $e->getMessage());
        }
    }

    /**
     * Lihat detail paket berdasarkan tracking number
     */
    public function show(string $trackingNumber)
    {
        try {
            $package   = $this->packageRepo->findPackageByTrackingNumber($trackingNumber);
            $logs      = $package->histories()->orderByDesc('recorded_at')->get();
            $latestLog = $package->latestLog;

            $hubs = Hub::orderBy('name')->get();
            $fleets = Fleet::orderBy('plate_number')->get();

            return view('tracking.show', compact('package', 'logs', 'latestLog', 'hubs', 'fleets'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return back()->with('error', 'Paket tidak ditemukan');
        }
    }

    /**
     * Tampilkan timeline detail paket
     */
    public function timeline(string $trackingNumber)
    {
        try {
            $package = $this->packageRepo->findPackageByTrackingNumber($trackingNumber);
            $logs    = $package->histories;

            return view('tracking.timeline', compact('package', 'logs'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return back()->with('error', 'Paket tidak ditemukan');
        }
    }

    /**
     * Form untuk melacak paket (search page)
     */
    public function search()
    {
        return view('tracking.search');
    }

    /**
     * Handle search form submission
     */
    public function doSearch(Request $request)
    {
        $keyword = $request->input('keyword');

        if (!$keyword) {
            return back()->with('error', 'Masukkan kata kunci pencarian');
        }

        try {
            $results = $this->packageRepo->getAllPackagesPaginated(['search' => $keyword], 15);
            return view('tracking.search-results', compact('results', 'keyword'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal melakukan pencarian');
        }
    }

    /**
     * API search (untuk autocomplete)
     * Search di packages.tracking_number, sender_name, receiver_name
     */
    public function apiSearch(Request $request)
    {
        $q = $request->query('q');

        if (strlen($q) < 3) {
            return response()->json([]);
        }

        $results = \App\Models\Package::where('tracking_number', 'like', "%$q%")
            ->orWhere('sender_name', 'like', "%$q%")
            ->orWhere('receiver_name', 'like', "%$q%")
            ->orWhere('origin', 'like', "%$q%")
            ->orWhere('destination', 'like', "%$q%")
            ->with(['warehouse', 'hub', 'fleet'])
            ->limit(10)
            ->select('id', 'tracking_number', 'sender_name', 'receiver_name', 'package_status', 'hub_id', 'fleet_id')
            ->get();

        return response()->json($results->map(fn ($package) => [
            'id'              => $package->id,
            'tracking_number' => $package->tracking_number,
            'sender_name'     => $package->sender_name,
            'receiver_name'   => $package->receiver_name,
            'status'          => $package->package_status,
        ]));
    }

    /**
     * Update status/lokasi paket dari web form
     */
    public function updateStatus(Request $request, string $trackingNumber)
    {
        $validated = $request->validate([
            'status'      => 'required|in:registered,picked_up,in_transit,arrived_at_hub,out_for_delivery,delivered,failed,returned',
            'hub_id'      => 'nullable|exists:hubs,id',
            'fleet_id'    => 'nullable|exists:fleets,id',
            'notes'       => 'nullable|string',
            'recorded_at' => 'nullable|date',
        ]);

        try {
            $package = $this->packageRepo->findPackageByTrackingNumber($trackingNumber);

            if (in_array($package->package_status, ['delivered', 'failed', 'returned'])) {
                return back()->with('error', 'Paket sudah dalam status final, tidak bisa diperbarui lagi.');
            }

            $this->packageRepo->updatePackageStatus($trackingNumber, $validated);

            if ($validated['status'] === 'arrived_at_hub' && !empty($validated['hub_id'])) {
                Hub::where('id', $validated['hub_id'])->increment('current_load');
            }

            return back()->with('success', 'Status/lokasi paket berhasil diperbarui!');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return back()->with('error', 'Paket tidak ditemukan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui status: ' . $e->getMessage());
        }
    }
}
