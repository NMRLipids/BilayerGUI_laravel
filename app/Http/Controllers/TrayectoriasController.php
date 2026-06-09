<?php

namespace App\Http\Controllers;

use App\Filtros\Filtros;
use App\Filtros\Lipidos;
use App\Trayectoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;




class TrayectoriasController extends Controller
{  

    const GitHubURL =    'https://raw.githubusercontent.com/NMRLipids/BilayerData/refs/heads/main/';
    const GitHubURLEXP = 'https://raw.githubusercontent.com/NMRLipids/BilayerData/main/';
    const GitHubDataRepoSimulations = "https://github.com/NMRLipids/BilayerData/tree/main/Simulations/";
    
    private $OPData = []; // This will hold the OP data structured for the view
    private $OPLegend = []; // This will hold the legend labels for the OP data
    private $ApLdata = ""; // This will hold the AP data structured for the view
    private $FFData = []; // This will hold the FF data structured for the view
    private $FFLegend = []; // This will hold the legend labels for the FF data 
    private $comp_ul = [];
    private $comp_ll = [];

    // This function is responsible for fetching the Area per Lipid (ApL) data for a given trajectory. 
    // It checks if the trajectory has an associated analysis, and if so, it calls the area_per_lipid_data() method on the analysis to retrieve the ApL data. 
    // The retrieved ApL data is then stored in the controller instance for later use in the view.
    private function fetchApLData($trayectoria): void {
        $ApLData = [];
        $analysis = $trayectoria->analisis;
        if (isset($analysis)) {
            $this->ApLdata =$analysis->area_per_lipid_data; // this is already a JSON string, so we can pass it directly to the view without encoding it again
        }
    }
   
    // This function is responsible for fetching the Form Factor (FF) data for a given trajectory.
    private function fetchFFData($trayectoria): void {
        $this->FFLegend = [$trayectoria->article_doi ? $trayectoria->article_doi : 'Simulation Data'];
        if (isset($trayectoria->analisis)) {
             $this->FFData[] = json_decode($trayectoria->analisis->form_factor_data, true); 
             // this is already a JSON string, so we decode it to a PHP array for easier manipulation in the view, 
             // and then we can re-encode it as JSON in the view when passing it to JavaScript for rendering the charts.
        }
    }

    private function augmentFFDataWithExperiments($trayectoria): void {
        if (isset($trayectoria->experimentsFF)) {
            foreach ($trayectoria->experimentsFF as $key => $experiment) {
                if (config('app.debug') && app()->environment('local')) {
                    error_log("Processing FF experiment: " . ($experiment->path ?? $experiment->article_doi ?? 'Unknown Experiment') . " for trajectory id " . $trayectoria->id);
                }                
                $experimentName = $experiment->path ?? $experiment->article_doi ?? 'Unknown Experiment';               
                // Each FF experiment stores its FF data in the data attribute of its base table, so we can access it directly from the experiment model
                if (config('app.debug') && app()->environment('local')) {
                    error_log(var_export($experiment->doi, true)); // Log the raw data for debugging
                }
                if (isset($experiment->data) && !empty($experiment->data)) { 
                    if (config('app.debug') && app()->environment('local')) {
                        error_log("Data found: FF data with experiment " . $experimentName);
                    }
                    $this->FFLegend[] = $experimentName; // Add the experiment name to the legend for chart labeling
                    $decodedFFData = json_decode($experiment->data, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        if (config('app.debug') && app()->environment('local')) {
                            error_log("Augmenting FF data with experiment " . $experimentName);
                        }
                        $this->FFData[] = $decodedFFData; // Append to existing FF data
                    } else {
                        if (config('app.debug') && app()->environment('local')) {
                            error_log("Error decoding FF data for experiment " . $experimentName . ": " . json_last_error_msg());
                        }
                    }   
                } else {
                    if (config('app.debug') && app()->environment('local')) {
                        error_log("No FF data found for ". $experiment->type. " experiment " . $experimentName);
                    }
                }

                if (config('app.debug') && app()->environment('local')) {
                    error_log("Finished processing experiment " . $experimentName);
                }
            }
        }
        else {
            if (config('app.debug') && app()->environment('local')) {
                error_log("No FF experiments found for trajectory id " . $trayectoria->id);
            }
        }
    }

