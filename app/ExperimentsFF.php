<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExperimentsFF extends Experiments
{
    protected $table = 'experiments_FF';

    public function getForeignKey()
    {
        return 'trajectory_id';
    }

    public function trajectories()
        {
            return $this->belongsToMany(
                Trajectory::class,
                'trajectories_experiments_FF',
                'experiment_id',
                'trajectory_id'
            );
        }
}
