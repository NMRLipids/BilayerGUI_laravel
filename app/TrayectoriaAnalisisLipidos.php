<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TrayectoriaAnalisisLipidos extends AppModel
{
    protected $table = 'trajectories_analysis_lipids';

    
    public function getLipid()
    {
        return $this->hasOne(Lipido::class, 'id', 'lipid_id');
    }
}
