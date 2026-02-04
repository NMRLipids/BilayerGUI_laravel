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

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use Maatwebsite\Excel\Facades\Excel;



class NewAdvancedSearchController extends Controller
{



  // Se manda los datos a la vista de resultado
  public function resultsGeneral(Request $request)
  {

    //`lipids`.full_name,
    //`peptides`.name as peptide_name,`peptides`.total_charge,`peptides`.sequence,`peptides`.activity,
    //`trajectories_peptides`.number_peptides,
    //,`forcefields`.resolution ,
    //`water_models`.full_name as wm_full_name,
    //`membranes`.name as mem_name,`membranes`.model as mem_model,
    //`ions`.full_name as ion_full_name,
    //`water_models`.short_name as wm_short_name,

//(SELECT COUNT(trajectories_analysis_lipids.id) FROM trajectories_analysis_lipids
  //WHERE trajectories_analysis_lipids.trajectory_id = trajectories.id AND trajectories_analysis_lipids.order_parameters_experiment ='') AS experimentdatacount,


    $Selects = "`lipids`.molecule as lipid_name,
    `forcefields`.name as ff_name,


    `ions`.molecule as ion_short_name,
    `trajectories_ions`.number as number_ions,
    `trajectories_analysis`.ff_quality,
    `trajectories_analysis`.op_quality_total,

    `trajectories_lipids`.leaflet_1,`trajectories_lipids`.leaflet_2,


    (SELECT
            COUNT(trajectories_experiments_OP.id)
        FROM
            trajectories_experiments_OP
        WHERE
            trajectories_experiments_OP.trajectory_id = trajectories.id)
    AS experimentdatacountOP,
    (SELECT
            COUNT(trajectories_experiments_FF.id)
        FROM
            trajectories_experiments_FF
        WHERE
            trajectories_experiments_FF.trajectory_id = trajectories.id)
    AS experimentdatacountFF,
    `trajectories`.temperature as temperature
    ";

    
    $Joins = " left join `trajectories_lipids` on `trajectories`.`id` = `trajectories_lipids`.`trajectory_id`

    


    left join `trajectories_ions` on `trajectories`.`id` = `trajectories_ions`.`trajectory_id`
 
    left join `trajectories_membranes` on `trajectories`.`id` = `trajectories_membranes`.`trajectory_id`
    left join `trajectories_analysis` on `trajectories`.`id` = `trajectories_analysis`.`trajectory_id`

    left join `forcefields` on `forcefields`.`id` = `trajectories`.`forcefield_id`
    left join `lipids` on `lipids`.`id` = `trajectories_lipids`.`lipid_id`

    left join `ions` on `ions`.`id` = `trajectories_ions`.`ion_id`
 

     left join `membranes` on `membranes`.`id` = `trajectories_membranes`.`membrane_id` ";

    $GroupBy = " group By trajectories.id, `trajectories_analysis`.`op_quality_total` ";

    $baseQuery = "select
    %s
    %s
    from trajectories
    %s
    WHERE %s %s
    ORDER BY `trajectories_analysis`.`op_quality_total` DESC
    ";

    //

    //echo ($baseQuery);
    //die();
    //DB::enableQueryLog();
    /*var_dump($request->path());
      var_dump($request->input('lipidos'));
      var_dump($request->input('lipidos_operador'));*/
    $filtrosPrincipales = Filtros::all(); //Filtros::filtrosEntidades();

    // Consulta de las valores analiticos
    $ArraySqlAnalytics = [];
    $inputs = $request->all();
    $jum = 0;
    foreach ($inputs as $key => $value) {
      // code...
      if (is_string($value)) {

        if (str_contains($key,"temperature")){

          if (str_ends_with($key, "-start")) {

            $a = "(`trajectories`.`" . substr($key, 0, -6) . "`>=" . $value;
            $a = $a . " AND ";
            $a = $a . "`trajectories`.`" . substr($key, 0, -6) . "`<=" . $request->input(substr($key, 0, -6) . "-end");
            $a = $a . ") ";

            if ($value != 0 && $request->input(substr($key, 0, -6) . "-end") != 0)
              $ArraySqlAnalytics[] = $a;
          }

        } else {


          if (str_ends_with($key, "-start")) {

            $a = "(`trajectories_analysis`.`" . substr($key, 0, -6) . "`>=" . $value;
            $a = $a . " AND ";
            $a = $a . "`trajectories_analysis`.`" . substr($key, 0, -6) . "`<=" . $request->input(substr($key, 0, -6) . "-end");
            $a = $a . ") ";

            if ($value != 0 && $request->input(substr($key, 0, -6) . "-end") != 0)
              $ArraySqlAnalytics[] = $a;
          }

        }
      }
    }

    $sqlAnalytics = implode(" AND ", $ArraySqlAnalytics);

    //  echo ($sqlAnalytics);
    // echo('<br><br><br>');
    // var_dump($filtrosPrincipales);
    // die();
    // echo('<br><br><br>');
    $cadSql = "";
    $cadSqlnueva = "";
    $cadSqlNot = "";
    $cadSqlNotNueva = "";
    $tableName = "trajectories"; // ForceField no esta en la tabla de trayectorias con nombre,
    $fieldName = "name"; // como valor por defecto, esto es un problema con los campos de las trayectorias
    // Nuevo sistema de busqueda
    $join_count = "";
    $where = "";
    $join = "";
    $JoinFinal = "";
    $search_char = array(" ", "-", ".", ","); // Espacios y guiones se cambia por barra baja
    $hackHeteromolecule = array();
    $hackHeteromoleculeOp = array();

    //var_dump($filtrosPrincipales['label']);
    foreach ($filtrosPrincipales as $key => $value) {

      /*
      var_dump($request->input($key));
      var_dump($request->input($key . '_operador'));
      echo ('<br>');
      echo $key . " :: <BR>";
      var_dump($value);
      echo ('<br>');

      die();


      array(2) { [1]=> string(4) "DDPC" [2]=> string(4) "CHOL" }
      array(2) { [1]=> string(3) "and" [2]=> string(3) "and" }
      lipidos ::
      object(App\Filtros\Lipidos)#330 (14) { ["codigo"]=> string(7) "lipidos" ["columna"]=> string(10) "short_name" ["tipo"]=> int(2) ["label"]=> string(6) "Lipids" ["modelo"]=> object(App\Lipido)#333 (27) { ["table":protected]=> string(6) "lipids" ["connection":protected]=> NULL ["primaryKey":protected]=> string(2) "id" ["keyType":protected]=> string(3) "int" ["incrementing"]=> bool(true) ["with":protected]=> array(0) { } ["withCount":protected]=> array(0) { } ["perPage":protected]=> int(15) ["exists"]=> bool(false) ["wasRecentlyCreated"]=> bool(false) ["attributes":protected]=> array(0) { } ["original":protected]=> array(0) { } ["changes":protected]=> array(0) { } ["casts":protected]=> array(0) { } ["classCastCache":protected]=> array(0) { } ["dates":protected]=> array(0) { } ["dateFormat":protected]=> NULL ["appends":protected]=> array(0) { } ["dispatchesEvents":protected]=> array(0) { } ["observables":protected]=> array(0) { } ["relations":protected]=> array(0) { } ["touches":protected]=> array(0) { } ["timestamps"]=> bool(true) ["hidden":protected]=> array(0) { } ["visible":protected]=> array(0) { } ["fillable":protected]=> array(0) { } ["guarded":protected]=> array(1) { [0]=> string(1) "*" } } ["operador"]=> string(3) "and" ["valor"]=> string(0) "" ["tooltip"]=> string(14) "POPC, POPG ..." ["visible"]=> bool(true) ["table"]=> string(6) "lipids" ["fields"]=> string(8) "molecule" ["join_count"]=> string(85) " COUNT(CASE WHEN trajectories_lipids.lipid_name = '%s' THEN 1 ELSE NULL END) AS `%s` " ["where"]=> string(13) "lipid.%s = 1 " ["join"]=> string(592) " INNER JOIN( SELECT lipid.id FROM ( SELECT trajectories_lipids.trajectory_id AS id, %s FROM `trajectories_lipids` WHERE 1 GROUP BY trajectories_lipids.trajectory_id ) lipid WHERE %s ) lipid_select ON trajectories.id = lipid_select.id " }
      */

      $act_request = $request->input($key);
      $act_request_operador = $request->input($key . '_operador');
      /*var_dump($act_request);
      echo '<br>';
      var_dump($act_request_operador);
      echo '<br>';
*/
      // HACK para el CHOL!!
      // var_dump($key);
      if ($key == "lipidos" && !is_null($act_request)) {
        $cn = 1;
        $hackHeteromolecule = array();
        $hackHeteromoleculeOp = array();
        $hackHeteromolecule[] = "";
        $hackHeteromoleculeOp[] = "";
        foreach ($act_request as $lip_request) {
          if (str_contains(strtoupper($lip_request), 'CH') || str_contains(strtoupper($lip_request), 'CLOL')) {
            $hackHeteromolecule[] = strtoupper($lip_request);
            $hackHeteromoleculeOp[] = $act_request_operador[$cn];
          }
          $cn++;
        }
        if (isset($hackHeteromolecule[0])) {
          unset($hackHeteromolecule[0]);
          unset($hackHeteromoleculeOp[0]);
        }

        if (isset($hackHeteromolecule[1])) {
          $request->merge(['moleculas' => $hackHeteromolecule]);
          $request->merge(['moleculas_operador' => $hackHeteromoleculeOp]);
        }
      }



      if (!is_null($act_request)) {
        $tableName = "trajectories";
        $fieldName = "name";
        // De Value es el donde se obtiene el nombre real de la tabla
        foreach ($value as $key2 => $value2) {
          if ($key2 == "table") $tableName = $value2;
          if ($key2 == "fields") $fieldName = $value2;
          if ($key2 == "columna") $fieldName = $value2;

          if ($key == 'lipidos' && (str_contains(strtoupper($lip_request), 'CH') || str_contains(strtoupper($lip_request), 'CLOL'))) {
          } else {
            // Nueva BusquedaAvanzadaExport––
            if ($key2 == "join_count") $join_count = $value2;
            if ($key2 == "where") $where = $value2;
            if ($key2 == "join") $join = $value2;
          }
          //echo ($key2." ->> ".$value2."<br>");
        }

        /*var_dump($key);
        echo '<br>';*/
        if ($key == "moleculas" && isset($hackHeteromolecule[1])) {
          //unset($hackHeteromolecule[0]);
          //unset($hackHeteromoleculeOp[0]);

          $act_request = $hackHeteromolecule;
          $act_request_operador = $hackHeteromoleculeOp;

          $tableName = 'heteromolecules';
          $fieldName = 'name';
          ### ICICICICICICI
          $join_count = "
          --- ICICIC
          COUNT( CASE WHEN trajectories_heteromolecules.molecule_name = '%s' THEN 1 ELSE NULL END) AS `%s`";
          $where = "  heteromolecule.%s = 1 ";
          $join = "INNER JOIN(
                       SELECT heteromolecule.id
                       FROM (
                           SELECT trajectories_heteromolecules.trajectory_id AS id, %s
                           FROM trajectories_heteromolecules
                           WHERE 1
                           GROUP BY trajectories_heteromolecules.trajectory_id
                   		 ) heteromolecule
                       WHERE %s
                       ) heteromolecule_select
                      ON trajectories.id = heteromolecule_select.id";
        }

        /*
        var_dump($join);
        die();
        */

        // PARCHE
        if ($fieldName == "force_field") {
          $tableName = "forcefields";
          $fieldName = "name";
        }
        // ------

        //$tableName =$value->table;
        //$fieldName =$value->fields; // esto deveria de ser un array
        // Recorro los array de consultas
        //$limit = count($request->input($key.'_operador')); // Este parametro ya no se usa
        $ind = 1;
        $join_count_array = [];
        $cadSqlnueva = "";
        $cadSqlNotNueva = "";

        if ($act_request_operador !== null) { // comprobamos si existe el paremetro de busqueda
          foreach ($act_request as $key3 => $value3) {
            if ($key == 'lipidos' && (str_contains(strtoupper($value3), 'CH') || str_contains(strtoupper($value3), 'CLOL'))) {
            } else {

              // Limpiamos la cadena para no tener problemas en la sql
              $valueClean = str_replace($search_char, "_", $value3) . $ind;
              if (is_numeric($value3)) $valueClean = "n" . $valueClean;

              if ($act_request_operador[$ind] != "not") {

                if ($cadSql != "") {
                  $cadSql .= " " . $act_request_operador[$ind] . " ";
                }
                if ($cadSqlnueva != "") {
                  $cadSqlnueva .= " " . $act_request_operador[$ind] . " ";
                }
                // parche para las membranas
                if ($tableName == "membranes") {
                  if (is_numeric($value3)) {
                    $fieldName = "id";
                  }
                }
                // ------------------------


                $cadSql .= " " . $tableName . "." . $fieldName;
                //$cadSql.= " like ";
                //$cadSql.=  "'%".$value3."%'";
                $cadSql .= " = ";
                $cadSql .=  "'" . $value3 . "'";

                // creamos la cadena de los JOINs

                $num_placeholders = substr_count($join_count, '%');

                if ($num_placeholders==3) {
                  // Esto es un Hack para el ranking_global
                  $magnitud = strlen(substr(strrchr($value3, "."), 1));

                  // Calcula la tolerancia relativa
                  $tolerancia = pow(10, -$magnitud);

                  $join_count_array[] = sprintf($join_count, $value3-$tolerancia, $value3+$tolerancia, $valueClean);
                } else {
                  $join_count_array[] = sprintf($join_count, $value3, $valueClean);
                }



                $cadSqlnueva .= sprintf($where, $valueClean);
                // ----

              } else {
                if ($cadSqlNot != "") {
                  $cadSqlNot .= " AND "; // esto es la lista de NOTs asi que los uno con ANDs

                }
                if ($cadSqlNotNueva != "")  $cadSqlNotNueva .= " AND ";
                $cadSqlNot .= " " . $tableName . "." . $fieldName;
                //$cadSqlNot.= " like ";
                //$cadSqlNot.=  "'%".$value3."%'";
                $cadSqlNot .= " = ";
                $cadSqlNot .=  "'" . $value3 . "'";

                $join_count_array[] = sprintf($join_count, $value3, $valueClean);
                $cadSqlNotNueva .= sprintf($where, $valueClean);
              }
            } // HACK

            $ind = $ind + 1;
          } // fin del bucle para los campos repetidos

          // Esta cadena es un inner join especia para todos los temas de And OR y NOT.. not no esta del todo OK
          // Not -> seria un AND not (algo1 and algo2 ) se tiene que agrupar los Nots en un bloque
          if (strlen($cadSqlNotNueva) > 0) {
            if (strlen($cadSqlnueva) > 0) {
              $cadSqlnueva .= " AND NOT (" . $cadSqlNotNueva . ")";
            } else {
              $cadSqlnueva .= "  NOT (" . $cadSqlNotNueva . ")";
            }
          }
          //echo ($cadSqlNotNueva);
          //die();
          $JoinFinal .= sprintf($join, implode(', ', $join_count_array), $cadSqlnueva) . " ";
        }
      }

      //echo('<br><br><br>');
    }

