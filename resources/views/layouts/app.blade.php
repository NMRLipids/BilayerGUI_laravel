<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.head')
<?php
$allSession = Session::all();
$numSelected = 0;
foreach ($allSession as $key => $value) {
    if (str_contains($key, 'CompareID') && $value == 1) {
        $numSelected = $numSelected + 1;
    }
}
?>

<body>
    <div id="app" class="bg-datos" style="height:auto;overflow-x:hidden; ">
        <nav id="mainNav" class="navbar navbar-expand-md navbar-light ">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img class="img-fluid" style="width:225px" alt="Responsive image"
                        src="{{ asset('storage/images/nmr_w_letras.png') }}" alt="">
                    <?php //{{ config('app.name') }}
                    ?>
                    <div class="d-none">
                        <span>Versión: {{ config('app.version') }}</span>
                        <span>Entorno: {{ config('app.env') }}</span>
                    </div>
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse"
                    data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if ($numSelected > 0)
                                <!--<li class="nav-item">
                                <a class="nav-link" href="{{ route('new_advanced_search.compare') }}">{{ __('Compare') }}</a>
                            </li>-->
                            @endif
                            <li class="nav-item">
                                <a class="nav-link"
                                    href="{{ route('new_advanced_search.form') }}">{{ __('Advanced Search') }}</a>
                            </li>
                            <!--  <li class="nav-item" -->
                                <!--/li>-->
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                        onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main style="padding-bottom: 140px;">
            @yield('content')
        </main>


    </div>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}"></script>
    <script>
        $(function() {
            $('[title]').tooltip()
        })
    </script>


    @yield('js')

    @include('layouts.foot')
