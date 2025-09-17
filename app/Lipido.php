<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Lipido
 * @property string short_name
 * @package App
 */
class Lipido extends AppModel
{
    protected $table = 'lipids';

    public function getForeignKey()
    {
        return 'lipid_id';
    }


    public function forcefields()
    {
        return $this->belongsToMany(
            CampoDeFuerza::class,
            'lipids_forcefields',
            'lipid_id',
            'forcefield_id'
        )->withPivot('mapping'); // 👈 include pivot column;
    }

    public function getMappingByForcefield(CampoDeFuerza $forcefield): ?string
    {
        // Assuming there's a relationship defined between Lipido and Forcefield defined in the 
        // lipids_forcefields table
        // Access the pivot table directly
        $pivot = $this->forcefields()->wherePivot('forcefield_id', $forcefield->id)->first();
        // Return the mapping from the pivot table
        return $pivot?->pivot?->mapping;
    }
}
