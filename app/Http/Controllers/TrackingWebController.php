<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ShipmentLogRepositoryInterface;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingWebController extends Controller
{
    protected ShipmentLogRepositoryInterface $logRepo;

    public function __construct(ShipmentLogRepositoryInterface $logRepo)
    {
        $this->logRepo = $logRepo;
    }

    /**
     * Dashboard tracking - tampilkan statistik dan list paket
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        try {
            $packages = $this->logRepo->getAllPackages($search, $status);

            // Statistik dari packages.package_status (bukan shipments)
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
                [CacheService::TAG_STATS, CacheService::TAG_SHIPMENT]
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
            $package   = $this->logRepo->findPackageByTrackingNumber($trackingNumber);
            $logs      = $this->logRepo->getLogsByPackage($package->id);
            $latestLog = $logs->last();

            return view('tracking.show', compact('package', 'logs', 'latestLog'));
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
            $package = $this->logRepo->findPackageByTrackingNumber($trackingNumber);
            $logs    = $this->logRepo->getLogsByPackage($package->id);

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
            $results = $this->logRepo->searchByTracking($keyword);
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
            ->with('latestLog')
            ->limit(10)
            ->select('id', 'tracking_number', 'sender_name', 'receiver_name', 'package_status')
            ->get();

        return response()->json($results->map(fn ($package) => [
            'id'              => $package->id,
            'tracking_number' => $package->tracking_number,
            'sender_name'     => $package->sender_name,
            'receiver_name'   => $package->receiver_name,
            'status'          => $package->package_status,
        ]));
    }
}
