<?php

namespace App\Filtros;

use App\AppModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Filtro
{
    const PEPTIDOS = 'peptidos';
    const LIPIDOS = 'lipidos';
    const IONES = 'iones';
    const AGUA = 'agua';
    //const AGUA = 'modelos acuaticos';
    const MOLECULAS = 'moleculas';
    const MEMBRANAS = 'membranas';

    const TIPO_PROPIEDAD = 1;
    const TIPO_ENTIDAD = 2;

    public string $codigo;
    public string $columna = 'short_name';
    public int $tipo = Filtro::TIPO_ENTIDAD;
    public string $label;
    public AppModel $modelo;
    public $operador = OPERADOR_AND;
    public string $valor = '';


    function nameOperador()
    {
        return $this->codigo . '_operador';
    }

    function aplicarOperador($operador, $tablaEntidad, $valor, &$builder, $campo = 'short_name')
    {

        switch ($operador) {
            case OPERADOR_OR:
                $builder->orWhere($tablaEntidad . '.' . $campo, 'LIKE', "%$valor%");
                break;
            case OPERADOR_NOT:
                if ($this->tipo == Filtro::TIPO_PROPIEDAD) {
                    $builder->where($tablaEntidad . '.' . $campo, '!=', "$valor");
                }
                break;
            default:
                $builder->where($tablaEntidad . '.' . $campo, 'LIKE', "%$valor%");
        }
    }

    function aplicarFiltro(Builder &$builder, $valor, $operador = OPERADOR_AND)
    {

        $tablaEntidad = $this->modelo->getTable();
        $this->aplicarOperador($operador, $tablaEntidad, $valor, $builder);
    }

    function aplicarFiltroJoin(Builder &$builder)
    {
        //var_dump($this->columna);
        if ($this->tipo == self::TIPO_ENTIDAD) {
            /**
             * $builder
             * ->join('trajectories_peptides', 'trajectories.id', '=', 'trajectories_peptides.trajectory_id')
             * ->join('peptides', 'peptides.id', '=', 'trajectories_peptides.peptide_id')
             * ->where('peptides.name', 'LIKE', "%$valor%");
             */

            $aplicarJoin = true;
            if (is_array($builder->getQuery()->joins)) {
                foreach ($builder->getQuery()->joins as $join) {
                    if ($join->table == $this->modelo->getTable()) {
                        $aplicarJoin = false;
                    }
                }
            }

            if ($aplicarJoin) {
                $builder->addSelect($this->modelo->getTable() . '.' . $this->columna . ' as ' . $this->modelo->getTable() . '.' . $this->columna);
                $builder
                    ->join($this->getTablePivot(), 'trajectories.id', '=', $this->getTablePivot() . '.trajectory_id')
                    ->join($this->modelo->getTable(), $this->modelo->getTable() . '.id', '=', $this->getTablePivot() . '.' . $this->modelo->getForeignKey());
            }

            if ($this->operador == OPERADOR_OR) {
                $builder->orWhere($this->modelo->getTable() . '.' . $this->columna, 'LIKE', "%$this->valor%");
            } else {
                $builder->where($this->modelo->getTable() . '.' . $this->columna, 'LIKE', "%$this->valor%");
            }
        }
        if ($this->tipo == self::TIPO_PROPIEDAD) {
            if ($this->operador == OPERADOR_OR) {
                $builder->orWhere($this->modelo->getTable() . '.' . $this->columna, 'LIKE', "%$this->valor%");
            } else {
                $builder->where($this->modelo->getTable() . '.' . $this->columna, 'LIKE', "%$this->valor%");
            }
        }
    }

    function getTablePivot()
    {

        return 'trajectories_' . $this->modelo->getTable();
    }

    function aplicarFiltroOld(Builder &$builder, $valor, $operador)
    {
        $tablaEntidad = $this->modelo->getTable();
        $builder
            ->join($this->getTablePivot(), 'trajectories.id', '=', $this->getTablePivot() . '.trajectory_id')
            ->join($this->modelo->getTable(), $this->modelo->getTable() . '.id', '=', $this->getTablePivot() . '.' . $this->modelo->getForeignKey());

        if ($this->operador == OPERADOR_OR) {
            $builder->orWhere($this->modelo->getTable() . '.' . $this->columna, 'LIKE', "%$this->valor%");
        } else {
            $builder->orWhere($this->modelo->getTable() . '.' . $this->columna, 'LIKE', "%$this->valor%");
        }
    }

    function html()
    {
        return view('filtros.filtro', [
            'filtro' => $this
        ]);
    }

    function html_busqueda_avanzada($numero = 1)
    {

        return view('filtros.busqueda_avanzada', [
            'filtro' => $this,
            'numero_id' => $numero
        ]);
    }

    function html_busqueda_avanzada_selects($numero = 1)
    {

        $tableName = "";
        $fieldName = "";
        foreach ($this as $key => $value) {
            //echo $key.":".$value."<br>";
            if ($key == "table") $tableName = $value;
            if ($key == "fields") $fieldName = $value;
            if ($key == "columna") $fieldName = $value;
        }

        $query = "";
        $listOptions = array();
        if ($tableName and $fieldName) {
           
            if ($tableName == "lipids") {
                $listOptions = array();
                $query = DB::table('lipids')->select('molecule' . ' as valor')->groupBy('molecule')->OrderBy('molecule', 'asc')->get();
                foreach ($query as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        $listOptions[] = $value2;                        }
                    }

            } else {
                $query = DB::table($tableName)->select($fieldName . ' as valor')->groupBy($fieldName)->OrderBy($fieldName, 'asc')->get();
                $listOptions = array();
                foreach ($query as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        $listOptions[] = $value2;
                    }
                }
            }


        }

        if ($tableName == "" and $fieldName != "") {
            if ($fieldName != "force_field") {
                $query = DB::table("trajectories")->select($fieldName . ' as valor')->groupBy($fieldName)->get();
                $listOptions = array();
                foreach ($query as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        $listOptions[] = $value2;
                    }
                }
            } else {
                $query = DB::table("forcefields")->select('name as valor')->groupBy("name")->OrderBy('name', 'asc')->get();
                $listOptions = array();
                foreach ($query as $key => $value) {
                    foreach ($value as $key2 => $value2) {
                        $listOptions[] = $value2;
                    }
                }
            }
        }


        return view('filtros.busqueda_avanzada_selects', [
            'filtro' => $this,
            'numero_id' => $numero,
            'options' =>  $listOptions
        ]);
    }
}
