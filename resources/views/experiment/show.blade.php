@extends('layouts.app')
@section('content')
    @php
        // Properties that get their own rows in the Overview tab (or are outdated and
        // deliberately suppressed) must not be repeated in the generic Properties tab.
        // Computed here, before the tab bar, so the Properties tab button knows whether
        // there is anything left to show.
        $properties_in_overview = [
            'TEMPERATURE', 'TOTAL_HYDRATION', 'PH', 'REAGENT_SOURCES', 'SAMPLE_PROTOCOL',
            'TOTAL_LIPID_CONCENTRATION', 'COUNTER_IONS', 'XRAY', 'NMR',
        ];
        $properties_to_show = array_diff_key($properties, array_flip($properties_in_overview));
    @endphp
    <!-- Main page -->
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-10">
                    <h3 class="text-white text-center mt-0">
                        Experiment {{ $entity['type'] }} - {{ $entity['path'] }}
                    </h3>                  
                        
                    <!-- Bootstrap Tabs -->
                    <ul class="nav nav-pills justify-content-start" id="experimentTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="analysis-tab" data-bs-toggle="tab" data-bs-target="#analysis" type="button" role="tab">Data</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link{{ count($properties_to_show) === 0 ? ' d-none' : '' }}" id="properties-tab" data-bs-toggle="tab"  data-bs-target="#properties" type="button" role="tab">Properties</button>
                        </li>
                        <!-- Add cross reference links to related simulations if available -->
                        @if (count($related_simulations) > 0) 
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="related-tab" data-bs-toggle="tab" data-bs-target="#related" type="button" role="tab">Simulations</button>
                        </li>
                        @endif
                    </ul>
                    <!-- Tab Contents -->
                    <div class="tab-content" id="experimentTabContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                              <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm table-glass">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Article DOI</th>
                                            <td>{!! renderDOI($entity['doi']) !!}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Data DOI</th>
                                            <td>{!! renderDOI($entity['data_doi']) !!}</td>
                                        </tr>
                                       
                                        <tr>
                                            <th scope="row">Internal ID</th>
                                            <td>{{ $entity['path'] }}</td>
                                        </tr>
                                       
                                        <tr>
                                            <th scope="row">Membrane composition (molar fraction)</th>

                                            <td>
                                            <table class="table table-striped table-sm table-glass">
                                                
                                                <tbody>
                                                @foreach ( $entity['membrane_composition'] as $component )
                                                    <tr>
                                                        <td><a href="/lipid/{{ $component->id }}" title="Lipid: {{ $component->molecule }}"> {{ $component->molecule }}</a></td>
                                                        <td>{{ $component->name  }} </td>
                                                        <td>{{ $component->mol_fraction }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>  
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Solution composition</th>
                                            <td>
                                                @if ( empty( $entity['solution_composition'] ) )
                                                    No data available                                               
                                                @elseif ($entity['solution_composition'] == 'pure water' )
                                                    Pure water

                                                @else
                                                    <table class="table table-striped table-sm table-glass table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th scope="col">Compound</th>
                                                                <th scope="col">Mass %</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                        @foreach ( $entity['solution_composition'] as $component )
                                                        @if($component->concentration > 0)
                                                            <tr>
                                                                <td> {{ $component->compound }}</td>
                                                                <td>{{ $component->concentration }}</td>
                                                            </tr>
                                                        @endif    
                                                        @endforeach
                                                    </tbody>
                                            </table>  
                                            @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Temperature (K)</th>
                                            <td>{{ $properties['TEMPERATURE']->value ?? 'N/A' }} </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Total hydration</th>
                                            <td>{{ $properties['TOTAL_HYDRATION']->value ?? 'N/A' }} (mass %) </td>
                                        </tr>   
                                        <tr>
                                            <th scope="row">pH</th>
                                            <td>{{ $properties['PH']->value ?? 'N/A' }}</td>
                                        </tr> 
                                        <tr>
                                            <th scope="row">Reagent sources</th>
                                            @php
                                                $decoded_value = $properties['REAGENT_SOURCES']->value ?? [];
                                            @endphp
                                            <td>
                                            <table class="table table-striped table-sm table-glass table-hover">
                                                        <tbody>
                                                            @foreach ($decoded_value as $key => $value)
                                                            <tr>
                                                                <td>{{ $key }}</td>
                                                                <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </td>        
                                        </tr>
                                        <tr>
                                        <th scope="row" >Sample protocol</th>
                                            <td>{{ $properties['SAMPLE_PROTOCOL']->value ?? 'N/A' }}</td> 
                                        </tr>
                                        @if ($properties['XRAY'] ?? false)
                                            <!-- tr>
                                                <th scope="row" colspan="2">X-ray properties</th>
                                            </tr -->
                                            @php
                                                // Keys with a dedicated row below; anything else the README
                                                // carries is listed generically at the end of the block so it
                                                // does not silently disappear from the page.
                                                $xray_labelled = [
                                                    'DETECTOR', 'SOURCE', 'LAMBDA', 'BEAMSIZE', 'DISTANCE',
                                                    'DATATYPE', 'EXPOSURE', 'FRAMES', 'SAMPLE_TYPE', 'QRANGE',
                                                ];
$xray_value = is_array($properties['XRAY']->value) ? $properties['XRAY']->value : [];
$properties['XRAY']->value = $xray_value;
$xray_extra = array_diff_key($xray_value, array_flip($xray_labelled));
                                            @endphp
                                             <tr>
                                                <th scope="row">X-ray detector</th>
                                                <td>{{ $properties['XRAY']->value['DETECTOR'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray source</th>
                                                <td>{{ $properties['XRAY']->value['SOURCE'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray wavelength (Å)</th>
                                                <td>{{ $properties['XRAY']->value['LAMBDA'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <!-- BEAMSIZE is recorded with its own unit, e.g. "0.24 x 0.24 mm" -->
                                                <th scope="row">X-ray beam size</th>
                                                <td>{{ $properties['XRAY']->value['BEAMSIZE'] ?? 'N/A' }}</td>
                                            </tr>

                                            <tr>
                                                <th scope="row">X-ray distance to sample (m)</th>
                                                <td>{{ $properties['XRAY']->value['DISTANCE'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray data type</th>
                                                <td>{{ $properties['XRAY']->value['DATATYPE'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray exposure time (s)</th>
                                                <td>{{ $properties['XRAY']->value['EXPOSURE'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray number of frames</th>
                                                <td>{{ $properties['XRAY']->value['FRAMES'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray sample type</th>
                                                <td>{{ $properties['XRAY']->value['SAMPLE_TYPE'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray Q-range (Å⁻¹)</th>
                                                <td>{{ $properties['XRAY']->value['QRANGE'] ?? 'N/A' }}</td>
                                            </tr>
                                            @foreach ($xray_extra as $key => $value)
                                            <tr>
                                                <th scope="row">X-ray {{ strtolower(str_replace('_', ' ', $key)) }}</th>
                                                <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                            </tr>
                                            @endforeach
                                        @endif
                                        @if ($properties['NMR'] ?? false)
                                            @php
                                                $nmr_labelled = ['METHOD', 'INSTRUMENT', 'T_RF_HEATING', 'SIGN_MEASURED', 'DETAILS'];
                                                $nmr_value = is_array($properties['NMR']->value) ? $properties['NMR']->value : [];
                                                $nmr_extra = array_diff_key($nmr_value, array_flip($nmr_labelled));
                                            @endphp
                                            <tr>
                                                <th scope="row">NMR method</th>
                                                <td>{{ $properties['NMR']->value['METHOD'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">NMR instrument</th>
                                                <td>{{ $properties['NMR']->value['INSTRUMENT'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">NMR RF heating correction</th>
                                                <td>{{ $properties['NMR']->value['T_RF_HEATING'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">NMR sign measured</th>
                                                <td>{{ $properties['NMR']->value['SIGN_MEASURED'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">NMR details</th>
                                                <td>{{ $properties['NMR']->value['DETAILS'] ?? 'N/A' }}</td>
                                            </tr>
                                            @foreach ($nmr_extra as $key => $value)
                                            <tr>
                                                <th scope="row">NMR {{ strtolower(str_replace('_', ' ', $key)) }}</th>
                                                <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                            </tr>
                                            @endforeach
                                        @endif
                                        <tr>
                                            <th scope="row">Show on GitHub</th>
                                            @php
                                                $github_url = 'experiments/'. 
                                                ($entity['type'] == 'OP' ? 'OrderParameters' : 'FormFactors') . '/' . $entity['path'];
                                            @endphp
                                            <td>{!! renderGitHubURL($github_url) !!}</td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Properties Tab: everything not already covered by the Overview tab -->
                        @if (count($properties_to_show) > 0)
                        
                            <div class="tab-pane fade" id="properties" role="tabpanel" aria-labelledby="properties-tab">
                                <br/>
                                <table class="table table-bordered table-striped table-sm table-glass table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">Name</th>
                                            <!-- th scope="col">Description</th -->
                                            <th scope="col">Value</th>
                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($properties_to_show as $prop)
                                        <tr>
                                            <td>{{ $prop->name }}</td>
                                            <!--td>{{ $prop->description }}</td-->
                                            <td>
                                            @if( preg_match('/^(array|list|dict)$/', $prop->type) )
                                            <!-- Format arrays and dictionaries nicely using html in nested tables -->
                                                @php
                                                    $decoded_value = $prop->value;
                                                @endphp
                                                @if (is_array($decoded_value))
                                                    @if (array_keys($decoded_value) === range(0, count($decoded_value) - 1))
                                                        <!-- It's an array -->
                                                        <table class="table table-striped table-sm table-glass table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Index</th>
                                                                    <th scope="col">Value</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($decoded_value as $index => $item)
                                                                <tr>
                                                                    <td>{{ $index }}</td>
                                                                    <td>{{ is_array($item) ? json_encode($item) : $item }}</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    @else
                                                        <!-- It's a dictionary -->
                                                        <table class="table table-striped table-sm table-glass table-hover">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Key</th>
                                                                    <th scope="col">Value</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($decoded_value as $key => $value)
                                                                <tr>
                                                                    <td>{{ $key }}</td>
                                                                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    @endif
                                                @else
                                                    <!-- Not a valid array or dictionary -->
                                                    {{ $prop->value }}
                                                @endif
                                                <!-- pre style="white-space: pre-wrap; color: white">{{ print_r($prop->value, true) }}</pre -->
                                                @else
                                                {{ $prop->value }}
                                                @endif
                                            </td>
                                        
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        @else
                        @endif

                        <!-- Analysis Tab -->
                        <div class="tab-pane fade" id="analysis" role="tabpanel" aria-labelledby="analysis-tab">
                            
                            @if ($entity['type'] === 'OP')                         
                                @foreach ( $entity['membrane_composition'] as $lipid )
                                    @php
                                        $lipidName = $lipid->molecule;
                                        $lipid_id = $lipid->id;
                                    @endphp
                                    @if (isset($OPData[$lipidName]))
                                    <hr class="my-4" />

                                    <h4 class="text-white">Order Parameters {{ $lipidName }}</h4>
                                    {!! renderGitHubURL($github_url.'/'.$lipidName . '_OrderParameters.json', text: 'View JSON', raw: true) !!}    

                                        <div class="op-chart-grid">

                                        @foreach ($OPData[$lipidName] as $group => $plot_data)   
                                        <!-- OP plot for each group of the lipid  {{$lipidName}}
                                                Data attributes 'data-opplot' and 'data-oplegend' are 
                                                used to pass the plot data and legend to the JavaScript 
                                                code that will render the chart -->
                                            <div class="op-chart-item">
                                            <div class="chart-container" style="max-height: 500px; min-height: 350px; background-color: #070220; position: relative;
                                            margin-top: 20px; padding: 20px; border: 1px solid #695e5e; border-radius: 8px;">
                                                <!-- h4>Group {{ $group }}</h4 -->
                                                <canvas
                                                    id="op_{{ $group }}_{{ $lipid_id }}"
                                                    data-opplot='@json($plot_data)'
                                                    data-oplegend='["{{ $lipidName }} - {{ $group }}"]'
                                                    data-optitle="Order Parameters - {{ $lipidName }} - {{ $group }}"
                                                    >
                                                </canvas>
                                                </div>
                                            </div>
                                        <!-- add a separator between different groups of the same lipid -->
                                        @endforeach
                                        </div>
                                    @else
                                        <div>
                                            <h2>No OP Data Available for {{ $lipidName }}</h2>
                                        </div>    
                                    @endif   
                                @endforeach
                                                          
                            @elseif  ($entity['type'] === 'FF' && ! empty($FFData) )
                            <div class="row p-2">
                                <div class="col-sm-12 col-md-12 chart-container-half">
                                    <div class="chart-container" style="max-height: 500px; max-width: 80vh; background-color: #070220; position: relative;
                                    margin-top: 20px; padding: 20px; border: 1px solid #695e5e; border-radius: 8px;">
                                        <canvas id="myChartFormFactEXP"
                                        data-ffdata="{{ json_encode($FFData) }}"
                                        data-fflegend='["Form Factor"]'
                                        data-fftitle="Form Factor - {{ $entity['doi'] }}"> </canvas>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        <!-- Related Simulations Tab -->
                        @if (count($related_simulations) > 0) 
                        <div class="tab-pane fade" id="related" role="tabpanel" aria-labelledby="related-tab">
                            <p>Related simulations linked to this experiment:</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-sm table-glass table-hover">
                                    <thead>
                                        <tr>
                                            
                                            <th scope="col">DOI</th>
                                            <th scope="col">Software</th>
                                            <th scope="col">Force field</th>
                                            <th scope="col">Trajectory length</th>
                                            <th scope="col">Temperature (K)</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($related_simulations as $simulation)
                                        
                                        <tr>
                                            <td>{!! renderDOI($simulation->doi) !!}</td>
                                            <td>{{ $simulation->software}}</td>
                                            <td>{{ $simulation->campo_de_fuerza?->name}}</td>
                                            <td>{{ $simulation->trj_length }}</td>
                                            <td>{{ $simulation->temperature }}</td>
                                            <td><a href="{{ route('trayectorias.show', ['trayectoria_id' => $simulation->id]) }}" class="btn btn-primary btn-sm" title="{{ $simulation->displayTitle() }}">View</a></td>
                                        
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </header>
</main>
@endsection


@vite(['resources/js/plotopcharts.js', 'resources/js/plotFFcharts.js'])

    <!-- Bootstrap core JS--><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Core theme JS-->
   
