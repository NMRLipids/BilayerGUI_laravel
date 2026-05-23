@php

    use App\Lipido;

    /**
     *
     * @var Lipido[] $lipidos
     */

    // Al entrar en el formulario borramos la seleccion de la session para empezar una nueva busqueda
    $listaIdsSesson = session()->all();
    // Borrramos los IDs que estaban en session

    foreach ($listaIdsSesson as $key => $value) {
        if (gettype($value) != 'array' && strpos($key, 'CompareID') !== false) {
            session()->forget($key);
        }
    }
@endphp

@extends('layouts.app')

@section('content')

    <div class="container" style="min-height: 100vh;">
        <div class="row justify-content-center" style="padding-top: 40px;">
            <div class="col-md-12">

                <form action="{{ route('search.results') }}" method="get">
                    <div class="input-group mb-3">
                        <input type="text" name="text" class="form-control" placeholder="Search..."
                            aria-label="Recipient's username" aria-describedby="button-addon2" value="{{ $texto }}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
                        </div>
                    </div>
                </form>

                <div class=" ">
                    <div class="search_result" style="padding: 1rem">
                        @if ($lipidos->isEmpty() and $iones->isEmpty() and $trayectorias->isEmpty() and $experiments->isEmpty())
                            <span class="text-white-75 mb-1" style="font-size: 1.2em;">
                                Your query has returned no data. We searched lipids, experiments, and simulations for partial matches in meta-data, paths and DOIs.
                                You can use partial matching, and wildcards (* and ?) and exact matching("",''). For example, searching for <em>P?PE</em> will return lipids POPE and PYPE
                                 and all trajectories and experiments that contain POPE or PYPE in their lipid composition, but also those that contain it in their name or in the path of the files.
                                 <em>"CHOL"</em> will return only results for CHOL but not DCHOL.
                                 See <a
                                    target="_blank" href="https://databank.readthedocs.io/stable/schemas/moleculesAndMapping.html">Molecules
                                    and Mapping</a> for a list of  molecules. For simulation search, try the Advanced
                                Search.<br>
                            </span>
                        @endif


                        @if (count($lipidos) > 0)
                            <h1 class="txt-white  mt-4">@lang('Lípido') <small class="text-muted">({{ count($lipidos) }})</small></h1>
                            <div class="row m-1" data-limit="20">
                                @foreach ($lipidos as $lipido)
                                    <div class="col-sm-12 col-lg-2 p-1 limit-item">
                                        <span class="badge badge-secondary">@lang('Lípido') </span>
                                        <span>
                                            <a href="{{ route('lipid.show', $lipido->id) }}"
                                                class="" title="{{ $lipido->displayTitle() }}">{{ $lipido->displayName() }}</a>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif


                        <!-- ION -->
                        @if (count($iones) > 0)
                            <h1 class="txt-white  mt-4">@lang('Ion') <small class="text-muted">({{ count($iones) }})</small></h1>
                            <div class="row m-1" data-limit="20">
                                @foreach ($iones as $ion)
                                    <div class="col-sm-12 col-lg-2 p-1 limit-item">
                                        <span class="badge badge-secondary">@lang('Ion') </span>
                                        <span>
                                            <a href="{{ route('new_advanced_search.results') . '?iones_operador[1]=or&iones[1]=' . $ion->molecule }}"
                                                class="" title="{{ $ion->displayTitle() }}">{{ $ion->displayName() }}</a>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Modelo de Membrana -->
                        @if (count($trayectorias) > 0)
                            <!-- Contador -->
                            <?php
                            $maxLipids = 0;
                            ?>
                            @foreach ($trayectorias as $trayectoria)
                                @php
                                    $membrana =  $trayectoria->membrana;
                                @endphp
                                @if (strlen($membrana->lipid_names_l1) > 0 && strlen($membrana->lipid_names_l2) > 0)
                                    <?php
                                    $lipidsInMembrane = explode(':', $membrana->lipid_number_l1);
                                    $numLipids = count($lipidsInMembrane);
                                    $maxLipids = max($maxLipids, $numLipids);
                                    ?>
                                @endif
                            @endforeach

                            <h1 class="txt-white mt-4">Simulations <small class="text-muted">({{ count($trayectorias) }})</small></h1>
                            <div class="row m-1">
                                <div class="col">
                                    <p>Hide by number of lipids: </p>
                                    @php
                                        for ($i = 1; $i <= $maxLipids; $i++) {
                                            echo '<label><input type="checkbox" class="b' .
                                                $i .
                                                '" name="' .
                                                $i .
                                                '" value="' .
                                                $i .
                                                '" onclick="PressCheck(this)">&nbsp;' .
                                                $i .
                                                ' lipid &nbsp;&nbsp;</label>';
                                        }
                                    @endphp
                                </div>
                            </div>
                            <div class="row m-1" data-limit="20">
                                @foreach ($trayectorias as $trayectoria)
                                    @php
                                        $membrana =  $trayectoria->membrana;
                                    @endphp
                                    @if (strlen($membrana->lipid_names_l1) > 0 && strlen($membrana->lipid_names_l2) > 0)
                                        <?php
                                        $lipidsInMembrane = explode(':', $membrana->lipid_number_l1);
                                        $numLipids = count($lipidsInMembrane);
                                        ?>

                                     <div class="col-12 col-md-6 col-lg-4 p-1 limit-item">
                                        <div class="num{{ $numLipids }}">
                                            @if ($trayectoria->hasExperiments())
                                                    <span class="badge badge-secondary" style="background-color: #168258;">@lang('Simulation + experiments')</span>
                                            @else
                                                    <span class="badge badge-secondary">@lang('Simulation')</span>
                                            @endif
                                            
                                            <span>
                                                <a href="{{ route('trayectorias.show', $trayectoria->id) }}"
                                                    class="" title="{{ $trayectoria->displayTitle() }}"> {{ $trayectoria->displayName() }}
                                                </a>
                                            </span>
                                        </div>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <!-- Experiments -->
                        @if (count($experiments) > 0)
                            <h1 class="txt-white  mt-4">@lang('Experiments') <small class="text-muted">({{ count($experiments) }})</small></h1>
                            <div class="row m-1" data-limit="20">
                                @foreach ($experiments as $experiment)
                                    <div class="col-12 col-md-6 col-lg-4 p-1 limit-item">
                                        <span class="badge badge-secondary">@lang('Experiment')
                                            ({{ $experiment->type }})</span>
                                        <span>
                                            <a href="{{ route('experiments.show', ['type' => $experiment->type, 'path' => $experiment->path]) }}"
                                                class="" title="{{ $experiment->displayTitle() }}">{{ $experiment->displayName() }}</a>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Pseudo-pagination: hide items beyond data-limit and add "Show more" button
        document.querySelectorAll('[data-limit]').forEach(function(container) {
            var limit = parseInt(container.dataset.limit, 10);
            var items = container.querySelectorAll('.limit-item');
            if (items.length <= limit) return;

            // Hide overflow items
            for (var i = limit; i < items.length; i++) {
                items[i].classList.add('d-none');
            }

            // Create "Show more" button
            var btn = document.createElement('button');
            var remaining = items.length - limit;
            btn.className = 'btn btn-sm btn-outline-light mt-2 mb-2';
            btn.textContent = 'Show more (' + remaining + ' more)';
            btn.addEventListener('click', function() {
                for (var i = limit; i < items.length; i++) {
                    items[i].classList.remove('d-none');
                }
                btn.style.display = 'none';
            });
            container.appendChild(btn);
        });

        // pulsas uno y marca el estado del gemelo
        function PressCheck(aa) {

            valuecheck = aa.value;
            status = 0;
            // Esto es por algo visual, realmente cuando pulso tengo que pasarlo por una varible de sesion
            if (aa.checked) {
                $("input[value*=" + valuecheck + "]").prop('checked', true);
                $(".num" + aa.name).attr("hidden", "true");
            } else {
                $("input[value*=" + valuecheck + "]").prop('checked', false);
                $(".num" + aa.name).removeAttr("hidden");

            }

        }
    </script>
@endsection
