<?php

namespace App\Http\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;


class ExperimentController extends Controller
{

    /**
     * Extract numerical components from a key for sorting
     * 
     * Extracts all numbers from the key in order of occurrence.
     * Example: "G1C3H1" → [1, 3, 1], "C3H2" → [3, 2]
     * 
     * @param string $key The key to parse
     * @return array Array of numbers in order of occurrence
     */
    public static function extractNumericComponents($key) {
        $numbers = [];
        preg_match_all('/\d+/', $key, $matches);
        
        if (!empty($matches[0])) {
            $numbers = array_map('intval', $matches[0]);
        }
        
        return $numbers;
    }
    /**
     * Sorting callback for comparing keys by their numeric components
     * 
     * Compares two keys by extracting their numeric components.
     * Missing components are treated as 0.
     * 
     * @param string $a First key
     * @param string $b Second key
     * @return int Comparison result for usort
     */
    public static function compareNumericComponents($a, $b) {
        $componentsA = self::extractNumericComponents($a);
        $componentsB = self::extractNumericComponents($b);

        // Pad shorter array with 0s for comparison
        $maxLen = max(count($componentsA), count($componentsB));
        $componentsA = array_pad($componentsA, $maxLen, 0);
        $componentsB = array_pad($componentsB, $maxLen, 0);

        // Compare arrays element by element
        for ($i = 0; $i < $maxLen; $i++) {
            if ($componentsA[$i] < $componentsB[$i]) {
                return -1;
            } elseif ($componentsA[$i] > $componentsB[$i]) {
                return 1;
            }
        }

        return 0;
    }

/**
 * Transform the plot data keys and split by G1, G2, G3 components
 * 
 * For each key:
 * 1. Split on space and use the second part
 * 2. Remove leading "M_" and trailing "_M"
 * 3. Categorize into G1, G2, or G3 arrays based on the key prefix
 * 4. Sort keys within each category by their numerical components
 * 
 * @param array $data The plot data array
 * @return array An associative array with 'G1', 'G2', 'G3' keys, each containing sorted filtered data
 */
function formatOPData($data) {
    $result = [
        'G1' => [],
        'G2' => [],
        'G3' => []
    ];
    
    foreach ($data as $key => $value) {
        // Split the key on space and get the second part
        $parts = explode(' ', $key);
        
        if (count($parts) < 2) {
            // If there's no space, use the original key
            $newKey = $key;
        } else {
            $newKey = $parts[1];
        }
        
        // Remove leading "M_"
        if (strpos($newKey, 'M_') === 0) {
            $newKey = substr($newKey, 2);
        }
        
        // Remove trailing "_M"
        if (substr($newKey, -2) === '_M') {
            $newKey = substr($newKey, 0, -2);
        }
        
        // Categorize by G1, G2, or G3
        if (strpos($newKey, 'G1') === 0) {
            $result['G1'][$newKey] = $value[0][0];
        } elseif (strpos($newKey, 'G2') === 0) {
            $result['G2'][$newKey] = $value[0][0];
        } elseif (strpos($newKey, 'G3') === 0) {
            $result['G3'][$newKey] = $value[0][0];
        }
    }
    
    // Sort each category by numeric components
    foreach ($result as $category => &$array) {
        uksort($array, [self::class, 'compareNumericComponents']);
    }
    
    return $result;
}



    // Function to format data for charting, returns formatted data and min/max values
    private  static function formatFFData(string $jsonData, int $mult=1): array | null
    {
        $inputData = json_decode($jsonData, true);
        $data = array();
        $max = -INF;
        $min = INF;
        if ($mult == 0) {
            $fact = 1;
        } else {
            $fact = $mult;
        }
        foreach ($inputData as $Values) {
            if ($min > $Values[1]) {
                $min = $Values[1];
            }
            if ($max < $Values[1]) {
                $max = $Values[1];
            }

            //$labelData = $labelData . "'" . $Values[0] . "',";
            if ($mult == 0) {
                $normal0 = $Values[0];
            } else {
                $normal0 = $Values[0] + 0.15;
            }
            $normal = $Values[1] * $fact;
            //$data = $data . "'" . $normal . "',";
            $d = array('x' => $normal0, 'y'=> $normal);

            $data[]= $d;
        }
        $min = round($min - 0.5);
        $max = round($max + 0.5);
        $jsondata = str_replace('"',"",json_encode($data));
        return array('data'=>$jsondata, 'min'=>$min, 'max'=>$max);
    }


   





