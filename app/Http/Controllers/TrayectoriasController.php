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
    static $DataStr = '';
    static $DataValue = '';
    static $DataError = '';

    static $DataExpStr = array();
    static $DataExpValue = array();
    static $DataExpError = array();

    static $DataExpStrArray =array();
    static $DataExpValueArray =array();
    static $DataExpErrorArray =array();

    static $maxValue = -INF;
    static $minValue = INF;

    static $sub_ns = ['5', '50', '100', '200'];

    static $metadatos_head = '';

    // Creamos el array de lipidos para las graficas e tarta
    static $l1lipid = array();
    static $l2lipid = array();
    static $l1lipidNum = array();
    static $l2lipidNum = array();

    static $l1lipidStr = '';
    static $l2lipidStr = '';
    static $l1lipidNumStr = '';
    static $l2lipidNumStr = '';

    static $RealNameAsoc = ''; // una lista para

    // Lista de lipidos de las dos cada para colorear
    // --------------------
    static $lip1Array = [];
    static $lip2Array = [];
    static $lipArray = [];

    private $plotData = [];

public static function urlFileExist($file)
    {
        $file_headers = @get_headers($file);
        if (!$file_headers || str_contains($file_headers[0], '400') || str_contains($file_headers[0], '404')) {
            $exists = false;
        } else {
            $exists = true;
        }

        return $exists;
    }

public static function urlFileExist2($file)
    {
        $file_headers = @get_headers($file);
        if ($file_headers && strpos($file_headers[0], '200')) {
            $exists = true;
        } else {
            $exists = false;
        }

        return $exists;
    }

public static function filtraValor($val)

    {
        //if ($val == 0 || $val == 4242) {
        if ($val == 4242) {
            return 'N/A';
        } else {
            return round($val, 2);
        }
    }

    public static function IgualaDecimales($n1, $n2)
    {
        $ent1 = $dec1 = $ent2 = $dec2 = '0';
        $a = explode('.', $n1);
        $b = explode('.', $n2);
        if (count($a) > 1) {
            $dec1 = $a[1];
        }
        if (count($b) > 1) {
            $dec2 = $b[1];
        }

        $maxdec = max(strlen($dec1), strlen($dec2));
        $dec1 = str_pad($dec1, $maxdec, '0', STR_PAD_RIGHT);
        $dec2 = str_pad($dec2, $maxdec, '0', STR_PAD_RIGHT);
        $ent1 = $a[0];
        $ent2 = $b[0];

        return $ent1 . '.' . $dec1 . ' &plusmn; ' . $ent2 . '.' . $dec2;
    }
    

    public static function urlFileExist_new($url)
    {
        return curl_init($url) !== false;
    }

    public static function CleanLabel($label)
    {
        $label = str_replace('_M M_', '_', $label);
        $label = str_replace('_M', '', $label);
        $label = str_replace('M_', '', $label);
        $labelExpl = explode('_', $label);
        return $labelExpl[1];
    }

    static $ColMemAssoc = [];
    static $MemNameAssoc = [];
    # $ColMemAssoc['CHOL'] = '#ffff00';

   # foreach ($trayectoria->lipidos as $key => $value) {
   #     $ColMemAssoc[$value['name']] = $value['color'];
   #     $MemNameAssoc[$value['molecule']] = $value['name'];
   #     $RealNameAsoc = $RealNameAsoc . "'" . $value['molecule'] . "':'" . $value['name'] . "',";
    #}
    
    private function makeOPData($trayectoria): void {
        $OPData = [];
        $legend = [$trayectoria->article_doi ? $trayectoria->article_doi : 'Simulation Data'];
        if (isset($trayectoria->analisis) && isset($trayectoria->TrayectoriaAnalisisLipidos)) {
            foreach ($trayectoria->TrayectoriaAnalisisLipidos as $key => $lipid) {

                $lipidName = $lipid->getLipid->molecule ?? throw new Exception("Unknown Lipid $lipid"); // Use molecule name or fallback to 'Unknown Lipid'
                $decodedPlotData = json_decode($lipid->op_plot_data, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error decoding OP plot data for lipid " . $lipidName . ": " . json_last_error_msg());
                    continue; // Skip this lipid if there's an error decoding the plot data
                }
                if (empty($decodedPlotData)) {
                    die("Decoded OP plot data for lipid " . $lipidName . " is empty or not an array");
                }
                foreach ($decodedPlotData as $group => $plot_data) {
                    $OPData[$lipidName][$group] = [$plot_data];
                }
            }
        } else {
            error_log("No analysis or lipid analysis data found for trajectory id " . $trayectoria->id);
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
                    if (!isset($this->OPData[$lipidName])) next; // Skip if we don't have Simulation OP data for this lipid
                    $decodedPlotData = json_decode($membraneComponent->data, true);
                    if (json_last_error() !== JSON_ERROR_NONE or !is_array($decodedPlotData)) {
                        error_log("Error decoding OP plot data for lipid " . $lipidName . ": ". " experiment " . $experiment->path . " " . json_last_error_msg());
                        continue; // Skip this lipid if there's an error decoding the plot data
                    }
                    foreach ($decodedPlotData as $group => $plot_data) {
                        if (empty($plot_data)) {
                            error_log("Decoded OP plot data for lipid " . $lipidName . " in experiment " . $experimentName . " is empty for group " . $group);
                            continue; // Skip if plot data is empty
                        }
                        // If we don't have existing data for this lipid and group, we don't plot it. 
                        // We choose to skip it to ensure we only include experiments that have corresponding simulation data.
                        if (isset($this->OPData[$lipidName][$group])) {
                            error_log("Augmenting OP data for lipid " . $lipidName . " group " . $group . " with experiment " . $experimentName);
                            $this->OPData[$lipidName][$group][] = $plot_data; // Push to existing data for this lipid and group
                        }  
                  
                    }
                }
            }
        }
    }

    function show($trayectoria_id) {
           $trayectoria = Trayectoria::findOrFail($trayectoria_id);
           $this->makeOPData($trayectoria);
           $this->augmentOPDataWithExperiments($trayectoria);

        return view('trayectorias.show', [
            'trayectoria' => $trayectoria,
            'OPData' => $this->OPData,
            'OPLegend' => $this->OPLegend, 
        ]);
    }
}
