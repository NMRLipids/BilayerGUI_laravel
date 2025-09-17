<?php

namespace App\Http\Controllers;

use App\Filtros\Filtros;
use App\Filtros\Lipidos;
use App\Trayectoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrayectoriasController extends Controller
{
    function show($trayectoria_id) {

           $trayectoria = Trayectoria::findOrFail($trayectoria_id);


 /*
          $trayectoriaTest = DB::table('trajectories')
                        ->join('forcefields','trajectories.forcefield_id','=','forcefields.id')
                        ->join('membranes','trajectories.membrane_id','=','membranes.id')
                        ->select('trajectories.*','forcefields.name','membranes.lipid_names_l1')
                        ->where('trajectories.id',$trayectoria_id)->first()->paginate(10);
*/
            //var_dump($trayectoria2);
            //die();

       // TODO : This is BAD, we got two queries for the same thing
        return view('trayectorias.show', [
            'trayectoria' => $trayectoria
        ]);
    }
}