    private function makeOPData($trayectoria): void {
        $OPData = [];
        $legend = [$trayectoria->article_doi ? $trayectoria->article_doi : 'Simulation Data'];
        if (isset($trayectoria->analisis) && isset($trayectoria->getTrayectoriaAnalisisLipidos)) {
            foreach ($trayectoria->getTrayectoriaAnalisisLipidos as $key => $lipid) {

                $lipidName = $lipid->getLipid->molecule ?? throw new Exception("Unknown Lipid $lipid"); // Use molecule name or fallback to 'Unknown Lipid'
                $decodedPlotData = json_decode($lipid->op_plot_data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error decoding OP plot data for lipid " . $lipidName . ": " . json_last_error_msg());
                    continue; // Skip this lipid if there's an error decoding the plot data
                }
                if (empty($decodedPlotData)) {
                    error_log("Decoded OP plot data for lipid " . $lipidName . " is empty or not an array");
                }
                foreach ($decodedPlotData as $group => $plot_data) {
                    $OPData[$lipidName][$group] = [$plot_data];
                }
            }
        } else {
            if (config('app.debug')) {
                error_log("No analysis or lipid analysis data found for trajectory id " . $trayectoria->id);
            }
        }
        //  where plot_data is the data to be plotted for that lipid and group, and legend is the label for the dataset in the chart. The view can then iterate over this structure to render charts for each lipid and group combination.
        // Example: $OPData['DPPC']['G1'] = [plot_data_for_DPPC_G1]
        // plot data is expected to be an array to be used in the view for rendering charts
        $this->OPData = $OPData; // Store the OP data in the controller instance for later use in the view
        $this->OPLegend = $legend; // Store the legend in the controller instance for later use in the view

    }

    // This function takes the OP data we have for the trajectory and then augments it with any additional data from related experiments. 
    // It checks if the trajectory has related experiments, and if so, it iterates through them to find any membrane composition data. 
    // For each lipid in the membrane composition, it checks if we have existing OP plot data for that lipid. 
    // If we do, it decodes the plot data and appends it to our existing OP data structure under the appropriate lipid and group. 
    // This way, we can include experimental data alongside our simulation data in the charts. 
    // The legend is also updated to include the experiment names for proper labeling in the charts. 

    private function augmentOPDataWithExperiments($trayectoria): void {
        if (isset($trayectoria->experimentsOP)) {
            foreach ($trayectoria->experimentsOP as $experiment) {
                $experimentName = $experiment->path ?? $experiment->article_doi ?? 'Unknown Experiment';
                $this->OPLegend[] = $experimentName; // Add the experiment name to the legend for chart labeling
                foreach ($experiment->membraneComposition as $membraneComponent) {
                    $lipid = $membraneComponent->lipid; // Get the lipid associated with this membrane component
                    $lipidName = $lipid->molecule ?? throw new Exception("Unknown Lipid in Membrane Composition for experiment $experimentName");
                    if (!isset($this->OPData[$lipidName])) continue; // Skip if we don't have Simulation OP data for this lipid
                    $decodedPlotData = json_decode($membraneComponent->data, true);
                    if (json_last_error() !== JSON_ERROR_NONE or !is_array($decodedPlotData)) {
                        error_log("Error decoding OP plot data for lipid " . $lipidName . ": ". " experiment " . $experiment->path . " " . json_last_error_msg());
                        continue; // Skip this lipid if there's an error decoding the plot data
                    }
                    foreach ($decodedPlotData as $group => $plot_data) {
                        if (empty($plot_data)) {
                            if (config('app.debug')) {
                                error_log("Decoded OP plot data for lipid " . $lipidName . " in experiment " . $experimentName . " is empty for group " . $group);
                            }       
                            continue; // Skip if plot data is empty
                        }
                        // If we don't have existing data for this lipid and group, we don't plot it. 
                        // We choose to skip it to ensure we only include experiments that have corresponding simulation data.
                        if (isset($this->OPData[$lipidName][$group])) {
                            #error_log("Augmenting OP data for lipid " . $lipidName . " group " . $group . " with experiment " . $experimentName);
                            $this->OPData[$lipidName][$group][] = $plot_data; // Push to existing data for this lipid and group
                        }  
                  
                    }
                }
            }
        }
    }

