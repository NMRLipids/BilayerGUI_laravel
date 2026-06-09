<?php

namespace App\Http\Controllers;
use App\Exports\NewAdvancedSearchExport;
use App\Exports\NewAdvancedSearchCompareExport;
use App\Filtros\Filtro;
use App\Filtros\Filtros;
use App\Trayectoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use Maatwebsite\Excel\Facades\Excel;



class NewAdvancedSearchController extends Controller
{

  private $query = null; // Allow the query builder to be accessed across methods 

  /*
    * Validates the advanced search request parameters.
    * Ensures that all expected parameters are present and correctly formatted.
    * Also checks for unexpected parameters to prevent potential issues.
    *
    * @param Request $request The incoming HTTP request containing search parameters
    * @return array The validated and sanitized input parameters
    * @throws ValidationException If validation fails, an exception is thrown with details about the errors
    */

  private $filters = [];

  private function validateAdvancedSearchRequest(Request $request): array
  {
    $inputs = $request->all();
    $rules = [
      // Range slider fields
      'temperature-start' => 'nullable|numeric',
      'temperature-end' => 'nullable|numeric',
      'Area_per_lipid-start' => 'nullable|numeric',
      'Area_per_lipid-end' => 'nullable|numeric',
      'quality_total-start' => 'nullable|numeric',
      'quality_total-end' => 'nullable|numeric',
      'quality_hg-start' => 'nullable|numeric',
      'quality_hg-end' => 'nullable|numeric',
      'quality_tails-start' => 'nullable|numeric',
      'quality_tails-end' => 'nullable|numeric',
      'Bilayer_thickness-start' => 'nullable|numeric',
      'Bilayer_thickness-end' => 'nullable|numeric',
      'Form_factor_quality-start' => 'nullable|numeric',
      'Form_factor_quality-end' => 'nullable|numeric',

      // Filter selects
      'lipidos' => 'array',
      'lipidos.*' => 'nullable|string|max:255',
      'lipidos_operador' => 'array',
      'lipidos_operador.*' => 'in:and,or,not',

      'iones' => 'array',
      'iones.*' => 'nullable|string|max:255',
      'iones_operador' => 'array',
      'iones_operador.*' => 'in:and,or,not',

      'membranas' => 'array',
      'membranas.*' => 'nullable|integer',
      'membranas_operador' => 'array',
      'membranas_operador.*' => 'in:and,or,not',

      'trayectoria' => 'array',
      'trayectoria.*' => 'nullable|integer',
      'trayectoria_operador' => 'array',
      'trayectoria_operador.*' => 'in:and,or,not',

      'trayectoria_force_field' => 'array',
      'trayectoria_force_field.*' => 'nullable|string|max:255',
      'trayectoria_force_field_operador' => 'array',
      'trayectoria_force_field_operador.*' => 'in:equals,contains,starts_with,ends_with',

      // Misc
      'nothinghere' => 'nullable|in:1',
      'page' => 'nullable|integer|min:1',
      'selected' => 'nullable|integer|in:1',
      'embed' => 'nullable|boolean',
      'sort' => 'nullable|string|in:id,temperature,length,area_per_lipid,op_quality_total,ff_quality',
      'direction' => 'nullable|string|in:asc,desc',
    ];

    $allowedKeys = array_filter(array_keys($rules), function ($key) {
      return !str_ends_with($key, '.*');
    });

    $validator = Validator::make($inputs, $rules);
    $validator->after(function ($validator) use ($inputs, $allowedKeys) {
      $unknownKeys = array_diff(array_keys($inputs), $allowedKeys);
      if (!empty($unknownKeys)) {
        $validator->errors()->add('params', 'Unexpected parameters: ' . implode(', ', $unknownKeys));
      }
    });

    $validator->validate();

    // Build a human-readable list of active filters for display in the results page
    foreach ($inputs as $key => $value) {
      if (str_ends_with($key, '-start') || str_ends_with($key, '-end')) {
        $this->filters[] = ucfirst(str_replace('_', ' ', str_replace(['-start', '-end'], '', $key)));
        
      }
      if (in_array($key, ['lipidos', 'iones', 'membranas'])) {
        $this->filters[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . implode(', ', (array)$value);
      }
      if (in_array($key, ['trayectoria_force_field'])) {
        $this->filters[] = 'Force Field: ' . implode(', ', (array)$value);
      } 
    }
    $this->filters = array_unique($this->filters);


    return $inputs;
  }

  /**
   * Build the filter query and return matching trajectory IDs.
   * This is lightweight — only integer IDs are fetched.
   */
  private function getFilteredIds(Request $request): \Illuminate\Support\Collection
  {
     
    $inputs = $this->validateAdvancedSearchRequest($request);

    if (config('app.debug') && app()->environment('local')) {
      error_log('Debug: Validated inputs: ' . json_encode($inputs)); // Debug line to check validated inputs
    }
    
    // Rebuild the SQL query using the facade
    // This is a complex query builder that dynamically constructs SQL based on the filters provided in the request. 
    // It handles various conditions, including AND/OR logic and NOT conditions, and also incorporates analytics filters. 
    // The final result is a list of trajectory IDs that match the specified criteria, 
    // which are then used to fetch the corresponding trajectory data from the database.

    // We start with a base query and then dynamically build the WHERE clause and JOINs.
    $this->query = DB::table('trajectories')->select('trajectories.id')->distinct();
    
    // This is a complex query builder that dynamically constructs SQL based on the filters provided in the request.
    // We will add joins only for the filters that are present in the request to optimize the query.
    // Allowing arbitray filters is powerful but can lead to SQL injection if not handled properly. Therefore
    // we will use parameter binding for all user inputs to ensure that the query is safe. 
    
    if ($inputs['lipidos'] ?? false) {
      $this->query->leftJoin('trajectories_lipids as tl', 'trajectories.id', '=', 'tl.trajectory_id');
      $this->query->leftJoin('lipids as l', 'tl.lipid_id', '=', 'l.id'); // Join with lipids for lipid-based filtering, almost always needed, 

      // If all lipids are selected with AND or OR, we can use a single join with 
      // GROUP BY and HAVING to filter trajectories that have all selected lipids.    
      // CHECK if all lipids are selected with the same operator
      if (!empty($inputs['lipidos_operador'])) {
          // Mixed operators, we need to handle AND and OR separately
          $orLipids = [];
          $andLipids = [];
          $notLipids = [];
          $missingLipids = false;
          $notMissingLipids = false;
          // sort the lipids into their respective operator groups, using AND as default if no operator is specified
          foreach ($inputs['lipidos'] as $index => $lipid) {
            $operator = $inputs['lipidos_operador'][$index] ?? 'and';
            $isMissingToken = is_string($lipid) && strtolower(trim($lipid)) === 'is_missing';
            if ($operator === 'or') {
              if ($isMissingToken) {
                $missingLipids = true;
              } else {
                $orLipids[] = $lipid;
              }
            } else if ($operator === 'and') {
              if ($isMissingToken) {
                $missingLipids = true;
              } else {
                $andLipids[] = $lipid;
              }
            } else if ($operator === 'not') {
              if ($isMissingToken) {
                $notMissingLipids = true;
              } else {
                $notLipids[] = $lipid;
              }
            }
          }
          // Handle OR lipids with simple WHERE IN
          if (!empty($orLipids)) {
            if (empty($andLipids) && empty($notLipids)) {
              // If only OR lipids are present, 
              // we can use a simple WHERE IN without GROUP BY
              if ($missingLipids) {
                $this->query->where(function ($q) use ($orLipids) {
                  $q->whereIn('l.molecule', $orLipids)
                    ->orWhereNull('tl.trajectory_id');
                });
              } else {
                $this->query->whereIn('l.molecule', $orLipids);
              }
            } else {
              // If there are also AND/NOT lipids, we need to include all OR lipids in the GROUP BY query to ensure correct results
              $andNotLipids = array_merge($andLipids, $notLipids);
              $this->query->groupBy('trajectories.id')
                          ->havingRaw('COUNT(DISTINCT CASE WHEN l.molecule IN (' . implode(',', array_fill(0, count($orLipids), '?')) .
                           ') THEN l.molecule END) > 0', $orLipids);
            }
          }
          // Handle AND lipids with GROUP BY and HAVING
          // For AND logic, we need to ensure that the trajectory has all selected lipids.
          // We can achieve this by counting the distinct lipids that match and comparing it to the number of AND lipids selected.
          if (!empty($andLipids)) {
            $this->query->groupBy('trajectories.id')
                        ->havingRaw('COUNT(DISTINCT CASE WHEN l.molecule IN (' . implode(',', array_fill(0, count($andLipids), '?')) .
                         ') THEN l.molecule END) = ?', array_merge($andLipids, [count($andLipids)]));
          }
          // Handle (AND) NOT lipids with HAVING to exclude trajectories that have any of the NOT lipids
          if (!empty($notLipids)) {
            $this->query->groupBy('trajectories.id')
                        ->havingRaw('COUNT(DISTINCT CASE WHEN l.molecule IN (' . implode(',', array_fill(0, count($notLipids), '?')) .
                         ') THEN l.molecule END) = 0', $notLipids);
          }
          if ($missingLipids && (!empty($andLipids) || !empty($notLipids) || empty($orLipids))) {
            $this->query->whereNull('tl.trajectory_id');
          }
          if ($notMissingLipids) {
            $this->query->whereNotNull('tl.trajectory_id');
          }
          
      }            
    }
    if (!empty($inputs['iones'] ?? false) || !empty($inputs['iones_operador'] ?? false)) {
      $this->query->leftJoin('trajectories_ions as ti', 'trajectories.id', '=', 'ti.trajectory_id')
                  ->leftJoin('ions as i', 'ti.ion_id', '=', 'i.id');

      if(!empty($inputs['iones_operador'] ?? false)) {
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
            } else if (!empty($ion)) {
              $orIons[] = $ion;
            }
          } else if ($operator === 'and') {
            if ($isMissingToken) {
              $missingIons = true;
            } else if (!empty($ion)) {
              $andIons[] = $ion;
            }
          } else if ($operator === 'not') {
            if ($isMissingToken) {
              $notMissingIons = true;
            } else if (!empty($ion)) {
              $notIons[] = $ion;
            }
          }
        }
        if (!empty($orIons)) {
          if (empty($andIons) && empty($notIons)) {
            // If only OR ions are present, we can use a simple WHERE IN without GROUP BY
            if ($missingIons) {
              $this->query->where(function ($q) use ($orIons) {
                $q->whereIn('i.molecule', $orIons)
                  ->orWhereNull('ti.trajectory_id');
              });
            } else {
              $this->query->whereIn('i.molecule', $orIons);
            }
          } else {
            // If there are also AND/NOT ions, we need to include all OR ions in the GROUP BY query to ensure correct results
            $andNotIons = array_merge($andIons, $notIons);
            $this->query->groupBy('trajectories.id')
                        ->havingRaw('COUNT(DISTINCT CASE WHEN i.molecule IN (' . implode(',', array_fill(0, count($orIons), '?')) .
                         ') THEN i.molecule END) > 0', $orIons);
          }
        }
        if (!empty($andIons)) {
          $this->query->groupBy('trajectories.id')
                      ->havingRaw('COUNT(DISTINCT CASE WHEN i.molecule IN (' . implode(',', array_fill(0, count($andIons), '?')) .
                       ') THEN i.molecule END) = ?', array_merge($andIons, [count($andIons)]));
        }
        if (!empty($notIons)) {
          $this->query->groupBy('trajectories.id')
                      ->havingRaw('SUM(CASE WHEN i.molecule IN (' . implode(',', array_fill(0, count($notIons), '?')) .
                       ') THEN 1 ELSE 0 END) = 0', $notIons);
        }
        if ($missingIons && (!empty($andIons) || !empty($notIons) || empty($orIons))) {
          $this->query->whereNull('ti.trajectory_id');
        }
        if ($notMissingIons) {
          $this->query->whereNotNull('ti.trajectory_id');
        }
      }
    }

    if (!empty($inputs['trayectoria_force_field'])) {
      $this->query->join('forcefields as ff', 'trajectories.forcefield_id', '=', 'ff.id');
      $conditions = [];
      $bindings = [];
      foreach ($inputs['trayectoria_force_field'] as $index => $ff) {
        $operator = $inputs['trayectoria_force_field_operador'][$index] ?? 'equals';
        if ($operator === 'equals') {
          $conditions[] = 'ff.name = ?';
          $bindings[] = $ff;
        } else if ($operator === 'contains') {
          $conditions[] = 'ff.name LIKE ?';
          $bindings[] = '%' . $ff . '%';
        } else if ($operator === 'starts_with') {
          $conditions[] = 'ff.name LIKE ?';
          $bindings[] = $ff . '%';
        } else if ($operator === 'ends_with') {
          $conditions[] = 'ff.name LIKE ?';
          $bindings[] = '%' . $ff;
        }
      }
      if (!empty($conditions)) {
        $this->query->whereRaw(implode(' OR ', $conditions), $bindings);
      }

    }
    // Additional filters for numeric ranges and other properties
    if (!empty($inputs['temperature-start']) && !empty($inputs['temperature-end'])) {
      $this->query->whereBetween('temperature', [$inputs['temperature-start'], $inputs['temperature-end']]);
    }

    // Join trajectories_analysis once if any analysis filter is active
    $needsAnalysisJoin = 
      (!empty($inputs['Area_per_lipid-start']) && !empty($inputs['Area_per_lipid-end'])) ||
      (!empty($inputs['quality_total-start']) && !empty($inputs['quality_total-end'])) ||
      (!empty($inputs['quality_hg-start']) && !empty($inputs['quality_hg-end'])) ||
      (!empty($inputs['quality_tails-start']) && !empty($inputs['quality_tails-end'])) ||
      (!empty($inputs['Bilayer_thickness-start']) && !empty($inputs['Bilayer_thickness-end'])) ||
      (!empty($inputs['Form_factor_quality-start']) && !empty($inputs['Form_factor_quality-end']));

    if ($needsAnalysisJoin) {
      $this->query->join('trajectories_analysis as ta', 'trajectories.id', '=', 'ta.trajectory_id');
    }

    if (!empty($inputs['Area_per_lipid-start']) && !empty($inputs['Area_per_lipid-end'])) {
      $this->query->whereBetween('ta.area_per_lipid', [$inputs['Area_per_lipid-start'], $inputs['Area_per_lipid-end']]);
    }
    if (!empty($inputs['quality_total-start']) && !empty($inputs['quality_total-end'])) {
      $this->query->whereBetween('ta.op_quality_total', [$inputs['quality_total-start'], $inputs['quality_total-end']]);
    }
    if (!empty($inputs['quality_hg-start']) && !empty($inputs['quality_hg-end'])) {
      $this->query->whereBetween('ta.op_quality_headgroups', [$inputs['quality_hg-start'], $inputs['quality_hg-end']]);
    }
    if (!empty($inputs['quality_tails-start']) && !empty($inputs['quality_tails-end'])) {
      $this->query->whereBetween('ta.op_quality_tails', [$inputs['quality_tails-start'], $inputs['quality_tails-end']]);
    }
    if (!empty($inputs['Bilayer_thickness-start']) && !empty($inputs['Bilayer_thickness-end'])) {
      $this->query->whereBetween('ta.bilayer_thickness', [$inputs['Bilayer_thickness-start'], $inputs['Bilayer_thickness-end']]);
    }
    if (!empty($inputs['Form_factor_quality-start']) && !empty($inputs['Form_factor_quality-end'])) {
      $this->query->whereBetween('ta.ff_quality', [$inputs['Form_factor_quality-start'], $inputs['Form_factor_quality-end']]);
    }

    if( !empty($inputs['trayectoria'] ?? false)) {
      $this->query->whereIn('trajectories.id', $inputs['trayectoria']);
    }
    if ( !empty($inputs['membranas'] ?? false)) {
      $this->query->join('trajectories_membranes as tm', 'trajectories.id', '=', 'tm.trajectory_id')
                  ->whereIn('tm.membrane_id', $inputs['membranas']);
    }

    if (config('app.debug') && app()->environment('local')) {
      // Debug: Print interpolated query
      $sql = $this->query->toSql();
      $bindings = $this->query->getBindings();
      $interpolatedQuery = $sql;
      foreach ($bindings as $binding) {
        $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
        $interpolatedQuery = preg_replace('/\?/', $value, $interpolatedQuery, 1);
      }
      error_log("\n\n=== DEBUG: Filter Query ===\n");
      error_log($interpolatedQuery . "\n");
      error_log("=================================\n\n");
    }

    return $this->query->get()->pluck('id');
  }

  /**
   * Paginated results with SQL-level sorting.
   * Only hydrates the 15 Eloquent models needed for the current page.
   */
  function results(Request $request)
  {
    $ids = $this->getFilteredIds($request);
    $total = $ids->count();

    $sortBy = $request->input('sort', 'id');
    $direction = $request->input('direction', 'asc');
    $page = $request->input('page', 1);
    $perPage = 15;

    // Map user-facing sort keys to actual SQL columns
    $sortColumnMap = [
      'id'               => 'trajectories.id',
      'temperature'      => 'trajectories.temperature',
      'length'           => 'trajectories.trj_length',
      'area_per_lipid'   => 'ta_sort.area_per_lipid',
      'op_quality_total' => 'ta_sort.op_quality_total',
      'ff_quality'       => 'ta_sort.ff_quality',
    ];

    $sortColumn = $sortColumnMap[$sortBy] ?? 'trajectories.id';

    // Build Eloquent query for just this page
    $query = Trayectoria::with(['analisis', 'lipidos', 'iones', 'campo_de_fuerza'])
      ->withCount(['experimentsOP', 'experimentsFF'])
      ->whereIn('trajectories.id', $ids);

    // Join trajectories_analysis for sorting when the sort column lives there
    if (str_starts_with($sortColumn, 'ta_sort.')) {
      $query->leftJoin('trajectories_analysis as ta_sort', 'trajectories.id', '=', 'ta_sort.trajectory_id')
            ->select('trajectories.*'); // prevent extra columns from overwriting model attributes
    }

    // Null-last ordering: IS NULL puts nulls at the bottom regardless of direction
    $query->orderByRaw("$sortColumn IS NULL, $sortColumn $direction");

    $trayectorias = $query
      ->offset(($page - 1) * $perPage)
      ->limit($perPage)
      ->get();

    $allTrayectorias = new LengthAwarePaginator($trayectorias, $total, $perPage, $page);
    $allTrayectorias->setPath(Paginator::resolveCurrentPath());

    return view('new_advanced_search.results', [
      'trayectorias' => $allTrayectorias,
      'sort' => $sortBy,
      'direction' => $direction,
      'filters' => $this->filters,
    ]);
  }

  /**
   * Export all matching results (no pagination).
   */
  function resultsExport(Request $request)
  {

    $ids = $this->getFilteredIds($request);
    $allTrayectorias = Trayectoria::with(['analisis', 'lipidos', 'iones', 'campo_de_fuerza'])
      ->withCount(['experimentsOP', 'experimentsFF'])
      ->whereIn('id', $ids)
      ->get();

    $filtroSelect = false;
    if ($request->input('selected') == 1) {
      $filtroSelect = true;
    }

    foreach ($allTrayectorias as $trayectoria) {
      foreach ($trayectoria->groupBy('id') as $key) {
        $tempData = array();
        foreach ($key as $key2 => $value2) {


          if (($filtroSelect && (session("CompareID" . $value2->id)) == 1) or ($filtroSelect == false)) {

            $trayectoriaTratada = [
              'trajectories.id' => $value2->id,
              'trajectories.force_field' => $value2->ff_name,
              'trajectories.op_quality_total' => $value2->op_quality_total,
              'trajectories.ff_quality' => $value2->ff_quality,
              'trajectories.length' => $value2->trj_length,
             
              'trajectories.temperature' => $value2->temperature,
             
              'trajectories.number_of_particles' => $value2->number_of_atoms,
              'trajectories.software_name' => $value2->software,
             
              'lipids.short_name' => $value2->lipid_name,
              'lipids.leaflet_1' => $value2->leaflet_1,
              'lipids.leaflet_2' => $value2->leaflet_2,
             
              'ions.short_name' => $value2->ion_short_name,
             
            ];

            $trayectoriasTratadas[] = $trayectoriaTratada;
          }

        }
      }
    }

    if (!isset($trayectoriasTratadas)) die('Please select some records to export');

    return Excel::download(new NewAdvancedSearchExport($trayectoriasTratadas), 'nmr_databank_export1.csv');
  }


  // Datos para crear  el formulario
  public function form(Request $request)
  {
    $filtros = array_merge(Filtros::filtrosEntidades(), Filtros::filtrosTrayectoria());
    $filtrosPrincipales = Filtros::filtrosEntidades();
    $filtroTrayectoria = Filtros::get('trayectoria');
    $filtrosTrayectorias = Filtros::filtrosTrayectoria();
    
    $QualityFactor = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(op_quality_total) AS quality_totalStart, MAX(op_quality_total) AS quality_totalEnd'))->get();
    $Quality_HG = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(op_quality_headgroups) AS quality_hgStart, MAX(op_quality_headgroups) AS quality_hgEnd'))->get();
    $Quality_Tails = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(op_quality_tails) AS quality_tailsStart, MAX(op_quality_tails) AS quality_tailsEnd'))->get();


    $Area_per_lipid = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(area_per_lipid) AS Area_per_lipidStart, MAX(area_per_lipid) AS Area_per_lipidEnd'))->get();

    $temperature = DB::table('trajectories')
        ->select(DB::raw('MIN(temperature) AS temperatureStart, MAX(temperature) AS temperatureEnd'))->get();

    $Form_factor_quality = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(ff_quality) AS Form_factor_qualityStart, MAX(ff_quality) AS Form_factor_qualityEnd'))
      ->whereNotNull('ff_quality')
      ->get();

    $Bilayer_thickness = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(bilayer_thickness) AS Bilayer_thicknessStart, MAX(bilayer_thickness) AS Bilayer_thicknessEnd'))->get();


    return view('new_advanced_search.form', [
      'filtros_principales' => $filtrosPrincipales,
      'filtro_trayectoria' => $filtroTrayectoria,
      'filtros_trayectorias' => $filtrosTrayectorias,
      'filtros_posibles' => $filtros,
      'Area_per_lipid' => $Area_per_lipid,
      'QualityFactor' => $QualityFactor,
      'Quality_HG' => $Quality_HG,
      'Quality_Tails' => $Quality_Tails,
      'temperature'=> $temperature,
      'Form_factor_quality' => $Form_factor_quality,
      'Bilayer_thickness' => $Bilayer_thickness,

    ]);
  }

  // Esto genera una vista con las trayectorias para ser comparadas
  public function compare(Request $request)
  {
    $data = session()->all();
    $listIDs = array();
   
    foreach ($data as $key => $value) {
      if (gettype($value) != 'array' && strpos($key, 'CompareID') !== false) {

        if ($value == "1") {
          $listIDs[] = substr($key, 9);
        }
      
      }
    }

    $ResultadoDB = null;

    if (count($listIDs) > 0) {
      $ResultadoDB = DB::table('trajectories_analysis')
        ->join('trajectories_analysis_lipids', 'trajectories_analysis.trajectory_id', '=', 'trajectories_analysis_lipids.trajectory_id')
        ->join('lipids', 'trajectories_analysis_lipids.lipid_id', '=', 'lipids.id')
        ->join('lipids_forcefields', 'lipids.id', '=', 'lipids_forcefields.lipid_id')
        ->join('forcefields', 'lipids_forcefields.forcefield_id', '=', 'forcefields.id')
        ->join('trajectories', 'trajectories.id', '=', 'trajectories_analysis.trajectory_id')
        ->select('trajectories.temperature as temperature', 'trajectories_analysis.*', 'trajectories_analysis_lipids.*', 'lipids.name as lipid_name', 'lipids.molecule', 'forcefields.name as name')
        ->whereIn('trajectories_analysis.trajectory_id', $listIDs)->get();
    }
    
    return view('new_advanced_search.compare', [
      'datos' => $ResultadoDB,
    ]);
  }


  public function updatecompare(Request $request)
  {
    //session_start();

    $response = collect($request);

    foreach ($response as $key => $value) {
     
      session([$key => $value]);
    }

    return view('new_advanced_search.updatecompare', [
      'respuesta' => $response
    ]);
  }

  public function exportarcompare(Request $request)
  {
    $data = session()->all();
    $listIDs = array();


    foreach ($data as $key => $value) {
      if (gettype($value) != 'array' && strpos($key, 'CompareID') !== false) {

        if ($value == "1") {
          $listIDs[] = substr($key, 9);
        }
      }
    }
    $ResultadoDB = null;
    if (count($listIDs) > 0) {
      $ResultadoDB = DB::table('trajectories_analysis')->whereIn('trajectory_id', $listIDs)->get();
    }

    foreach ($ResultadoDB as $resultado) {

      $comparacion = [
        'trajectory_id' => $resultado->trajectory_id,
        'Bilayer_thickness' => $resultado->Bilayer_thickness,
        'Bilayer_thickness_std' => $resultado->Bilayer_thickness_std,
        'Tilt' => $resultado->Tilt,
        'Tilt_std' => $resultado->Tilt_std,
        'COG_BB_first' => $resultado->COG_BB_first,
        'COG_BB_first_std' => $resultado->COG_BB_first_std,
        'COG_BB_last' => $resultado->COG_BB_last,
        'COG_BB_last_std' => $resultado->COG_BB_last_std,
        'COG_of_membrane' => $resultado->COG_of_membrane,
        'COG_of_membrane_std' => $resultado->COG_of_membrane_std,

        'COG_headgroups_upper_leaflet' => $resultado->COG_headgroups_upper_leaflet,
        'COG_headgroups_upper_leaflet_std' => $resultado->COG_headgroups_upper_leaflet_std,
        'COG_headgroups_lower_leaflet' => $resultado->COG_headgroups_lower_leaflet,
        'COG_headgroups_lower_leaflet_std' => $resultado->COG_headgroups_lower_leaflet_std,


        'Area_per_lipid' => $resultado->Area_per_lipid,
        'Area_per_lipid_std' => $resultado->Area_per_lipid_std,

        'Area_per_lipid_upper_leaflet' => $resultado->Area_per_lipid_upper_leaflet,
        'Area_per_lipid_upper_leaflet_std' => $resultado->Area_per_lipid_upper_leaflet_std,
        'Area_per_lipid_lower_leaflet' => $resultado->Area_per_lipid_lower_leaflet,
        'Area_per_lipid_lower_leaflet_std' => $resultado->Area_per_lipid_lower_leaflet_std,
      ];

      $comparaciones[] = $comparacion;
    }

    return Excel::download(new  NewAdvancedSearchCompareExport($comparaciones), 'NMR_export_compare.csv');
  }

  public function export(Request $request)
  {
    $trayectorias = $this->getTrayectoriasConFiltroAplicados($request);

    foreach ($trayectorias as $k => $trayectoria) {
      $trayectorias[$k]['max_elementos'] = max(
        count($trayectoria['lipidos']),
        count($trayectoria['iones']),
        count($trayectoria['modelos_acuaticos']),
        count($trayectoria['membranas']),
      );
    }

    $trayectoriasTratadas = [];
    foreach ($trayectorias as $trayectoria) {
      for ($i = 0; $i < $trayectoria['max_elementos']; $i++) {

        $trayectoriaTratada = [
          'trajectories.id' => $trayectoria['id'],
          'trajectories.force_field' => $trayectoria['force_field'],
          'trajectories.resolution' => $trayectoria['resolution'],
          'trajectories.membrane_model' => $trayectoria['membrane_model'],
          'trajectories.length' => $trayectoria['length'],
          'trajectories.electric_field' => $trayectoria['electric_field'],
          'trajectories.temperature' => $trayectoria['temperature'],
          'trajectories.pressure' => $trayectoria['pressure'],
          'trajectories.number_of_particles' => $trayectoria['number_of_particles'],
          'trajectories.software_name' => $trayectoria['software_name'],
          'trajectories.supercomputer' => $trayectoria['supercomputer'],
          'trajectories.performance' => $trayectoria['performance'],
          'lipids.short_name' => null,
          'lipids.leaflet_1' => null,
          'lipids.leaflet_2' => null,
         
          'ions.short_name' => null,
          
          'membranes.name' => null,
        ];

        if (!empty($trayectoria['lipidos'][$i])) {
          $trayectoriaTratada['lipids.short_name'] = $trayectoria['lipidos'][$i]['short_name'];
          $trayectoriaTratada['lipids.leaflet_1'] = $trayectoria['lipidos'][$i]['leaflet_1'];
          $trayectoriaTratada['lipids.leaflet_2'] = $trayectoria['lipidos'][$i]['leaflet_2'];
        }
        if (!empty($trayectoria['iones'][$i])) {
          $trayectoriaTratada['ions.short_name'] = $trayectoria['iones'][$i]['short_name'];
          //      $trayectoriaTratada['ions.bulk'] = $trayectoria['iones'][$i]['bulk'];
        }
        

       
        $trayectoriasTratadas[] = $trayectoriaTratada;
      }
    }

    return Excel::download(new AdvancedSearchExport($trayectoriasTratadas), 'trajectory_export.csv');
  }



  private function aplicarFiltros(Request $request, Builder &$builder)
  {

    $filtros = Filtros::all();
    $datosFomulario = $request->all();

    $filtrosAplicados = [];
    foreach ($datosFomulario as $codigoFiltro => $valor) {
      if (strpos($valor, 'and') !== false) {
        // Do nothing
      } else {
        if (!empty($valor) && array_key_exists($codigoFiltro, $filtros)) {
          $operador = !empty($datosFomulario[$codigoFiltro . '_operador']) ? $datosFomulario[$codigoFiltro . '_operador'] : null;
          $filtros[$codigoFiltro]->aplicarFiltro($builder, $valor, $operador);
          $filtrosAplicados[$codigoFiltro] = $filtros[$codigoFiltro];
          $filtrosAplicados[$codigoFiltro]->valor = $valor;
        }
      }
    }

   
    return $filtrosAplicados;
  }


  /**
   * @param Filtro[]|Collection $filtrosAplicables
   * @param Builder $builder
   */
  private function aplicarFiltrosBuilder($filtrosAplicables, Builder &$builder)
  {
    foreach ($filtrosAplicables as $filtro) {
      $filtro->aplicarFiltroJoin($builder);
    }
  }

  private function filtrosAplicables(Request $request)
  {
    $filtros = Filtros::all();
    $datosFomulario = $request->all();
    $filtrosAplicables = collect();

    foreach ($datosFomulario as $codigoFiltro => $valor) {
      if (is_array($valor)) {
        foreach ($valor as $k => $v) {
          if (!empty($v) && array_key_exists($codigoFiltro, $filtros)) {
            $operador = !empty($datosFomulario[$codigoFiltro . '_operador'][$k]) ? $datosFomulario[$codigoFiltro . '_operador'][$k] : null;
            $filtro = $filtros[$codigoFiltro];
            /** @var Filtro $filtro */
            $filtro->valor = $v;
            $filtro->operador = $operador;
            $filtrosAplicables->push($filtro);
          }
        }
      }
    }

    return $filtrosAplicables;
  }

  /**
   * @param Request $request
   * @param Builder $builder
   * @param Filtro[] $filtrosAndOr
   * @return array
   */
  private function aplicarFiltrosOld(Request $request, Builder &$builder, $filtrosAndOr)
  {
    $filtros = Filtros::all('adv');
    $datosFomulario = $request->all('adv');

    foreach ($filtrosAndOr as $filtro) {
      $filtro->aplicarFiltroJoin($builder);
    }
  }

  /**
   * @return Builder
   */
  private function consultaBase($whereCad)
  {

    $trayectorias = Trayectoria::select(
      // Trayectoria
      'trajectories.*',
      // Lipidos
      'lipids.*',
      'trajectories_lipids.*',
      
  
      
      // Iones
      'ions.*',
     
     

      'membranes.*'
    )
      ->leftJoin('trajectories_lipids', 'trajectories.id', '=', 'trajectories_lipids.trajectory_id')
      ->leftJoin('lipids', 'lipids.id', '=', 'trajectories_lipids.lipid_id')

      ->leftJoin('trajectories_ions', 'trajectories.id', '=', 'trajectories_ions.trajectory_id')
      ->leftJoin('ions', 'ions.id', '=', 'trajectories_ions.ion_id')

      ->leftJoin('trajectories_membranes', 'trajectories.id', '=', 'trajectories_membranes.trajectory_id')
      ->leftJoin('membranes', 'membranes.id', '=', 'trajectories_membranes.membrane_id')

      ->orderBy('trajectories.id')
      ->where($whereCad)->get();


    return $trayectorias;
  }

  private function getTrayectoriasConFiltroAplicados($request)
  {


    DB::enableQueryLog();
    $filtrosAplicables = $this->filtrosAplicables($request);


    $filtrosNot = $filtrosAplicables->where('operador', OPERADOR_NOT);
    
    $trayectoriasDescartadasPorFiltroNot = [];
    foreach ($filtrosNot as $filtro) {

      $result = DB::table($filtro->getTablePivot())->select($filtro->getTablePivot() . '.trajectory_id')
        ->join($filtro->modelo->getTable(), $filtro->modelo->getTable() . '.id', $filtro->getTablePivot() . '.' . $filtro->modelo->getForeignKey())
        ->where($filtro->modelo->getTable() . '.' . $filtro->columna, 'LIKE', '%' . $filtro->valor . '%')
        ->get();

      $trayectoriasDescartadasPorFiltroNot = array_merge($trayectoriasDescartadasPorFiltroNot, $result->pluck('trajectory_id')->toArray());
    }


    $trayectorias = Trayectoria::select('trajectories.*')->orderBy('trajectories.id')
      ->with('lipidos', 'iones', 'modelos_acuaticos', 'moleculas', 'membranas') //,'membranas'
      ->whereNotIn('trajectories.id', $trayectoriasDescartadasPorFiltroNot)->get();
    
    $filtrosAnd = $filtrosAplicables->where('operador', OPERADOR_AND);
    $filtrosOr = $filtrosAplicables->where('operador', OPERADOR_OR);

    if ($filtrosAnd->isEmpty() && $filtrosOr->isEmpty()) {
      $trayectoriasFiltradas = $trayectorias;
    } else {
      if (!$filtrosOr->isEmpty()) {
        $trayectoriasFiltradas = collect();

        foreach ($trayectorias as $k => $trayectoria) {
          foreach ($filtrosOr as $filtro) {
            $columna = $filtro->columna;

            if ($filtro->tipo == Filtro::TIPO_ENTIDAD) {
              $propiedad = $filtro->codigo;
              $entidades = $trayectoria->$propiedad;
              if ($propiedad == "membranas") {
                if (is_numeric($filtro->valor)) {
                  $filtro->columna = "id";
                  $columan = "id";
                } else {
                  $filtro->columna = "name";
                  $columan = "name";
                }
              }
             
              foreach ($entidades as $entidad) {
                // OJO :: entidad->columan no es el campo para la sql
                if (preg_match("%$filtro->valor%i", $entidad->$columna)) {
                  $trayectoriasFiltradas->push($trayectoria);
                }
              }
            }
            if ($filtro->tipo == Filtro::TIPO_PROPIEDAD) {
              if (preg_match("%$filtro->valor%i", $trayectoria->$columna)) {
                $trayectoriasFiltradas->push($trayectoria);
              }
            }
          }
        }
      } else {
        $trayectoriasFiltradas = $trayectorias;
      }


      foreach ($trayectoriasFiltradas as $k => $trayectoria) {
        foreach ($filtrosAnd as $filtro) {
          $columna = $filtro->columna;
          if ($filtro->tipo == Filtro::TIPO_ENTIDAD) {
            $propiedad = $filtro->codigo;
            $entidades = $trayectoria->$propiedad;
            if ($propiedad == "membranas") {
              if (is_numeric($filtro->valor)) {
                $filtro->columna = "id";
                $columan = "id";
              } else {
                $filtro->columna = "name";
                $columan = "name";
              }
            }

            $esta = false;
            foreach ($entidades as $entidad) {
              if (preg_match("%$filtro->valor%i", $entidad->$columna)) {
                $esta = true;
              }
            }
            if (!$esta) {
              unset($trayectoriasFiltradas[$k]);
            }
          }
          if ($filtro->tipo == Filtro::TIPO_PROPIEDAD) {
            if (!preg_match("%$filtro->valor%i", $trayectoria->$columna)) {
              unset($trayectoriasFiltradas[$k]);
            }
          }
        }
      }
    }
    return $trayectoriasFiltradas;
  }
}