    public function list(): \Illuminate\View\View
    {
        // Fetch all experiments
        $experiments = DB::table('experiments as e')
            ->select('e.id', 'e.article_doi', 'e.data_doi', 'e.section', 'e.type', 'e.path')
            ->leftJoin('experiments_membrane_composition as emc', 'e.id', '=', 'emc.experiment_id')
            ->groupBy('e.id')
            ->orderBy('type', 'asc')
            ->orderBy('article_doi', 'asc')
            ->orderBy('section', 'asc')
            
            ->selectRaw('COUNT(emc.lipid_id) as lipid_count')
            ->paginate(10);

        return View::make('experiment', [
            'experiments_list' => $experiments,
        ]);
    }


    public function show($type, $doi, $section): \Illuminate\View\View
    {
        // Fetch experiment by DOI, section, and type
        $experiment = DB::table('experiments')
            ->where('article_doi', $doi)
            ->where('section', $section)
            ->where('type', $type)
            ->first();

        if (!$experiment) {
            abort(404, 'Experiment not found');
        }

        // Fetch associated properties
        $properties = DB::table('experiment_property as ep')
            ->join('experiments_properties_linker as efl', 'ep.id', '=', 'efl.property_id')
            ->where('efl.experiment_id', $experiment->id)
            ->select('ep.name', 'ep.value', 'ep.unit', 'ep.type', 'ep.description')
            ->get();
        // convert properties with type 'array' or 'dict' from JSON strings to PHP arrays
        $assocProps = [];
        foreach ($properties as $prop) {
            if ($prop->type === 'array' || $prop->type === 'dict') {
                $prop->value = json_decode($prop->value, true);
            }
            $assocProps[$prop->name] = $prop;
        }
        // Fetch membrane composition property if exists
        $membraneComposition = DB::table('experiments_membrane_composition as emc')
            ->join('lipids as l', 'emc.lipid_id', '=', 'l.id')
            ->where('emc.experiment_id', $experiment->id)
            ->select('l.id','l.name','l.molecule', 'emc.mol_fraction', 'emc.data')
            ->get();
        // Fetch solution composition property if exists
        $solutionComposition = DB::table('experiments_solution_composition as esc')
            ->where('esc.experiment_id', $experiment->id)
            ->select('esc.compound', 'esc.concentration','data')
            ->get();    
        if (empty($membraneComposition)) {
            $membraneComposition = null;
        } else {
            foreach ($membraneComposition as $index => $memComp) {
                if (!empty($memComp->data)) {
                    $formattedData = $this->formatOPData(json_decode($memComp->data, true) ?? []);
                    $membraneComposition[$index]->data = $formattedData;
                } else {
                    $membraneComposition[$index]->data = null;
            }
        }
        
        if (empty($solutionComposition)) {
            $solutionComposition = null;
        } else {
            $pureWater = true;
            foreach ($solutionComposition as $index => $solComp) {
                if (is_numeric($solComp->concentration)) {
                    $solutionComposition[$index]->concentration = floatval($solComp->concentration);
                }
                if (strtolower($solComp->compound) != 'water' && strtolower($solComp->compound) != 'sol' && $solComp->concentration > 0) {
                    $pureWater = false;
                }
            }
            if ($pureWater) {
                $solutionComposition = 'pure water';
            }
        }            

        
        $datFF =  ($experiment->type === 'FF' && !empty($experiment->data)) ? $this->formatFFData($experiment->data) : null;

        return View::make('experiment', [
                'entity' => ['doi' => $experiment->article_doi,
                             'data_doi' => $experiment->data_doi,
                            'section' => $experiment->section, 
                            'path' => $experiment->path,
                            'type' => ($experiment->type),
                            'data' => $datFF['data'] ?? null,
                            'data_min' => $datFF['min'] ?? null,
                            'data_max' => $datFF['max'] ?? null,
                            'membrane_composition' => $membraneComposition,
                            'solution_composition' => $solutionComposition,
                            ],
                'properties' => $assocProps,
        ]);
    }
}
}