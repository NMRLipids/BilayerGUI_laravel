<!doctype html>
<html class="welcome" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.head')
@php
use App\Http\Controllers\StatisticsController;
@endphp


<body id="page-top">

    <!-- Navigation-->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
        <div class="container px-4 px-lg-5">
            <a class="navbar-brand" href="#page-top">FAIRMD Lipids Databank</a>
            <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarResponsive">
                <ul class="navbar-nav ms-auto my-2 my-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>                    
                </ul>
            </div>
        </div>
    </nav>

<div class="container-fluid p-0" style="display: flex; flex-direction: column; min-height: 100vh;">
    <!-- Masthead-->
    <header class="masthead">
        <div class="container" style="max-height: 100vh; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; margin-top: 1em; margin-bottom: 1em;">
            <div class="row gx-4 gx-lg-5  align-items-center justify-content-center text-center">
                <div class="col-lg-8 align-self-end">
                    <h2 class="text-white font-weight-bold">
                        <img class="img-fluid" alt="FAIRMD Lipids Databank Logo"
                            src="{{ asset('storage/images/nmr_w_letras.png') }}" alt="">
                          (version <?php echo config('app.version'); ?>)
                    </h2>
                </div>
                @if(config('app.debug'))
                <div class="col-lg-8 alert alert-warning" style="display: flex; flex-direction: column; padding: 1em;" role="alert">
                    <h5>⚠️ Development Preview</h5>

                    <div class="text-left">
                   
                    <br><small>Latest updates: </small>
                    <ul>
                        <li>Re-implemented the OP data plotting for simulations.</li>
                        <li>OP plot now supports multiple groups and experiments per lipid, with data properly organized by lipid and group.</li>
                        <li>Added ApL and FF data plots on the trajectory pages.</li>
                        <li>All plot data is now stored in the database tables directly.</li>
                        <li>Added proper handling of quality data</li>
                        <li>OP data is now visualized as box-and-whisker plots using standard deviation.</li>
                        <li>Added a checkbox to toggle normalization of FF data between 0 and 1.</li>
                        <li>Improved mobile responsive design.</li>
                    </ul>
                    Quick links to new functionality:
                    <ul>                        
                        <li><a href="/trajectories/5" style="color: green;">Simulation with multiple experimental data and quality annotation</a></li>
                        <li><a href="/trajectories/768" style="color: green;">Simulation with diverse lipid set (check Membrane and Analysis tab)</a></li>
                        <li><a href="/experiments?page=1" style="color: green;">Experiments list</a></li>
                        <li><a href="/experiment/FF/10.1016/j.bbamem.2012.05.007/1" style="color: green;">Form factor experiment with plot</a></li>
                        <li><a href="/experiment/OP/10.1021/acs.jpcb.4c04719/4" style="color: green;">Order parameter experiment with plot</a></li>

                    </ul>
                    </div>
                </div>
                @endif
                <div class="col-lg-8 align-self-baseline" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">

                    <p class="text-white-80 mb-5">The FAIRMD Lipids databank user interface can be used to search data in the <a
                            href="https://github.com/NMRLipids/Databank">FAIRMD Lipids
                            Databank</a>.
                        For programmatic access, see FAIRMD Lipids <a
                            href="https://github.com/NMRLipids/Databank">Databank-API</a>. For more details refer to the <a
                            href="https://www.nature.com/articles/s41467-024-45189-z"> FAIRMD Lipids databank publication</a>.
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
                        <script type="text/javascript">
                           $(function(){
                            $('#expopc').click(function(){
                                $('#BasicSearch').val('POPC');
                                $('#BasicSearch').focus();
                            });

                            $('#expopcpope').click(function(){
                                $('#BasicSearch').val('POPC:POPE');
                                $('#BasicSearch').focus();

                            });
                            });
                        </script> 



                        <p class="text-white-75 mb-3">Search based on lipid composition for example by: 
                            <span style="text-decoration: underline;" id="expopc">POPC</span> or
                           <span style="text-decoration: underline;" id="expopcpope">POPC:POPE</span>. Please refer to the
                             <a
                                href="https://nmrlipids.github.io/schemas/moleculesAndMapping.html#molecule-names">
                                list of universal molecule and ion names that can be used in searches</a>.
                            You can search for trajectories by their ID by typing 'ID' followed by their numeric ID, for example,
                            ID123. More options are available in Advanced search.
                        </p>
                        <p>Current content of the FAIRMD Lipids Databank:</p>
                        <span class="text-white-75 mb-1" style="font-size: 0.9em;">
                        {{ StatisticsController::totals() }}
                        </span>
                                                   
                    </div>

                   
                </div>
                
            </div>
        </div>
        
    </header>
    <!--div class="container" style=" height: 10em; display: flex; background-color: #00000000;">
      &nbsp;  
    </div-->
   

    <!-- About-->
    <section class="page-section bg-primary"  id="about" style="display: flex; align-items: center; justify-content: center;">
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="text-white mt-0">About</h2>
                    <hr class="divider divider-light" />
                    <h3 class="text-white mt-0">What is FAIRMD Lipids Databank?</h2>
                    <p class="text-white-75 mb-4 txt_desc text-left">FAIRMD Lipids Databank is a community-driven catalogue containing
                        atomistic molecular dynamics (MD) simulations of biologically relevant
                        lipid membranes emerging from the <a href="http://nmrlipids.blogspot.com/"> NMRlipids open
                            collaboration</a>.
                        It has been designed to improve the <a href="https://www.go-fair.org/fair-principles/">Findability, Accessibility, Interoperability, and Reuse
                        (FAIR)</a> of MD simulation data.
                        FAIRMD Lipids databank is implemented using an overlay databank structure and is described in detail in
                        the <a href="https://www.nature.com/articles/s41467-024-45189-z"> databank publication</a>. 
                        Please refer to the <a href="https://nmrlipids.github.io/">online documentation</a> of the system and its components</p>
                    <h3 class="text-white mt-0">Using FAIRMD Lipids </h2>
                        <p class="text-white-75 mb-4 txt_desc text-left">
                            FAIRMD Lipids consists of three main components:

                            <ul     class="text-white-75 mb-4 txt_desc text-left">
                                <li>FAIRMD Lipids Databank-GUI (this website)</li>
                                <li>FAIRMD Lipids Databank-API</li>
                                <li>The BilayerData repository</li>
                            </ul>
                        </p>

                    <p class="text-white-75 mb-4 txt_desc text-left">
                        The FAIRMD Lipids Databank-GUI </a> can be used to browse and search the content of the
                        Databank, to select the best available simulations for specific systems based on ranking lists,
                        and to perform comparisons between basic properties of membranes. It is implemented as a web
                        application using the <a href="https://laravel.com/">Laravel framework</a>. The source code is available on <a href="https://github.com/NMRLipids/BilayerGUI_laravel">GitHub</a>.
                        We have made efforts towards easy local deployment of the application to use, e.g., with private data.
                    </p>

                    <p class="text-white-75 mb-4 txt_desc text-left">
                        The <a href="http://github.com/NMRlipids/Databank/"> FAIRMD Lipids Databank-API</a> provides
                        programmatic access to all simulation data in the FAIRMD Lipids Databank. This enables a wide range of
                        novel data-driven applications —
                        from construction of machine learning models that predict membrane properties to automatic
                        analysis of virtually any property across all simulations in the Databank.
                         </p>
                    <p class="text-white-75 mb-4 txt_desc text-left">
                        <a
                            href="https://github.com/NMRLipids/databank-template/blob/main/scripts/"> Jupyter Notebooks</a>
                        and other examples for applications of FAIRMD Lipids Databank-API are included on <a
                            href="https://github.com/NMRlipids/Databank">GitHub</a> and in the <a
                            href="https://www.nature.com/articles/s41467-024-45189-z"> FAIRMD Lipids databank publication</a>.
                    </p>

                    <p class="text-white-75 mb-4 txt_desc text-left">
                        The <a href="https://github.com/NMRLipids/BilayerData">BilayerData repository</a> is the main data storage of the FAIRMD Lipids
                        Databank. It contains the actual meta-data on MD simulation data and metadata describing simulations and molecules. The actual
                        trajectory files are stored in <a href="https://zenodo.org/">Zenodo</a> and linked to the BilayerData repository.
                        The repository is open for contributions from the community. Instructions for contributing data
                        are available in the <a href="https://nmrlipids.github.io/dbcontribute.html">online
                        documentation</a>.
                    </p>
                    <h3 class="text-white mt-0">Citing and licensing</h2>
                    <p class="text-white-75 mb-4 txt_desc text-left">
                        If you use the FAIRMD Lipids databank in your publications, please cite the FAIRMD Lipids <a
                            href="https://www.nature.com/articles/s41467-024-45189-z">Databank
                            publication</a>,
                        as well as the trajectory entries and related publications whereever appropriate.
                        The data in the BilayerData repository are provided under a Creative Commons Attribution 4.0
                        International (CC-BY-4.0) license
                        (see <a href="https://github.com/NMRLipids/BilayerData/blob/main/LICENSE">LICENSE</a>).
                        The code for the FAIRMD Lipids Databank-API is provided under the GNU General Public   
                         license version 3 (GPLv3) (see <a
                            href="https://github.com/NMRLipids/Databank/blob/main/LICENSE.txt">LICENSE</a>). 
                        The user interface code is provided under an MIT license (see <a href="https://github.com/NMRLipids/BilayerGUI_laravel/blob/main/LICENSE">LICENSE</a>).  
                        
                        All data and code are provided AS-IS. 
                        There is no warranty of any kind that the data or software are correct
                        or suitable for any specific purpose.
                    </p>
                    <h3 class="text-white mt-0">Feedback and bug reports</h2>
                    <p class="text-white-75 mb-4 txt_desc text-left">
                        Please contact us via the GitHub issue tracker of each component for feedback or if you find
                        any errors or bugs.
                    </p>
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
        });
    </script>
    @include('layouts.foot')
