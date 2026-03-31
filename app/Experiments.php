<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Experiments extends AppModel
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

    public function getLipids()
    {
        return $this->membraneComposition()->with('lipid')->get()->pluck('lipid.molecule');
    }

    public function getMembraneCompositionsByLipid($lipid_id)
    {
        return $this->hasMany(MembraneComposition::class, 'experiment_id', 'id')->where('lipid_id', $lipid_id)->get();
    }

    public function properties()
    {
        return $this->belongsToMany(ExperimentProperty::class, 'experiments_properties_linker', 'experiment_id', 'property_id');
    }

    public function getTemperature()
    {
        return $this->properties()->where('name', 'temperature')->first();
    }

    private const TYPE_MAP = [
        'OP' => ExperimentsOP::class,
        'FF' => ExperimentsFF::class,
    ];

    public function newFromBuilder($attributes = [], $connection = null): static
    {
        $attrs = (array) $attributes;
        $class = self::TYPE_MAP[$attrs['type'] ?? ''] ?? static::class;
        /** @var static $model */
        $model = (new $class)->newInstance([], true);
        $model->setRawAttributes($attrs, true);
        $model->setConnection($connection ?? $this->getConnectionName());
        $model->fireModelEvent('retrieved', false);
        return $model;
    }

    public function trajectories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        throw new \LogicException('Call trajectories() on ExperimentsOP or ExperimentsFF, not the base Experiments class.');
    }

    public function getTrajectories()
    {
        return $this->trajectories()->get()->sortBy('id');
    }

    public function displayTitle()
    {
        return "Experiment: " . ($this->displayName()) . ($this->article_doi ? " DOI: " . $this->article_doi  : '');
    }
   

    public function displayName()
    {
        $composition = $this->membraneComposition()->with('lipid')->get()
            ->map(fn($mc) => $mc->lipid->molecule . ':' . $mc->mol_fraction)
            ->implode(' ');
        $temp = $this->getTemperature();
        return ($composition ?: 'Experiment ID: ' . $this->id) . ($temp ? " at {$temp->value}K" : '');
    }
}
