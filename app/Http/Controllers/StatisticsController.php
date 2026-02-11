<?php

namespace App\Http\Controllers;

use App\Agua;
use App\Ion;
use App\Lipido;
use App\Molecula;
use App\Peptido;
use App\Trayectoria;
use App\TrayectoriaAnalisis;
use App\Membrana;
use Illuminate\Http\Request;
// Added
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function results(Request $request)
    {

        DB::enableQueryLog();

        $TotalTrayectorias = TrayectoriaAnalisis::select('id')->count();
        $TotalMembranas = Membrana::select('id')->count();
        $CountMembranas = Membrana::groupBy('name')->select('name', DB::raw('count(*) as total'))->get();
        $CountPeptideActivity = Peptido::groupBy('activity')->select('activity', DB::raw('count(*) as total'))->get();
        $CountPeptideLength = Peptido::groupBy('length')->select('length', DB::raw('count(*) as total'))->get();
        $CountPeptideCharge = Peptido::groupBy('total_charge')->select('total_charge', DB::raw('count(*) as total'))->get();


        $CountPeptideElectrostatic_dipolar_moment = Peptido::groupBy('electrostatic_dipolar_moment')->select('electrostatic_dipolar_moment', DB::raw('count(*) as total'))->get();
        $CountPeptideHydrophobic_dipolar_moment = Peptido::groupBy('hydrophobic_dipolar_moment')->select('hydrophobic_dipolar_moment', DB::raw('count(*) as total'))->get();

        $PeptideElectrostatic_dipolar_moment = Peptido::select('electrostatic_dipolar_moment')->get();
        $PeptideHydrophobic_dipolar_moment = Peptido::select('hydrophobic_dipolar_moment')->get();


        $CountMembraneForcefield = Membrana::groupBy('forcefields.name')->join('forcefields','membranes.forcefield_id','=','forcefields.id')->select('forcefields.name', DB::raw('count(*) as total'))->get();

        
        return view('statistics.results', [
            'totalTrayectorias' => $TotalTrayectorias,
            'totalMembranas'=>$TotalMembranas,
            'membranas' => $CountMembranas,
            'PeptideActivity' => $CountPeptideActivity,
            'PeptideLength' => $CountPeptideLength,
            'PeptideCharge' => $CountPeptideCharge,
            'Electrostatic_dipolar_moment' => $CountPeptideElectrostatic_dipolar_moment,
            'Hydrophobic_dipolar_moment' => $CountPeptideHydrophobic_dipolar_moment,
            'Electrostatic_dipolar_moment_values' => $PeptideElectrostatic_dipolar_moment,
            'Hydrophobic_dipolar_moment_values' => $PeptideHydrophobic_dipolar_moment,
            'Forcefields' => $CountMembraneForcefield
        ]);
    }

    static function totals()
    {
      $TotalTrayectorias = TrayectoriaAnalisis::select('id')->count();
      $TotalMembranas = Membrana::select('id')->count();

      return view('statistics.totals', [
          'totalTrayectorias' => $TotalTrayectorias,
          'totalMembranas'=>$TotalMembranas
        ]);
    }


}
