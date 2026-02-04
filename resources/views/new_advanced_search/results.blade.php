<?php

use App\Trayectoria;

/**
 * @var Trayectoria[] $trayectorias
 */

?>
@extends('layouts.app')

@section('content')
    <?php
    function filtraValor($val)
    {
        if ($val == 0 || $val == 4242) {
            return 'N/A';
        } else {
            return $val;
        }
    }
    //var_dump($_GET);
    // Al entrar en el formulario borramos la seleccion de la session para empezar una nueva busqueda
    /*
                $listaIdsSesson = session()->all();
                // Borrramos los IDs que estaban en session

                foreach ($listaIdsSesson as $key => $value) {
                    if (gettype($value) != 'array' && strpos($key, 'CompareID') !== false) {
                        session()->forget($key);
                    }
                }*/
    ?>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <?php
            if (!empty($trayectorias)) {
            ?>
                <div class="txt-white ">
                    <div class="card-header  " style="display: flex; justify-content: space-between">
                        @lang('Búsqueda avanzada')
                        {{ session('lifetime') }}
                        @if ($trayectorias->hasPages())
                            -
                            {{ $trayectorias->count() }} / {{ $trayectorias->total() }}
                            @lang('Registros')
                        @else
                            {{ $trayectorias->count() }} @lang('Registros')
                        @endif


                        <?php
                        // Divido la URL para poder mandar los mismos parametros al exportador
                        $newlink = '';
                        $newlinkSel = '';

                        $actual_link = "$_SERVER[REQUEST_URI]";
                        $actlink = explode('?', $actual_link);
                        if (isset($actlink[1])) {
                            $newlink = 'export?' . $actlink[1];
                            $newlinkSel = 'export?' . $actlink[1] . '&selected=1';
                        }

                        $allSession = Session::all();
                        $numSelected = 0;
                        foreach ($allSession as $key => $value) {
                            if (str_contains($key, 'CompareID') && $value == 1) {
                                $numSelected = $numSelected + 1;
                            }
                        }
                        //var_dump($allSession);
                        // die();
                        ?>


                    </div>

                    <div class="d-flex p-2" style="background-color:rgb(255 255 255 / 18%)">
                        <div class="mr-auto ">
                            <a class="btn btn-primary btn-sm" href="#" onclick="SelectAll(this)">Select page</buttom>
                                <a class="ml-lg-2 btn btn-primary btn-sm" href="{{ route('new_advanced_search.compare') }}">
                                    Compare selected </a>
                        </div>


                        <div class="p2">
                            <a class="btn btn-primary btn-sm" href="{{ $newlinkSel }}"> @lang('Exportar seleccionado')</a>
                            <a class="ml-lg-2 btn btn-primary btn-sm" href="{{ $newlink }}"> @lang('Exportar todo')</a>
                        </div>
                    </div>

                </div>
                <?php } ?>

                <div class="table-responsive txt-white">
                    <?php
                if (empty($trayectorias)) {
                ?>
                    <div class="p-4">
                        <h3>Data not found.</h3>
                    </div>

                    <?php
                } else {
                ?>

                    <div id="accordion" class="  d-xl-none">
                        <?php
                        $tempData = array();

                        foreach ($trayectorias as $trayectoria) {

                            foreach ($trayectoria->groupBy('id') as $key) {

                                $tempData = array();

                                foreach ($key as $key2 => $value2) {
                                    foreach ($value2 as $key3 => $value3) {
                                        if (isset($tempData[$key3])) {
                                            if (!in_array($value3, $tempData[$key3])) {
                                                $tempData[$key3][] = $value3;
                                            }
                                        } else {
                                            $tempData[$key3][] = $value3;
                                        }
                                    }
                                }
                                //var_dump($tempData);die();
                                $id = implode(', ', $tempData['id']); // Acorto ID

                        ?>
                        <div class=" bg-mole my-2">
                            <div class="head-mole" id="heading{{ $id }}">
                                <h5 class="mb-0">

                                    <button class="btn btn-link" data-toggle="collapse"
                                        data-target="#collapse{{ $id }}" aria-expanded="true"
                                        aria-controls="collapse{{ $id }}">
                                        @lang('ID') {{ $id }}
                                    </button>
                                    <?php
                                    $isChecked = Session::get('CompareID' . $tempData['id'][0], '0');
                                    $Cheked = '';
                                    if ($isChecked == '1') {
                                        $Cheked = 'Checked';
                                    }
                                    ?>
                                    <input type="checkbox" class="selectCompare a{{ $tempData['id'][0] }}"
                                        name="{{ $tempData['id'][0] }}" value="{{ $tempData['id'][0] }}"
                                        onclick="PressCheck(this)" {{ $Cheked }}>
                                    <span class="txt_dato" style="color:gray; font-size:10pt;"> Select for compare</span>

                                    <span class="right"><a class="btn btn-primary"
                                            href="{{ route('trayectorias.show', $id) }}"><i
                                                class="bi bi-arrow-right-circle-fill"></i></a></span>
                                </h5>
                            </div>

                            <div id="collapse{{ $id }}" class="collapse show"
                                aria-labelledby="heading{{ $id }}" data-parent="#accordion">
                                <div class="card-body">
                                    <div class="row">

                                        <div class="col-sm-12 col-md-4">

                                            <p>
                                                <span class="title">@lang('Lipidos')</span><br>
                                                <?php
                                                foreach ($key as $keyr => $valuer) {
                                                    echo $valuer->lipid_name . ' (' . $valuer->leaflet_1 . ':' . $valuer->leaflet_2 . ')<br>';
                                                }
                                                ?>
                                                {{-- implode(', ',$tempData['mem_name']) --}}<br>
                                            </p>

                                        </div>



                                        <div class="col-sm-12 col-md-4">

                                            <p><span class="title">@lang('Iones')</span><br>
                                                <?php
                                                //if (strlen(implode(', ',$tempData['ion_short_name']))>0){

                                                echo implode(', ', $tempData['ion_short_name']);

                                                //}

                                                ?>
                                            </p>
                                            {{-- <p><span class="title">@lang('Modelo de agua')</span><br>
                                                {{ implode(', ', $tempData['wm_short_name']) }}
                                                    </p> --}}
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <p><span class="title">@lang('Parametros de simulación')</span><br>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-6 col-md-4">
                                            {{ c('longitud') }} : {{ implode(', ', $tempData['trj_length']) }}<br>
                                            Force field : {{ implode(', ', $tempData['ff_name']) }}<br>
                                            {{ c('temperatura') }} : {{ implode(', ', $tempData['temperature']) }} K<br>
                                        </div>

                                        <div class="col-sm-6 col-md-4">

                                            {{ c('particulas') }} : {{ implode(', ', $tempData['number_of_atoms']) }}<br>
                                            {{ c('software') }} : {{ implode(', ', $tempData['software']) }}<br>
                                        </div>

                                        <div class="col-sm-6 col-md-4">
                                            <?php
                                            $expCount = implode(', ', $tempData['experimentdatacountFF']) + implode(', ', $tempData['experimentdatacountOP']);
                                            if ($expCount > 0) {
                                              echo 'Simulation and<br>Experimental data';
                                            } else {
                                              echo 'Simulation data';
                                            }
                                            ?>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                            }
                        } // FOREACH
                        ?>

                        {{ $trayectorias->withQueryString()->links() }}

                    </div>

                    <div class="card-body d-none   d-xl-block ">
                        <div class="table-responsive txt-white">
                            <table id="tabla-busqueda-avanzada" class="table table-striped">

                                <thead class="thead-light">
                                    <tr>
                                        <th>@lang('Compare')</th>
                                        <th>Order parameters quality</th>
                                        <th>@lang('ID')</th>
                                        <!--<th>@lang('FF') (@lang('resolución'))</th>-->
                                        <th>@lang('Lipidos')</th>

                                        <!--<th>@lang('Heteromoléculas')</th>-->
                                        <th>@lang('Iones')</th>
                                        {{-- <th>@lang('Modelodeagua')</th> --}}
                                        <th>@lang('Parametros de simulación')</th>
                                        <th>Simulation/Experimental</th>
                                        <th>@lang('Analisis')</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($trayectorias as $trayectoria)
                                        <tr>
                                            <?php

                                        foreach ($trayectoria->groupBy('id') as $key) {

                                            $tempData = array();

                                            foreach ($key as $key2 => $value2) {
                                                foreach ($value2 as $key3 => $value3) {
                                                    if (isset($tempData[$key3])) {
                                                        if (!in_array($value3, $tempData[$key3])) {
                                                            $tempData[$key3][] = $value3;
                                                        }
                                                    } else {
                                                        $tempData[$key3][] = $value3;
                                                    }
                                                }
                                            }

                                        ?>
                                            <td>
                                                <?php

                                                $isChecked = Session::get('CompareID' . $tempData['id'][0], '0');
                                                $Cheked = '';
                                                if ($isChecked == '1') {
                                                    $Cheked = 'Checked';
                                                }
                                                ?>
                                                <input type="checkbox" class="selectCompare b{{ $tempData['id'][0] }}"
                                                    name="{{ $tempData['id'][0] }}" value="{{ $tempData['id'][0] }}"
                                                    {{ $Cheked }} onclick="PressCheck(this)">
                                            </td>

                                            <td>
                                                <?php
                                                if (empty(implode(', ', $tempData['op_quality_total'])) || implode(', ', $tempData['op_quality_total']) == '4242') {
                                                    echo 'N/A';
                                                } else {
                                                    echo (implode(', ', $tempData['op_quality_total']));
                                                }

                                                ?>

                                            </td>
                                            <td>{{ implode(', ', $tempData['id']) }}</td>

                                            <td>
                                                <?php
                                                $listCampos = [];
                                                foreach ($key as $keyr => $valuer) {
                                                    $listCampos[] = $valuer->lipid_name . ' (' . $valuer->leaflet_1 . ':' . $valuer->leaflet_2 . ')';
                                                    //echo $valuer->lipid_name." (".$valuer->leaflet_1.":".$valuer->leaflet_2.")<br>";
                                                }
                                                $listUnique = array_unique($listCampos);

                                                echo implode('<br>', $listUnique);
                                               
                                                ?>
                                            </td>



                                            <td>
                                                <?php
                                                if (strlen(implode(', ', $tempData['ion_short_name'])) > 0) {
                                                    echo implode(', ', $tempData['ion_short_name']);
                                                    echo '(' . implode(', ', $tempData['number_ions']) . ')';
                                                }

                                                ?>
                                            </td>

                                            {{-- <td>
                                                {{ implode(', ', $tempData['wm_short_name']) }}
                                            </td>
                                            --}}
                                            <td>
                                                {{ c('longitud') }} : {{ implode(', ', $tempData['trj_length']) }}<br>
                                                Force field : {{ implode(', ', $tempData['ff_name']) }}<br>
                                                {{ c('temperatura') }} : {{ implode(', ', $tempData['temperature']) }}<br>

                                                {{ c('particulas') }} :
                                                {{ implode(', ', $tempData['number_of_atoms']) }}<br>

                                                {{ c('software') }} : {{ implode(', ', $tempData['software']) }}<br>


                                            </td>
                                            <td style="text-align: center">
                                                <p>
                                                    <?php
                                                    $expCount = implode(', ', $tempData['experimentdatacountFF']) + implode(', ', $tempData['experimentdatacountOP']);
                                                    if ($expCount > 0) {
                                                        echo 'Simulation and <br>Experimental data';
                                                    } else {
                                                        echo 'Simulation data';
                                                    }
                                                    ?>
                                                </p>
                                            </td>

                                            <td style="text-align: center">

                                              <?php
                                              if (implode(', ', $tempData['git_path'])!=""){
                                                ?>

                                                <a
                                                <?php

                                                $expCount = implode(', ', $tempData['experimentdatacountFF']) + implode(', ', $tempData['experimentdatacountOP']);
                                                if ($expCount > 0) {
                                                    echo 'class="btn btn-success"';
                                                } else {
                                                    echo 'class="btn btn-primary"';
                                                }
                                                ?>
                                                    href="{{ route('trayectorias.show', implode(', ', $tempData['id'])) }}">
                                                    <i class="bi bi-arrow-right-circle"></i>
                                                </a>
                                                <?php
                                                }
                                                ?>
                                            </td>


                                            <?php
                                            /*
                                                // intento de monstar si tiene experimentos... peor esn
                                                if (implode(', ', $tempData['form_factor_experiment']) != '') {
                                                    echo 'class="btn btn-primary"';
                                                } else {
                                                    echo 'class="btn btn-success"';
                                                }
                                                */

                                        } // END IF
                                        ?>
                                        </tr>
                                    @endforeach

                                </tbody>
                            </table>
                            {{ $trayectorias->withQueryString()->links() }}
                        </div>
                    </div>
                    <div class="d-flex p-2">
                        <div class="p2 ">
                            <a class="m-2 btn btn-primary btn-sm" onclick="SelectAll(this)">Unselect page</buttom>
                                <a class="m-2 btn btn-primary btn-sm" href="{{ route('new_advanced_search.compare') }}">
                                    Compare selected </a>
                        </div>
                    </div>

                    <form id="formulario-compare-submit" action="{{ route('new_advanced_search.updatecompare') }}"
                        method="post">

                        {{-- csrf_field() --}}
                        @csrf
                        <?php
                        foreach ($trayectorias as $trayectoria) {
                            foreach ($trayectoria->groupBy('id') as $key) {
                                $tempData = [];

                                foreach ($key as $key2 => $value2) {
                                    foreach ($value2 as $key3 => $value3) {
                                        if (isset($tempData[$key3])) {
                                            if (!in_array($value3, $tempData[$key3])) {
                                                $tempData[$key3][] = $value3;
                                            }
                                        } else {
                                            $tempData[$key3][] = $value3;
                                        }
                                    }
                                }

                                $id = $tempData['id'][0];
                                $valueSession = Session::get('CompareID' . $tempData['id'][0], '0');
                                echo '<input type="hidden" id="Check' . $id . '" name="CompareID' . $id . '" value="' . $valueSession . '" />';
                            }
                        }
                        ?>

                    </form>

                    <?php
                } // END IF
                ?>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection


