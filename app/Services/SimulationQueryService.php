<?php

namespace App\Services;

use App\Trayectoria;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only query logic for trajectories (simulations).
 *
 * Ported from NewAdvancedSearchController and TrayectoriasController so it can be
 * reused by both HTTP controllers and the MCP server without rendering views.
 */
class SimulationQueryService
{
    /**
     * Sort keys accepted by advancedSearch(), mapped to SQL columns.
     *
     * @var array<string, string>
     */
    public const SORT_COLUMNS = [
        'id' => 'trajectories.id',
        'temperature' => 'trajectories.temperature',
        'length' => 'trajectories.trj_length',
        'area_per_lipid' => 'ta_sort.area_per_lipid',
        'op_quality_total' => 'ta_sort.op_quality_total',
        'ff_quality' => 'ta_sort.ff_quality',
    ];

    /**
     * Run an advanced trajectory search.
     *
     * @param  array<string, mixed>  $filters  Filter set using the same keys as the
     *                                          web advanced-search form (e.g. 'lipidos',
     *                                          'lipidos_operador', 'temperature-start', ...).
     * @return array{total:int, page:int, per_page:int, last_page:int, data:Collection}
     */
    public function advancedSearch(
        array $filters,
        string $sort = 'id',
        string $direction = 'asc',
        int $page = 1,
        int $perPage = 15
    ): array {
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
        $sortColumn = self::SORT_COLUMNS[$sort] ?? 'trajectories.id';
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        // NOTE: This materializes all matching trajectory IDs to compute $total and
        // feed the paged query's whereIn(). Acceptable because the trajectories table
        // is small (hundreds of rows) and the MCP endpoint is rate-limited. If the
        // dataset grows by orders of magnitude, switch to a COUNT(*) + LIMIT/OFFSET on
        // the filtered query to avoid loading every ID and a large WHERE IN (...).
        $ids = $this->getFilteredIds($filters);
        $total = $ids->count();

        $query = Trayectoria::with(['analisis', 'lipidos', 'iones', 'campo_de_fuerza', 'membrana'])
            ->withCount(['experimentsOP', 'experimentsFF'])
            ->whereIn('trajectories.id', $ids);

        if (str_starts_with($sortColumn, 'ta_sort.')) {
            $query->leftJoin('trajectories_analysis as ta_sort', 'trajectories.id', '=', 'ta_sort.trajectory_id')
                ->select('trajectories.*');
        }

        $query->orderByRaw("$sortColumn IS NULL, $sortColumn $direction");

        $results = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil(($total ?: 1) / $perPage),
            'data' => $results->map(fn (Trayectoria $t) => $this->summarize($t)),
        ];
    }

    /**
     * Rank simulations using the rank-product of OP and FF quality (lower = better).
     * Optionally restrict to simulations containing a given lipid molecule.
     *
     * @return array{total:int, data:Collection}
     */
    public function bestSimulations(?string $lipid = null, int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));

        $query = Trayectoria::with(['analisis', 'lipidos', 'campo_de_fuerza', 'membrana'])
            ->withCount(['experimentsOP', 'experimentsFF']);

        if ($lipid !== null && $lipid !== '') {
            $query->whereHas('lipidos', function ($q) use ($lipid) {
                $q->where('molecule', $lipid);
            });
        }

        $query->leftJoin('trajectories_analysis as ta_sort', 'trajectories.id', '=', 'ta_sort.trajectory_id')
            ->select('trajectories.*')
            ->orderByRaw('
                (CASE WHEN ta_sort.op_quality_total IS NULL
                      THEN (SELECT COUNT(*) FROM trajectories_analysis) + 1
                      ELSE (SELECT COUNT(*) FROM trajectories_analysis ta2
                            WHERE ta2.op_quality_total IS NOT NULL
                              AND ta2.op_quality_total > ta_sort.op_quality_total) + 1
                END)
                *
                (CASE WHEN ta_sort.ff_quality IS NULL
                      THEN (SELECT COUNT(*) FROM trajectories_analysis) + 1
                      ELSE (SELECT COUNT(*) FROM trajectories_analysis ta3
                            WHERE ta3.ff_quality IS NOT NULL
                              AND ta3.ff_quality > ta_sort.ff_quality) + 1
                END)
                ASC
            ');

        $results = $query->limit($limit)->get();

        return [
            'total' => $results->count(),
            'data' => $results->map(fn (Trayectoria $t) => $this->summarize($t)),
        ];
    }

    /**
     * Fetch a single trajectory with a full detail payload.
     *
     * @return array<string, mixed>|null
     */
    public function getById(int $id, bool $includePlotData = false): ?array
    {
        $trayectoria = Trayectoria::with([
            'analisis', 'lipidos', 'iones', 'campo_de_fuerza', 'membrana',
        ])->withCount(['experimentsOP', 'experimentsFF'])->find($id);

        if ($trayectoria === null) {
            return null;
        }

        $detail = $this->summarize($trayectoria);
        $detail['system'] = $trayectoria->system;
        $detail['publication'] = $trayectoria->publication;
        $detail['author'] = $trayectoria->author;
        $detail['software_name'] = $trayectoria->software_name;
        $detail['number_of_particles'] = $trayectoria->number_of_particles;
        $detail['pressure'] = $trayectoria->pressure;
        $detail['git_path'] = $trayectoria->git_path;
        $detail['url'] = url('/trajectories/'.$trayectoria->id);

        $analysis = $trayectoria->analisis;
        if ($includePlotData && $analysis !== null) {
            $detail['plot_data'] = [
                'area_per_lipid_data' => $this->decodeJson($analysis->area_per_lipid_data),
                'form_factor_data' => $this->decodeJson($analysis->form_factor_data),
            ];
        }

        return $detail;
    }

    /**
     * Reduce a trajectory model to a compact array suitable for MCP responses.
     *
     * @return array<string, mixed>
     */
    private function summarize(Trayectoria $t): array
    {
        $analysis = $t->analisis;

        return [
            'id' => $t->id,
            'name' => $t->displayName(),
            'temperature' => $t->temperature,
            'trj_length' => $t->trj_length,
            'doi' => $t->doi,
            'force_field' => $t->campo_de_fuerza?->name,
            'lipids' => $t->lipidos->pluck('molecule')->unique()->values()->all(),
            'ions' => $t->iones->pluck('molecule')->unique()->values()->all(),
            'quality' => [
                'op_quality_total' => $analysis?->op_quality_total,
                'op_quality_headgroups' => $analysis?->op_quality_headgroups,
                'op_quality_tails' => $analysis?->op_quality_tails,
                'ff_quality' => $analysis?->ff_quality,
                'area_per_lipid' => $analysis?->area_per_lipid,
                'bilayer_thickness' => $analysis?->bilayer_thickness,
            ],
            'experiments_op_count' => $t->experiments_op_count ?? null,
            'experiments_ff_count' => $t->experiments_ff_count ?? null,
        ];
    }

    private function decodeJson(?string $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * Build the filter query and return matching trajectory IDs.
     *
     * Ported from NewAdvancedSearchController::getFilteredIds. All user input is
     * passed through parameter binding.
     *
     * @param  array<string, mixed>  $inputs
     */
    public function getFilteredIds(array $inputs): Collection
    {
        $query = DB::table('trajectories')->select('trajectories.id')->distinct();

        if ($inputs['lipidos'] ?? false) {
            $query->leftJoin('trajectories_lipids as tl', 'trajectories.id', '=', 'tl.trajectory_id');
            $query->leftJoin('lipids as l', 'tl.lipid_id', '=', 'l.id');

            if (! empty($inputs['lipidos_operador'])) {
                $orLipids = [];
                $andLipids = [];
                $notLipids = [];
                $missingLipids = false;
                $notMissingLipids = false;
                foreach ($inputs['lipidos'] as $index => $lipid) {
                    $operator = $inputs['lipidos_operador'][$index] ?? 'and';
                    $isMissingToken = is_string($lipid) && strtolower(trim($lipid)) === 'is_missing';
                    if ($operator === 'or') {
                        $isMissingToken ? $missingLipids = true : $orLipids[] = $lipid;
                    } elseif ($operator === 'and') {
                        $isMissingToken ? $missingLipids = true : $andLipids[] = $lipid;
                    } elseif ($operator === 'not') {
                        $isMissingToken ? $notMissingLipids = true : $notLipids[] = $lipid;
                    }
                }
                if (! empty($orLipids)) {
                    if (empty($andLipids) && empty($notLipids)) {
                        if ($missingLipids) {
                            $query->where(function ($q) use ($orLipids) {
                                $q->whereIn('l.molecule', $orLipids)->orWhereNull('tl.trajectory_id');
                            });
                        } else {
                            $query->whereIn('l.molecule', $orLipids);
                        }
                    } else {
                        $query->groupBy('trajectories.id')
                            ->havingRaw('COUNT(DISTINCT CASE WHEN l.molecule IN ('.implode(',', array_fill(0, count($orLipids), '?')).
                                ') THEN l.molecule END) > 0', $orLipids);
                    }
                }
                if (! empty($andLipids)) {
                    $query->groupBy('trajectories.id')
                        ->havingRaw('COUNT(DISTINCT CASE WHEN l.molecule IN ('.implode(',', array_fill(0, count($andLipids), '?')).
                            ') THEN l.molecule END) = ?', array_merge($andLipids, [count($andLipids)]));
                }
                if (! empty($notLipids)) {
                    $query->groupBy('trajectories.id')
                        ->havingRaw('COUNT(DISTINCT CASE WHEN l.molecule IN ('.implode(',', array_fill(0, count($notLipids), '?')).
                            ') THEN l.molecule END) = 0', $notLipids);
                }
                if ($missingLipids && (! empty($andLipids) || ! empty($notLipids) || empty($orLipids))) {
                    $query->whereNull('tl.trajectory_id');
                }
                if ($notMissingLipids) {
                    $query->whereNotNull('tl.trajectory_id');
                }
            }
        }

        if (! empty($inputs['iones'] ?? false) || ! empty($inputs['iones_operador'] ?? false)) {
            $query->leftJoin('trajectories_ions as ti', 'trajectories.id', '=', 'ti.trajectory_id')
                ->leftJoin('ions as i', 'ti.ion_id', '=', 'i.id');

            if (! empty($inputs['iones_operador'] ?? false)) {
                $orIons = [];
                $andIons = [];
                $notIons = [];
                $missingIons = false;
                $notMissingIons = false;
                foreach ($inputs['iones_operador'] as $index => $operator) {
                    $ion = $inputs['iones'][$index] ?? null;
                    $isMissingToken = is_string($ion) && strtolower(trim($ion)) === 'is_missing';
                    if ($operator === 'or') {
                        if ($isMissingToken) {
                            $missingIons = true;
                        } elseif (! empty($ion)) {
                            $orIons[] = $ion;
                        }
                    } elseif ($operator === 'and') {
                        if ($isMissingToken) {
                            $missingIons = true;
                        } elseif (! empty($ion)) {
                            $andIons[] = $ion;
                        }
                    } elseif ($operator === 'not') {
                        if ($isMissingToken) {
                            $notMissingIons = true;
                        } elseif (! empty($ion)) {
                            $notIons[] = $ion;
                        }
                    }
                }
                if (! empty($orIons)) {
                    if (empty($andIons) && empty($notIons)) {
                        if ($missingIons) {
                            $query->where(function ($q) use ($orIons) {
                                $q->whereIn('i.molecule', $orIons)->orWhereNull('ti.trajectory_id');
                            });
                        } else {
                            $query->whereIn('i.molecule', $orIons);
                        }
                    } else {
                        $query->groupBy('trajectories.id')
                            ->havingRaw('COUNT(DISTINCT CASE WHEN i.molecule IN ('.implode(',', array_fill(0, count($orIons), '?')).
                                ') THEN i.molecule END) > 0', $orIons);
                    }
                }
                if (! empty($andIons)) {
                    $query->groupBy('trajectories.id')
                        ->havingRaw('COUNT(DISTINCT CASE WHEN i.molecule IN ('.implode(',', array_fill(0, count($andIons), '?')).
                            ') THEN i.molecule END) = ?', array_merge($andIons, [count($andIons)]));
                }
                if (! empty($notIons)) {
                    $query->groupBy('trajectories.id')
                        ->havingRaw('SUM(CASE WHEN i.molecule IN ('.implode(',', array_fill(0, count($notIons), '?')).
                            ') THEN 1 ELSE 0 END) = 0', $notIons);
                }
                if ($missingIons && (! empty($andIons) || ! empty($notIons) || empty($orIons))) {
                    $query->whereNull('ti.trajectory_id');
                }
                if ($notMissingIons) {
                    $query->whereNotNull('ti.trajectory_id');
                }
            }
        }

        if (! empty($inputs['trayectoria_force_field'])) {
            $query->join('forcefields as ff', 'trajectories.forcefield_id', '=', 'ff.id');
            $conditions = [];
            $bindings = [];
            foreach ($inputs['trayectoria_force_field'] as $index => $ff) {
                $operator = $inputs['trayectoria_force_field_operador'][$index] ?? 'equals';
                if ($operator === 'equals') {
                    $conditions[] = 'ff.name = ?';
                    $bindings[] = $ff;
                } elseif ($operator === 'contains') {
                    $conditions[] = 'ff.name LIKE ?';
                    $bindings[] = '%'.$ff.'%';
                } elseif ($operator === 'starts_with') {
                    $conditions[] = 'ff.name LIKE ?';
                    $bindings[] = $ff.'%';
                } elseif ($operator === 'ends_with') {
                    $conditions[] = 'ff.name LIKE ?';
                    $bindings[] = '%'.$ff;
                }
            }
            if (! empty($conditions)) {
                $query->whereRaw(implode(' OR ', $conditions), $bindings);
            }
        }

        if (! empty($inputs['temperature-start']) && ! empty($inputs['temperature-end'])) {
            $query->whereBetween('temperature', [$inputs['temperature-start'], $inputs['temperature-end']]);
        }

        $needsAnalysisJoin =
            (! empty($inputs['Area_per_lipid-start']) && ! empty($inputs['Area_per_lipid-end'])) ||
            (! empty($inputs['quality_total-start']) && ! empty($inputs['quality_total-end'])) ||
            (! empty($inputs['quality_hg-start']) && ! empty($inputs['quality_hg-end'])) ||
            (! empty($inputs['quality_tails-start']) && ! empty($inputs['quality_tails-end'])) ||
            (! empty($inputs['Bilayer_thickness-start']) && ! empty($inputs['Bilayer_thickness-end'])) ||
            (! empty($inputs['Form_factor_quality-start']) && ! empty($inputs['Form_factor_quality-end']));

        if ($needsAnalysisJoin) {
            $query->join('trajectories_analysis as ta', 'trajectories.id', '=', 'ta.trajectory_id');
        }

        if (! empty($inputs['Area_per_lipid-start']) && ! empty($inputs['Area_per_lipid-end'])) {
            $query->whereBetween('ta.area_per_lipid', [$inputs['Area_per_lipid-start'], $inputs['Area_per_lipid-end']]);
        }
        if (! empty($inputs['quality_total-start']) && ! empty($inputs['quality_total-end'])) {
            $query->whereBetween('ta.op_quality_total', [$inputs['quality_total-start'], $inputs['quality_total-end']]);
        }
        if (! empty($inputs['quality_hg-start']) && ! empty($inputs['quality_hg-end'])) {
            $query->whereBetween('ta.op_quality_headgroups', [$inputs['quality_hg-start'], $inputs['quality_hg-end']]);
        }
        if (! empty($inputs['quality_tails-start']) && ! empty($inputs['quality_tails-end'])) {
            $query->whereBetween('ta.op_quality_tails', [$inputs['quality_tails-start'], $inputs['quality_tails-end']]);
        }
        if (! empty($inputs['Bilayer_thickness-start']) && ! empty($inputs['Bilayer_thickness-end'])) {
            $query->whereBetween('ta.bilayer_thickness', [$inputs['Bilayer_thickness-start'], $inputs['Bilayer_thickness-end']]);
        }
        if (! empty($inputs['Form_factor_quality-start']) && ! empty($inputs['Form_factor_quality-end'])) {
            $query->whereBetween('ta.ff_quality', [$inputs['Form_factor_quality-start'], $inputs['Form_factor_quality-end']]);
        }

        if (! empty($inputs['trayectoria'] ?? false)) {
            $query->whereIn('trajectories.id', $inputs['trayectoria']);
        }
        if (! empty($inputs['membranas'] ?? false)) {
            $query->join('trajectories_membranes as tm', 'trajectories.id', '=', 'tm.trajectory_id')
                ->whereIn('tm.membrane_id', $inputs['membranas']);
        }

        return $query->get()->pluck('id');
    }
}
