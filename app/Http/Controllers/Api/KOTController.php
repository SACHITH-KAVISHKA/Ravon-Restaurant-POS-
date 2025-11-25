<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KOTService;
use App\Models\Kot;
use App\Models\KitchenStation;
use Illuminate\Http\Request;

class KOTController extends Controller
{
    public function __construct(
        private KOTService $kotService
    ) {}

    /**
     * Get all KOTs for a kitchen station
     */
    public function forStation(KitchenStation $station, Request $request)
    {
        $query = Kot::where('kitchen_station_id', $station->id)
            ->with(['order.table', 'items']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // By default, show pending and preparing
            $query->whereIn('status', ['pending', 'preparing']);
        }

        $kots = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $kots,
        ]);
    }

    /**
     * Get KOT details
     */
    public function show(Kot $kot)
    {
        $kot->load(['order.table.floor', 'order.waiter', 'kitchenStation', 'items']);

        return response()->json([
            'success' => true,
            'data' => $kot,
        ]);
    }

    /**
     * Update KOT status
     */
    public function updateStatus(Request $request, Kot $kot)
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,ready,completed',
        ]);

        $kot = $this->kotService->updateKOTStatus($kot, $request->status);

        return response()->json([
            'success' => true,
            'message' => 'KOT status updated',
            'data' => $kot,
        ]);
    }

    /**
     * Print KOT
     */
    public function print(Kot $kot)
    {
        $result = $this->kotService->printKOT($kot);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'KOT printed successfully' : 'Failed to print KOT',
        ]);
    }

    /**
     * Reprint KOT
     */
    public function reprint(Kot $kot)
    {
        $result = $this->kotService->reprintKOT($kot);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'KOT reprinted successfully' : 'Failed to reprint KOT',
        ]);
    }

    /**
     * Get pending KOTs summary
     */
    public function pending()
    {
        $pendingKOTs = Kot::where('status', 'pending')
            ->with(['order.table', 'kitchenStation'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pendingKOTs,
            'count' => $pendingKOTs->count(),
        ]);
    }

    /**
     * Get all kitchen stations
     */
    public function stations()
    {
        $stations = KitchenStation::active()->get();

        return response()->json([
            'success' => true,
            'data' => $stations,
        ]);
    }
}