    /*
    var_dump($JoinFinal);
    die();
*/

    // Sumamos al WHERE los Campos de Analitycs solo si hay algo
    if (trim($sqlAnalytics) != "") {
      if (trim($cadSql) == "") {
        $cadSql = $sqlAnalytics;
      } else {
        $cadSql = $cadSql . " AND (" . $sqlAnalytics . ")";
      }
    }
    /*
    var_dump($cadSql) . "<br>";
    var_dump($cadSqlNot) . "<br>";
    die();
*/
    //Extraemos los IDs de la consulta
    // Consulta Negativa
    $IdListNot = array();
    //echo $cadSqlNot."<br>";
    if ($cadSqlNot != "") {
      $ConsultaIDsNot = sprintf($baseQuery, "trajectories.id", "", $Joins, $cadSqlNot, $GroupBy);
      // echo $ConsultaIDsNot."<br>";
      $trayectoriasIDNot = DB::select($ConsultaIDsNot);

      foreach ($trayectoriasIDNot as $keyNot => $valueNot) {
        $IdListNot[] = $valueNot->id;
      }
      // var_dump($IdListNot);
    }
    // Consulta Positiva
    if ($cadSql == "") $cadSql = " 1=1 ";
    $ConsultaIDsOld = sprintf($baseQuery, "trajectories.id", "", $Joins, $cadSql, $GroupBy);

