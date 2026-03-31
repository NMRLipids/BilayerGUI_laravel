<!doctype html>
<html class="welcome" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.head')

@php
use App\Http\Controllers\StatisticsController;
@endphp


<body>

    <div id="page-top"></div>

    <!-- Navigation-->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
        <div class="container px-4 px-lg-5">
            <ul class="navbar-nav ms-auto my-2 my-lg-0">
                <li class="nav-item">
                <a class="nav-link" href="#page-top"> <span class="nav-icon" style="">&#8962;</span> FAIRMD Lipids</a>
                </li>
            </ul>
                <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse"
                data-nav-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ms-auto my-2 my-lg-0">
                    <!-- li class="nav-item">
                        <form action="{{ route('search.results') }}" method="get" class="d-flex align-items-center">
                            <input type="text" name="text" class="form-control form-control-sm" placeholder="Search..." aria-label="Search" style="width: 150px;">
                            <button class="btn btn-sm btn-outline-light ms-1" type="submit">Go</button>
                        </form>
                    </li --- IGNORE --->
                    <li class="nav-item"><a class="nav-link" href="{{ route('new_advanced_search.form') }}"><span class="nav-icon" aria-hidden="true" style="font-size: 1.2rem;">⌕ </span>Advanced Search</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('lipids.list') }}"><span class="nav-icon" aria-hidden="true">◌</span>Lipids</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('experiments.list') }}"><span class="nav-icon" aria-hidden="true">⚗</span>Experiments</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('simulations.list') }}"><span class="nav-icon" aria-hidden="true">◈</span>Simulations</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about"><span class="nav-icon" aria-hidden="true">ⓘ</span>About</a></li>
                    <li class="nav-item d-flex align-items-center ms-2">
                        <button class="contrast-toggle" id="contrastToggle" type="button" title="Toggle high-contrast mode" aria-label="Toggle high-contrast mode">&#9684;</button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container-fluid p-0" style="display: flex; flex-direction: column; min-height: 50vh;">
    <!-- Masthead-->
    <header class="masthead">
        <div class="container px-4 px-lg-5">

            {{-- Hero area --}}
            <div class="row justify-content-center text-center" style="padding-top: 2rem;">
                <div class="col-lg-8">
                    <img class="img-fluid d-block mx-auto fairmd-logo" alt="FAIRMD Lipids Databank Logo"
                        src="{{ asset('storage/images/fairmd_w_letras.png') }}"
                        style="max-width: 420px; width: 100%; padding: 0.5rem 0 1rem;">
                    <p class="text-white-75" style="font-size: 0.85rem; margin-bottom: 0.25rem;">version {{ config('app.version') }}</p>
                    <p class="text-white-80 mb-4" style="max-width: 600px; margin: 0 auto; line-height: 1.6;">
                        Browse, search and compare atomistic MD simulations of lipid membranes from the
                        <a href="https://github.com/NMRLipids/Databank">FAIRMD Lipids Databank</a>.
                    </p>
                </div>
            </div>

            {{-- Search card --}}
            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6">
                    <div class="hero-card">
                        {{-- Main search --}}
                        <form action="{{ route('search.results') }}" method="get" class="mb-3">
                            <div class="input-group input-group-lg">
                                <input id="BasicSearch" type="text" name="text" class="form-control"
                                    placeholder="Search by name, InChI, SMILES, DOI…"
                                    aria-label="Search field">
                                <button class="btn btn-accent" type="submit">Search</button>
                            </div>
                        </form>

                        {{-- Quick links --}}
                        <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                            <a href="{{ route('search.results') }}" class="btn btn-outline-light btn-sm">Browse All</a>
                            <a href="{{ route('new_advanced_search.form') }}" class="btn btn-outline-light btn-sm">Advanced Search</a>
                            <a href="{{ route('lipids.list') }}" class="btn btn-outline-light btn-sm">Lipids</a>
                            <a href="{{ route('experiments.list') }}" class="btn btn-outline-light btn-sm">Experiments</a>
                            <a href="{{ route('simulations.list') }}" class="btn btn-outline-light btn-sm">Simulations</a>
                        </div>

                        <hr style="border-color: rgba(255,255,255,0.2); margin: 0.75rem 0;">

                        {{-- Best simulation finder --}}
                        <form action="{{ route('simulations.list') }}" method="get" class="d-flex justify-content-center">
                            <input type="hidden" name="sort" value="best">
                            <input type="hidden" name="direction" value="desc">
                            <div class="input-group" style="max-width: 400px;">
                                <select name="lipid" class="form-select" aria-label="Select lipid">
                                    <option value="" selected disabled>Best simulation for lipid…</option>
                                    @foreach (\App\Lipido::orderBy('molecule')->get()->unique('molecule') as $lipid)
                                        <option value="{{ $lipid->molecule }}">{{ $lipid->molecule }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-warning" type="submit">★ Find Best</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Examples & stats --}}
            <div class="row justify-content-center text-center mt-3">
                <div class="col-lg-7">
                    <p class="text-white-75 mb-2" style="font-size: 0.85rem;">
                        Try:
                        <a href="#" id="expopc" class="hero-example">POPC</a>,
                        <a href="#" id="expopcpope" class="hero-example">POPC:POPE</a>, or
                        <a href="#" id="lipid_name_example" class="hero-example">full lipid names</a>.
                        Search by trajectory ID (<code style="color: #ffe0b2;">ID123</code>) or
                        DOI (<code style="color: #ffe0b2;">DOI:10.1021/…</code>).
                    </p>
                    <p class="hero-stats">
                        {{ StatisticsController::totals() }}
                    </p>
                </div>
            </div>
        </div>
        
    </header>
    <!--div class="container" style=" height: 10em; display: flex; background-color: #00000000;">
      &nbsp;  
    </div-->
   

    <!-- About-->
    <section class="page-section" id="about" style="padding: 4rem 0;">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-9">

                    <h2 class="text-center mb-4" style="color: #0e4a56; font-weight: 700;">About FAIRMD Lipids Databank</h2>
                    <hr class="divider mb-4">

                    <div class="about-card mb-4">
                        <h4>What is FAIRMD Lipids Databank?</h4>
                        <p>FAIRMD Lipids Databank is a community-driven catalogue containing
                            atomistic molecular dynamics (MD) simulations of biologically relevant
                            lipid membranes emerging from the <a href="http://nmrlipids.blogspot.com/">NMRlipids open collaboration</a>.
                            It improves the <a href="https://www.go-fair.org/fair-principles/">Findability, Accessibility, Interoperability, and Reuse (FAIR)</a>
                            of MD simulation data using an overlay databank structure described in the
                            <a href="https://www.nature.com/articles/s41467-024-45189-z">databank publication</a>.
                            See the <a href="https://nmrlipids.github.io/">online documentation</a> for full details.</p>
                    </div>

                    <div class="about-card mb-4">
                        <h4>Components</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="component-pill">
                                    <strong>Databank-UI</strong>
                                    <span>This website — browse, search and compare simulations.
                                        <a href="https://github.com/NMRLipids/BilayerUI_laravel">GitHub</a></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="component-pill">
                                    <strong>Databank-API</strong>
                                    <span>Programmatic access for data-driven applications.
                                        <a href="http://github.com/NMRlipids/Databank/">GitHub</a></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="component-pill">
                                    <strong>BilayerData</strong>
                                    <span>Metadata repository with trajectories on <a href="https://zenodo.org/">Zenodo</a>.
                                        <a href="https://github.com/NMRLipids/BilayerData">GitHub</a></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="about-card h-100">
                                <h4>Citing &amp; Licensing</h4>
                                <p>Please cite the <a href="https://www.nature.com/articles/s41467-024-45189-z">Databank publication</a>
                                    and relevant trajectory entries.</p>
                                <ul class="small mb-0">
                                    <li>Data: <a href="https://github.com/NMRLipids/BilayerData/blob/main/LICENSE">CC-BY-4.0</a></li>
                                    <li>API code: <a href="https://github.com/NMRLipids/Databank/blob/main/LICENSE.txt">GPLv3</a></li>
                                    <li>GUI code: <a href="https://github.com/NMRLipids/BilayerGUI_laravel/blob/main/LICENSE">MIT</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="about-card h-100">
                                <h4>Resources</h4>
                                <ul class="small mb-2">
                                    <li><a href="https://github.com/NMRLipids/databank-template/blob/main/scripts/">Jupyter Notebooks</a> and API examples</li>
                                    <li><a href="https://nmrlipids.github.io/dbcontribute.html">How to contribute data</a></li>
                                    <li><a href="https://nmrlipids.github.io/">Full documentation</a></li>
                                </ul>
                                <p class="small text-dark mb-0">All data and code are provided AS-IS with no warranty. Report issues via each component's GitHub tracker.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

</div>

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

            $('#expopc').click(function(e) {
                e.preventDefault();
                $('#BasicSearch').val('POPC').focus();
            });
            $('#expopcpope').click(function(e) {
                e.preventDefault();
                $('#BasicSearch').val('POPC:POPE').focus();
            });
            $('#lipid_name_example').click(function(e) {
                e.preventDefault();
                $('#BasicSearch').val('1-octadecanoyl-2-(9Z)-octadecenoyl-sn-glycero-3-phosphocholine').focus();
            });
        });
    </script>
    @include('layouts.foot')
