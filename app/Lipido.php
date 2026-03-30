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

    public function properties()
    {
        return $this->belongsToMany(
            Property::class,
            'lipid_properties',
            'lipid_id',
            'property_id'
        );
    }

    public function synonyms()
    {
        return $this->hasMany(LipidSynonym::class, 'lipid_id', 'id');
    }

    public function cross_references()
    {
        return $this->hasMany(LipidCrossReference::class, 'lipid_id', 'id');
    }

    public function getInchiKeyAttribute()
    {
        $inchiKeyProperty = $this->properties()->where('properties.name', 'InChIKey')->first();
        return $inchiKeyProperty?->value;
    }
    
    public function getShortestSynonym()
    {
        $shortestSynonym = $this->synonyms()->orderByRaw('LENGTH(synonym) ASC')->first();
        return $shortestSynonym?->synonym;
    }



    public function displayName()
    {
        return ($this->molecule ?? $this->short_name ?? $this->name ?? 'Unknown Lipid '. $this->id. ' (ID)');
    }

    public function displayTitle()
    {
        return "Lipid: " . $this->name .
        ($this->getShortestSynonym() ? ' (' . $this->getShortestSynonym() . ')' : ' ('.$this->name.')');

    }
}
