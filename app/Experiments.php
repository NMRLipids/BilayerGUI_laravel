<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Experiments extends Model
{
    protected $table = 'experiments';

    public function getForeignKey()
    {
        return 'trajectory_id';
    }

    public function membraneComposition()
    {
        return $this->hasMany(MembraneComposition::class, 'experiment_id', 'id');
    }
    public function getMembraneCompositionsByLipid($lipid_id)
    {
        return $this->hasMany(MembraneComposition::class, 'experiment_id', 'id')->where('lipid_id', $lipid_id)->get();
    }
}