    // NUEVA VERSION
    $ConsultaIDs = "SELECT trajectories.id FROM trajectories " . $JoinFinal . " LEFT JOIN `trajectories_analysis` ON `trajectories`.`id` = `trajectories_analysis`.`trajectory_id`";
    if (strlen($sqlAnalytics) > 0) $ConsultaIDs .= " WHERE " . $sqlAnalytics;

    //echo $ConsultaIDs."<br>";

    // var_dump($ConsultaIDs);
    // die();

    //SELECT trajectories.id FROM trajectories INNER JOIN( SELECT lipid.id FROM ( SELECT trajectories_lipids.trajectory_id AS id, COUNT(CASE WHEN trajectories_lipids.lipid_name = 'CHOL' THEN 1 ELSE NULL END) AS CHOL1 FROM `trajectories_lipids` WHERE 1 GROUP BY trajectories_lipids.trajectory_id ) lipid WHERE lipid.CHOL1 = 1 ) lipid_select ON trajectories.id = lipid_select.id LEFT JOIN `trajectories_analysis` ON `trajectories`.`id` = `trajectories_analysis`.`trajectory_id`

    // Buscamos los ID positivos
    $trayectoriasID = DB::select($ConsultaIDs);
    // Pasamos los IDs a un Array
    $IdList = array();
    foreach ($trayectoriasID as $key => $value) {
      if (!empty($IdListNot)) {
        if (!in_array($value->id, $IdListNot)) $IdList[] = $value->id;
      } else {
        $IdList[] = $value->id;
      }
    }
    // Si tenemos IDs negativos los resto del array

