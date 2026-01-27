<?php

namespace App\Filtros;

use App\Peptido;
use Illuminate\Database\Eloquent\Builder;

class PeptidosSecuencia extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        $this->codigo = 'aminoacids';
        $this->label = __('Aminoacids');
        $this->tooltip = 'DRAMPXXXX';
        $this->modelo = new Peptido();
        $this->table = 'peptides';
        $this->fields = 'sequence';
        $this->columna = 'sequence';
        $this->visible = true;
        $this->join_count = " COUNT(CASE WHEN peptides.sequence LIKE '%%%s%%' THEN 1 ELSE NULL END) AS `%s` ";
        $this->where = " pep_sequence.%s = 1 ";
        $this->join = "INNER JOIN(
                      SELECT pep_sequence.id
                      FROM ( SELECT
                              trajectories_peptides.trajectory_id AS id,
                              %s
                              FROM `trajectories_peptides`
                              LEFT JOIN peptides ON peptides.id = trajectories_peptides.peptide_id
                              WHERE 1
                              GROUP BY trajectories_peptides.trajectory_id
                           ) pep_sequence
                      WHERE %s
                      ) pep_sequence_select
                      ON trajectories.id = pep_sequence_select.id";
    }
}
