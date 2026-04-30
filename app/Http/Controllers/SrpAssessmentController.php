<?php

namespace App\Http\Controllers;

use App\Services\SrpFarmerDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SrpAssessmentController extends Controller
{
    public function index(Request $request, SrpFarmerDirectoryService $farmerDirectory): View
    {
        $farmers = $this->applyListFilters($farmerDirectory->farmers(), $request);

        return view('admin.srp-farmers', [
            'farmers' => $farmers,
            'databaseAvailable' => $farmerDirectory->requiredTablesAvailable(),
            'missingTables' => $farmerDirectory->missingRequiredTables(),
            'summary' => $farmerDirectory->summary($farmers),
        ]);
    }

    public function passed(SrpFarmerDirectoryService $farmerDirectory): View
    {
        $farmers = $farmerDirectory->passedFarmers();

        return view('admin.srp-passed-farmers', [
            'farmers' => $farmers,
            'databaseAvailable' => $farmerDirectory->requiredTablesAvailable(),
            'missingTables' => $farmerDirectory->missingRequiredTables(),
            'summary' => $farmerDirectory->summary($farmers),
        ]);
    }

    public function show(string $farmer, SrpFarmerDirectoryService $farmerDirectory): View
    {
        $farmerData = $farmerDirectory->farmers()->firstWhere('slug', $farmer);

        abort_unless($farmerData, 404);

        return view('admin.srp-farmer-detail', [
            'farmer' => $farmerData,
            'databaseAvailable' => $farmerDirectory->requiredTablesAvailable(),
            'missingTables' => $farmerDirectory->missingRequiredTables(),
            'plotSummary' => [
                'total' => $farmerData['plot_count'],
                'with_activity' => collect($farmerData['plots'])->filter(fn (array $plot) => $plot['activity_count'] > 0)->count(),
                'average_progress' => $farmerData['average_progress'],
                'latest_activity_date' => $farmerData['last_activity_at'] ?: '-',
            ],
        ]);
    }

    private function applyListFilters(Collection $farmers, Request $request): Collection
    {
        if ($request->boolean('passed')) {
            $farmers = $farmers
                ->filter(fn (array $farmer): bool => (int) ($farmer['average_progress'] ?? 0) >= 100)
                ->values();
        }

        return $farmers;
    }
}
