<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.head')
@php
$allSession = Session::all();
$numSelected = 0;
foreach ($allSession as $key => $value) {
    if (str_contains($key, 'CompareID') && $value == 1) {
        $numSelected = $numSelected + 1;
    }
}
@endphp

<body>
    <div id="app" class="bg-datos" style="height:auto;overflow-x:hidden; ">
        <nav id="mainNav" class="navbar navbar-expand-md navbar-light ">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img class="img-fluid" style="max-height: 40px;" alt="FAIRMD Lipids Databank"
                        src="{{ asset('storage/images/fairmd_w_letras.png') }}">
                   
                    <div class="d-none">
                        <span>Versión: {{ config('app.version') }}</span>
                        <span>Entorno: {{ config('app.env') }}</span>
                    </div>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item"><a class="nav-link" href="{{ route('search.results') }}">Search</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('new_advanced_search.form') }}">Advanced Search</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('experiments.list') }}">Browse Experiments</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('search.results') }}">Browse Simulations</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <main style="padding-bottom: 140px;">
            @yield('content')
        </main>


    </div>

    <!-- Tooltips are initialized by Vite-bundled Bootstrap 5 JS -->


    @yield('js')

    @include('layouts.foot')
