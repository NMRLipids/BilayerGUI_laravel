<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrayectoriasLipidos extends AppModel
{
    protected $table = 'trajectories_lipids';

    public function getForeignKey() {
        return 'trajectory_id';
    }

    public function lipid() {
        return $this->belongsTo(Lipido::class, 'lipid_id', 'id');
    }
    
}