    function buildCompostion ($trayectoria): void {
        
        foreach ($trayectoria->trajectoriesLipids as $lipidComponent) {
            $lipidName = $lipidComponent->lipid->molecule ?? throw new Exception("Unknown Lipid in Trajectory Analysis for trajectory id " . $trayectoria->id);
            $this->comp_ul[$lipidName] = $lipidComponent->leaflet_1;
            $this->comp_ll[$lipidName] = $lipidComponent->leaflet_2;
        }

    }

    function list()
    {
        $request = request();
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'asc');
        $perPage = $request->input('per_page', 10);

        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';
        $allowedSorts = ['id', 'temperature', 'trj_length', 'op_quality_total', 'ff_quality', 'best'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'id';

        $query = Trayectoria::with(['campo_de_fuerza', 'membrana', 'lipidos', 'analisis'])
            ->withCount(['experimentsOP', 'experimentsFF']);

        // Filter by lipid
        $lipidFilter = $request->input('lipid', '');
        if ($lipidFilter !== '') {
            $query->whereHas('lipidos', function ($q) use ($lipidFilter) {
                $q->where('molecule', $lipidFilter);
            });
        }

        // Join trajectories_analysis for sorting by quality columns
        if (in_array($sort, ['op_quality_total', 'ff_quality', 'best'])) {
            $query->leftJoin('trajectories_analysis as ta_sort', 'trajectories.id', '=', 'ta_sort.trajectory_id')
                  ->select('trajectories.*');
            if ($sort === 'best') {
                // Rank product: rank(OP) * rank(FF), lower product = better.
                // OP quality: higher is better → rank by count of values above.
                // FF quality: higher is better → rank by count of values above.
                // NULLs get worst rank (N+1) per dimension, so they always sort below
                // any simulation with actual data for both metrics.

                // Note, this also means that if both metrics have different amounts of missing data, 
                // simulations with missing data in the metric with more missing data will be ranked better than 
                // those with missing data in the metric with less missing data, 
                // which reflects the current raw rank-product behavior when data completeness varies
                // across metrics; no additional compensation or weighting is applied here.

                $rpDir = $direction === 'desc' ? 'ASC' : 'DESC';
                $query->orderByRaw("
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
                    $rpDir
                ");
            } else {
                $query->orderByRaw("ta_sort.$sort IS NULL, ta_sort.$sort $direction");
            }
        } else {
            $query->orderBy($sort, $direction);
        }

        if ($perPage === 'all') {
            $trayectorias = $query->paginate($query->count() ?: 1);
        } else {
            $perPage = in_array((int)$perPage, [10, 20, 50]) ? (int)$perPage : 20;
            $trayectorias = $query->paginate($perPage);
        }

        return view('trayectorias.list', [
            'trayectorias' => $trayectorias,
            'sort' => $sort,
            'direction' => $direction,
            'per_page' => $request->input('per_page', 10),
        ]);
    }

    function show($trayectoria_id) {
        $trayectoria = Trayectoria::findOrFail($trayectoria_id);
        $this->makeOPData($trayectoria);
        $this->augmentOPDataWithExperiments($trayectoria);

        $this->fetchApLData($trayectoria);

        $this->fetchFFData($trayectoria);
        $this->augmentFFDataWithExperiments($trayectoria);
        $this->buildCompostion($trayectoria);

        return view('trayectorias.show', [
            'trayectoria' => $trayectoria,
            'OPData' => $this->OPData,
            'OPLegend' => $this->OPLegend,
            'ApLData' => $this->ApLdata,
            'FFData' => $this->FFData,
            'FFLegend' => $this->FFLegend,
            'compul' => $this->comp_ul,
            'compll' => $this->comp_ll,
            'related_experiments' => $trayectoria->getExperiments(),
        ]);
    }
}
