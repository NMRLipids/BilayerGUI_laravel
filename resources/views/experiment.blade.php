<!doctype html>
<html class="welcome" lang="{{ str_replace('_', '-', app()->getLocale()) }}"> 
@include('layouts.head')
<style>
/* Custom pagination styling for dark theme */

.pagination {
    --bs-pagination-color: #fff;
    --bs-pagination-bg: #343a40;
    --bs-pagination-border-color: #495057;
    --bs-pagination-hover-color: #fff;
    --bs-pagination-hover-bg: #495057;
    --bs-pagination-hover-border-color: #6c757d;
    --bs-pagination-focus-color: #fff;
    --bs-pagination-focus-bg: #495057;
    --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
    --bs-pagination-active-color: #fff;
    --bs-pagination-active-bg: #0d6efd;
    --bs-pagination-active-border-color: #0d6efd;
    --bs-pagination-disabled-color: #6c757d;
    --bs-pagination-disabled-bg: #343a40;
    --bs-pagination-disabled-border-color: #495057;
    font-size: 0.875rem;
}
.page-link {
    color: var(--bs-pagination-color);
    background-color: var(--bs-pagination-bg);
    border-color: var(--bs-pagination-border-color);
    padding: 0.375rem 0.75rem;
}
.page-link:hover {
    color: var(--bs-pagination-hover-color);
    background-color: var(--bs-pagination-hover-bg);
    border-color: var(--bs-pagination-hover-border-color);
}
.page-link:focus {
    color: var(--bs-pagination-focus-color);
    background-color: var(--bs-pagination-focus-bg);
    box-shadow: var(--bs-pagination-focus-box-shadow);
}
.page-item.active .page-link {
    color: var(--bs-pagination-active-color);
    background-color: var(--bs-pagination-active-bg);
    border-color: var(--bs-pagination-active-border-color);
}
.page-item.disabled .page-link {
    color: var(--bs-pagination-disabled-color);
    background-color: var(--bs-pagination-disabled-bg);
    border-color: var(--bs-pagination-disabled-border-color);
}
</style>

<script>

function OPPlot(canvasId, dataValues, labels, legendText) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: legendText,
                data: dataValues,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: false,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: '#ffffff' 
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: '#ffffff' 
                    }
                },
                y: {
                    ticks: {
                        color: '#ffffff' 
                }
            }
        }
     }
    });

    var size = '90%';
    if (myChart.canvas) {
        myChart.canvas.parentNode.style.width = size;
    }
}



