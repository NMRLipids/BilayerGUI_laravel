<?php

namespace App\Http\Controllers;

use App\Ion;
use App\Lipido;
use App\Molecula;
use App\Trayectoria;
use App\Membrana;
use Illuminate\Http\Request;
// Added

class SearchController extends Controller
{
    public function results(Request $request)
    {

        //DB::enableQueryLog();
        $texto = trim($request->get('text'));

        // Para poner el ID con un numero
        if (str_contains(strtoupper($texto),'ID')){
            $id = substr($texto,2);

            if (is_numeric($id)){
                //$trayectoria = Trayectoria::where('id', $texto)->first();
                //if (!is_null($trayectoria)) {
                    return redirect()->route('trayectorias.show', ['trayectoria_id' => $id]);
                //}
            }
        }

        $claves = preg_split("/[\s,]+/", $texto);
        $cadregexp = "";

            if (count($claves)>0){

                for ($i=0; $i < count($claves); $i++) {
                    $cadregexp = $cadregexp ."(?=.*".$claves[$i].")";# code...
                }

            }

        // Al buscar si era un numero era el ID que se pasaba ...pero
        // esto falla si pones un numero a buscar en el el input de busqueda simple

        // OJO ... LO DESCONECTO PARA PROBAR QUE PUEDA PONER TEMPERATUAS
       /* if (is_numeric($texto)) {
            $trayectoria = Trayectoria::where('id', $texto)->first();
            if (!is_null($trayectoria)) {
                return redirect(route('trayectorias.show', $texto));
            }
        }*/


        //$peptidos = Peptido::where('name', 'LIKE', "%$texto%")->get();
        //$peptidosSequence = Peptido::where('sequence', 'LIKE', "%$texto%")->get();
        //$lipidos = Lipido::where('full_name', 'LIKE', "%$texto%")->get();
        $lipidos = Lipido::where('molecule', 'LIKE', "%$texto%")->get()->unique('molecule'); // Unique se usa por que los lipidos estan duplicados por el campo forcefield
        //$moleculas = Molecula::where('molecule', 'LIKE', "%$texto%")->get()->unique('molecule');
        $iones = Ion::where('molecule', 'LIKE', "%$texto%")->get()->unique('molecule');
        //$agua = Agua::where('short_name', 'LIKE', "%$texto%")->get()->unique('short_name'); //->orWhere('short_name', 'LIKE', "%$texto%")
        //$membrana = Membrana::where('name', 'LIKE', "%$texto%")->orWhere('lipid_names_l1', 'LIKE', "%$texto%")->orWhere('lipid_names_l2', 'LIKE', "%$texto%")->get();

       
        if ($cadregexp==""){
            // Busqueda simple de un texto... modo LIKE
            $membrana = Membrana::where('lipid_names_l1', 'LIKE', "%$texto%")->orWhere('lipid_names_l2', 'LIKE', "%$texto%")->get();
        }else {
            // Busqueda REGEXP
            $membrana = Membrana::where('lipid_names_l1', 'regexp', $cadregexp)->orWhere('lipid_names_l2', 'regexp', $cadregexp)->get();
        }

        $temperature = Trayectoria::where('temperature', 'LIKE', "%$texto%")->orderBy('temperature', 'asc')->get()->unique('temperature'); // Unique se usa por que los lipidos estan duplicados por el campo forcefield
        // dd(DB::getQueryLog());

        return view('search.results', [
            'texto' => $texto,
            'cadregexp' => $cadregexp,
            'claves' => $claves,
            //'peptidos' => $peptidos,
            //'moleculas' => $moleculas,
            'iones' => $iones,
            'lipidos' => $lipidos,
            'membranas' => $membrana,
            'temperatures' => $temperature, // new field to search
            //'sequence'=>$peptidosSequence,
        ]);
    }
}
