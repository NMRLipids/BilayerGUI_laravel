<?php

namespace App\Filtros;

use App\RankingGlobal;
use Illuminate\Database\Eloquent\Builder;

class RankingGlobals extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        $this->codigo = 'ranking';
        $this->label = __('Ranking');
        $this->tooltip = 'Quality total ... ';
        $this->valor = '';
        $this->visible = false;
        $this->table = 'ranking_global';
        $this->fields = 'quality_total';

        //$this->join_count = "COUNT( CASE WHEN ranking_global.quality_total >= %s AND ranking_global.quality_total <= %s  THEN 1 ELSE NULL END ) AS `%s`";
        $this->join_count = "COUNT( CASE WHEN  ranking_global.quality_total >= '%s' AND ranking_global.quality_total <= '%s' THEN 1 ELSE NULL END ) AS `%s`";
        $this->where = "%s = 1";
        //$this->where = "ranking_global.%s = 1";
        $this->join = "INNER JOIN(
                          SELECT  id
                          FROM
                              ( SELECT
                              ranking_global.trajectory_id as id,
                                  %s
                              FROM `ranking_global`
                              WHERE 1
                              GROUP BY ranking_global.trajectory_id
                          ) ranking
                          WHERE %s
                          ) ranking_select
                          ON trajectories.id = ranking_select.id";

        $this->modelo = new RankingGlobal();
    }
}
/*

SELECT trajectories.id
FROM trajectories
INNER JOIN (
    SELECT ranking_global.trajectory_id AS id,
           COUNT(CASE WHEN ranking_global.quality_total = '0.0611039' THEN 1 ELSE NULL END) AS n0_06110391
    FROM `ranking_global`
    WHERE 1
    GROUP BY ranking_global.trajectory_id
) ranking ON trajectories.id = ranking.id
LEFT JOIN `trajectories_analysis` ON `trajectories`.`id` = `trajectories_analysis`.`trajectory_id`



$this->join = "INNER JOIN(
                  SELECT ranking_global.id
                  FROM
                      ( SELECT
                      ranking_global.trajectory_id AS id,
                          %s
                      FROM `ranking_global`
                      WHERE 1
                      GROUP BY ranking_global.trajectory_id
                  ) ranking
                  WHERE %s
                  ) ranking_select
                  ON trajectories.id = ranking_select.id";
*/