function DrawPlot(canvasId, data, labelsArray , step, chartType, title, labelX, labelY, border, radio, gridOn,
        responsive, AutoSkiping, showLegend, xtype) {

        var colorList = ['#ffffff', '#00ffff', '#ff00ff', '#0000ff', '#FFDAC1', '#E2F0CB', ];
        var borderCol = 'rgb(255, 255, 255)';
        var borderCol2 = 'rgb(70, 70, 70)';
        var textCol = '#ffffff';

        var ddd = [];
        // we start from index 1 to avoid white color for first dataset
        var indpos = 1;
        data.forEach((itemArray, i) => {
            console.log(indpos);
          var d = {
              label: labelsArray[indpos-1],
              backgroundColor: colorList[indpos],
              borderColor: colorList[indpos],
              data: itemArray,
              radius: radio,
              borderWidth: border,
              fill: false,
              spanGaps: false,
              showLines: true,
              yAxisID: 'y-axis-1',
          };
          ddd.push(d);
          indpos=indpos+1;


        });

        dataTop = {
            labels: "",
            datasets: ddd
        };

        var options = {

            maintainAspectRatio: false,
            responsive: responsive,
            errorBarColor: {
                v: ['#ff0000', '#ff0000']
            },
            errorBarWhiskerColor: '#ff0000',

            plugins: {
                title: {
                    display: true,
                    text: title,
                    color: '#ffffff',

                },
                legend: {
                    display: showLegend,
                    position: 'top',
                    labels: {
                        display: true,
                        color: 'rgb(255, 255, 255)'
                    },
                    title: {
                        display: false,
                        text: title,
                        color: 'rgb(255, 255, 255)'
                    },

                },
                tooltip: {

                    callbacks: {
                        title: (items) => {
                            const item = items[0].parsed;
                            //  console.log(item);
                            if (item.yMax != null) {
                                cad = items[0].label + ` : ` + item.y.toFixed(2) + ` max: ` + item.yMax
                                    .toPrecision(2) + ` min: ` + item
                                    .yMin.toPrecision(2);
                            } else {
                                cad = items[0].label + ` : ` + item.y.toFixed(2);
                            }
                            return cad;
                            //return items[0].label+` : ` + items[0].parsed;
                        },
                        label: (items) => {
                            return ``;
                        },
                    },
                },
            },
            scales: {
                x: {
                    //offset: true,
                    //type: 'linear',
                    grid: {
                        display: gridOn,
                        drawBorder: gridOn,
                        drawOnChartArea: gridOn,
                        drawTicks: gridOn,
                        color: '#74C3D8'
                    },
                    display: true,
                    title: {
                        display: true,
                        text: labelX,
                        color: '#ffffff'
                    },
                    ticks: {
                        display: gridOn,
                        autoSkip: AutoSkiping,
                        stepSize: step,
                        beginAtZero: false,
                        color: '#eeeeee'
                    },
                },
                
                'y-axis-1': {
                    type: 'linear',

                    position: 'left',
                      beginAtZero: true,
                    grid: {
                       display: gridOn,
                        drawBorder: gridOn,
                        drawOnChartArea: gridOn,
                        drawTicks: gridOn,
                        color: '#00ffff',
                        drawOnChartArea: true, // Dibujamos la linea horizontal del grid
                    },
                    display: true,
                    title: {
                        display: true,
                        text: labelY + " experiment",
                        color: '#ffffff'
                    },
                    ticks: {

                        display: gridOn,
                        color: '#eeeeee'
                    },
                }
            }

        };

        var config1 = {
            type: chartType,
            data: dataTop,
            options: options,
        };

        if (xtype != '') {
            config1.options.scales.x.type = xtype;
        }
        //config1.options.scales.y.type = 'linear';

        var ctx1 = document.getElementById(canvasId);

        var myChart1 = new Chart(ctx1, config1);

        var size = '90%';
        if (myChart1.canvas) {
            myChart1.canvas.parentNode.style.width = size;
            myChart1.canvas.parentNode.style.height = size / 2;
        }
    }


</script>