    $cadSql = "trajectories.id in (%s)";
    //var_dump ($IdList);
    //echo implode(', ',$IdList);
    //echo count($IdList);
    $cadSql2 = sprintf($cadSql, implode(', ', $IdList));

    // var_dump($cadSql2);
    // die();

    // Vamos a cambiar la forma de comparar
    // Limpiaremos la sesion de varibles de comparacion
    // Con este formato -> CompareID752
    // y pasaremos toda la lista de nuevos IDs a la session.

    $listaIdsSesson = session()->all();
    // Borrramos los IDs que estaban en session
    /*
         foreach ($listaIdsSesson as $key => $value) {
           if (gettype($value)!='array' && strpos($key, 'CompareID')!==false){
             $request->session()->forget($key);
           }
         }
         // Damos de alta los nuevos IDs
         if ( count($IdList) > 0 )
         {
           for ($i=0; $i < count($IdList); $i++) {
             $cadid = 'CompareID'.$IdList[$i];
             $request->session()->put($cadid,'1');
           }
         }
         */
    // Fin --- update session lists IDs

    $ConsultaFinal = sprintf($baseQuery, "trajectories.*,", $Selects, $Joins, $cadSql2, "");

    // echo $ConsultaIDs."<br>";
    //  echo $ConsultaFinal;
    //  die();

    $trayectorias = "";
    if (!empty($IdList))
      $trayectorias = collect(DB::select($ConsultaFinal))->groupBy('id');

    //$dd = collect($trayectorias);
    //$dd->groupBy('id');
    //var_dump($trayectorias);die();
    //echo($baseQuery.$cadSql);
    //var_dump($trayectorias);
    //$this->consultaBase($cadSql);


    //dd(DB::getQueryLog());

