<?php

use App\Trayectoria;
use Illuminate\Filesystem\Filesystem;

/**
 * @var Trayectoria $trayectoria
 */
/**@php
 *  var_dump($trayectoria);
 *  @endphp
 */

?>

<div class="row">
    <div class="col">
        <p>Total trajectories</p>
        <p>{{ $totalTrayectorias }}</p>
    </div>
    <div class="col">
        <p>Total membranes</p>
        <p>{{ $totalMembranas }}</p>
    </div>
</div>
