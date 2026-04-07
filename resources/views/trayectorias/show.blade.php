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
<div class="container trajectory-show-page">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="container">
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
                            <button class="nav-link active" id="homeSimulationOverview-tab" 
                            data-bs-toggle="tab" data-bs-target="#homeSimulationOverview" type="button" 
                            role="tab">Methodology</button>

                        </li>

                        <li role="presentation" class="nav-item">
                            <button class="nav-link" id="homeMembrane-tab"
                                data-bs-toggle="tab" data-bs-target="#homeMembrane" 
                                type="button" role="tab">Lipid composition</button>

                        </li>

                        <li role="presentation" class="nav-item">
                            <button class="nav-link" id="homeAnalysis-tab"
                                data-bs-toggle="tab" data-bs-target="#homeAnalysis"
                                type="button" role="tab">Analysis/Experiment</button>                          
                        </li>
                        @if (isset($related_experiments) && count($related_experiments) > 0)
                        <li role="presentation" class="nav-item">
                            <button class="nav-link" id="homeCrossReferences-tab"
                                data-bs-toggle="tab" data-bs-target="#homeCrossReferences"
                                type="button" role="tab">Experiments</button>                          
                        </li>
                        @endif

                    </ul>
                    <div class="tab-content">
                        <!-- Simulation Tab -->
                        <div role="tabpanel" class="tab-pane fade show active" id="homeSimulationOverview" aria-labelledby="homeSimulationOverview-tab">

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-sm table-glass table-hover">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Computational methods</th>
                                                <td>Simulation metadata</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">System</th>
                                                <td>{{ $trayectoria->system ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Author(s)</th>
                                                <td>{{ $trayectoria->author ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Date</th>
                                                <td>{{ $trayectoria->date ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">DOI</th>
                                                <td>{!! renderDOI($trayectoria->doi) !!}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Publication</th>
                                                <td>{{ $trayectoria->publication ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">{{ c('campo_de_fuerza') }}</th>
                                                <td>{{ $trayectoria->campo_de_fuerza->name ?? 'N/A' }}</td>
                                            </tr>
                                            
                                            <tr>
                                                <th scope="row">{{ c('longitud') }}</th>
                                                <td>{{ $trayectoria->trj_length ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Trajectory size</th>
                                                <td>{{ $trayectoria->trj_size ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Pre-equilibration time</th>
                                                <td>{{ $trayectoria->preeq_time ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Time left out</th>
                                                <td>{{ $trayectoria->timeleftout ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">{{ c('temperatura') }}</th>
                                                <td>{{ $trayectoria->temperature ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">{{ c('particulas') }}</th>
                                                <td>{{ $trayectoria->number_of_atoms ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">{{ c('software') }}</th>
                                                <td>{{ $trayectoria->software ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">@lang('Iones')</th>
                                                <td>{{ $trayectoria->iones_num->map(function ($ion) { return "{$ion->ion_name}({$ion->number})"; })->implode(', ') ?: 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">@lang('Water')</th>
                                                <td>{{ $trayectoria->water_resname ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">@lang('Lipidos') - L1</th>
                                                <td>
                                                    {{ $trayectoria->membranas->implode('lipid_names_l1', ', ') ?: 'N/A' }}
                                                    @if($trayectoria->membranas->implode('lipid_number_l1', ', '))
                                                        ({{ $trayectoria->membranas->implode('lipid_number_l1', ', ') }})
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">@lang('Lipidos') - L2</th>
                                                <td>
                                                    {{ $trayectoria->membranas->implode('lipid_names_l2', ', ') ?: 'N/A' }}
                                                    @if($trayectoria->membranas->implode('lipid_number_l2', ', '))
                                                        ({{ $trayectoria->membranas->implode('lipid_number_l2', ', ') }})
                                                    @endif
                                                </td>
                                            </tr>
                                           

                                            <tr>
                                                <th scope="row">Files</th>
                                                <td>
                                                   {!! renderGitHubURL('Simulations/' . $trayectoria->git_path, text: 'View on GitHub') !!}<br>

                                                    @php $cadPath = asset('storage/simulations/' . $trayectoria->git_path) @endphp
                                                    <a class="bi bi-cloud-download" href="{{ $cadPath }}/conf.pdb.gz">&nbsp;Download PDB File</a><br>
                                                    <a class="bi bi-cloud-download" href="https://doi.org/{{ $trayectoria->doi }}" target="_blank">&nbsp;Link to simulation files</a><br>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Membrane Tab -->
                        <div role="tabpanel" class="tab-pane fade" id="homeMembrane" aria-labelledby="homeMembrane-tab" >
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
                                                    <h5 class=" "><a href="{{ route('lipid.show', $lipido->id) }}" title="{{ $lipido->displayTitle() }}">{{ $lipido->molecule }}</a></h5>
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
                                                            'Molecules/membrane/' .
                                                            $lipido->molecule .
                                                            '/' .
                                                            $mappingFile;
                                                    @endphp 
                                                    {!! renderGitHubURL($pathToScr, text: 'Download Mapping File', raw: true) !!}
                                                </div>
                                            </div>
                                        </div> <!--  CARD loop end-->
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <!-- Cross References Tab -->
                        <div role="tabpanel" class="tab-pane fade" id="homeCrossReferences" aria-labelledby="homeCrossReferences-tab">
                            <div class="card-body">
                                <p>Related experiments:</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-sm table-glass table-hover">
                                        <thead>
                                            <tr>
                                                <th>Article DOI</th>
                                                <th>Internal ID</th>
                                                <th>Type</th>
                                                <th>Temperature</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($related_experiments as $experiment)
                                                <tr>
                                                    <td>{!! renderDOI($experiment->article_doi) !!}</td>
                                                    <td>{{ $experiment->path }}</td>
                                                    <td>{{ $experiment->type }}</td>
                                                    <td>{{ $experiment->getTemperature()?->value ?? 'N/A' }} {{ $experiment->getTemperature()?->unit ?? '' }}</td>
                                                    <td>

                                                        <a href="{{ route('experiments.show', ['type' => $experiment->type, 'path' => $experiment->path]) }}"
                                                            class="btn btn-sm btn-primary" title="{{ $experiment->displayTitle() }}">View</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                </div>
                        </div>   

                        <!-- Analysis Tab -->
                        <div role="tabpanel" class="tab-pane fade" id="homeAnalysis" aria-labelledby="homeAnalysis-tab">
                            <div class="card-body">

                                @if (isset($trayectoria->analisis))
                                    @if (isset($trayectoria->getTrayectoriaAnalisisLipidos))
                                        {{-- OP section quick-nav (repeated at top of each section) --}}
                                        @php
                                            $showNav = count($trayectoria->getTrayectoriaAnalisisLipidos) > 1
                                                || isset($ApLData) || isset($FFData);
                                        @endphp

                                        @foreach ($trayectoria->getTrayectoriaAnalisisLipidos as $key => $analisis_lipid)
                                            @php
                                                $lipidName = $analisis_lipid->getLipid->molecule;
                                                $lipid_id = $analisis_lipid->lipid_id;
                                            @endphp
                                            <!-- Order Parameters -->
                                            <div id="op-{{ Str::slug($lipidName) }}" class="row p-2">
                                                <div class="col-sm-12 col-md-12" style="max-height: 50%;  padding: 10px;">
                                                    <hr class="my-4" />
                                                    @if ($showNav)
                                                    <nav class="op-section-nav my-2 d-flex flex-wrap gap-2 align-items-center">
                                                        <span class="text-white-50 small me-1">Jump to:</span>
                                                        @foreach ($trayectoria->getTrayectoriaAnalisisLipidos as $nav_lipid)
                                                            @php $navSlug = Str::slug($nav_lipid->getLipid->molecule); @endphp
                                                            @if ($nav_lipid->getLipid->molecule === $lipidName)
                                                                <span class="badge bg-light text-dark">{{ $nav_lipid->getLipid->molecule }}</span>
                                                            @else
                                                                <a href="#op-{{ $navSlug }}" class="badge bg-secondary text-decoration-none">{{ $nav_lipid->getLipid->molecule }}</a>
                                                            @endif
                                                        @endforeach
                                                        @if (isset($ApLData))
                                                            <a href="#apl-section" class="badge bg-secondary text-decoration-none">Area/Lipid</a>
                                                        @endif
                                                        @if (isset($FFData))
                                                            <a href="#ff-section" class="badge bg-secondary text-decoration-none">Form Factor</a>
                                                        @endif
                                                        <a href="#homeAnalysis" class="badge bg-secondary text-decoration-none" title="Back to top">&#9650; Top</a>
                                                    </nav>
                                                    @endif
                                                    <h4>Order Parameters {{ $lipidName }} </h4>
                                                    {!! renderGitHubURL($analisis_lipid->order_parameters_file, text: 'Download JSON', raw: true) !!}
                                                        @if (isset($OPData[$lipidName]))
                                                            <div class="op-chart-grid">
                                                            @foreach ($OPData[$lipidName] as $group => $plot_data)   
                                                            <!-- OP plot for each group of the lipid  {{$lipidName}}
                                                                    Data attributes 'data-opplot' and 'data-oplegend' are 
                                                                    used to pass the plot data and legend to the JavaScript 
                                                                    code that will render the chart -->
                                                                <div class="op-chart-item">
                                                                <div class="chart-container" style="max-height: 500px; min-height: 350px; background-color: #070220; position: relative;
                                                                margin-top: 20px; padding: 20px; border: 1px solid #695e5e; border-radius: 8px;">
                                                                    <h4 class="chart-label">Group {{ $group }}</h4>
                                                                    <canvas
                                                                        id="op_{{ $group }}_{{ $lipid_id }}"
                                                                        data-opplot='@json($plot_data)'
                                                                        data-oplegend='@json($OPLegend)'
                                                                        data-optitle="Order Parameters - {{ $lipidName }} - {{ $group }}"
                                                                        >
                                                                    </canvas>
                                                                    </div>
                                                                </div>
                                                                        
                                                            @endforeach
                                                            </div>
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
                                    <div id="apl-section" class="row" style="">
                                        <hr class="my-4" />
                                        @if ($showNav)
                                        <nav class="op-section-nav my-2 d-flex flex-wrap gap-2 align-items-center">
                                            <span class="text-white-50 small me-1">Jump to:</span>
                                            @foreach ($trayectoria->getTrayectoriaAnalisisLipidos as $nav_lipid)
                                                <a href="#op-{{ Str::slug($nav_lipid->getLipid->molecule) }}" class="badge bg-secondary text-decoration-none">{{ $nav_lipid->getLipid->molecule }}</a>
                                            @endforeach
                                            <span class="badge bg-light text-dark">Area/Lipid</span>
                                            @if (isset($FFData))
                                                <a href="#ff-section" class="badge bg-secondary text-decoration-none">Form Factor</a>
                                            @endif
                                            <a href="#homeAnalysis" class="badge bg-secondary text-decoration-none" title="Back to top">&#9650; Top</a>
                                        </nav>
                                        @endif
                                        <div class="chart-container" style="max-height: 500px; min-height: 350px; background-color: #070220; position: relative;
                                                                margin-top: 20px; padding: 20px; border: 1px solid #1c0876; border-radius: 8px;">
                                        
                                            <h4 class="chart-label">Area per lipid</h4>
                                            <canvas id="myChartAreaxLip"
                                                data-apldata="{{  json_encode($ApLData) }}"
                                                data-apltitle="Area per lipid">
                                                </canvas> 
                                    </div>
                                    @else
                                    <div>
                                        <h4>No Area per Lipid Data Available</h4>
                                    </div>
                                    @endif

                                    @if (isset($FFData))
                                    <div id="ff-section" class="row" style="">
                                        <hr class="my-4" />
                                        @if ($showNav)
                                        <nav class="op-section-nav my-2 d-flex flex-wrap gap-2 align-items-center">
                                            <span class="text-white-50 small me-1">Jump to:</span>
                                            @foreach ($trayectoria->getTrayectoriaAnalisisLipidos as $nav_lipid)
                                                <a href="#op-{{ Str::slug($nav_lipid->getLipid->molecule) }}" class="badge bg-secondary text-decoration-none">{{ $nav_lipid->getLipid->molecule }}</a>
                                            @endforeach
                                            @if (isset($ApLData))
                                                <a href="#apl-section" class="badge bg-secondary text-decoration-none">Area/Lipid</a>
                                            @endif
                                            <span class="badge bg-light text-dark">Form Factor</span>
                                            <a href="#homeAnalysis" class="badge bg-secondary text-decoration-none" title="Back to top">&#9650; Top</a>
                                        </nav>
                                        @endif
                                        <div class="chart-container" style="max-height: 500px; min-height: 350px; background-color: #070220; position: relative;
                                                                margin-top: 20px; padding: 20px; border: 1px solid #1c93a0; border-radius: 8px;">
                                        <h4 class="chart-label">Form Factor</h4>
                                            <label class="chart-label" style="display: inline-flex; align-items: center; gap: 6px; color: #ffffff; font-weight: 600; margin-bottom: 8px;">
                                                <input type="checkbox" data-ffnormalize-target="myChartFormFact" checked>
                                                Normalize (by max of first series)
                                            </label>
                                            <canvas id="myChartFormFact"
                                                data-ffdata="{{ json_encode($FFData) }}"
                                                data-fftitle="Form Factor"
                                                data-fflegend="{{ json_encode($FFLegend) }}">
                                             </canvas>
                                        </div>
                                    </div>
                                    @else
                                    <div>
                                        <h2>No Form Factor Data Available</h2>
                                    </div>
                                    @endif
                                    <div class="row p-2">
                                        <div class="col-sm-12 col-md-12">
                                            <h4> Experimental and Molecular Dynamics based descriptors</h4>
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
<!-- Bootstrap core JS--><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
 integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

@endsection

@section('meta-tags')
    
@endsection
