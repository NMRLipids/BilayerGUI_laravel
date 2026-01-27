<?php

namespace App\Filtros;

class Filtros
{
    static public function principales() {
        $filtros = [
            new Lipidos(),
            //new Peptidos(),
            //new PeptidosLength(),
            //new PeptidosSecuencia(),
            //new PeptidosActivity(),
            //new PeptidosCharge(),
            
            new Iones(),
            //new Aguas(), // movido a filtrosTrayectoria
            //new Membranas(),
            new TrayectoriaFiltro(),
            new RankingGlobals(),
            //new RankingHeteromolecules(),
            //new RankingLipids(),

          //  new TrayectoriaTcoupling(),
          //  new TrayectoriaPcoupling(),
          //  new TrayectoriaPcouplingType(),
        ];
        $result = [];
        foreach ($filtros as $filtro) {
            $result[$filtro->codigo] = $filtro;
        }

        return $result;
    }

    static public function filtrosEntidades() {
        $filtros = [
            new Lipidos(),
            //new PeptidosLength(),
            //new PeptidosSecuencia(),
            //new PeptidosActivity(),
            //new PeptidosCharge(),
            //new Peptidos(),
            new RankingGlobals(),
            //new RankingHeteromolecules(),
            //new RankingLipids(),

           // new Moleculas(),
            new Iones(),
            //new Aguas(),// movido a filtrosTrayectoria
            //new Membranas(),
          //  new TrayectoriaTcoupling(),
          //  new TrayectoriaPcoupling(),
          //  new TrayectoriaPcouplingType(),
        ];
        $result = [];
        foreach ($filtros as $filtro) {
            $result[$filtro->codigo] = $filtro;
        }

        return $result;
    }

    static public function all()
    {
        $filtros = [
            new Lipidos(),
            //new PeptidosLength(),
            //new PeptidosSecuencia(),
            //new PeptidosActivity(),
            //new PeptidosCharge(),
            //new Peptidos(),
            new RankingGlobals(),
            //new RankingHeteromolecules(),
            //new RankingLipids(),

            new Iones(),
            //new Aguas(),// movido a filtrosTrayectoria
            new Membranas(),
            new TrayectoriaFiltro(),
          //  new TrayectoriaTcoupling(),
          //  new TrayectoriaPcoupling(),
          //  new TrayectoriaPcouplingType(),
          //  new PeptidosSecuencia(),

        ];

        $result = [];
        foreach ($filtros as $filtro) {
            $result[$filtro->codigo] = $filtro;
        }

        $result = array_merge($result, self::filtrosTrayectoria());

        return $result;
    }

    static public function filtrosTrayectoria()
    {
        $filtros = [
            //new Aguas(), // Movemos Aguas a los filtros de trayectorias y los desconecto del resto, no se si solo afecta a la busqueda avanzada
          //  new TrayectoriasFiltros('length','ns',true,'',''),
          //  new TrayectoriasFiltros('electric_field','kJ mol-1 nm-1 e-1',true,'',''),
          // HACK:: Quitamos la temperatura de aqui
          //  new TrayectoriasFiltros('temperature','K',true,'',''),
        //    new TrayectoriasFiltros('pressure','Bar',true,'',''),
        //    new TrayectoriasFiltros('number_of_particles','',false,'',''),
        //    new TrayectoriasFiltros('software_name','',true,'',''),
        //    new TrayectoriasFiltros('supercomputer','',false,'',''),
        //    new TrayectoriasFiltros('performance','ns/day',false,'',''),
            new TrayectoriasFiltros('force_field','',true,'LEFT JOIN forcefields ON forcefields.id = trajectories.forcefield_id','forcefields.name')

        ];

        $result = [];
        foreach ($filtros as $filtro) {
            $result[$filtro->codigo] = $filtro;
        }

        return $result;
    }

    /**
     * @param $clave
     * @return mixed|Filtro
     */
    static public function get($clave) {
        $filtros = self::all();

        return $filtros[$clave];
    }
}
