<?php

namespace App\Filtros;

use App\Peptido;
use Illuminate\Database\Eloquent\Builder;

class Peptidos extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        $this->codigo = 'peptidos';
        $this->label = __('Peptido ID');
        $this->tooltip = 'DRAMPXXXX';
        $this->modelo = new Peptido();
        $this->table = 'peptides';
        $this->fields = 'name';
        $this->columna = 'name';
        $this->visible = true;
        // para hacer las subconsultas
        $this->join_count ="COUNT( CASE WHEN peptides.name = '%s' THEN 1 ELSE NULL END ) AS `%s`";
        $this->where = "peptid.%s = 1";
        $this->join ="INNER JOIN(
                          SELECT peptid.id
                          FROM (
                              SELECT
                                  trajectories_peptides.trajectory_id AS id,
                                  %s
                              FROM `trajectories_peptides`
                              LEFT JOIN peptides ON peptides.id = trajectories_peptides.peptide_id
                              WHERE 1 GROUP BY trajectories_peptides.trajectory_id
                              ) peptid
                      WHERE %s
                      ) peptid_select
                      ON trajectories.id = peptid_select.id";
    }
}
