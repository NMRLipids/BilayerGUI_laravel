<?php

namespace App\Filtros;

use App\Trayectoria;

class TrayectoriasFiltros extends Filtro
{
    use FiltrosTrait;

    public function __construct($columna,$unidad='',$visible=true,$jointo ='',$countfix ='')
    {
        $this->columna = $columna;
        $this->codigo = 'trayectoria_'.$this->columna;
        $this->label = (string) __('columna.'.$this->columna);
        $this->unidades = $unidad;
        $this->visible = $visible;
        $this->modelo = new Trayectoria();
        $this->tipo = self::TIPO_PROPIEDAD;

        // Parche para el forcefields
        $this->countpiece = "trajectories.".$this->columna."";
        if ($countfix!="") $this->countpiece = $countfix;

        $this->join_count = " COUNT(CASE WHEN  $this->countpiece  = '%s' THEN 1 ELSE NULL END) AS `%s` ";
        $this->where = $columna.".%s = 1 ";
        $this->join = " INNER JOIN(
                                  SELECT ".$this->columna.".id
                                  FROM
                                      ( SELECT
                                          trajectories.id AS id,
                                          %s
                                        FROM `trajectories`
                                        ".$jointo."
                                        WHERE 1
                                        GROUP BY trajectories.id
                                      ) ".$this->columna."
                                  WHERE %s
                                  ) ".$this->columna."_select
                      ON trajectories.id = ".$this->columna."_select.id ";

    }

    function html() {
        $filtro = new TrayectoriaFiltro();
        $filtro->valor = $this->valor;
        return view('filtros.trayectoria', [
                'filtro' => $filtro
            ]
        );
    }
}
