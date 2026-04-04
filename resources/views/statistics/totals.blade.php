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
    <div class="col hero-stats">
        <p>Total&nbsp;trajectories:<br>
        {{ $totalTrayectorias }}</p>
    </div>
    <div class="col hero-stats">
        <p>Total&nbsp;membranes:<br>
        {{ $totalMembranas }}</p>
    </div>
</div>
