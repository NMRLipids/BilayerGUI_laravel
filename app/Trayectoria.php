<?php

namespace App;

use App\Lib\Coleccion;
use App\TrayectoriaAnalisisLipidos;


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
 * @property Membrana[]|Coleccion membranas
 * @package App
 */
class Trayectoria extends AppModel
{
    protected $table = 'trajectories';

    public function getForeignKey() {
        return 'trajectory_id';
    }

   
    function trajectories_analysis() {
      return $this->hasOne(TrayectoriaAnalisis::class, 'trajectory_id', 'id');
    }

    function trajectories_analysis_lipids() {
      return $this->hasMany(TrayectoriaAnalisisLipidos::class, 'trajectory_id', 'id');
    }

    function getTrayectoriaAnalisisLipidos() {
      return $this->hasMany(TrayectoriaAnalisisLipidos::class, 'trajectory_id', 'id')->where('lipid_id', '!=', null);
    }

    function get_trajectory_analysis_lipids_by_lipid($lipid_id) {
      return $this->hasOne(TrayectoriaAnalisisLipidos::class, 'trajectory_id', 'id')->where('lipid_id', $lipid_id)->first();
    }

    function trajectoriesLipids() {
      return $this->hasMany(TrayectoriasLipidos::class, 'trajectory_id', 'id');
    }

    function lipidos() {
      $lipidosData =$this->hasManyThrough(Lipido::class, TrayectoriasLipidos::class, 'trajectory_id', 'id', 'id', 'lipid_id');
      return $lipidosData;
    }

    function analisi_lipidos() {
        return $this->belongsToMany(Lipido::class, TrayectoriaAnalisisLipidos::getTableName());
    }

    function analisis() {
      $analisisData = $this->hasOne(TrayectoriaAnalisis::class, 'trajectory_id', 'id');
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
    public function getExperiments() {
        return $this->belongsToMany(Experiments::class, 'trajectories_experiments_OP', 'trajectory_id', 'experiment_id')
            ->withPivot('experiment_id')
            ->get()
            ->merge($this->belongsToMany(Experiments::class, 'trajectories_experiments_FF', 'trajectory_id', 'experiment_id')->get())
            ->sortBy('type');
    }   

    public function hasExperiments() {
        return $this->experimentsOP()->exists() || $this->experimentsFF()->exists();
    }


    function countExperiments() {

        // Prefer counts loaded via withCount() to avoid N+1 queries,
        // and fall back to querying the relationships if not present.
        $op = array_key_exists('experiments_op_count', $this->attributes)
            ? $this->attributes['experiments_op_count']
            : $this->experimentsOP()->count();
        $ff = array_key_exists('experiments_ff_count', $this->attributes)
            ? $this->attributes['experiments_ff_count']
            : $this->experimentsFF()->count();

        return $op + $ff;
    }
    

    function iones() {
        return $this->belongsToMany(Ion::class, TrayectoriasIones::getTableName());//->withPivot('bulk');
    }
    function iones_num() {
        return $this->hasMany(TrayectoriasIones::class);//->withPivot('bulk');
    }
    
    function membranas() {
      
        $res = $this->belongsToMany(Membrana::class, Trayectoria::getTableName(),'id');
        return $res;
    }

    function campo_de_fuerza() {
        return $this->belongsTo('App\CampoDeFuerza', 'forcefield_id');
    }

    function membrana() {
        return $this->belongsTo('App\Membrana', 'membrane_id');
    }

    function displayName()
    {
     $membrana = $this->membrana;  
     $l1 = $membrana->lipid_names_l1;
     $l2 = $membrana->lipid_names_l2;
     $forceFieldName = $this->campo_de_fuerza?->name ?? '';
     $shortForceFieldName = strlen($forceFieldName) > 15
         ? substr($forceFieldName, 0, 15) . '...'
         : $forceFieldName;
     return  $l1 . ($l1 != $l2 ? ', ' . $l2 : '') . ' - ' . $shortForceFieldName;    
    
    }   

    function longName(){
        $membrana = $this->membrana;  
        $l1 = $membrana->lipid_names_l1 . ':' .$membrana->lipid_number_l1 ?? 'N/A';
        $l2 = $membrana->lipid_names_l2 . ':' .$membrana->lipid_number_l2 ?? 'N/A';
        return  $l1 . ($l1 != $l2 ? ', ' . $l2 : '') . ' - ' . $this->campo_de_fuerza?->name . ' at ' . 
           $this->temperature . 'K, ' . $this->trj_length . ' ns';
    }

    function displayTitle()
    {
        return "Trajectory: " . $this->longName();
    }

}
