<?php

namespace App\Filtros;

use App\Lipido;
use Illuminate\Database\Eloquent\Builder;

class Lipidos extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        $this->codigo = 'lipidos';
        $this->label = __('Lipidos');
        $this->tooltip = 'POPC, POPG ...';
        $this->valor = '';
        $this->visible = true;
        $this->table = 'lipids';
        $this->fields = 'molecule';
        $this->join_count = " COUNT(CASE WHEN  trajectories_lipids.lipid_name  = '%s' THEN 1 ELSE NULL END) AS `%s` ";
        $this->where = "lipid.%s = 1 ";
        $this->join = " INNER JOIN(
                                  SELECT lipid.id
                                  FROM
                                      ( SELECT
                                          trajectories_lipids.trajectory_id AS id,
                                          %s
                                        FROM `trajectories_lipids` WHERE 1 GROUP BY trajectories_lipids.trajectory_id
                                      ) lipid
                                  WHERE %s
                                  ) lipid_select
                      ON trajectories.id = lipid_select.id ";
        $this->modelo = new Lipido();
    }
}
