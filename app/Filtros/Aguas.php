<?php

namespace App\Filtros;

use App\Agua;

class Aguas extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        //$this->codigo = 'agua';
        $this->codigo = 'modelos_acuaticos'; // Este es el prefijo de los input del formularioArregla el fallo de buscar un codigo que no existe
        $this->label = __('Modelo de agua');
        $this->tooltip = 'PW or W';
        $this->modelo = new Agua();
        $this->valor = '';
        $this->visible = true;
        $this->table = 'water_models';
        $this->fields = 'short_name';
        $this->join_count = " COUNT(CASE WHEN trajectories_water.water_name = '%s' THEN 1 ELSE NULL END) AS `%s` ";
        $this->where = " water.%s = 1 ";
        $this->join = " INNER JOIN(
            SELECT water.id
            FROM ( SELECT
                    trajectories_water.trajectory_id AS id,
                    %s
                    FROM `trajectories_water`
                    WHERE 1
                    GROUP BY trajectories_water.trajectory_id
                    ) water
            WHERE %s
            ) water_select
            ON trajectories.id = water_select.id ";
    }

    function getTablePivot() {
        return 'trajectories_water';
    }
}
