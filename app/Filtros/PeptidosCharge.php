<?php

namespace App\Filtros;

use App\Peptido;
use Illuminate\Database\Eloquent\Builder;

class PeptidosCharge extends Filtro
{
    use FiltrosTrait;

    public function __construct()
    {
        $this->codigo = 'peptidos_total_charge';
        $this->label = __('Peptides total charge');
        $this->tooltip = 'Peptides total charge';
        $this->modelo = new Peptido();
        $this->table = 'peptides';
        $this->fields = 'total_charge';
        $this->columna = 'total_charge';
        $this->visible = true;
        $this->join_count = " COUNT(CASE WHEN peptides.total_charge = '%s' THEN 1 ELSE NULL END) AS `%s` ";
        $this->where = " pep_total_charge.%s = 1 ";
        $this->join = "INNER JOIN(
                      SELECT pep_total_charge.id
                      FROM ( SELECT
                              trajectories_peptides.trajectory_id AS id,
                              %s
                              FROM `trajectories_peptides`
                              LEFT JOIN peptides ON peptides.id = trajectories_peptides.peptide_id
                              WHERE 1
                              GROUP BY trajectories_peptides.trajectory_id
                           ) pep_total_charge
                      WHERE %s
                      ) pep_total_charge_select
                      ON trajectories.id = pep_total_charge_select.id";
    }
}
