<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class MembraneComposition extends Model
{
    protected $table = 'experiments_membrane_composition';

    public function getForeignKey()
    {
        return 'experiment_id';
    }

    public function experiment()
    {
        return $this->belongsTo(ExperimentsOP::class, 'experiment_id', 'id');
    }
    public function lipid()
    {
        return $this->belongsTo(Lipido::class, 'lipid_id', 'id');
    }
   
}