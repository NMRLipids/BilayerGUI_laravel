<?php

namespace App\Filtros;

use App\Ion;
use Illuminate\Database\Eloquent\Builder;

class Iones extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        $this->codigo = 'iones';
        $this->label = __('Iones');
        $this->tooltip = 'NA, CL ... ';
        $this->valor = '';
        $this->visible = false;
        $this->table = 'ions';
        $this->fields = 'name';
        $this->modelo = new Ion();
        $this->join_count = "COUNT( CASE WHEN trajectories_ions.ion_name = '%s' THEN 1 ELSE NULL END ) AS `%s`";
        $this->where = "ion.%s = 1";
        $this->join = "INNER JOIN(
                          SELECT ion.id
                          FROM
                              ( SELECT trajectories_ions.trajectory_id AS id,
                                  %s
                              FROM `trajectories_ions`
                              WHERE 1
                              GROUP BY trajectories_ions.trajectory_id
                      		) ion
                          WHERE %s
                          ) ion_select ON trajectories.id = ion_select.id";

    }
}
