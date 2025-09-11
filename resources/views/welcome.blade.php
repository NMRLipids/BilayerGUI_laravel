<?php

use App\Http\Controllers\StatisticsController;
?>
<!doctype html>
<html class="welcome" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.head')

<script>
    // Referencia: http://www.html5rocks.com/en/tutorials/speed/animations/
    var last_known_scroll_position = 0;
    var ticking = false;

    function doSomething(scroll_pos) {
        // Hacer algo con la posición del scroll
        //console.log("scrolleo " + scroll_pos + "  " + window.innerHeight);
        if (scroll_pos > 100 && scroll_pos < (window.innerHeight - 80)) {
            $('footer').fadeOut();
        } else {
            $('footer').fadeIn();
        }


    }
    /*
    window.addEventListener('scroll', function(e) {
      last_known_scroll_position = window.scrollY;

      if (!ticking) {
        window.requestAnimationFrame(function() {
          doSomething(last_known_scroll_position);
          ticking = false;
        });
      }
      ticking = true;
    });
    */
</script>

<body id="page-top">

    <!-- Fonts
<link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
-->
    <!-- Navigation-->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
        <div class="container px-4 px-lg-5">
            <a class="navbar-brand" href="#page-top">NMRlipids Databank</a>
            <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto my-2 my-lg-0">

                    <!--  <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>-->
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <!--<li class="nav-item"><a class="nav-link" href="#portfolio">Project</a></li>-->
                    <!--  @if (Route::has('login'))
<li class="nav-item">
                  @auth
                                                                          <a class="nav-link" href="{{ url('/home') }}">Home</a>
@else
    <a  class="nav-link" href="{{ route('login') }}">Login</a>
                  @endauth
                </li>
@endif-->
                </ul>
            </div>
        </div>
    </nav>


    <!-- Masthead-->
    <header class="masthead">
        <div class="container px-4 px-lg-5  ">
            <div class="row gx-4 gx-lg-5   align-items-center justify-content-center text-center">
                <div class="col-lg-8 align-self-end">
<<<<<<< HEAD
                    <h2 class="text-white font-weight-bold">
                        <img class="img-fluid" alt="Responsive image"
                            src="{{ asset('storage/images/nmr_w_letras.png') }}" alt="">
                         Welcome to NMRlipids Databank-GUI (v<?php echo config('app.version'); ?>)
                    </h2>
=======
                    <h1 class="text-white font-weight-bold">
                        <img class="img-fluid" alt="Responsive image"
                            src="{{ asset('storage/images/nmr_w_letras.png') }}" alt="">
                        <br><br> Welcome to NMRlipids Databank-GUI (nightly build)
                    </h1>
>>>>>>> be5aded0e2b1b02c35080ab63f8ae7e5d202cde7
                    <hr class="divider" />
                </div>
                <div class="col-lg-8 align-self-baseline">
                    <p class="text-white-75 mb-5">NMRlipids Databank-GUI can be used to search data from the <a
                            href="https://github.com/NMRlipids/Databank">NMRlipids
                            Databank</a><br>
                        For the programmatic access, see NMRlipids <a
                            href="https://github.com/NMRlipids/Databank">Databank-API</a>. For more details see the <a
                            href="https://www.nature.com/articles/s41467-024-45189-z"> NMRlipids databank publication</a>.
                    <div class="row">
                        <div class="col-9 p-xs-1 p-sm-2">
                            <form action="{{ route('search.results') }}" method="get">
                                <div class="input-group mb-3 ui-widget">
                                    <input id="BasicSearch" type="text" name="text" class="form-control"
                                        placeholder="@lang('Buscar')..." aria-label="Search field"
                                        aria-describedby="button-addon2">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit"
                                            id="button-addon2">@lang('Buscar')</button>
                                    </div>
                                </div>
                            </form>

                        </div>

                        <div class="col-3 p-xs-1 p-sm-2">

                            <a href="{{ route('new_advanced_search.form') }}"
                                class="btn btn-outline-secondary"><span>@lang('Búsqueda avanzada')</span></a>

                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <p class="text-white-75 mb-5">Search based on lipid composition for example by typing: POPC or
                            POPC:POPE. For abbreviation of molecules,
                            see table <a
                                href="https://github.com/NMRLipids/Databank/blob/main/Scripts/BuildDatabank/info_files/README.md#composition-compulsory">Composition
                                compulsory link</a>.
                            Search based on trajectory identity number by typing the number after ‘ID’, for example,
                            ID123. More options are available in Advanced Search.</p>
                    </div>

                    <div class="col-12 m-4">
                        <div style="color:#fff;">
                            {{ StatisticsController::totals() }}
                        </div>
                        <div class="m-4">
                            <!--<p class="text-white-75 mb-5"><a href="/statistics">See more Statistics</a></p>-->
                        </div>

                    </div>

                    <!--<img class="img-fluid" alt="Responsive image" src="{{ asset('storage/images/supepmem1_100.jpg') }}" alt="">-->

                </div>
            </div>
        </div>
    </header>

    <!-- About-->
    <section class="page-section bg-primary" id="about">
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="text-white mt-0">NMRlipids Databank</h2>
                    <hr class="divider divider-light" />
                    <p class="text-white-75 mb-4 txt_desc">NMRlipids Databank is a community-driven catalogue containing
                        atomistic MD simulations of biologically relevant
                        lipid membranes emerging from the <a href="http://nmrlipids.blogspot.com/"> NMRlipids open
                            collaboration</a>.
                        It has been designed to improve the Findability, Accessibility, Interoperability, and Reuse
                        (FAIR) of MD simulation data.
                        NMRlipids databank is implemented using overlay databank structure described more detailed in
                        the <a href="https://www.nature.com/articles/s41467-024-45189-z"> databank publication</a>. </p>
                    <p class="text-white-75 mb-4 txt_desc">
                        NMRlipids Databank-GUI (this website) can be used to browse and search the content of the
                        Databank, to select the best available simulations for specific systems based on ranking lists,
                        and to perform comparisons between basic properties of membranes.
                    </p>

                    <p class="text-white-75 mb-4 txt_desc">
                        <a href="http://github.com/NMRlipids/Databank/"> NMRlipids Databank-API</a> provides
                        programmatic access to all simulation data in the NMRlipids Databank. This enables wide range of
                        novel data-driven applications —
                        from construction of machine learning models that predict membrane properties to automatic
                        analysis of virtually any property across all simulations in the Databank.
                        A <a
                            href="https://github.com/NMRLipids/Databank/blob/main/Scripts/AnalyzeDatabank/template.ipynb">template</a>
                        and other examples for applications of NMRlipids Databank-API are available at <a
                            href="https://github.com/NMRlipids/Databank">GitHub</a> and in the <a
                            href="https://www.nature.com/articles/s41467-024-45189-z"> NMRlipids databank publication</a>.
                    </p>
                    <p class="text-white-75 mb-4 txt_desc">
                        If you use the NMRlipids databank in your publications, ease always cite the NMRlipids <a
                            href="https://www.nature.com/articles/s41467-024-45189-z">Databank
                            publication</a>,
                        as well as the trajectory entries and related publications whenever appropriate.
                        That said, all the content is provided as is: There is no guarantee that the content is correct
                        or suitable for any purpose — you should check it yourself (and please let us know once you find
                        bugs). </p>
                </div>
            </div>
        </div>
    </section>



    <script>
        $(function() {

            $("#BasicSearch").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "{{ route('search.basic') }}",
                        dataType: "json",
                        data: {
                            term: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                }

            });
        });
    </script>
    @include('layouts.foot')
