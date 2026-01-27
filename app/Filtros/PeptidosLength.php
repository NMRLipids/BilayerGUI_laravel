<?php

namespace App\Filtros;

use App\Peptido;
use Illuminate\Database\Eloquent\Builder;

class PeptidosLength extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        $this->codigo = 'peptidos_length';
        $this->label = __('Peptides Length');
        $this->tooltip = 'Peptides Length';
        $this->modelo = new Peptido();
        $this->table = 'peptides';
        $this->fields = 'length';
        $this->columna = 'length';
        $this->visible = true;
        $this->join_count = " COUNT(CASE WHEN peptides.length = '%s' THEN 1 ELSE NULL END) AS `%s` ";
        $this->where = " pep_length.%s = 1 ";
        $this->join = "INNER JOIN(
                      SELECT pep_length.id
                      FROM ( SELECT
                              trajectories_peptides.trajectory_id AS id,
                              %s
                              FROM `trajectories_peptides`
                              LEFT JOIN peptides ON peptides.id = trajectories_peptides.peptide_id
                              WHERE 1
                              GROUP BY trajectories_peptides.trajectory_id
                           ) pep_length
                      WHERE %s
                      ) pep_length_select
                      ON trajectories.id = pep_length_select.id";
    }
}
