<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExperimentsOP extends Model
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
}
