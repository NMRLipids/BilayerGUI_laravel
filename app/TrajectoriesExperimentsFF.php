<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TrajectoriesExperimentsFF extends Pivot
{
    protected $table = 'trajectories_experiments_FF';



     public function trayectoria()
     {
         return $this->belongsTo(Trajectoria::class, 'trayectoria_id');
      }

      public function experiment()
      {
          return $this->hasOne(ExperimentsFF::class, 'experiment_id'); 

    }
}