<?php

namespace App\Services;

use App\Experiments;
use App\Membrana;
use App\Trayectoria;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregate statistics for the database.
 *
 * Ported from StatisticsController so it can be reused by the MCP server without
 * rendering a view.
 */
class StatisticsService
{
    /**
     * Cached high-level totals for the database.
     *
     * @return array<string, mixed>
     */
    public function totals(): array
    {
        $totals = Cache::remember('statistics.totals', now()->addHours(6), function () {
            return [
                'totalTrayectorias' => Trayectoria::select('id')->count(),
                'totalMembranas' => Membrana::select('id')->count(),
                'totalExperiments' => Experiments::select('id')->count(),
                'lastUpdate' => DB::table('update_record')->latest('updated_at')->first(),
            ];
        });

        $lastUpdate = $totals['lastUpdate'];
        $lastUpdatedAt = null;
        if ($lastUpdate && $lastUpdate->updated_at) {
            $lastUpdatedAt = Carbon::parse($lastUpdate->updated_at, 'UTC')
                ->setTimezone(config('app.timezone'))
                ->toIso8601String();
        }

        return [
            'total_trajectories' => $totals['totalTrayectorias'],
            'total_membranes' => $totals['totalMembranas'],
            'total_experiments' => $totals['totalExperiments'],
            'last_update' => $lastUpdatedAt,
        ];
    }

    /**
     * Force-field breakdown: number of membranes per force field.
     *
     * @return array<int, array{name:string, total:int}>
     */
    public function forceFieldBreakdown(): array
    {
        return Membrana::groupBy('forcefields.name')
            ->join('forcefields', 'membranes.forcefield_id', '=', 'forcefields.id')
            ->select('forcefields.name', DB::raw('count(*) as total'))
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'total' => (int) $row->total])
            ->all();
    }
}
