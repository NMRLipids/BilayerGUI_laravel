@extends('layouts.app')

@php

    use App\Http\Controllers\TrayectoriasController as TC;
    use App\TrayectoriaAnalisisLipidos;
    use App\Lipido;
    use App\TrayectoriaAnalisis;
    use App\Trayectoria;

    $CadSelectMem = '';
    $cadPath = asset('storage/simulations/' . $trayectoria->git_path);
    $ncol = 0;
@endphp
@section('content')
<style>
    .chart-container {
            max-width: 90%;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .chart-container-half {
        position: relative;
        width: 100%;
        height: 800px;
    }
</style>       

@vite(['resources/js/plotopcharts.js'])
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class=" ">
                    <div class="card-header txt-white">
                        <h1>@lang('Trayectoria') {{ $trayectoria->id }}</h1>
                    </div>
                    <div class="card-header txt-white">
                        <h3>
                            Order parameters quality = {{ $trayectoria->trajectories_analysis->op_quality_total ?? 'N/A' }}

                        </h3>
                    </div>
                    <div role="tabpanel" class="pt-4">
                        <ul class="nav nav-pills justify-content-start" id="trajectoryTab" role="tablist">

                            <li role="presentation" class="nav-item ">
                                <a href="#homeSimulationOverview" class="nav-link active  " aria-controls="homeSimulationOverview" role="tab"
                                    data-toggle="tab">Methodology</a>
                            </li>

                            <li role="presentation" class="nav-item">
                                <a href="#homeMembrane" class="nav-link" aria-controls="homeMembrane" role="tab"
                                    data-toggle="tab">Membrane</a>
                            </li>

                            <li role="presentation" class="nav-item">
                                <a href="#homeAnalysis" class="nav-link" aria-controls="homeAnalysis" role="tab"
                                    data-toggle="tab">Analysis/Experiment</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <!-- Simulation Tab -->
                            <div role="tabpanel" class="tab-pane active bg-solapa card-datos" id="homeSimulationOverview">

                                <div class="card-body">
                                    <div class="row p-2">
                                        <div class="  col-12 pt-2">
                                            <div class="container overflow-hidden">
                                                <div class="row g-5">
                                                    <div class=" col-12"
                                                        style="background-color: #5fbac4;border-right-width:  19px;border-right-style: solid;border-color: rgb(163 163 163);">
                                                        <div class="p-3">
                                                            <span class="txt-titulo">Computational methods </span>
                                                            <span class="txt-titulo">{{ c('campo_de_fuerza') }}:</span>
                                                            <span class="txt-dato">
                                                                {{ $trayectoria->campo_de_fuerza->name }}</span><br>
                                                            <span class="txt-titulo">{{ c('longitud') }}:</span>
                                                            <span class="txt-dato">
                                                                {{ $trayectoria->trj_length }}</span><br>
                                                            <!--<span class="txt-titulo">{{ c('campo_electrico') }}:</span><span class="txt-dato"> {{ $trayectoria->electric_field }}</span><br>-->
                                                            <span class="txt-titulo">{{ c('temperatura') }}:</span>
                                                            <span class="txt-dato">{{ $trayectoria->temperature }}
                                                            </span><br>
                                                            <!--<span class="txt-titulo">{{ c('presion') }}:</span><span class="txt-dato">{{ $trayectoria->pressure }} ({{ $trayectoria->pressure_coupling }} {{ $trayectoria->pressure_coupling_type }})</span><br>-->
                                                            <span class="txt-titulo">{{ c('particulas') }}:</span>
                                                            <span
                                                                class="txt-dato">{{ $trayectoria->number_of_atoms }}</span><br>
                                                            <!--<span class="txt-titulo">{{ c('timestep') }} :</span><span class="txt-dato">{{ $trayectoria->timeleftout }} </span><br>-->
                                                            <span class="txt-titulo">{{ c('software') }}:</span>
                                                            <span class="txt-dato">{{ $trayectoria->software }} </span><br>
                                                            <span class="txt-titulo">@lang('Iones'):</span>
                                                            <span class="txt-dato">

                                                                {{ $trayectoria->iones_num->map(function ($ion) {return "{$ion->ion_name}({$ion->number})";})->implode(', ') }}

                                                            </span><br>

                                                            <span class="txt-titulo">@lang('Water'):</span>
                                                            <span class="txt-dato">

                                                                {{ $trayectoria->water_resname }}

                                                            </span><br>

                                                            <span class="txt-titulo">@lang('Lipidos'):</span><br>

                                                            <span class="txt-dato"> L1:
                                                                {{ $trayectoria->membranas->implode('lipid_names_l1', ', ') }}
                                                                ({{ $trayectoria->membranas->implode('lipid_number_l1', ', ') }})
                                                            </span><br>
                                                            <span class="txt-dato"> L2:
                                                                {{ $trayectoria->membranas->implode('lipid_names_l2', ', ') }}
                                                                ({{ $trayectoria->membranas->implode('lipid_number_l2', ', ') }})
                                                            </span><br>
                                                            <p>
                                                                @php
                                                                    // ICICIC:
                                                                    // Generate the path to the simulation files 
                                                                    // without using echo
                                                                    $cadPath = asset(
                                                                        'storage/simulations/' . $trayectoria->git_path,
                                                                    );
                                                                    echo "<a class=\"bi bi-cloud-download\" href=\"" .
                                                                        $cadPath .
                                                                        "/conf.pdb.gz\" class=\"card-link\" >&nbsp;Download PDB File. </a></br>";

                                                                    echo '<a class="bi bi-cloud-download card-link" href="https://doi.org/' .
                                                                        $trayectoria->doi .
                                                                        '" target="_blank">&nbsp;Link to simulation files</a>';
                                                                @endphp

                                                            </p>
                                                        </div>

                                                    </div>


                                                    
                                                    <span class="txt-dato">
                                                        <a
                                                            href="{{ TC::GitHubDataRepoSimulations . $trayectoria->git_path }}">
                                                            <br/>
                                                            <span>See the system on GitHub</span>
                                                        </a>
                                                    </span><br>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Membrane Tab -->
                            <div role="tabpanel" class="tab-pane m-1 bg-solapa card-datos" id="homeMembrane">
                                <div class="card-body" style="height: 100%;">


                                    <div class="row p-4">

                                        <div class="col-xl-8 col-md-12 col-lg-6 pt-4 pb-4 ">
                                            <div class="row">

                                                <div class="text-center">
                                                    Hover over a component to view composition data.
                                                </div>
                                            </div>
                                            <div class="row">

                                            <!-- A plot depicting the composition of the membrane leaflets as a ring plot  -->
                                                <div class=" col-xs-12 col-sm-6 chart-container-half text-center" style="height: 30vh;">

                                                    Upper leaflet
                                                    <canvas id="UpperLeafletChart" width="50" height="50"
                                                    data-composition-ul="{{ json_encode($compul) }}"> </canvas>

                                                </div>

                                                <div class="col-xs-12 col-sm-6 chart-container-half text-center" style="height: 30vh;">
                                                    Lower leaflet
                                                    <canvas id="LowerLeafletChart" width="50" height="50"
                                                    data-composition-ll="{{ json_encode($compll) }}"> </canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm p-4">
                                            <span>
                                                <h3>Lipids</h3>
                                            </span></br>
                                        </div>
                                    </div>

                                    <div class="row justify-content">

                                        @php
                                            $col = 0;
                                        @endphp
                                        <!-- Loop through the lipids of the trajectory and display them in cards -->
                                        @foreach ($trayectoria->lipidos as $lipido)
                                            <div class="col-xs-12 col-lg-6 d-flex flex-wrap cardlipids">
                                                <div class=" m-2 w-100" style="width: 18rem;">
                                                    <div class="card-header text-left bg-card-header">
                                                        <h5 class=" ">{{ $lipido->molecule }}</h5>
                                                        <ul>
                                                            <li>
                                                                Quality total:
                                                                {{ $trayectoria->get_trajectory_analysis_lipids_by_lipid($lipido->id)->op_quality_total ?? 'N/A' }}
                                                            </li>
                                                            <li>
                                                                Quality headgroups:
                                                                {{ $trayectoria->get_trajectory_analysis_lipids_by_lipid($lipido->id)->op_quality_headgroups ?? 'N/A' }}
                                                            </li>
                                                            <li>
                                                                Quality tails:
                                                                {{ $trayectoria->get_trajectory_analysis_lipids_by_lipid($lipido->id)->op_quality_tails ?? 'N/A' }}
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="card-body text-center">
                                                        @php
                                                            $mappingFile = $lipido->getMappingByForcefield($trayectoria->campo_de_fuerza);
                                                            $pathToScr =
                                                                TC::GitHubURL .
                                                                'Molecules/membrane/' .
                                                                $lipido->molecule .
                                                                '/' .
                                                                $mappingFile;
                                                            echo '<a href="' .
                                                                $pathToScr .
                                                                '" title="Download Mapping file" target="_blank">';
                                                            echo '<span ><b>Download Mapping file</b>  </span></br>';
                                                            echo '</a>';
                                                        @endphp
                                                    </div>
                                                </div>
                                            </div> <!--  CARD loop end-->
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Analysis Tab -->
                            <div role="tabpanel" class="tab-pane bg-solapa card-datos" id="homeAnalysis">
                                <div class="card-body">

                                    @if (isset($trayectoria->analisis))
                                        @if (isset($trayectoria->getTrayectoriaAnalisisLipidos))
                                            @foreach ($trayectoria->getTrayectoriaAnalisisLipidos as $key => $analisis_lipid)
                                                @php
                                                    $lipidName = $analisis_lipid->getLipid->molecule;
                                                    $lipid_id = $analisis_lipid->lipid_id;
                                                @endphp
                                                <!-- Order Parameters -->
                                               <div class="row p-2">
                                                    <div class="col-sm-12 col-md-12" style="max-height: 50%; background-color:
                                                     #0d0d0e;border-right-width:  1px;border-right-style: none; padding: 0px; border-radius: 0px;">

                                                        <h3>Order Parameters : '{{ $lipidName }}' </h3>
                                                        <a href="{{ TC::GitHubURLEXP }}{{ $analisis_lipid->order_parameters_file }}">Download
                                                            JSON</a>
                                                            @if (isset($OPData[$lipidName]))
                                                                @foreach ($OPData[$lipidName] as $group => $plot_data)   
                                                                <!-- OP plot for each group of the lipid  {{$lipidName}}
                                                                     Data attributes 'data-opplot' and 'data-oplegend' are 
                                                                     used to pass the plot data and legend to the JavaScript 
                                                                     code that will render the chart -->
                                                                    <div class="chart-container" style="max-height: 500px; background-color: #3b3944; position: relative;
                                                                    margin-top: 20px; padding: 20px; border: 1px solid #695e5e; border-radius: 8px;">
                                                                        <h4>Group {{ $group }}</h4>
                                                                        <canvas
                                                                            id="op_{{ $group }}_{{ $lipid_id }}"
                                                                            data-opplot='@json($plot_data)'
                                                                            data-oplegend='@json($OPLegend)'
                                                                            data-optitle="Order Parameters - {{ $lipidName }} - {{ $group }}"
                                                                            >
                                                                        </canvas>
                                                                        </div>                                                                        
                                                                            <p style="cursor: pointer;" data-toggle="collapse" data-target="#dataCollapse_{{ $group }}_{{ $lipid_id }}">
                                                                                <span class="bi bi-chevron-down"></span> Data
                                                                            </p>
                                                                            <div id="dataCollapse_{{ $group }}_{{ $lipid_id }}" class="collapse" style="background-color: #1a1a1a; padding: 10px; border-radius: 5px;">
                                                                                <pre style="color: #fff; overflow-x: auto;">{{ json_encode($plot_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                                                            </div>
                                                                @endforeach
                                                            @else
                                                                <div>
                                                                    <h2>No OP Data Available for {{ $lipidName }}</h2>
                                                                </div>    
                                                            @endif   
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                        @if (isset($ApLData))
                                        <div class="row" style="">
                                            <div class="col-sm-12 col-md-12 chart-container" style=" background-color:
                                                     #0d0d0e;border-right-width:1px;border-right-style: none; padding: 4px; border-radius: 0px;" >
                                                <h3>Area per lipid</h3>
                                                <canvas id="myChartAreaxLip"
                                                    data-apldata="{{  json_encode($ApLData) }}"
                                                    data-apltitle="Area per lipid">
                                                 </canvas> 
                                        </div>
                                        @else
                                        <div>
                                            <h2>No Area per Lipid Data Available</h2>
                                        </div>
                                        @endif

                                        @if (isset($FFData))
                                        <div class="row" style="">
                                            <div class="col-sm-12 col-md-12 chart-container" style=" background-color:
                                                     #0d0d0e;border-left-width: 1px;border-left-style: none; padding: 4px; border-radius: 0px;">
                                                <h3>Form Factor</h3>
                                                <label style="display: inline-flex; align-items: center; gap: 6px; color: #ffffff; font-weight: 600; margin-bottom: 8px;">
                                                    <input type="checkbox" data-ffnormalize-target="myChartFormFact" checked>
                                                    Normalize (0-1)
                                                </label>
                                                <canvas id="myChartFormFact"
                                                    data-ffdata="{{ json_encode($FFData) }}"
                                                    data-fftitle="Form Factor"
                                                    data-fflegend="{{ json_encode($FFLegend) }}"
                                                > </canvas>
                                            </div>
                                        </div>
                                        @else
                                        <div>
                                            <h2>No Form Factor Data Available</h2>
                                        </div>
                                        @endif
                                        <div class="row p-2">
                                            <div class="col-sm-12 col-md-12">
                                                <h3> Experimental and Molecular Dynamics based descriptors<h3>
                                            </div>
                                        </div>

                                        <div class="row p-2">


                                            <div class="col-sm-6 col-md-6">

                                                <span class="txt-titulo">Quality of Order Parameters :</span>
                                                <span class="txt-dato">
                                                    {{ $trayectoria->analisis->op_quality_total ?? 'N/A' }}</span><br>
                                                <span class="txt-titulo">OP Quality of headgroups:

                                                    {{ $trayectoria->analisis->op_quality_headgroups ?? 'N/A' }}
                                                </span>
                                                <br>
                                                <span class="txt-titulo">OP Quality of tails:
                                                    {{ $trayectoria->analisis->op_quality_tails ?? 'N/A' }}
                                                </span>
                                                <br>
                                                <span class="txt-titulo">FF Quality:
                                                    {{ $trayectoria->analisis->ff_quality ?? 'N/A' }}
                                                </span>
                                                <br><br>

                                            </div>
                                            <div class="col-sm-6 col-md-6">
                                                <span class="txt-titulo">Bilayer thickness :
                                                    {{ round($trayectoria->analisis->bilayer_thickness, 1) ?? 'N/A' }} nm
                                                </span>
                                                <br>

                                                <span class="txt-titulo">Area per lipid :
                                                    {{ round($trayectoria->analisis->area_per_lipid, 1) ?? 'N/A' }}
                                                    &Aring;<sup>2</sup>
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <div>
                                            <h2>NO DATA</h2>
                                        </div>
                                    @endif
                                </div>

                            </div>


                            
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


@vite(['resources/js/plotopcharts.js', 'resources/js/plotApLchart.js', 'resources/js/plotFFcharts.js', 'resources/js/plotMembrane.js'])

@endsection

@section('meta-tags')
    
@endsection
