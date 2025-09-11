<!doctype html>
<html class="welcome" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.head')

<body id="page-top">
    <!-- Navigation-->
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

    <!-- About-->
    <section class="page-section bg-primary" id="about">
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-10">
                    <h2 class="text-white text-center mt-0">NMRlipids Databank</h2>
                    <hr class="divider divider-light" />
                    <h3 class="text-white text-center mt-0">{{ $lipid['name'] }}</h3>

                    <?php 
                        $properties = $lipid['properties'] ?? []; 
                        $cross_refs = $lipid['cross_references'] ?? [];
                        unset($lipid['properties']);  
                        unset($lipid['cross_references']);  
                    ?>

                    <!-- Bootstrap Tabs -->
                    <ul class="nav nav-pills justify-content-start" id="lipidTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="properties-tab" data-bs-toggle="tab" data-bs-target="#properties" type="button" role="tab">Properties</button>
                        </li>
                        @if(!empty($cross_refs))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="crossrefs-tab" data-bs-toggle="tab" data-bs-target="#crossrefs" type="button" role="tab">Cross References</button>
                        </li>
                        @endif
                    </ul>

                    <!-- Tab Contents -->
                    <div class="tab-content bg-dark text-white p-4 rounded-bottom" id="lipidTabContent">
                        
                        <!-- Overview -->
                        <div class="tab-pane fade show active" id="overview" role="tabpanel">
                            <ul class="mb-0" style="font-size:1.1em;">
                                @foreach ($lipid as $key => $value)
                                    <li><strong>{{ ucfirst($key) }}:</strong> {{ $value }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Properties -->
                        <div class="tab-pane fade" id="properties" role="tabpanel">
                            @if(!empty($properties))
                                <ul style="font-size:1.1em;">
                                    @foreach ($properties as $x)
                                        <li><strong>{{ $x->name }}:</strong> {{ $x->value }}{{ $x->unit }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p>No properties available.</p>
                            @endif
                        </div>

                        <!-- Cross References -->
                        @if(!empty($cross_refs))
                        <div class="tab-pane fade" id="crossrefs" role="tabpanel">
                            <ul style="font-size:1.1em;">
                                @foreach ($cross_refs as $xref)
                                    <li>
                                        <strong>{{ $xref->database ?? 'Database' }}:</strong>
                                        @if(!empty($xref->url))
                                            <a href="{{ $xref->url }}" target="_blank" class="text-white-75">{{ $xref->external_id ?? '' }}</a>
                                        @else
                                            <!-- Link to identifiers.org if no URL is provided -->
                                            <a href="https://identifiers.org/{{ $xref->database }}/{{ $xref->external_id }}" target="_blank" class="text-white-75">{{ $xref->external_id ?? '' }}</a>

                                         
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer-->
    <footer class="bg-light py-5">
        <div class="container px-4 px-lg-5">
            <div class="small text-center text-muted">
                Copyright &copy;{{ date('Y') }} - NMRlipids
            </div>
        </div>
    </footer>
    <!-- Bootstrap core JS--><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Core theme JS-->
    <script src="{{ asset('js/scripts.js') }}"></script>

</body>
</html>
