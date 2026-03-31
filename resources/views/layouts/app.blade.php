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
        <nav id="mainNav" class="navbar navbar-expand-lg navbar-light fixed-top py-3">
            <div class="container px-4 px-lg-5">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img class="img-fluid fairmd-logo" style="max-height: 40px;" alt="FAIRMD Lipids Databank"
                        src="{{ asset('storage/images/fairmd_w_letras.png') }}">
                   
                    <div class="d-none">
                        <span>Versión: {{ config('app.version') }}</span>
                        <span>Entorno: {{ config('app.env') }}</span>
                    </div>
                </a>
                <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse"
                    data-nav-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarResponsive">
                    <ul class="navbar-nav ms-auto my-2 my-lg-0">
                        <li class="nav-item">
                            <form action="{{ route('search.results') }}" method="get" class="d-flex align-items-center">
                                <input type="text" name="text" class="form-control form-control-sm" placeholder="Search..." aria-label="Search" style="width: 150px;">
                                <button class="btn btn-sm btn-outline-light ms-1" type="submit">Go</button>
                            </form>
                        </li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('new_advanced_search.form') }}"><span class="nav-icon" aria-hidden="true" style="font-size: 1.2rem;">⌕ </span>Advanced Search</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('lipids.list') }}"><span class="nav-icon" aria-hidden="true" style="font-size: 1.2rem;">◌</span>Lipids</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('experiments.list') }}"><span class="nav-icon" aria-hidden="true">⚗</span>Experiments</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('simulations.list') }}"><span class="nav-icon" aria-hidden="true">◈</span>Simulations</a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ url('/#about') }}"><span class="nav-icon" aria-hidden="true">ⓘ</span>About</a></li>
                            <li class="nav-item d-flex align-items-center ms-2">
                            <button class="contrast-toggle" id="contrastToggle" type="button" title="Toggle high-contrast mode" aria-label="Toggle high-contrast mode">&#9684;</button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <main style="padding-top: 96px; padding-bottom: 140px;">
            @yield('content')
        </main>


    </div>

    <!-- Tooltips are initialized by Vite-bundled Bootstrap 5 JS -->


    @yield('js')

    @include('layouts.foot')
