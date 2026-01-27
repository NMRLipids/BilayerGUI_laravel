<?php

use App\Trayectoria;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

/**
 * @var Trayectoria $trayectoria
 */

// var_dump($trayectoria);

?>
@extends('layouts.app')


@section('content')
    <?php

    // PATH to the simulation data in github https://github.com/NMRLipids/BilayerData/Simulations  -> 
    /* ICICIC: this breaks the MVC pattern because there is a lot of controller code in here */ 

    $GitHubURL =    'https://raw.githubusercontent.com/NMRLipids/BilayerData/refs/heads/main/';
    $GitHubURLEXP = 'https://raw.githubusercontent.com/NMRLipids/BilayerData/main/';
    // TODO: Why do we have two same vars?

    function filtraValor($val)
    {
        //if ($val == 0 || $val == 4242) {
        if ($val == 4242) {
            return 'N/A';
        } else {
            return round($val, 2);
        }
    }
    function IgualaDecimales($n1, $n2)
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
    function urlFileExist($file)
    {
        $file_headers = @get_headers($file);
        if (!$file_headers || str_contains($file_headers[0], '400') || str_contains($file_headers[0], '404')) {
            $exists = false;
        } else {
            $exists = true;
        }

        return $exists;
    }

    function urlFileExist2($file)
    {
        $file_headers = @get_headers($file);
        if ($file_headers && strpos($file_headers[0], '200')) {
            $exists = true;
        } else {
            $exists = false;
        }
        /*var_dump($file);
        var_dump($file_headers);
        die();*/

        return $exists;
    }

    function urlFileExist_new($url)
    {
        return curl_init($url) !== false;
    }

    $DataStr = '';
    $DataValue = '';
    $DataError = '';

    $DataExpStr = array();
    $DataExpValue = array();
    $DataExpError = array();

    $DataExpStrArray =array();
    $DataExpValueArray =array();
    $DataExpErrorArray =array();

    $maxValue = -INF;
    $minValue = INF;

    function CleanLabel($label)
    {
        $label = str_replace('_M M_', '_', $label);
        $label = str_replace('_M', '', $label);
        $label = str_replace('M_', '', $label);
        $labelExpl = explode('_', $label);
        return $labelExpl[1];
    }

    function genDataParamOrde($GitHubURL, $FileUrl, &$labelData, &$data, &$dataerror, &$maxData, &$minData, $Grupo)
    {
        //global $GitHubURL,$DataStr,$DataValue,$DataValueMax,$DataValueMin;

        $jsonFileUrl = '';

        $cutSize = 31;
        $cutSize = 0; // nuevo cambio
        if (strlen($FileUrl) == 0) {
            return;
        }
        $jsonFileUrl = $GitHubURL . substr($FileUrl, $cutSize); // 10

        //if (file_exists($jsonFileUrl)){
        $jsonFile = file_get_contents($jsonFileUrl);

        $jsonFileData = json_decode($jsonFile);

        $labelData = '';
        $data = '';
        $dataerror = '';
        $maxData = -INF;
        $minData = INF;

        foreach ($jsonFileData as $label => $Values) {
            if (is_numeric($Values[0][0]) && is_numeric($Values[0][2])) {
                $Values[0][0] = $Values[0][0] * -1.0;

                if ($Grupo == '') {
                    $labelData = $labelData . "'" . CleanLabel($label) . "',";
                    $data = $data . "'" . $Values[0][0] . "',";
                    $ymax = $Values[0][0] + $Values[0][2];
                    $ymin = $Values[0][0] - $Values[0][2];

                    $dataerror = $dataerror . '{y:' . $Values[0][0] . ', yMax:' . $ymax . ',yMin:' . $ymin . '},';

                    if ($minData > $Values) {
                        $minData = $Values;
                    }
                    if ($maxData < $Values) {
                        $maxData = $Values;
                    }
                } else {
                    if (str_contains($label, $Grupo)) {
                        /*$label = str_replace("M_","",$label);
                 $label = str_replace("_M","",$label);
                 $labelExpl = explode(" ",$label);*/

                        $labelData = $labelData . "'" . CleanLabel($label) . "',";
                        $data = $data . "'" . $Values[0][0] . "',";
                        //$dataerror = $dataerror."[".($Values[0][0]+$Values[0][2]).",".($Values[0][0]-$Values[0][2])."],";
                        //var_dump($Values);
                        //die();
                        $ymax = $Values[0][0] + $Values[0][2];
                        $ymin = $Values[0][0] - $Values[0][2];
                        $dataerror = $dataerror . '{y:' . $Values[0][0] . ', yMax:' . $ymax . ',yMin:' . $ymin . '},';
                        //echo($data ."-> ".$Values[0][2]." - >". $dataerror);
                        //die();
                        if ($minData > $Values) {
                            $minData = $Values;
                        }
                        if ($maxData < $Values) {
                            $maxData = $Values;
                        }
                    }
                }
            }
        }
    }

    // Esto es para fusionar los datos
    function genDataParamOrdeFusion($GitHubURL, $FileUrl, &$labelData, &$data, &$dataerror, &$maxData, &$minData, $Grupo)
    {
        $jsonFileUrl = '';



        $cutSize = 31;
        $cutSize = 0;
        //if (strlen($FileUrl)==0) return;
        $jsonFileUrl = $GitHubURL . substr($FileUrl, $cutSize); // 10


      //  die($jsonFileUrl);

        //if (file_exists($jsonFileUrl)){
        $jsonFile = file_get_contents($jsonFileUrl);
        $jsonFileData = json_decode($jsonFile, true, JSON_ERROR_INF_OR_NAN | JSON_NUMERIC_CHECK | JSON_PARTIAL_OUTPUT_ON_ERROR);

        /*var_dump($jsonFileData);
        die();   */
        $labelData = '';
        $data = '';
        $dataerror = '';
        $maxData = -INF;
        $minData = INF;
      if (is_array($jsonFileData) || is_object($jsonFileData))
        foreach ($jsonFileData as $label => $Values) {
            if (is_numeric($Values[0][0]) && is_numeric($Values[0][2])) {
                //  $Values[0][0] = $Values[0][0] * -1.0;

                if ($Grupo == '') {
                    $labelData = $labelData . "'" . CleanLabel($label) . "',";

                    if ($data == '') {
                        $data = $Values[0][0];
                    } else {
                        $data = $data . ',' . $Values[0][0];
                    }
                } else {
                    if (str_contains($label, $Grupo)) {
                        $labelCleaned = CleanLabel($label); // save al labels to create a unique array
                        if ($labelCleaned == 'G1H1' || $labelCleaned == 'G1H2' || $labelCleaned == 'G2H1') {
                            // HACK THIS LABEL IS GONNA GOT TO HEAD GROUP
                        } else {
                            $labelData = $labelData . "'" . $labelCleaned . "',";
                            if ($data == '') {
                                $data = $Values[0][0];
                            } else {
                                $data = $data . ',' . $Values[0][0];
                            }
                        }
                    } else {
                        $labelCleaned = CleanLabel($label); // save al labels to create a unique array
                        if ($labelCleaned == 'G1H1' || $labelCleaned == 'G1H2' || $labelCleaned == 'G2H1') {
                            if ($Grupo == 'G3') {
                                $labelData = $labelData . "'" . $labelCleaned . "',";
                                if ($data == '') {
                                    $data = $Values[0][0];
                                } else {
                                    $data = $data . ',' . $Values[0][0];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function genDataParamOrderExperiment($GitHubURL, $FileUrl, &$labelData, &$data, &$dataerror, &$maxData, &$minData, $Grupo, $ind, $lipid_id)
    {
        $jsonFileUrl = '';

         //if (strlen($FileUrl)==0) return;
        if ($lipid_id != '') {
            
            $jsonFileUrl = $GitHubURL . $FileUrl . '/' . $lipid_id. '_Order_Parameters.json'; //10
        } else {
            $jsonFileUrl = $GitHubURL . $FileUrl; //10
        }
        $jsonFile = '';

        $jsonFile = file_get_contents($jsonFileUrl);

        $jsonFile = str_replace('NaN', 0.0, $jsonFile);

        $jsonFileData = [];

        $jsonFile = str_replace('_M M_', '_', $jsonFile);
        $jsonFile = str_replace('_M', '', $jsonFile);
        $jsonFile = str_replace('M_', '', $jsonFile);

        $jsonFileData = json_decode($jsonFile, true);
        //var_dump($jsonFileData);

        $labelData = array();
        $data = array();
        $dataerror = array();
        $maxData = -INF;
        $minData = INF;

        if (is_array($jsonFileData) || is_object($jsonFileData)) {

            foreach ($jsonFileData as $label => $Values) {

                if (is_array($Values)) {

                    if (is_numeric($Values[0][0])) {

                        if ($Grupo == '') {
                          //echo("D");
                            $labelData[] =  CleanLabel($label);

                            $data[] = $Values[0][0];

                            $dataerror = $dataerror . '{y:' . $Values[0][0] . '},';

                        } else {

                            $labelCleaned = CleanLabel($label);

                            if (str_contains($label, $Grupo)) {

                                if ($labelCleaned == 'G1H1' || $labelCleaned == 'G1H2' || $labelCleaned == 'G2H1') {
                                    // HACK THIS LABEL IS GONNA GOT TO HEAD GROUP
                                } else {

                                    $labelData[] = CleanLabel($label);

                                    $data[]= $Values[0][0];

                                    $dataerror[] = $Values[0][0];

                                 }
                            } else {

                                if ($labelCleaned == 'G1H1' || $labelCleaned == 'G1H2' || $labelCleaned == 'G2H1') {
                                  if($Grupo == 'G3') {
                                        $labelData[] = $labelCleaned;

                                        $data[] = $Values[0][0];

                                        $dataerror[] = $Values[0][0];


                                }
                            }
                        }
                    }
                }
            }
        }
      }
    }

    function LeeIndices($GitHubURL, $FileUrlIndices)
    {
        $jsonFileUrlIndices = '';

        $jsonFileUrlIndices = $GitHubURL . $FileUrlIndices;

        $jsonFileIndices = file_get_contents($jsonFileUrlIndices);

        $jsonFileData = [];
        $jsonFileData = json_decode($jsonFileIndices, true, JSON_ERROR_INF_OR_NAN | JSON_NUMERIC_CHECK | JSON_PARTIAL_OUTPUT_ON_ERROR);

        $indices = [];
        foreach ($jsonFileData as $key => $data) {
            $indices[] = $key;
        }

        return $indices;
    }

    function genData($GitHubURL, $FileUrl, &$labelData, &$data, &$maxData, &$minData, $fact)
    {
        //global $GitHubURL,$DataStr,$DataValue,$DataValueMax,$DataValueMin;

        $cutpos = 31;
        $cutpos = 0;
        //$jsonFileUrl = $GitHubURL . substr($FileUrl, $cutpos); //10
        $jsonFileUrl = $GitHubURL . $FileUrl; //10


        if (!urlFileExist($jsonFileUrl)) {
            return false;
        } // salimos si no existe el fichero

        $jsonFile = file_get_contents($jsonFileUrl);
        $jsonFileData = json_decode($jsonFile);

        $labelData = '';
        $data = '';
        $maxData = -INF;
        $minData = INF;

        $fact = 0.001;
        foreach ($jsonFileData as $label => $Values) {
            $labelData = $labelData . "'" . round((float) $label * (float) $fact) . "',";
            $data = $data . "'" . $Values . "',";

            if ($minData > $Values) {
                $minData = $Values;
            }
            if ($maxData < $Values) {
                $maxData = $Values;
            }
        }

        $minData = round($minData - 0.5);
        $maxData = round($maxData + 0.5);

        return true;
    }

    // No todo los json tiene el mismo formato,
    function genData2($GitHubURL, $FileUrl, &$labelData, &$data, &$maxData, &$minData, $mult)
    {
        //global $GitHubURL,$DataStr,$DataValue,$DataValueMax,$DataValueMin;
        //if (strlen($FileUrl)==0) return;
        $cutpos = 31;
        $cutpos = 0;
        //$jsonFileUrl = $GitHubURL . substr($FileUrl, $cutpos); //10
        $jsonFileUrl = $GitHubURL . $FileUrl; //10


        if (!urlFileExist($jsonFileUrl)) {
            return false;
        } // salimos si no existe el fichero

        $jsonFile = file_get_contents($jsonFileUrl);
        $jsonFileData = json_decode($jsonFile);

        $labelData = '';
        $data = '';
        $maxData = -INF;
        $minData = INF;

        if ($mult == 0) {
            $fact = 1;
        } else {
            $fact = $mult;
        }

        foreach ($jsonFileData as $Values) {
            if ($minData > $Values[1]) {
                $minData = $Values[1];
            }
            if ($maxData < $Values[1]) {
                $maxData = $Values[1];
            }

            $labelData = $labelData . "'" . $Values[0] . "',";
            if ($mult == 0) {
                $normal0 = $Values[0];
            } else {
                $normal0 = $Values[0] + 0.15;
            }
            $normal = $Values[1] * $fact;
            //$data = $data . "'" . $normal . "',";
            $data = $data . '{x:' . $normal0 . ', y:' . $normal . '},';
        }

        $minData = round($minData - 0.5);
        $maxData = round($maxData + 0.5);

        return true;
    }

    function genData2Array($GitHubURL, $FileUrl, &$labelData, &$data, &$maxData, &$minData, $mult)
    {

        $jsonFileUrl = $GitHubURL . $FileUrl; //10

        $jsonFile = file_get_contents($jsonFileUrl);
        $jsonFileData = json_decode($jsonFile);

        $labelData = '';
        $data = array();
        $maxData = -INF;
        $minData = INF;

        if ($mult == 0) {
            $fact = 1;
        } else {
            $fact = $mult;
        }

        foreach ($jsonFileData as $Values) {
            if ($minData > $Values[1]) {
                $minData = $Values[1];
            }
            if ($maxData < $Values[1]) {
                $maxData = $Values[1];
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

        $minData = round($minData - 0.5);
        $maxData = round($maxData + 0.5);


    }

    $sub_ns = ['5', '50', '100', '200'];

    $metadatos_head = $trayectoria->membrana->metadata;

    // Creamos el array de lipidos para las graficas e tarta
    $l1lipid = Str::of($trayectoria->membrana->lipid_names_l1)->split('/[\s:]+/');
    $l2lipid = Str::of($trayectoria->membrana->lipid_names_l2)->split('/[\s:]+/');
    $l1lipidNum = Str::of($trayectoria->membrana->lipid_number_l1)->split('/[\s:]+/');
    $l2lipidNum = Str::of($trayectoria->membrana->lipid_number_l2)->split('/[\s:]+/');

    $l1lipidStr = '';
    $l2lipidStr = '';
    $l1lipidNumStr = '';
    $l2lipidNumStr = '';

    $RealNameAsoc = ''; // una lista para

    // Lista de lipidos de las dos cada para colorear
    // --------------------
    $lip1Array = [];
    $lip2Array = [];
    $lipArray = [];

    foreach ($l1lipid as $lip) {
        $l1lipidStr = $l1lipidStr . "'" . $lip . "',";
        $lip1Array[] = $lip;
    }
    foreach ($l2lipid as $lip) {
        $l2lipidStr = $l2lipidStr . "'" . $lip . "',";
        $lip2Array[] = $lip;
    }
    $lipArray = array_unique(array_merge($lip1Array, $lip2Array));
    // select [DOPE]; color '#FF9AA2';

    $ColMemAssoc = [];
    $MemNameAssoc = [];
    $ColMemAssoc['CHOL'] = '#ffff00';

    foreach ($trayectoria->lipidos as $key => $value) {
        $ColMemAssoc[$value['name']] = $value['color'];
        $MemNameAssoc[$value['molecule']] = $value['name'];
        $RealNameAsoc = $RealNameAsoc . "'" . $value['molecule'] . "':'" . $value['name'] . "',";
    }
    

    $colorList = ['#FF9AA2', '#C7CEEA', '#FFB7B2', '#B5EAD7', '#FFDAC1', '#E2F0CB', '#FF9AA2', '#C7CEEA', '#FFB7B2', '#B5EAD7', '#FFDAC1', '#E2F0CB'];
    //$colorList = array('#C82842','#F08041','#FEC544','__#6BAC2E','#055B4E','#FF00FF','#FF3300','#663300');
    //$colorListAssoc  = array('CHOL' => , );

    // componemos una cadena de parametros segun como este compuesto el lipido
    $CadSelectMem = '';


    $ncol = 0;
    // Coloreo los componentes
    foreach ($lipArray as $lip) {
        if (array_key_exists($lip, $MemNameAssoc)) {
            $CadSelectMem = $CadSelectMem . 'select [' . $MemNameAssoc[$lip] . '];';
            //$CadSelectMem=$CadSelectMem. " color '".$ColMemAssoc[$lip]."';"; // ESTO es de supmem tenia el color en la BD
            $CadSelectMem = $CadSelectMem . " color '" . $colorList[$ncol] . "';";
            $ncol = $ncol + 1;
            if ($ncol >= count($lipArray)) {
                $ncol = 0;
            }
        }
    }



    foreach ($l1lipidNum as $lip) {
        $l1lipidNumStr = $l1lipidNumStr . "'" . $lip . "',";
    }
    foreach ($l2lipidNum as $lip) {
        $l2lipidNumStr = $l2lipidNumStr . "'" . $lip . "',";
    }
 if ($trayectoria->git_path==""){

   ?>



  <div class="container">
       <div class="row justify-content-center">
           <div class="col-md-12">
               <div class=" ">
                   <div class="card-header txt-white text-center">
                       <h1>This system is no longer available in the databank.</h1>
                   </div>
              </div>
            </div>
      </div>
  </div>


   <?php
 } else {
    ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class=" ">
                    <div class="card-header txt-white">
                        <h1>@lang('Trayectoria') {{ $trayectoria->id }}</h1>
                    </div>
                    <div class="card-header txt-white">
                        <h3>
                            Order parameters quality =
                            <?php
                            if (isset($trayectoria->ranking_global->quality_total)) {
                                echo filtraValor($trayectoria->ranking_global->quality_total);
                            }
                            ?>
                        </h3>
                    </div>


                    <div role="tabpanel" class="pt-4">
                        <ul class="nav nav-pills justify-content-start" id="trajectoryTab" role="tablist">

                            <li role="presentation" class="nav-item ">
                                <a href="#homePeptide" class="nav-link active  " aria-controls="homePeptide" role="tab"
                                    data-toggle="tab">Methodology</a>
                            </li>

                            <li role="presentation" class="nav-item">
                                <a href="#homeMembrane" class="nav-link" aria-controls="homeMembrane" role="tab"
                                    data-toggle="tab">Membrane</a>
                            </li>

                            <li role="presentation" class="nav-item">
                                <a href="#homeAnalysis" class="nav-link" aria-controls="homeAnalysis" role="tab"
                                    data-toggle="tab">Analysis/Experiment</a>
                            </li>
                        </ul>


                        <div class="tab-content">
                            <!-- homePeptide -->
                            <div role="tabpanel" class="tab-pane active bg-solapa card-datos" id="homePeptide">

                                <div class="card-body">
                                    <div class="row p-2">
                                        <div class="  col-12 pt-2">
                                            <!--<div class="row"><div class="col  bg-jmol "><span class="d-flex justify-content-center" id=jmolViewPeptide></span></div></div>-->


                                            <div class="container overflow-hidden">
                                                <div class="row g-5">
                                                    <div class=" col-12"
                                                        style="background-color: #5fbac4;border-right-width:  19px;border-right-style: solid;border-color: rgb(163 163 163);">
                                                        <div class="p-3">
                                                            <span class="txt-titulo">Computational methods </span>



                                                            <br><br>
                                                            <span class="txt-titulo">{{ c('campo_de_fuerza') }}:</span>
                                                            <span class="txt-dato">
                                                                {{ $trayectoria->campo_de_fuerza->name }}</span><br>
                                                            <span class="txt-titulo">{{ c('longitud') }}:</span>
                                                            <span class="txt-dato">
                                                                {{ $trayectoria->trj_length }}</span><br>
                                                            <!--<span class="txt-titulo">{{ c('campo_electrico') }}:</span><span class="txt-dato"> {{ $trayectoria->electric_field }}</span><br>-->
                                                            <span class="txt-titulo">{{ c('temperatura') }}:</span>
                                                            <span class="txt-dato">{{ $trayectoria->temperature }}
                                                            </span><br>
                                                            <!--<span class="txt-titulo">{{ c('presion') }}:</span><span class="txt-dato">{{ $trayectoria->pressure }} ({{ $trayectoria->pressure_coupling }} {{ $trayectoria->pressure_coupling_type }})</span><br>-->
                                                            <span class="txt-titulo">{{ c('particulas') }}:</span>
                                                            <span
                                                                class="txt-dato">{{ $trayectoria->number_of_atoms }}</span><br>
                                                            <!--<span class="txt-titulo">{{ c('timestep') }} :</span><span class="txt-dato">{{ $trayectoria->timeleftout }} </span><br>-->
                                                            <span class="txt-titulo">{{ c('software') }}:</span>
                                                            <span class="txt-dato">{{ $trayectoria->software }} </span><br>

                                                          
                                                            <span class="txt-titulo">@lang('Iones'):</span>
                                                            <span class="txt-dato">

                                                                 {{ $trayectoria->iones_num->map(function($ion) {return "{$ion->ion_name}({$ion->number})";})->implode(', ') }}

                                                               </span><br>

                                                               <span class="txt-titulo">@lang('Water'):</span>
                                                               <span class="txt-dato">

                                                                   {{ $trayectoria->water_resname }} 
                                                                   
                                                                  </span><br>

                                                            <span class="txt-titulo">@lang('Lipidos'):</span><br>

                                                            <span class="txt-dato"> L1:
                                                                {{ $trayectoria->membranas->implode('lipid_names_l1', ', ') }}
                                                                ({{ $trayectoria->membranas->implode('lipid_number_l1', ', ') }})
                                                            </span><br>
                                                            <span class="txt-dato"> L2:
                                                                {{ $trayectoria->membranas->implode('lipid_names_l2', ', ') }}
                                                                ({{ $trayectoria->membranas->implode('lipid_number_l2', ', ') }})
                                                            </span><br>
                                                            <p>
                                                                <?php
                                                               
                                                                $cadPath = asset('storage/simulations/' . $trayectoria->git_path);
                                                                echo "<a class=\"bi bi-cloud-download\" href=\"" . $cadPath . "/conf.pdb.gz\" class=\"card-link\" >&nbsp;Download PDB File. </a></br>";

                                                                echo '<a class="bi bi-cloud-download card-link" href="https://doi.org/' . $trayectoria->doi . '" target="_blank">&nbsp;Link to simulation files</a>';

                                                                ?>
                                                            </p>
                                                        </div>

                                                    </div>



                                                    <span class="txt-dato">
                                                        <a
                                                            href="{{ 'https://github.com/NMRLipids/BilayerData/tree/main/Simulations/' . $trayectoria->git_path }}">
                                                            <span>See the system in : </span><img style="width: 120px;"
                                                                src="{{ asset('storage/images/github.png') }}">
                                                        </a>
                                                    </span><br>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- homeMembrane -->
                            <div role="tabpanel" class="tab-pane m-1 bg-solapa card-datos" id="homeMembrane">
                                <div class="card-body" style="height: 100%;">
                                   

                                    <div class="row p-4">

                                        <div class="col-xl-8 col-md-12 col-lg-6 pt-4 pb-4 ">
                                            <div class="row">

                                                <div class="text-center">
                                                    Click on any component to highlight it from the plot.
                                                </div>
                                            </div>
                                            <div class="row">

                                                <div class=" col-xs-12 col-sm-6 chart-container-half text-center">

                                                    Upper leaflet
                                                    <canvas id="myChart1" width="50" height="50"> </canvas>

                                                </div>

                                                <div class="col-xs-12 col-sm-6 chart-container-half text-center">
                                                    Lower leaflet
                                                    <canvas id="myChart2" width="50" height="50"> </canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm p-4">
                                            <span>
                                                <h3>Lipids</h3>
                                            </span></br>
                                        </div>
                                    </div>

                                    <div class="row justify-content">

                                        <!--  This won't work properly -->
                                        <?php
                                    $col = 0;
                                    foreach ($trayectoria->lipidos as $lipido) {
                                        $gifFile = $lipido->forcefield_id . "/" . $lipido->short_name . ".gif";
                                        $pathlip = asset('storage/forcefields/' . $gifFile);


                                    ?>
                                        <div class="col-xs-12 col-lg-6 d-flex flex-wrap cardlipids">

                                            <div class=" m-2 w-100" style="width: 18rem;">
                                                <div class=" ">
                                                    <h5 class=" ">{{ $lipido->molecule }}</h5>


                                                    <?php

                                                    $pathToPeptid = '/molecule2D/' . $lipido->molecule . '.png';
                                                    if (file_exists(public_path('storage' . $pathToPeptid))) {
                                                        echo '<a class="portfolio-box" href="' . asset('storage/' . $pathToPeptid) . '" title="Click to Zoom">';
                                                        echo '<span ><b>Show Lipid</b>  </span></br>';
                                                        echo '</a>';
                                                    }
                                                    $mappingFile = $lipido->getMappingByForcefield($trayectoria->campo_de_fuerza);
                                                    $pathToScr =$GitHubURL . 'Molecules/membrane/' . $lipido->molecule .'/'. $mappingFile;
                                                    
                                                    echo '<a href="' . $pathToScr  . '" title="Download Mapping file" target="_blank">';
                                                    echo '<span ><b>Download Mapping file</b>  </span></br>';
                                                    echo '</a>';

                                                    foreach ($trayectoria->ranking_lipids as $ranking_lipido) {
                                                        if ($ranking_lipido->lipid_id == $lipido->id) {
                                                            //  echo($ranking_lipido->ranking_total);
                                                            //echo("<br><span> Ranking total </span>");
                                                            //echo($ranking_lipido->quality_total);
                                                            echo '<span>Quality (ranking)</span><br>';
                                                            echo '<ul>';
                                                            echo '<li>Total : ' . filtraValor($ranking_lipido->quality_total) . ' ( ' . filtraValor($ranking_lipido->ranking_total) . ' )</li>';
                                                            echo '<li>Headgroups : ' . filtraValor($ranking_lipido->quality_hg) . ' ( ' . filtraValor($ranking_lipido->ranking_hg) . ' )</li>';
                                                            echo '<li>Tail 1: ' . filtraValor($ranking_lipido->{"quality_sn-1"}) . ' ( ' . filtraValor($ranking_lipido->{"ranking_sn-1"}) . ' )</li>';
                                                            echo '<li>Tail 2: ' . filtraValor($ranking_lipido->{"quality_sn-2"}) . ' ( ' . filtraValor($ranking_lipido->{"ranking_sn-2"}) . ' )</li>';
                                                            echo '</ul>';
                                                        }
                                                    }

                                                    ?>
                                                </div>
                                            </div>
                                        </div> <!--  CARD loop end-->

                                        <?php
                                    } // Foreach End
                                    ?>

                                    </div>
                                </div>
                            </div>



                            <!-- homeAnalysis -->
                            <div role="tabpanel" class="tab-pane bg-solapa card-datos" id="homeAnalysis">

                                <div class="card-body">
                                    @if (!is_null($trayectoria->analisis))
                                        <div class="row p-2">
                                          


                                        </div>
                                        <?php

                                if ($trayectoria->TrayectoriaAnalisisLipidosfunc != null) {
                                    $nlip = 1;
                                    foreach ($trayectoria->TrayectoriaAnalisisLipidosfunc as $key => $value) {
                                        $lipidName = '';
                                        foreach ($trayectoria->lipidos as $key222 => $value222) {
                                            /* echo($key222."<br>");
                             echo($value222->molecule."<br>");
                             echo($value->lipid_id."<br>");*/
                                            if ($value222->id == $value->lipid_id) {
                                                $lipidName = $value222->molecule;
                                            }
                                        }
                                        //$clave = array_search($value->lipid_id,$trayectoria->lipidos);
                                ?>
                                        <div class="row p-2">
                                            <div class="col">

                                                <h3>Order Parameters : '{{ $lipidName }}' </h3>
                                                <a href="{{ $GitHubURLEXP }}{{ $value->order_parameters_file }}">Download
                                                    JSON</a>
                                                
                                               
                                                <div class="chart-container-half"><canvas
                                                        id="myChartOrderParamLipidsg1{{ $nlip }}"></canvas>
                                                </div>
                                                <div class="chart-container-half"><canvas
                                                        id="myChartOrderParamLipidsg2{{ $nlip }}"></canvas>
                                                </div>
                                                <div class="chart-container-half"><canvas
                                                        id="myChartOrderParamLipidsg3{{ $nlip }}"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        $nlip++;
                                    }
                                }

                                if ($trayectoria->TrayectoriasAnalysisHeteromoleculas != null) {
                                    ?>
                                        <div class="row p-2">
                                            <div class="col-sm-12 col-md-12 chart-container-half">
                                                <h3>Order Parameters :
                                                    '{{ $trayectoria->TrayectoriasHeteromoleculas->molecule_name }}'
                                                </h3>
                                                @if ($trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_experiment != null)
                                                    <a
                                                        href="{{ $GitHubURLEXP }}{{ $trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_experiment }}">Download
                                                        JSON</a>
                                                @endif
                                                <canvas id="myChartOrderParam"> </canvas>
                                            </div>
                                        </div>
                                        <?php
                                }
                                ?>

                                        <div class="row" style="padding-bottom:50px!important;">
                                            <div class="col-sm-6 col-md-6 chart-container-half">
                                                <h3>Area per lipid</h3>
                                                <canvas id="myChartAreaxLip"> </canvas>
                                            </div>

                                            <div class="col-sm-6 col-md-6 chart-container-half">
                                                <h3>Form Factor</h3>
                                                <canvas id="myChartFormFact"> </canvas>
                                            </div>
                                        </div>



                                        <div class="row p-2">
                                            <div class="col-sm-12 col-md-12">
                                                <h3> Experimental and Molecular Dynamics based descriptors<h3>
                                            </div>
                                        </div>

                                        <div class="row p-2">
                                            <div class="col-sm-6 col-md-6">
                                                <?php
                                                //if (isset($trayectoria->ranking_global->quality_total))
                                                //echo ($trayectoria->ranking_global->quality_total);

                                                //echo("<span class='txt-titulo'> Ranking total </span>");
                                                //echo($trayectoria->ranking_global->ranking_total);
                                                echo "<span class='txt-titulo'>Quality (ranking)</span><br>";
                                                echo '<ul>';
                                                echo '<li>Total : ' . filtraValor($trayectoria->ranking_global->quality_total) . ' ( ' . filtraValor($trayectoria->ranking_global->ranking_total) . ' )</li>';
                                                echo '<li>Headgroups : ' . filtraValor($trayectoria->ranking_global->quality_hg) . ' ( ' . filtraValor($trayectoria->ranking_global->ranking_hg) . ' )</li>';
                                                echo '<li>Tail 1: ' . filtraValor($trayectoria->ranking_global->{"quality_sn-1"}) . ' ( ' . filtraValor($trayectoria->ranking_global->{"ranking_sn-1"}) . ' )</li>';
                                                echo '<li>Tail 2: ' . filtraValor($trayectoria->ranking_global->{"quality_sn-2"}) . ' ( ' . filtraValor($trayectoria->ranking_global->{"ranking_sn-2"}) . ' )</li>';
                                                echo '</ul>';

                                                ?>

                                            </div>

                                            <div class="col-sm-6 col-md-6">

                                                <span class="txt-titulo">Bilayer thickness :
                                                    <?php
                                                    //if (isset($trayectoria->bilayer_thickness))
                                                    echo round($trayectoria->analisis->bilayer_thickness, 1) . ' nm';
                                                    //var_dump($trayectoria->analisis);
                                                    ?>
                                                </span>
                                                <br>

                                                <span class="txt-titulo">Area per lipid :
                                                    <?php
                                                    //if (isset($trayectoria->bilayer_thickness))
                                                    echo round($trayectoria->analisis->area_per_lipid, 1) . ' &Aring;<sup>2</sup>';
                                                    //var_dump($trayectoria->analisis);
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <div>
                                            <h1>NO DATA</h1>
                                        </div>
                                    @endif
                                </div>

                            </div>


                            <!-- homeExperiment -->
                            <div role="tabpanel" class="tab-pane bg-solapa card-datos" id="homeExperiment">

                                <div class="card-body">
                                    @if (!is_null($trayectoria->analisis))
                                        <?php
                                // HETEROMOLECULE COLESTEROL HETEROMOLECULE COLESTEROL HETEROMOLECULE COLESTEROL HETEROMOLECULE COLESTEROL
                                if ($trayectoria->TrayectoriasAnalysisHeteromoleculas != null) {
                                    if ($trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_experiment != null) {
                                        echo ('<div class="row p-2">
                                                    <div class="col-sm-12 col-md-12 chart-container-half">
                                                        <h3>Order Parameters : ' . $trayectoria->TrayectoriasHeteromoleculas->molecule_name . '</h3>
                                                        <a href="' . $GitHubURLEXP . "/" . substr($trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_experiment, 14) . '">Download JSON</a>
                                                        <canvas id="myChartOrderParamEXP" > </canvas>
                                                    </div>
                                                    </div>');
                                    }
                                }

                                if ($trayectoria->TrayectoriaAnalisisLipidosfunc != null) {

                                    $nlip = 1;

                                    foreach ($trayectoria->TrayectoriaAnalisisLipidosfunc as $key => $value) {

                                        if ($value->order_parameters_experiment != null) {

                                            $lipidName = "";

                                            foreach ($trayectoria->lipidos as $key222 => $value222) {
                                                if ($value222->id == $value->lipid_id) {
                                                    $lipidName = $value222->molecule;
                                                }
                                            }




                                            echo ('<div class="row p-2">
                            <div class="col-sm-12 col-md-12">
                              <h3>Order Parameters :' . $lipidName . ' </h3>
                              <a href="' . $GitHubURLEXP .$value->order_parameters_experiment . '">Download JSON</a>

                              <br>
                            ');

                                            $pathToPeptid = '/mol2d_landscape/' . $lipido->molecule . '.png';
                                            if (file_exists(public_path('storage' . $pathToPeptid))) {
                                                // echo '<a class="portfolio-box" href="'.asset('storage/'.$pathToPeptid).'" title="Click to Zoom">';
                                                //     echo '<span ><b>Show Lipid</b>  </span></br>';
                                                //echo '</a>';

                                                echo (' <div class="m-2">
                              <img class="img-fluid" src="' . asset('storage/' . $pathToPeptid) . '">
                           </div>');
                                            }

                                            echo ('</div>
                 </div>
                          <div class="row p-2">
                            <div class="col-sm-12 col-md-12 chart-container-half">
                              <canvas id="myChartOrderParamLipidsEXPg1' . $nlip . '" > </canvas>
                            </div>
                          </div>

                          <div class="row p-2">
                            <div class="col-sm-12 col-md-12 chart-container-half">
                              <canvas id="myChartOrderParamLipidsEXPg2' . $nlip . '" > </canvas>
                            </div>
                          </div>

                          <div class="row p-2">
                            <div class="col-sm-12 col-md-12 chart-container-half">
                              <canvas id="myChartOrderParamLipidsEXPg3' . $nlip . '" > </canvas>
                            </div>
                          </div>
                       ');

                                            $nlip++;
                                        }
                                    }
                                ?>
                                        @if ($trayectoria->experimentsFF->implode('path')!='')
                                            <div class="row p-2">
                                                <div class="col-sm-12 col-md-12 chart-container-half">
                                                    <h3>Form Factor</h3>
                                                    <canvas id="myChartFormFactEXP"> </canvas>
                                                </div>
                                            </div>
                                        @else
                                            <div>
                                                <h1>NO DATA</h1>
                                            </div>
                                        @endif
                                        <?php
                                }
                                ?>
                                    @else
                                        <div>
                                            <h1>NO DATA</h1>
                                        </div>
                                    @endif
                                </div>

                            </div> <!--  FIn de la solapa de experimentos -->



                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <?php


   

    $cadPath = asset('storage/simulations/' . $trayectoria->git_path);

   

    ?>


    <script>

        function arrayUnique(array) {
          return array.filter(function(item, index, self) {
              return self.indexOf(item) === index;
          });
        }

        function uniqueArrays(arr1, arr2) {
          var combinedArray = arr1.concat(arr2);
          return arrayUnique(combinedArray);
        }

        // This function draws charts using Chart.js
        // canvasId,names,data,data2,step,chartType,title,labelX,labelY,borde,radio,gridOn,responsive,AutoSkiping,showLeyend,xtype
        // Example call:    
        // DrawChart('myChart1', ['A','B','C'], [10,20,30], '', 1, 'bar', 'Sample Chart', 'Categories', 'Values', 1, 5, true, true, true, true, '');

        function DrawChart(canvasId, names, data,names2, data2, step, chartType, title, labelX, labelY, borde, radio, gridOn,
            responsive, AutoSkiping, showLeyend, xtype) {

            var labels1 = names;
            var labels2 = names2;
            var ArrayTop = data;

            var colorList = ['#FF9AA2', '#C7CEEA', '#FFB7B2', '#B5EAD7', '#FFDAC1', '#E2F0CB', ];
            var borderCol = 'rgb(255, 255, 255)';
            var borderCol2 = 'rgb(70, 70, 70)';
            var textCol = '#ffffff';

            var dataTop = {
                labels: labels1,

                datasets: [{
                    label: title,
                    backgroundColor: colorList,
                    borderColor: borderCol,
                    data: ArrayTop,
                    radius: radio,
                    borderWidth: borde,
                    fill: false,
                    spanGaps: false,
                    showLines: true,
                }]
            };

            var dataTop2 = {
                labels: labels2,

                datasets: [{
                    label: title,
                    backgroundColor: colorList,

                    borderColor: borderCol,
                    borderWidth: borde,

                    radius: radio,
                    data: data2,
                    type: 'lineWithErrorBars',

                    fill: false,
                    spanGaps: false,
                    showLines: true,
                }]
            };

            // _____________---______----
            // Data fusion
            var dataTop3



            // OJO ::: esto no esta bien ahora sumo los datos...pero antes usaba para diferenciar una grafica con  datos de error

            var labelFusion = [];
            var data1Fusion = [];
            var data2Fusion = [];

            if (data2 != '') {

              labelFusion =  uniqueArrays(names,names2);
              // Inicializamos
              for (var i = 0; i < labelFusion.length; i++) {
                data1Fusion[i] = NaN;
                data2Fusion[i] = NaN;
              }

              // ponemos los valores en su sitio
              for (var i = 0; i < labelFusion.length; i++) {

                pos1indx = names.indexOf(labelFusion[i]);
                if (pos1indx!=-1){
                  data1Fusion[pos1indx] = data[pos1indx];
                }
                pos2indx = names2.indexOf(labelFusion[i]);
                if (pos2indx!=-1){
                  data2Fusion[pos1indx] = data2[pos2indx];
                }

              }
             // Simulated and Experimental Data fusion

              dataTop = {
                  labels: labelFusion,

                  datasets: [{

                      label: title,
                      backgroundColor: borderCol,
                      borderColor: borderCol,
                      data: data1Fusion,
                      radius: radio,
                      borderWidth: borde,
                      fill: false,
                      spanGaps: false,
                      showLines: true,
                  }, {

                      label: title + "Experiment",
                      backgroundColor: borderCol2,
                      borderColor: borderCol2,
                      data: data2Fusion,
                      radius: radio,
                      borderWidth: borde,
                      fill: false,
                      spanGaps: false,
                      showLines: true,
                  }]
              };


            }

            var options = {

                maintainAspectRatio: false,
                responsive: responsive,
                errorBarColor: {
                    v: ['#ff0000', '#ff0000']
                },
                errorBarWhiskerColor: '#ff0000',

                plugins: {
                    title: {
                        display: true,
                        text: title,
                        color: '#ffffff',

                    },
                    legend: {
                        display: showLeyend,
                        position: 'top',
                        labels: {
                            display: true,
                            color: 'rgb(255, 255, 255)'
                        },
                        title: {
                            display: false,
                            text: title,
                            color: 'rgb(255, 255, 255)'
                        },

                    },
                    tooltip: {

                        callbacks: {
                            title: (items) => {
                                const item = items[0].parsed;
                                //  console.log(item);
                                if (item.yMax != null) {
                                    cad = items[0].label + ` : ` + item.y.toFixed(2) + ` max: ` + item.yMax
                                        .toPrecision(2) + ` min: ` + item
                                        .yMin.toPrecision(2);
                                } else {
                                    cad = items[0].label + ` : ` + item.y.toFixed(2);
                                }
                                return cad;
                                //return items[0].label+` : ` + items[0].parsed;
                            },
                            label: (items) => {
                                return ``;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        //offset: true,
                        //type: 'linear',
                        grid: {
                            display: gridOn,
                            drawBorder: gridOn,
                            drawOnChartArea: gridOn,
                            drawTicks: gridOn,
                            color: '#74C3D8'
                        },
                        display: true,
                        title: {
                            display: true,
                            text: labelX,
                            color: '#ffffff'
                        },
                        ticks: {
                            display: gridOn,
                            autoSkip: AutoSkiping,
                            stepSize: step,
                            beginAtZero: false,
                            color: '#eeeeee'
                        },
                    },
                    y: {
                        // type: 'linear',
                        grid: {
                            display: gridOn,
                            drawBorder: gridOn,
                            drawOnChartArea: gridOn,
                            drawTicks: gridOn,
                            color: '#74C3D8'
                        },
                        display: true,
                        title: {
                            display: true,
                            text: labelY,
                            color: '#ffffff'
                        },
                        ticks: {
                            display: gridOn,
                            color: '#eeeeee'

                        },
                    }
                }

            };

            var config1 = {
                type: chartType, //'doughnut',
                data: dataTop,
                options: options,
            };

            if (xtype != '') {
                config1.options.scales.x.type = xtype;
            }
            //config1.options.scales.y.type = 'linear';

            var ctx1 = document.getElementById(canvasId);

            var myChart1 = new Chart(ctx1, config1);

            //ctx1.canvas.width = window.innerWidth;
            //ctx1.canvas.height = windows.innerHeight;


            //var size = '350px';
            var size = '90%';
            if (myChart1.canvas) {
                myChart1.canvas.parentNode.style.width = size;
                myChart1.canvas.parentNode.style.height = size / 2;
            }
        }
        // canvasId,names,data,data2,step,chartType,title,labelX,labelY,borde,radio,gridOn,responsive,AutoSkiping



  function DrawChartArray(canvasId, names, data, names2, data2,label2, step, chartType, title, labelX, labelY, borde, radio, gridOn,
      responsive, AutoSkiping, showLeyend, xtype) {

      var labels1 = names;
      var ArrayTop = data;

      var colorList = ['#FF9AA2', '#C7CEEA', '#FFB7B2', '#B5EAD7', '#FFDAC1', '#E2F0CB', ];
      var borderCol = 'rgb(255, 255, 255)';
      var borderCol2 = 'rgb(70, 70, 70)';
      var textCol = '#ffffff';
      // _____________
      // Data fusion

      var labelFusion = [];
      var data1Fusion = [];
      var ddd = [];

      // Datos Calculados... los siguientes es e loop de los datos experimentales.
      var dataTop =
           {
              label: "Simulation",//title,
              backgroundColor: colorList[0],
              borderColor: borderCol,
              data: data,
              radius: radio,
              borderWidth: borde,
              fill: false,
              spanGaps: false,
              showLines: true,
          };

      ddd.push(dataTop);

// --------------------------------------------------
      labelFusion = names; // esto son los label de
      names2.forEach((itemArray, i) => {
        labelFusion =  uniqueArrays(labelFusion,itemArray);
      });
      var indpos = 0;
      names2.forEach((itemArray, i) => {
      console.log(itemArray);
        var dataFusion = [];
        // Inicializamos
        for (var i = 0; i < labelFusion.length; i++) {
          dataFusion[i] = NaN;
        }
        // ponemos los valores en su sitio
        for (var i = 0; i < labelFusion.length; i++) {
          pos1indx = itemArray.indexOf(labelFusion[i]);

          if (pos1indx!=-1){
            //console.log(pos1indx + " :: " + labelFusion[i] + " -> " + data2[indpos][pos1indx]);
            dataFusion[i] = data2[indpos][pos1indx];
          }
        }
        indpos=indpos+1;
        console.log(label2);
        var d = {
            label: label2[indpos-1],
            backgroundColor: colorList[indpos],
            borderColor: borderCol,
            data: dataFusion,
            radius: radio,
            borderWidth: borde,
            fill: false,
            spanGaps: false,
            showLines: true,
        };

        ddd.push(d);
      });

      //console.log(ddd);

      dataTop = {
          labels: labelFusion,
          datasets: ddd
      };

      //console.log(dataTop);

      var options = {

          maintainAspectRatio: false,
          responsive: responsive,
          errorBarColor: {
              v: ['#ff0000', '#ff0000']
          },
          errorBarWhiskerColor: '#ff0000',

          plugins: {
              title: {
                  display: true,
                  text: title,
                  color: '#ffffff',

              },
              legend: {
                  display: showLeyend,
                  position: 'top',
                  labels: {
                      display: true,
                      color: 'rgb(255, 255, 255)'
                  },
                  title: {
                      display: false,
                      text: title,
                      color: 'rgb(255, 255, 255)'
                  },

              },
              tooltip: {

                  callbacks: {
                      title: (items) => {
                          const item = items[0].parsed;
                          //  console.log(item);
                          if (item.yMax != null) {
                              cad = items[0].label + ` : ` + item.y.toFixed(2) + ` max: ` + item.yMax
                                  .toPrecision(2) + ` min: ` + item
                                  .yMin.toPrecision(2);
                          } else {
                              cad = items[0].label + ` : ` + item.y.toFixed(2);
                          }
                          return cad;
                          //return items[0].label+` : ` + items[0].parsed;
                      },
                      label: (items) => {
                          return ``;
                      },
                  },
              },
          },
          scales: {
              x: {
                  //offset: true,
                  //type: 'linear',
                  grid: {
                      display: gridOn,
                      drawBorder: gridOn,
                      drawOnChartArea: gridOn,
                      drawTicks: gridOn,
                      color: '#74C3D8'
                  },
                  display: true,
                  title: {
                      display: true,
                      text: labelX,
                      color: '#ffffff'
                  },
                  ticks: {
                      display: gridOn,
                      autoSkip: AutoSkiping,
                      stepSize: step,
                      beginAtZero: false,
                      color: '#eeeeee'
                  },
              },
              y: {
                  // type: 'linear',
                  grid: {
                      display: gridOn,
                      drawBorder: gridOn,
                      drawOnChartArea: gridOn,
                      drawTicks: gridOn,
                      color: '#74C3D8'
                  },
                  display: true,
                  title: {
                      display: true,
                      text: labelY,
                      color: '#ffffff'
                  },
                  ticks: {
                      display: gridOn,
                      color: '#eeeeee'

                  },
              }
          }

      };

      var config1 = {
          type: chartType, //'doughnut',
          data: dataTop,
          options: options,
      };

      if (xtype != '') {
          config1.options.scales.x.type = xtype;
      }
      //config1.options.scales.y.type = 'linear';

      var ctx1 = document.getElementById(canvasId);

      var myChart1 = new Chart(ctx1, config1);

      var size = '90%';
      if (myChart1.canvas) {
          myChart1.canvas.parentNode.style.width = size;
          myChart1.canvas.parentNode.style.height = size / 2;
      }
  }


    function DrawChartArray2(canvasId, data,data2, labelsArray ,step, chartType, title, labelX, labelY, borde, radio, gridOn,
        responsive, AutoSkiping, showLeyend, xtype) {

        var colorList = ['#ffffff', '#00ffff', '#ff00ff', '#0000ff', '#FFDAC1', '#E2F0CB', ];
        var borderCol = 'rgb(255, 255, 255)';
        var borderCol2 = 'rgb(70, 70, 70)';
        var textCol = '#ffffff';

        var ddd = [];

        // Datos Calculados... los siguientes es e loop de los datos experimentales.
        var dataTop =
             {
                label: "Simulation",
                backgroundColor: colorList[0],
                borderColor: colorList[0],
                data: data,
                radius: radio,
                borderWidth: borde,
                fill: false,
                spanGaps: false,
                showLines: true,
                yAxisID: 'y-axis-1',
            };

        ddd.push(dataTop);

        var indpos = 1;
        data2.forEach((itemArray, i) => {
            console.log(indpos);
          var d = {
              label: labelsArray[indpos-1],
              backgroundColor: colorList[indpos],
              borderColor: colorList[indpos],
              data: itemArray,
              radius: radio,
              borderWidth: borde,
              fill: false,
              spanGaps: false,
              showLines: true,
              yAxisID: 'y-axis-2',
          };
          ddd.push(d);
          indpos=indpos+1;


        });

        dataTop = {
            labels: "",
            datasets: ddd
        };

        var options = {

            maintainAspectRatio: false,
            responsive: responsive,
            errorBarColor: {
                v: ['#ff0000', '#ff0000']
            },
            errorBarWhiskerColor: '#ff0000',

            plugins: {
                title: {
                    display: true,
                    text: title,
                    color: '#ffffff',

                },
                legend: {
                    display: showLeyend,
                    position: 'top',
                    labels: {
                        display: true,
                        color: 'rgb(255, 255, 255)'
                    },
                    title: {
                        display: false,
                        text: title,
                        color: 'rgb(255, 255, 255)'
                    },

                },
                tooltip: {

                    callbacks: {
                        title: (items) => {
                            const item = items[0].parsed;
                            //  console.log(item);
                            if (item.yMax != null) {
                                cad = items[0].label + ` : ` + item.y.toFixed(2) + ` max: ` + item.yMax
                                    .toPrecision(2) + ` min: ` + item
                                    .yMin.toPrecision(2);
                            } else {
                                cad = items[0].label + ` : ` + item.y.toFixed(2);
                            }
                            return cad;
                            //return items[0].label+` : ` + items[0].parsed;
                        },
                        label: (items) => {
                            return ``;
                        },
                    },
                },
            },
            scales: {
                x: {
                    //offset: true,
                    //type: 'linear',
                    grid: {
                        display: gridOn,
                        drawBorder: gridOn,
                        drawOnChartArea: gridOn,
                        drawTicks: gridOn,
                        color: '#74C3D8'
                    },
                    display: true,
                    title: {
                        display: true,
                        text: labelX,
                        color: '#ffffff'
                    },
                    ticks: {
                        display: gridOn,
                        autoSkip: AutoSkiping,
                        stepSize: step,
                        beginAtZero: false,
                        color: '#eeeeee'
                    },
                },
                'y-axis-1': {
                    // type: 'linear',

                    beginAtZero: true,
                    position: 'left',
                    grid: {
                        display: gridOn,
                        drawBorder: gridOn,
                        drawOnChartArea: gridOn,
                        drawTicks: gridOn,
                        color: '#ffffff',

                    },
                    display: true,
                    title: {
                        display: true,
                        text: labelY + " simulation",
                        color: '#ffffff'
                    },
                    ticks: {

                        display: gridOn,
                        color: '#eeeeee'
                    },
                },
                'y-axis-2': {
                    // type: 'linear',

                    position: 'right',
                      beginAtZero: true,
                    grid: {
                       display: gridOn,
                        drawBorder: gridOn,
                        //drawOnChartArea: gridOn,
                        drawTicks: gridOn,
                        color: '#00ffff',
                         drawOnChartArea: false, // Dibujamos la linea horizontal del grid
                    },
                    display: true,
                    title: {
                        display: true,
                        text: labelY + " experiment",
                        color: '#ffffff'
                    },
                    ticks: {

                        display: gridOn,
                        color: '#eeeeee'
                    },
                }
            }

        };

        var config1 = {
            type: chartType,
            data: dataTop,
            options: options,
        };

        if (xtype != '') {
            config1.options.scales.x.type = xtype;
        }
        //config1.options.scales.y.type = 'linear';

        var ctx1 = document.getElementById(canvasId);

        var myChart1 = new Chart(ctx1, config1);

        var size = '90%';
        if (myChart1.canvas) {
            myChart1.canvas.parentNode.style.width = size;
            myChart1.canvas.parentNode.style.height = size / 2;
        }
    }

        <?php
        /*
var_dump($trayectoria);
die();
 */
        if ($trayectoria->TrayectoriasAnalysisHeteromoleculas != null) {
            //genDataParamOrde($GitHubURL, $trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_file, $DataStr, $DataValue, $DataError, $maxValue, $minValue, '');
            genDataParamOrdeFusion($GitHubURL, $trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_file, $DataStr, $DataValue, $DataError, $maxValue, $minValue, '');

            //echo 'DrawChart("myChartOrderParam",[' . $DataStr . '],[' . $DataError . '],[' . $DataError . '],1,"line","Order parameters","","-SCH",1,5,true,true,false);';
            echo 'DrawChart("myChartOrderParam",[' . $DataStr . '],[' . $DataValue . '],[],[],1,"line","Order parameters","","SCH",1,5,true,true,false,"");';
        }
       

        if ($trayectoria->TrayectoriaAnalisisLipidosfunc != null) {
            $nlip = 1;

            foreach ($trayectoria->TrayectoriaAnalisisLipidosfunc as $key => $value) {
                if (urlFileExist2($GitHubURL . $value->order_parameters_file)) {
                    $ind =0; //LeeIndices($GitHubURL, $value->order_parameters_file); // indices de texto...


                    // $value->order_parameters_file ESTO SALE DE LA TABLA VIENE LA RUTA Y EL FICHERO.
                    genDataParamOrdeFusion($GitHubURL, $value->order_parameters_file, $DataStr, $DataValue, $DataError, $maxValue, $minValue, 'G1');

                    $DataExpStrArray = array();
                    $DataExpValueArray = array();
$DataExpLabelArray = array();
                    // HACK CARGA DE EXPERIMETOS VAN A SER MAS DE UNO ASI QUE A VER SOMO SE HACE ESTO...
                    // HACK (english) LOADING EXPERIMENTS THEY WILL BE MORE THAN () ONE SO LET'S SEE HOW TO DO THIS...
                    // Problem is that now we have multiple experimental data for the same order parameter simulation data.
                    // So we have to load all the experimental data and plot them together with the simulation data
                    foreach ($trayectoria->experimentsOP as $keyOP => $valueOP) {

                        if (!is_null($valueOP->path)) {
                            if (!empty(trim($valueOP->path))) {
                              genDataParamOrderExperiment($GitHubURLEXP, 
                              $valueOP->path, 
                              $DataExpStr, 
                              $DataExpValue, 
                              $DataExpError, $maxValue, $minValue, 'G1', $ind, 
                              $nlip);
                              
                              $DataExpStrArray[] = $DataExpStr;
                              $DataExpValueArray[] = $DataExpValue;
                              $DataExpLabelArray[] = $valueOP->doi;
                            }
                        }
                    } // Foreach

                    // DIBUJAMOS LA PRIMERA GRAFICA

                     //echo 'DrawChart("myChartOrderParamLipidsg1' . $nlip . '",[' . $DataStr . '],[' . $DataValue . '],[' . $DataExpStr . '],[' . $DataExpValue . '],1,"line","Tail S1","C-H bond","SCH",1,5,true,true,false,true,"");';
                     echo 'DrawChartArray("myChartOrderParamLipidsg1' . $nlip . '",[' . $DataStr . '],[' . $DataValue . '],'.json_encode($DataExpStrArray) . ',' . json_encode($DataExpValueArray) . ','.json_encode($DataExpLabelArray).',1,"line","Tail S1","C-H bond","SCH",1,5,true,true,false,true,"");';
                     echo "\r\n";

                    //
                    genDataParamOrdeFusion($GitHubURL, $value->order_parameters_file, $DataStr, $DataValue, $DataError, $maxValue, $minValue, 'G2');

                    if (!is_null($value->order_parameters_experiment)) {
                        if (!empty(trim($value->order_parameters_experiment))) {
                          $DataExpStrArray = array();
                          $DataExpValueArray = array();
                          $DataExpLabelArray = array();
                            foreach ($trayectoria->experimentsOP as $keyOP => $valueOP) {

                                if (!is_null($valueOP->path)) {
                                    if (!empty(trim($valueOP->path))) {
                                      genDataParamOrderExperiment(
                                            $GitHubURLEXP, 
                                            $valueOP->path, 
                                            $DataExpStr, 
                                            $DataExpValue, 
                                            $DataExpError, 
                                            $maxValue, 
                                            $minValue, 
                                            'G2', 
                                            $ind,
                                            $nlip
                                        );
                                      $DataExpStrArray[] = $DataExpStr;
                                      $DataExpValueArray[] = $DataExpValue;
                                        $DataExpLabelArray[] = $valueOP->doi;
                                      //$DataExpErrorArray[] = $DataExpError;
                                    }
                                }
                            } // Foreach
                        }
                    }
                    echo 'DrawChartArray("myChartOrderParamLipidsg2' . $nlip . '",[' . $DataStr . '],[' . $DataValue . '],' . json_encode($DataExpStrArray) . ',' . json_encode($DataExpValueArray) . ','.json_encode($DataExpLabelArray).',1,"line","Tail S2","C-H bond","SCH",1,5,true,true,false,true,"");';
                    echo "\r\n";

                    genDataParamOrdeFusion($GitHubURL, $value->order_parameters_file, $DataStr, $DataValue, $DataError, $maxValue, $minValue, 'G3');
                  if (!is_null($value->order_parameters_experiment)) {
                        if (!empty(trim($value->order_parameters_experiment))) {
                              $DataExpStrArray = array();
                              $DataExpValueArray = array();
                              $DataExpLabelArray = array();
                                foreach ($trayectoria->experimentsOP as $keyOP => $valueOP) {
                                    if (!is_null($valueOP->path)) {
                                        if (!empty(trim($valueOP->path))) {
                                          genDataParamOrderExperiment($GitHubURLEXP, 
                                            $valueOP->path, 
                                            $DataExpStr, 
                                            $DataExpValue, 
                                            $DataExpError, 
                                            $maxValue, 
                                            $minValue, 
                                            'G3', 
                                            $ind,
                                            $nlip);
                                          $DataExpStrArray[] = $DataExpStr;
                                          $DataExpValueArray[] = $DataExpValue;
                                          $DataExpLabelArray[] = $valueOP->doi;
                                          //$DataExpErrorArray[] = $DataExpError;
                                        }
                                    }
                                } // Foreach
                        }
                    }

                    //echo 'DrawChart("myChartOrderParamLipidsg3' . $nlip . '",[' . $DataStr . '],[' . $DataValue . '],' . json_encode($DataExpStr) . ',' . json_encode($DataExpValue) . ',1,"line","Headgroup","C-H bond","SCH",1,5,true,true,false,true,"");';
                    echo 'DrawChartArray("myChartOrderParamLipidsg3' . $nlip . '",[' . $DataStr . '],[' . $DataValue . '],' . json_encode($DataExpStrArray) . ',' . json_encode($DataExpValueArray) . ','.json_encode($DataExpLabelArray).',1,"line","Headgroup","C-H bond","SCH",1,5,true,true,false,true,"");';
                    echo "\r\n";
                    $nlip++;
                } else {
                    // echo "console.log('FilenotFound" . $GitHubURL . "')";
                }
            }
        }

        // HETERO MOLECULES Experiment
        //var_dump($trayectoria);
        //die();
        if (!is_null($trayectoria->TrayectoriasAnalysisHeteromoleculas)) {
            if (!empty(trim($trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_experiment))) {
                //die('4');
                if (urlFileExist2($GitHubURLEXP . $trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_experiment)) {
                    genDataParamOrderExperiment($GitHubURLEXP, $trayectoria->TrayectoriasAnalysisHeteromoleculas->order_parameters_experiment, $DataStr, $DataValue, $DataError, $maxValue, $minValue, '', '');

                    echo 'DrawChart("myChartOrderParamEXP",[' . $DataStr . '],[' . $DataValue . '],"","",1,"line","Order parameters","","SCH",1,5,true,true,false,false,"");';
                }
            }
        }
      
        // ANALISIS ANALISIS ANALISIS ANALISIS ANALISIS

        if (!is_null($trayectoria->analisis)) {
            if (urlFileExist2($GitHubURL . $trayectoria->analisis->area_per_lipid_file)) {
                if (genData($GitHubURL, $trayectoria->analisis->area_per_lipid_file, $DataStr, $DataValue, $maxValue, $minValue, '', '0.001')) {
                    echo 'DrawChart("myChartAreaxLip",[' . $DataStr . '],[' . $DataValue . '],"","",0.0001,"line","Area per lipid","Time (ns)","Area per Lipid (\u{212B}\u{B2})",1,0,true,true,true,false,"");';
                    echo "\r\n";
                }
            }
            if (urlFileExist2($GitHubURL . $trayectoria->analisis->form_factor_file)) {
                // Datos experimentales con el nuevo formato de mysql_tablename
                // EXPERIMENT  EXPERIMENT  EXPERIMENT  EXPERIMENT  EXPERIMENT  EXPERIMENT
                $FFpath = $trayectoria->experimentsFF->implode('path');
                if (genData2($GitHubURL, $trayectoria->analisis->form_factor_file, $DataStr, $DataValue, $maxValue, $minValue, 1)) {
                    $DataExpValueArray = array();
                    $DataExpLabelsArray = array();
                    foreach ($trayectoria->experimentsFF as $keyFF => $valueFF) {
                      genData2Array($GitHubURLEXP, $valueFF->path, $DataExpStr, $DataExpValue, $maxValue, $minValue, 1);
                      $DataExpValueArray[] = $DataExpValue;
                      $DataExpLabelsArray[] = $valueFF->doi;
                    }
                      $cleanDataExp = str_replace('"',"",json_encode($DataExpValueArray));
                        echo 'DrawChartArray2("myChartFormFact",[' . $DataValue . '],' . $cleanDataExp . ','.json_encode($DataExpLabelsArray).',0.01,"line","Form factor","Qz (\u{212B}\u{AF}\u{B9}) ","  |F(Qz)|  ",1,0,true,true,true,true,"linear");';
                        echo "\r\n";
                }
            }
        }
        ?>
        // === include 'setup' then 'config' above ===

        const labels1 = [<?php echo $l1lipidStr; ?>];
        const labels2 = [<?php echo $l2lipidStr; ?>];
        var colorList = ['#FF9AA2', '#C7CEEA', '#FFB7B2', '#B5EAD7', '#FFDAC1', '#E2F0CB', ];
        var borderCol = 'rgb(128, 128, 128)';
        var textCol = '#ffffff';
        var lastpressed = "";

        const RealNameAsoc = {
            <?php echo $RealNameAsoc; ?>
        };

        const ArrayTop = [<?php echo $l1lipidNumStr; ?>];

        const ArrayBottom = [<?php echo $l2lipidNumStr; ?>];


        const dataTop = {
            labels: labels1,
            datasets: [{
                label: 'leaflet_1',
                backgroundColor: colorList,
                borderColor: borderCol,
                data: ArrayTop,
            }]
        };
        const dataBottom = {
            labels: labels2,

            datasets: [{
                label: 'leaflet_bottom',
                backgroundColor: colorList,
                borderColor: borderCol,
                data: ArrayBottom,

            }]
        };

        var options = {
            algo: true,
            maintainAspectRatio: true,
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: 'rgb(255, 255, 255)'
                    },
                    onClick(a, b, c) {

                        console.log(RealNameAsoc[b.text]);

                        //console.log("select all; color darkgray; select ["+b.text+"]; color "+b.fillStyle+";");
                        if (lastpressed == b.text) {
                            Jmol.script(jmolApplet1, "<?php echo $CadSelectMem; ?>");
                            lastpressed = "";
                        } else {
                            Jmol.script(jmolApplet1, "select all; color white; select [" + RealNameAsoc[b.text] +
                                "]; color '" + b.fillStyle + "';");
                            lastpressed = b.text;
                        }
                    },
                },
                labels: {
                    fontColor: "blue",
                    fontSize: 18
                },
                tooltip: {
                    callbacks: {
                        footer: (items) => {
                            // Items [0] es un array de un solo elemento...
                            let sum = 0;
                            for (var i = 0; i < items[0].dataset.data.length; i++) {
                                sum = sum + parseInt(items[0].dataset.data[i]);
                            };
                            return 'Total: ' + sum;
                        },
                    },
                },
            },
        };

        const config1 = {
            type: 'doughnut',
            data: dataTop,
            options: options,
        };
        const config2 = {
            type: 'doughnut',
            data: dataBottom,
            options: options,

        };

        var ctx1 = document.getElementById("myChart1");
        var ctx2 = document.getElementById("myChart2");


        var myChart1 = new Chart(ctx1, config1);
        var myChart2 = new Chart(ctx2, config2);

        var size = '256px';

        myChart1.canvas.parentNode.style.height = size;
        myChart1.canvas.parentNode.style.width = size;
        myChart2.canvas.parentNode.style.height = size;
        myChart2.canvas.parentNode.style.width = size;
    </script>

    <?php
      } // IF git_path esta vacio o no
    ?>
@endsection

@section('meta-tags')
     {!! $metadatos_head !!}
@endsection