    return $trayectorias;
  }


  // Se manda los datos a la vista de resultado
  function results(Request $request)
  {

    $trayectorias = $this->resultsGeneral($request);

    if (!is_string($trayectorias)) {
      $page = $request->input('page', 1);
      $perPage = 15;
      $offset = $page * $perPage - $perPage;
      /*$allTrayectorias = new LengthAwarePaginator($trayectorias->slice($offset, $perPage, true), $trayectorias->count(), $perPage, $page,[
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);*/

      $allTrayectorias = new LengthAwarePaginator($trayectorias->slice($offset, $perPage, true), $trayectorias->count(), $perPage, $page);

      $allTrayectorias->setPath(Paginator::resolveCurrentPath());

      /*
        echo("--->>");
        var_dump($allTrayectorias);
        die();
  */
    } else {
      /*var_dump($trayectorias);
        die();*/
      $allTrayectorias = $trayectorias;
      //echo $trayectorias;  // Esto es para mostrar el error

    }

    return view('new_advanced_search.results', [
      'trayectorias' => $allTrayectorias,
    ]);
  }

  // Se manda los datos a la vista de resultado
  // Se llama de el resultado de la busqueda avanzada
  function resultsExport(Request $request)
  {

    $allTrayectorias = $this->resultsGeneral($request);

    $filtroSelect = false;
    if ($request->input('selected') == 1) {
      $filtroSelect = true;
    }
    //var_dump(session());die();

    foreach ($allTrayectorias as $trayectoria) {
      foreach ($trayectoria->groupBy('id') as $key) {
        $tempData = array();
        foreach ($key as $key2 => $value2) {

          //echo ($key."<br>");
          //var_dump($value2);
          //die();
          //echo (session("CompareID".$value2->id)."<br>");

          if (($filtroSelect && (session("CompareID" . $value2->id)) == 1) or ($filtroSelect == false)) {

            $trayectoriaTratada = [
              'trajectories.id' => $value2->id,
              'trajectories.force_field' => $value2->ff_name,
              //'trajectories.resolution' => $value2->resolution,
              //'trajectories.membrane_model' => $value2->mem_model,
              'trajectories.length' => $value2->trj_length,
              //'electric_field' =>   (string)$value2->electric_field,
              'trajectories.temperature' => $value2->temperature,
              //  'trajectories.pressure' => $value2->pressure,
              'trajectories.number_of_particles' => $value2->number_of_atoms,
              'trajectories.software_name' => $value2->software,
              //'trajectories.supercomputer' => $value2->supercomputer,
              //'trajectories.performance' => $value2->performance,
              'lipids.short_name' => $value2->lipid_name,
              'lipids.leaflet_1' => $value2->leaflet_1,
              'lipids.leaflet_2' => $value2->leaflet_2,
              //'peptides.name' => $value2->peptide_name,
              //'peptides.sequence' => $value2->sequence,
              //'peptides.activity' => $value2->activity,
              //'peptides.membrane' => null,
              //'peptides.bulk' => null,
              'ions.short_name' => $value2->ion_short_name,
              //'ions.bulk' => null,
              //'heteromolecules.bulk' => null,
              //'water_models.short_name' => $value2->wm_short_name,
              //'membranes.name' => $value2->mem_name,
            ];

            $trayectoriasTratadas[] = $trayectoriaTratada;
          }

          /*foreach ($value2 as $key3 => $value3) {
                  echo ($key3." >> ".$value3."<br>");
                  if( isset($tempData[$key3]) ){
                    if (!in_array($value3,$tempData[$key3] )){
                        $tempData[$key3][] = $value3;
                    }
                  } else{
                    $tempData[$key3][] = $value3;
                  }
                }*/
          //echo("<br>");
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

    $QualityFactor = DB::table('ranking_global')
      ->select(DB::raw('MIN(quality_total) AS quality_totalStart, MAX(quality_total) AS quality_totalEnd'))->get();
    $Quality_HG = DB::table('ranking_global')
      ->select(DB::raw('MIN(quality_hg) AS quality_hgStart, MAX(quality_hg) AS quality_hgEnd'))->get();
    $Quality_Tails = DB::table('ranking_global')
      ->select(DB::raw('MIN(quality_tails) AS quality_tailsStart, MAX(quality_tails) AS quality_tailsEnd'))->get();


    $Area_per_lipid = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(Area_per_lipid) AS Area_per_lipidStart, MAX(Area_per_lipid) AS Area_per_lipidEnd'))->get();

// HACK :: para que salga la temperatura con un slide de seleccion...

    $temperature = DB::table('trajectories')
        ->select(DB::raw('MIN(temperature) AS temperatureStart, MAX(temperature) AS temperatureEnd'))->get();

    $Form_factor_quality = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(Form_factor_quality) AS Form_factor_qualityStart, MAX(Form_factor_quality) AS Form_factor_qualityEnd'))
      ->where('form_factor_quality', '!=', '4242')
      ->get();

    /*$Area_per_lipid_upper_leaflet= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(Area_per_lipid_upper_leaflet) AS Area_per_lipid_upper_leafletStart, MAX(Area_per_lipid_upper_leaflet) AS Area_per_lipid_upper_leafletEnd'))->get();
      $Area_per_lipid_lower_leaflet= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(Area_per_lipid_lower_leaflet) AS Area_per_lipid_lower_leafletStart, MAX(Area_per_lipid_lower_leaflet) AS Area_per_lipid_lower_leafletEnd'))->get();
      $COG_of_protein= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(COG_of_protein) AS COG_of_proteinStart, MAX(COG_of_protein) AS COG_of_proteinEnd'))->get();
      $COG_BB_first= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(COG_BB_first) AS COG_BB_firstStart, MAX(COG_BB_first) AS COG_BB_firstEnd'))->get();
      $COG_BB_last= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(COG_BB_last) AS COG_BB_lastStart, MAX(COG_BB_last) AS COG_BB_lastEnd'))->get();
      $COG_of_membrane= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(COG_of_membrane) AS COG_of_membraneStart, MAX(COG_of_membrane) AS COG_of_membraneEnd'))->get();
      $COG_headgroups_upper_leaflet= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(COG_headgroups_upper_leaflet) AS COG_headgroups_upper_leafletStart, MAX(COG_headgroups_upper_leaflet) AS COG_headgroups_upper_leafletEnd'))->get();
      $COG_headgroups_lower_leaflet= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(COG_headgroups_lower_leaflet) AS COG_headgroups_lower_leafletStart, MAX(COG_headgroups_lower_leaflet) AS COG_headgroups_lower_leafletEnd'))->get();
*/
    $Bilayer_thickness = DB::table('trajectories_analysis')
      ->select(DB::raw('MIN(Bilayer_thickness) AS Bilayer_thicknessStart, MAX(Bilayer_thickness) AS Bilayer_thicknessEnd'))->get();



    /*    $Protein_depthness= DB::table('trajectories_analysis')
            ->select(DB::raw('MIN(`Protein_depthness`) AS Protein_depthnessStart, MAX(`Protein_depthness`) AS Protein_depthnessEnd'))->get();
      $Contacts_Protein_solvent= DB::table('trajectories_analysis')
            ->select(DB::raw("MIN(`Contacts_Protein-solvent`) AS Contacts_Protein_solventStart, MAX(`Contacts_Protein-solvent`) AS Contacts_Protein_solventEnd"))->get();
      $Contacts_Protein_headgroups= DB::table('trajectories_analysis')
            ->select(DB::raw("MIN(`Contacts_Protein-headgroups`) AS Contacts_Protein_headgroupsStart, MAX(`Contacts_Protein-headgroups`) AS Contacts_Protein_headgroupsEnd"))->get();

      $Contacts_Protein_tailgroups= DB::table('trajectories_analysis')
            ->select(DB::raw("MIN(`Contacts_Protein-tailgroups`) AS Contacts_Protein_tailgroupsStart, MAX(`Contacts_Protein-tailgroups`) AS Contacts_Protein_tailgroupsEnd"))->get();
      $Tilt= DB::table('trajectories_analysis')
             ->select(DB::raw("MIN(Tilt) AS TiltStart, MAX(Tilt) AS TiltEnd"))->get();
*/
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
      //'Area_per_lipid_upper_leaflet' => $Area_per_lipid_upper_leaflet,
      //'Area_per_lipid_lower_leaflet' => $Area_per_lipid_lower_leaflet,
      //'COG_of_protein' => $COG_of_protein,
      //'COG_BB_first' => $COG_BB_first,
      //'COG_BB_last' => $COG_BB_last,
      //'COG_of_membrane' => $COG_of_membrane,
      //'COG_headgroups_upper_leaflet' => $COG_headgroups_upper_leaflet,
      //'COG_headgroups_lower_leaflet' => $COG_headgroups_lower_leaflet,
      'Bilayer_thickness' => $Bilayer_thickness,
      //'Protein_depthness' => $Protein_depthness,
      //'Contacts_Protein_solvent' => $Contacts_Protein_solvent,
      //'Contacts_Protein_headgroups' => $Contacts_Protein_headgroups,
      //'Contacts_Protein_tailgroups' => $Contacts_Protein_tailgroups,
      //'Tilt' => $Tilt
    ]);
  }

  // Esto genera una vista con las trayectorias para ser comparadas
  public function compare(Request $request)
  {
    $data = session()->all();
    $listIDs = array();
    //var_dump($data);
    //die();

    foreach ($data as $key => $value) {
      if (gettype($value) != 'array' && strpos($key, 'CompareID') !== false) {

        if ($value == "1") {
          $listIDs[] = substr($key, 9);
        }
        //echo($key." :: ".$value."<br>");
      }
    }

    //var_dump($listIDs);die();
    $ResultadoDB = null;

    if (count($listIDs) > 0) {
      $ResultadoDB = DB::table('trajectories_analysis')
        ->join('trajectories_analysis_lipids', 'trajectories_analysis.trajectory_id', '=', 'trajectories_analysis_lipids.trajectory_id')
        ->join('lipids', 'trajectories_analysis_lipids.lipid_id', '=', 'lipids.id')
        ->join('forcefields', 'lipids.forcefield_id', '=', 'forcefields.id')
        ->join('trajectories', 'trajectories.id', '=', 'trajectories_analysis.trajectory_id')
        ->select('trajectories.temperature as temperature', 'trajectories_analysis.*', 'trajectories_analysis_lipids.*', 'lipids.name as lipid_name', 'lipids.molecule', 'forcefields.name as name')
        ->whereIn('trajectories_analysis.trajectory_id', $listIDs)->get();


      //
      /*  $ResultadoDBSQL = DB::table('trajectories_analysis')
    ->join('trajectories_analysis_lipids','trajectories_analysis.trajectory_id','=','trajectories_analysis_lipids.trajectory_id')
    ->join('lipids','trajectories_analysis_lipids.lipid_id','=','lipids.id')
    ->join('forcefields','lipids.forcefield_id','=','forcefields.id')
    ->join('trajectories','trajectories.id','=','trajectories_analysis.trajectory_id')
    ->select('trajectories.temperature as temperature','trajectories_analysis.*','trajectories_analysis_lipids.*','lipids.*','forcefields.name as name')
    ->whereIn('trajectories_analysis.trajectory_id', $listIDs)->toSql();
 */
    }
    //dd($ResultadoDBSQL);
    /*
DB::enableQueryLog();
$quries = DB::getQueryLog();
dd($quries);
*/

    return view('new_advanced_search.compare', [
      'datos' => $ResultadoDB,
    ]);
  }


  public function updatecompare(Request $request)
  {
    //session_start();

    $response = collect($request);

    foreach ($response as $key => $value) {
      //Session::put($key, $value);
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
        'Protein_depthness' => $resultado->Protein_depthness,
        'Protein_depthness_std' => $resultado->Protein_depthness_std,
        'Tilt' => $resultado->Tilt,
        'Tilt_std' => $resultado->Tilt_std,
        'COG_of_protein' => $resultado->COG_of_protein,
        'COG_of_protein_std' => $resultado->COG_of_protein_std,
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

        'Contacts_Protein-lipids' => $resultado->{'Contacts_Protein-lipids'},
        'Contacts_Protein-lipids_std' => $resultado->{'Contacts_Protein-lipids_std'},
        'Contacts_Protein-headgroups' => $resultado->{'Contacts_Protein-headgroups'},
        'Contacts_Protein-headgroups_std' => $resultado->{'Contacts_Protein-headgroups_std'},
        'Contacts_Protein-tailgroups' => $resultado->{'Contacts_Protein-tailgroups'},
        'Contacts_Protein-tailgroups_std' => $resultado->{'Contacts_Protein-tailgroups_std'},
        'Contacts_Protein-solvent' => $resultado->{'Contacts_Protein-solvent'},
        'Contacts_Protein-solvent_std' => $resultado->{'Contacts_Protein-solvent_std'},
        'PepDF_5_distance' => $resultado->PepDF_5_distance,
        'PepDF_5_distance_std' => $resultado->PepDF_5_distance_std,
        'PepDF_5_angle' => $resultado->PepDF_5_angle,
        'PepDF_5_angle_std' => $resultado->PepDF_5_angle_std,
        'PepDF_50_distance' => $resultado->PepDF_50_distance,
        'PepDF_50_distance_std' => $resultado->PepDF_50_distance_std,
        'PepDF_50_angle' => $resultado->PepDF_50_angle,
        'PepDF_50_angle_std' => $resultado->PepDF_50_angle_std,
        'PepDF_100_distance' => $resultado->PepDF_100_distance,
        'PepDF_100_distance_std' => $resultado->PepDF_100_distance_std,
        'PepDF_100_angle' => $resultado->PepDF_100_angle,
        'PepDF_100_angle_std' => $resultado->PepDF_100_angle_std,
        'PepDF_200_distance' => $resultado->PepDF_200_distance,
        'PepDF_200_distance_std' => $resultado->PepDF_200_distance_std,
        'PepDF_200_angle' => $resultado->PepDF_200_angle,
        'PepDF_200_angle_std' => $resultado->PepDF_200_angle_std

      ];

      $comparaciones[] = $comparacion;
    }

    //var_dump($comparaciones);
    //die();

    return Excel::download(new  NewAdvancedSearchCompareExport($comparaciones), 'NMR_export_compare.csv');
  }

  public function export(Request $request)
  {
    $trayectorias = $this->getTrayectoriasConFiltroAplicados($request);

    foreach ($trayectorias as $k => $trayectoria) {
      $trayectorias[$k]['max_elementos'] = max(
        count($trayectoria['peptidos']),
        count($trayectoria['lipidos']),
        count($trayectoria['iones']),
        //count($trayectoria['moleculas']),
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
          //'peptides.name' => null,
          //'peptides.sequence' => null,
          //'peptides.activity' => null,
          //'peptides.membrane' => null,
          //'peptides.bulk' => null,
          'ions.short_name' => null,
          //'ions.bulk' => null,
          //'heteromolecules.short_name' => null,
          //'heteromolecules.leaflet_1' => null,
          //'heteromolecules.leaflet_2' => null,
          //'heteromolecules.bulk' => null,
          //'water_models.short_name' => null,
          'membranes.name' => null,
        ];

        /*if(!empty($trayectoria['peptidos'][$i]))
            {
                $trayectoriaTratada['peptides.name'] = $trayectoria['peptidos'][$i]['name'];
                $trayectoriaTratada['peptides.sequence'] = $trayectoria['peptidos'][$i]['sequence'];
                $trayectoriaTratada['peptides.activity'] = $trayectoria['peptidos'][$i]['activity'];
                $trayectoriaTratada['peptides.membrane'] = $trayectoria['peptidos'][$i]['membrane'];
                $trayectoriaTratada['peptides.bulk'] = $trayectoria['peptidos'][$i]['bulk'];
            }*/
        if (!empty($trayectoria['lipidos'][$i])) {
          $trayectoriaTratada['lipids.short_name'] = $trayectoria['lipidos'][$i]['short_name'];
          $trayectoriaTratada['lipids.leaflet_1'] = $trayectoria['lipidos'][$i]['leaflet_1'];
          $trayectoriaTratada['lipids.leaflet_2'] = $trayectoria['lipidos'][$i]['leaflet_2'];
        }
        if (!empty($trayectoria['iones'][$i])) {
          $trayectoriaTratada['ions.short_name'] = $trayectoria['iones'][$i]['short_name'];
          //      $trayectoriaTratada['ions.bulk'] = $trayectoria['iones'][$i]['bulk'];
        }
        

        /*if (!empty($trayectoria['modelos_acuaticos'][$i])) {
          $trayectoriaTratada['water_models.short_name'] = $trayectoria['modelos_acuaticos'][$i]['short_name'];
        }*/

        $trayectoriasTratadas[] = $trayectoriaTratada;
      }
    }

    return Excel::download(new AdvancedSearchExport($trayectoriasTratadas), 'supepmem_export2.csv');
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

    // var_dump($filtrosAplicados);
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

    //var_dump($datosFomulario);
    //die();

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
      // Peptidos
      //'peptides.*',
      //'trajectories_peptides.*',
      // Moleculas
      'heteromolecules.*',
      'trajectories_heteromolecules.*',
      // Iones
      'ions.*',
      //Aguas
      //'water_models.*',

      'membranes.*'
    )
      //->leftJoin('trajectories_peptides', 'trajectories.id', '=', 'trajectories_peptides.trajectory_id')
      //->leftJoin('peptides', 'peptides.id', '=', 'trajectories_peptides.peptide_id')

      ->leftJoin('trajectories_lipids', 'trajectories.id', '=', 'trajectories_lipids.trajectory_id')
      ->leftJoin('lipids', 'lipids.id', '=', 'trajectories_lipids.lipid_id')

      //->leftJoin('trajectories_water', 'trajectories.id', '=', 'trajectories_water.trajectory_id')
      //->leftJoin('water_models', 'water_models.id', '=', 'trajectories_water.water_id')

      ->leftJoin('trajectories_ions', 'trajectories.id', '=', 'trajectories_ions.trajectory_id')
      ->leftJoin('ions', 'ions.id', '=', 'trajectories_ions.ion_id')

      ->leftJoin('trajectories_heteromolecules', 'trajectories.id', '=', 'trajectories_heteromolecules.trajectory_id')
      ->leftJoin('heteromolecules', 'heteromolecules.id', '=', 'trajectories_heteromolecules.molecule_id')

      ->leftJoin('trajectories_membranes', 'trajectories.id', '=', 'trajectories_membranes.trajectory_id')
      ->leftJoin('membranes', 'membranes.id', '=', 'trajectories_membranes.membrane_id')

      ->orderBy('trajectories.id')
      ->where($whereCad)->get();


    return $trayectorias;
  }

  private function getTrayectoriasConFiltroAplicados($request)
  {

    //  var_dump($request);

    DB::enableQueryLog();
    $filtrosAplicables = $this->filtrosAplicables($request);

    //    var_dump($filtrosAplicables);

    $filtrosNot = $filtrosAplicables->where('operador', OPERADOR_NOT);
    /** @var Filtro[] $filtrosNot */
    //      var_dump($filtrosNot);
    //die();
    $trayectoriasDescartadasPorFiltroNot = [];
    foreach ($filtrosNot as $filtro) {

      $result = DB::table($filtro->getTablePivot())->select($filtro->getTablePivot() . '.trajectory_id')
        ->join($filtro->modelo->getTable(), $filtro->modelo->getTable() . '.id', $filtro->getTablePivot() . '.' . $filtro->modelo->getForeignKey())
        ->where($filtro->modelo->getTable() . '.' . $filtro->columna, 'LIKE', '%' . $filtro->valor . '%')
        ->get();

      $trayectoriasDescartadasPorFiltroNot = array_merge($trayectoriasDescartadasPorFiltroNot, $result->pluck('trajectory_id')->toArray());
    }


    $trayectorias = Trayectoria::select('trajectories.*')->orderBy('trajectories.id')
      ->with('lipidos', 'peptidos', 'iones', 'modelos_acuaticos', 'moleculas', 'membranas') //,'membranas'
      ->whereNotIn('trajectories.id', $trayectoriasDescartadasPorFiltroNot)->get();
    //var_dump($trayectoriasDescartadasPorFiltroNot);
    //die();
    //dd(DB::getQueryLog());

    $filtrosAnd = $filtrosAplicables->where('operador', OPERADOR_AND);
    $filtrosOr = $filtrosAplicables->where('operador', OPERADOR_OR);
    //var_dump($filtrosOr);

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
              //var_dump($trayectoria);
              //var_dump($propiedad);
              //var_dump($entidades);
              //die();
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
