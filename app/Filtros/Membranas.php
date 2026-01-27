<?php

namespace App\Filtros;

use App\Membrana;
use Illuminate\Database\Eloquent\Builder;

class Membranas extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {

        $this->codigo = 'membranas';
        $this->label = __('Modelos de Membrenas');
        $this->tooltip = 'Membranes';
        $this->valor = '';
        $this->visible = true;
        $this->columna = 'id';
        $this->table = 'membranes';
        $this->fields = 'id'; // Y no hay name en esta base de datos,
        $this->modelo = new Membrana();
        $this->join_count = "COUNT(CASE WHEN membranes.id = '%s' THEN 1 ELSE NULL END) AS `%s`";//"COUNT(CASE WHEN membranes.name = '%s' THEN 1 ELSE NULL END) AS `%s`";
        $this->where = "  membrane.%s = 1 ";
        $this->join = "INNER JOIN(
                       SELECT membrane.id
                       FROM (
                           SELECT trajectories_membranes.trajectory_id AS id, %s
                           FROM trajectories_membranes
                           INNER JOIN membranes ON membranes.id =  trajectories_membranes.membrane_id
                           WHERE 1 GROUP BY trajectories_membranes.trajectory_id
                      ) membrane
                       WHERE %s
                       ) membrane_select
                       ON trajectories.id = membrane_select.id";
        }

    function getTablePivot() {
        return 'trajectories_membranes';
    }
}
/*
$this->join = "INNER JOIN(
               SELECT membrane.id
               FROM (
                   SELECT trajectories_membranes.trajectory_id AS id, %s
                   FROM trajectories_membranes
                   INNER JOIN membranes ON membranes.id =  trajectories_membranes.membrane_id
                   WHERE 1 GROUP BY trajectories_membranes.trajectory_id
              ) membrane
               WHERE %s
               ) membrane_select
               ON trajectories.id = membrane_select.id";

}
*/
