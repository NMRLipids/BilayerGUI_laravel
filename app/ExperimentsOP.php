<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExperimentsOP extends Experiments
{
    protected $table = 'experiments_OP';

    public function getForeignKey()
    {
        return 'trajectory_id';
    }

    public function trajectories()
        {
            return $this->belongsToMany(
                Trayectoria::class,
                'trajectories_experiments_OP',
                'experiment_id',
                'trajectory_id'
            );
        }

    public function membraneComposition()
    {
        return $this->hasMany(MembraneComposition::class, 'experiment_id', 'id');
    }    

    
    
}