<body id="page-top">
    <!-- Navigation-->
     <main>
     <header class="masthead">
        <div class="container px-4 px-lg-5 h-100">
            <div class="row gx-4 gx-lg-5 h-100 align-items-center justify-content-center text-center">
                <div class="col-lg-10 align-self-end">
                    <h1 class="text-white   font-weight-bold">NMRlipids Databank</h1>
                     <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
                         <div class="container px-4 px-lg-5">
                            <a class="navbar-brand" href="/#page-top">NMRlipids Databank</a>
                            <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse"
                               data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                                  aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon"></span>
                             </button>
                                <div class="collapse navbar-collapse" id="navbarResponsive">
                                    <ul class="navbar-nav ms-auto my-2 my-lg-0">
                                        <li class="nav-item"><a class="nav-link" href="/#about">About</a></li>
                                    </ul>
                                </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    <!-- Main page -->
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-10">
                    <hr class="divider divider-light" />
                    <h3 class="text-white text-center mt-0">
                        @if (! empty($experiments_list)) Experiments @else {{ $entity['type'] }} Experiment @endif</h3>
                    <?php 
                        $experiments_list = $experiments_list ?? [];
                        $entity = $entity ?? [];
                        $properties = $properties ?? []; 
                    ?>
                    @if (! empty($experiments_list))
                        <div class="text-white text-center mt-0">
                        <table class="table table-bordered table-striped table-sm table-dark">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Article DOI</th>
                                    <th scope="col">Data DOI</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Section</th>
                                    <th scope="col"># types of lipids</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($experiments_list as $experiment)
                                <tr>
                                    <td>{{ $experiment->id }}</td>
                                    <td>{{ $experiment->article_doi }}</td>
                                    <td>{{ $experiment->data_doi }}</td>
                                    <td>{{ $experiment->type }}</td>
                                    
                                    <td>{{ $experiment->section }}</td>
                                    <td>{{ $experiment->lipid_count }}</td>
                                    <td><a href="{{ route('experiments.show', ['type' => $experiment->type, 'doi' => $experiment->article_doi, 'section' => $experiment->section]) }}" class="btn btn-primary btn-sm">View</a></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center">
                        {{ $experiments_list->links() }}
                        </div>
                        </div>
                    @else
                        
                    <!-- Bootstrap Tabs -->
                    <ul class="nav nav-pills justify-content-start" id="experimentTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="analysis-tab" data-bs-toggle="tab" data-bs-target="#analysis" type="button" role="tab">Data</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="properties-tab" data-bs-toggle="tab"  data-bs-target="#properties" type="button" role="tab">Properties</button>
                        </li>
                    </ul>
                    <!-- Tab Contents -->
                    <div class="tab-content" id="experimentTabContent">
                        <!-- Overview Tab -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                                <br/>
                                <table class="table table-bordered table-striped table-sm table-dark">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Article DOI</th>
                                            <td>{{ $entity['doi'] }}</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Data DOI</th>
                                            <td>{{ $entity['data_doi'] }}</td>
                                        </tr>
                                       
                                        <tr>
                                            <th scope="row">Internal ID</th>
                                            <td>{{ $entity['path'] }}</td>
                                        </tr>
                                       
                                        <tr>
                                            <th scope="row">Membrane composition (molar fraction)</th>

                                            <td>
                                            <table class="table">
                                                
                                                <tbody>
                                                @foreach ( $entity['membrane_composition'] as $component )
                                                    <tr>
                                                        <td><a href="/lipid/{{ $component->id }}"> {{ $component->molecule }}</a></td>
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
                                                    <table class="table table-striped table-sm ">
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
                                            <table class="table table-striped table-sm table-dark">
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
                                             <tr>
                                                <th scope="row">X-ray detector</th>
                                                <td>{{ $properties['XRAY']->value['DETECTOR'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray source</th>
                                                <td>{{ $properties['XRAY']->value['SOURCE'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray wavelength (nm)</th>
                                                <td>{{ $properties['XRAY']->value['LAMBDA'] ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">X-ray beam size (mm)</th>
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

                                       
                                        @endif
                                    </tbody>
                                </table>
                        </div>
                        <!-- Properties Tab -->
                        @php
                            unset($properties['TEMPERATURE']);
                            unset($properties['TOTAL_HYDRATION']);
                            unset($properties['PH']);
                            unset($properties['REAGENT_SOURCES']);
                            unset($properties['SAMPLE_PROTOCOL']);
                            unset($properties['TOTAL_LIPID_CONCENTRATION']);
                            unset($properties['COUNTER_IONS']);
                            unset($properties['XRAY']);

                        @endphp
                          
                        @if (count($properties) > 0)
                        
                        <div class="tab-pane fade" id="properties" role="tabpanel" aria-labelledby="properties-tab">
                            <br/>
                            <table class="table table-bordered table-striped table-sm table-dark">
                                <thead>
                                    <tr>
                                        <th scope="col">Name</th>
                                        <!-- th scope="col">Description</th -->
                                        <th scope="col">Value</th>
                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($properties as $prop)
                                    <tr>
                                        <td>{{ $prop->name }}</td>
                                        <!--td>{{ $prop->description }}</td-->
                                        <td>
                                         @if( preg_match('/^(array|dict)$/', $prop->type) )
                                          <!-- Format arrays and dictionaries nicely using html in nested tables -->
                                            @php
                                                $decoded_value = $prop->value;
                                            @endphp
                                            @if (is_array($decoded_value))
                                                @if (array_keys($decoded_value) === range(0, count($decoded_value) - 1))
                                                    <!-- It's an array -->
                                                    <table class="table table-striped table-sm table-dark">
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
                                                    <table class="table table-striped table-sm table-dark">
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
                            <!-- Hide the properties tab if there are no properties to show -->
                        @php
                            echo "<style>\n";
                            echo "#properties-tab { display: none; }\n";
                            echo "</style>\n";
                        @endphp
                        @endif
                        <!-- Analysis Tab -->
                        <div class="tab-pane fade" id="analysis" role="tabpanel" aria-labelledby="analysis-tab">
                            
                            @if ($entity['type'] === 'OP')
                            <table class="table table-bordered table-striped table-sm table-dark">
                                <thead>
                                    <tr>
                                        <th scope="col">Lipid</th>
                                        <th scope="col">OP data</th>
                                    </tr>
                                </thead>
                                <tbody>

                                @foreach ( $entity['membrane_composition'] as $component )
                                    <tr>
                                        <td><a href="/lipid/{{ $component->id }}"> {{ $component->molecule }}</a></td>
                                        <td>
                                            @if ( isset( $component->data ) )
                                                <table class="table table-striped table-sm table-dark">
                                                    
                                                    <tbody>
                                                        @foreach ( $component->data as $key => $data )
                                                        @if ( empty( $data ) )
                                                        <tr> <td colspan="2">
                                                        <p> No OP {{ $key }} data available for this lipid. </p> 
                                                         @php continue; @endphp
                                                        </td></tr> 
                                                        @endif
                                                        <tr>
                                                            <td>
                                                                <div class="chart-container-half">
                                                                    <canvas id="myChartOP{{ $component->id }}{{ $key }}"> </canvas>
                                                                </div>
                                                                <?php
                                                                    $data_values = json_encode($data);
                                                                    echo "<script>\n";
                                                                    echo "var dataOP = " . $data_values . ";\r\n";
                                                                    echo "var labels = " . json_encode(array_keys($data)) . ";\r\n";
                                                                    echo "var label = [\"". $entity['doi'] . " - " . $component->molecule . "\"];\r\n";
                                                                    echo 'OPPlot("myChartOP' . $component->id . $key . '", dataOP, labels, label);' . "\r\n";
                                                                    echo "</script>\r\n";
                                                                    //echo '<textarea rows="10" cols="100" id="dataOP' . $component->id . $key . '" value="' . $data_values . '">    ' . $data_values . '</textarea>';
                                                            ?>
                                                            </td>
                                                                                                                  
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>  
                                            @else
                                                No OP data available for this lipid.
                                            @endif
                                        </td>  
                                      
                                    </tr>
                                @endforeach

                                </tbody>
                            </table>
                           
                            @elseif  ($entity['type'] == 'FF' && ! empty( $entity['data'] ) )
                            <div class="row p-2">
                                <div class="col-sm-12 col-md-12 chart-container-half">
                                    <canvas id="myChartFormFactEXP"> </canvas>
                                    <?php
                                        $dataFF = $entity['data'];
                                        echo "<script>\n";
                                        echo "var dataFF = [" . $dataFF . "];\r\n";
                                        echo "var label = [\"". $entity['doi'] . "\"];\r\n";
                                        echo 'DrawPlot(
                                                "myChartFormFactEXP",
                                                
                                                dataFF,
                                                
                                                label,
                                                0.01,
                                                "line",
                                                "Form factor",
                                                "Qz (\u{212B}\u{207B}\u{00B9})",
                                                "  |F(Qz)|  ",
                                                1,
                                                0,
                                                true,
                                                true,
                                                true,
                                                true,
                                                "linear"
                                                );' . "\r\n";
                                        echo "</script>\r\n";
                                    ?>
                                </div>
                            </div>

                            @endif

                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </header>
    </main>
    @include('layouts.foot')




    <!-- Bootstrap core JS--><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Core theme JS-->
    <script src="{{ asset('js/scripts.js') }}"></script>
   
</body>