@section('js')
    <script>
        var url_filtros_html = "{{ route('filtros.html', ':filtro') }}";
        $('.mostrar_ocultar_filtros').click(function() {
            $('.contenedor-filtros').toggle();
            if ($('.fa-chevron-left').length) {
                $('.fa-chevron-left').removeClass('fa-chevron-left').addClass('fa-chevron-down');
            } else {
                $('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-left');
            }
        })
        $('#selector-filtros').change(function() {
            $('.contenedor-filtros').show();
            let codigo = $(this).find('option:selected').val();
            if (codigo !== 'trayectoria') {
                $(this).find('option[value=' + codigo + ']').hide();
            }
            if (codigo) {
                $.ajax({
                    'url': url_filtros_html.replace(':filtro', codigo),
                    'success': function(response) {
                        $('#filtros').prepend(response);
                        agregar_evento_eliminar_filtro();
                    }
                })

            }
            $('#selector-filtros').find('option[value=0]').prop('selected', true);
        })

        agregar_evento_eliminar_filtro();

        function agregar_evento_eliminar_filtro() {
            $('.eliminar_filtro').click(function() {
                let codigo = $(this).closest('.form-group').find('input').attr('name');
                $(this).closest('.form-group').remove();
                $('#selector-filtros').find('option[value=' + codigo + ']').show();
            })
        }

        function SelectAll(aa) {
            status = 0;
            // Se hace asi para que los selects gemelos tengan el mismo estado en las dos vistas
            if (aa.innerHTML == "Unselect page") {
                $('.selectCompare').prop('checked', false);
                aa.innerHTML = "Select page"
                status = 0;
            } else {
                $('.selectCompare').prop('checked', true);
                aa.innerHTML = "Unselect page"
                status = 1;
            }

            // Cambio es estado en los inputs ocultos para cambiar las variables de session.
            $("input[name*='CompareID']").val(status);
            // Mando el cambio de los inputs para actualizar las variables de session.
            $('#formulario-compare-submit').submit();
        }

        function InitializeInputs() {


        }

        // como son dos paginas en una con distintas formas de mostrarse hay inputs duplicados
        // pulsas uno y marca el estado del gemelo
        function PressCheck(aa) {

            //console.log(aa.value);
            valuecheck = aa.value;
            status = 0;
            // Esto es por algo visual, realmente cuando pulso tengo que pasarlo por una varible de sesion
            if (aa.checked) {
                $("input[value*=" + valuecheck + "]").prop('checked', true);
                status = 1;
            } else {
                $("input[value*=" + valuecheck + "]").prop('checked', false);
                status = 0;
            }
            if ($("#Check" + aa.name).length == 0) {
                $('#formulario-compare-submit').append('<input type="hidden" id="Check' + aa.name + '" name="CompareID' + aa
                    .name + '" value="' + status + '" />');
            } else {
                $("#Check" + aa.name).val(status);
            }
            // TESTEO
            $('#formulario-compare-submit').submit();

        }

        $('#formulario-compare-submit').submit(function() {

            //$('#formulario-compare-submit').html('');

            $.ajax({
                type: "POST",
                url: "{{ route('new_advanced_search.updatecompare') }}",
                data: $(this).serialize(),
                // headers: {
                //     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                // },
                complete: function(response) {
                    //console.log("complete"+ response);
                    // Do what you want to do when the session has been updated
                    console.log("complete> " + JSON.stringify(response) + " <");
                },
                success: function(response) {
                    console.log("success");
                    // Do what you want to do when the session has been updated
                    //console.log("success> "+ JSON.stringify(response)+" <");
                }
            });

            return false;

        });
    </script>
@endsection
