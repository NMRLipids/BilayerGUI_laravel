<?php

namespace App\Filtros;

use App\Peptido;
use Illuminate\Database\Eloquent\Builder;

class PeptidosActivity extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        $this->codigo = 'peptidos_activity';
        $this->label = __('Peptides Activities');
        $this->tooltip = 'Activities';
        $this->modelo = new Peptido();
        $this->table = 'peptides';
        $this->fields = 'activity';
        $this->columna = 'activity';
        $this->visible = true;
        $this->join_count = " COUNT(CASE WHEN peptides.activity = '%s' THEN 1 ELSE NULL END) AS `%s` ";
        $this->where = " pep_activity.%s = 1 ";
        $this->join = "INNER JOIN(
                      SELECT pep_activity.id
                      FROM ( SELECT
                              trajectories_peptides.trajectory_id AS id,
                              %s
                              FROM `trajectories_peptides`
                              LEFT JOIN peptides ON peptides.id = trajectories_peptides.peptide_id
                              WHERE 1
                              GROUP BY trajectories_peptides.trajectory_id
                           ) pep_activity
                      WHERE %s
                      ) pep_activity_select
                      ON trajectories.id = pep_activity_select.id";
    }
}
