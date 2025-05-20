<?php

namespace App;

use App\Lib\Coleccion;
use App\TrayectoriaAnalisisLipidos;
use App\TrajectoriesExperimentsFF;
use App\TrajectoriesExperimentsOP;
use App\RankingHeteromolecules;
use App\RankingLipids;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

/**
 * Class Trayectoria
 * @property int length
 * @property int electric_field
 * @property float temperature
 * @property string pressure
 * @property int number_of_particles
 * @property string software_name
 * @property string supercomputer
 * @property int performance
 * @property Lipido[]|Coleccion lipidos
 * @property Peptido[]|Coleccion peptidos
 * @property Peptido[]|Coleccion iones
 * @property Peptido[]|Coleccion modelos_acuaticos
 * @property Peptido[]|Coleccion moleculas
 * @property Membrana[]|Coleccion membranas
 * @package App
 */
class Trayectoria extends AppModel
{
    protected $table = 'trajectories';

    public function getForeignKey() {
        return 'trajectory_id';
    }

    function ranking_global() {
      //$ranking_global = $this->belongsToMany(Lipido::class, TrayectoriasLipidos::getTableName())->withPivot('leaflet_1', 'leaflet_2');
      //DB::enableQueryLog();
      $ranking_global =$this->hasOne(RankingGlobal::class);
      //dd(DB::getQueryLog());
      return $ranking_global;

    }

    function ranking_heteromolecules() {

      //DB::enableQueryLog();
      $rankingHeteromol = $this->hasMany(RankingHeteromolecules::class);
      //dd(DB::getQueryLog());
      return $rankingHeteromol;

    }


    function ranking_lipids() {
      //DB::enableQueryLog();
      $ranking_lipid = $this->hasMany(RankingLipids::class);
      //dd(DB::getQueryLog());
      return $ranking_lipid;

    }


    function TrayectoriasAnalysisHeteromoleculas() {
      //$ranking_global = $this->belongsToMany(Lipido::class, TrayectoriasLipidos::getTableName())->withPivot('leaflet_1', 'leaflet_2');
      //dd(DB::getQueryLog());
      return $this->hasOne(TrayectoriasAnalysisHeteromoleculas::class);
    }

    function TrayectoriaAnalisisLipidosfunc() {
      return $this->hasMany(TrayectoriaAnalisisLipidos::class);
    }

    function TrayectoriasHeteromoleculas() {
      return $this->hasOne(TrayectoriasHeteromoleculas::class);
    }
    function TrayectoriasHeteromoleculasMolecule() {
      $heteromoleculeData =$this->belongsToMany(Molecula::class, TrayectoriasHeteromoleculas::getTableName());
      return $heteromoleculeData;
    }


    function lipidos() {
      //  DB::enableQueryLog();
      $lipidosData =$this->belongsToMany(Lipido::class, TrayectoriasLipidos::getTableName())->withPivot('leaflet_1', 'leaflet_2');
      // dd(DB::getQueryLog());
        return $lipidosData;
    }

    function analisi_lipidos() {
        return $this->belongsToMany(Lipido::class, TrayectoriaAnalisisLipidos::getTableName());
    }

    function analisis() {
      $analisisData = $this->belongsTo('App\TrayectoriaAnalisis', 'id','trajectory_id');
      return $analisisData;
    }

    function experimentsFF() {
      return $this->belongsToMany(
            ExperimentsFF::class,
            'trajectories_experiments_FF',
            'trajectory_id',
            'experiment_id'
        );
    }

    function experimentsOP() {
      return $this->belongsToMany(
            ExperimentsOP::class,
            'trajectories_experiments_OP',
            'trajectory_id',
            'experiment_id'
        );
    }


    /*function peptidos() {
        //return $this->belongsToMany(Peptido::class, TrayectoriasPeptidos::getTableName())->withPivot('bulk');
        return $this->belongsToMany(Peptido::class, TrayectoriasPeptidos::getTableName())->withPivot('peptide_id');
    }*/

    function iones() {
        return $this->belongsToMany(Ion::class, TrayectoriasIones::getTableName());//->withPivot('bulk');
    }
    function iones_num() {
        return $this->hasMany(TrayectoriasIones::class);//->withPivot('bulk');
    }



    function modelos_acuaticos() {
        return $this->belongsToMany(Agua::class, TrayectoriasAgua::getTableName());
    }

    function modelos_acuaticos_num() {
        return $this->hasMany(TrayectoriasAgua::class);
    }

    function moleculas() {
        //return $this->belongsToMany(Molecula::class, TrayectoriasHeteromoleculas::getTableName())->withPivot('leaflet_1', 'leaflet_2', 'bulk');
        return $this->belongsToMany(Molecula::class, TrayectoriasHeteromoleculas::getTableName());//->withPivot('leaflet_1', 'leaflet_2');
    }


    //return $this->belongsTo('App\Membrana', 'membrane_id');
    //$resaa = $this->belongsTo('App\TrayectoriasMembranas', 'id','membrane_id')->dd();

    function membranas() {
      //dd(TrayectoriasMembranas::getTableName());
        //$res = $this->belongsToMany(Membrana::class, TrayectoriasMembranas::getTableName());

        $res = $this->belongsToMany(Membrana::class, Trayectoria::getTableName(),'id');
        //dd($res);
        return $res;
    }

    function campo_de_fuerza() {
        return $this->belongsTo('App\CampoDeFuerza', 'forcefield_id');
    }

    function membrana() {
        return $this->belongsTo('App\Membrana', 'membrane_id');
    }


}
