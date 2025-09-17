<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no,maximum-scale=1.0, user-scalable=no" />
    <meta name="description" content="Nmrlipids" />
    <meta name="author" content="NMRlipids Databank" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NMRlipids Databank">
    <meta property="og:title" content="NMRlipids Databank">
    <meta property="og:description" content="NMRlipids Databank">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:image" content="{{ url('storage/images/nmr_w_letras.png') }}">
    <!-- Include bioschemas only if entity is provided -->
    @if(isset($entity) && !empty($entity))
     @include('bioschemas.molecular_entity', ['entity' => $entity])
     <meta property="og:title" content="{{ $entity['name'] ?? 'Molecular Entity' }}">
     <meta property="og:description" content="{{ $entity['properties_flat']['description'] ?? 'Details about the molecular entity.' }}">
    
    @endif

    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
    <!-- Bootstrap Icons-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css" rel="stylesheet" />
    <!-- Google fonts-->
    <link href="https://fonts.googleapis.com/css?family=Merriweather+Sans:400,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Merriweather:400,300,300italic,400italic,700,700italic" rel="stylesheet" type="text/css" />
    <!-- SimpleLightbox plugin CSS-->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/SimpleLightbox/2.1.0/simpleLightbox.min.css" rel="stylesheet" />
    <!-- Jquery UI plugin CSS-->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
    <!--  SLIDER -->
    <link href="{{ asset('css/multislider.css') }}" rel="stylesheet" />

    @yield('meta-tags')
    
     <!-- Styles -->
     <link href="{{ asset('css/app.css') }}" rel="stylesheet">
     <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

<!-- End Add template -->


    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <!-- Fonts
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    -->
    <!--<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">-->
    <!-- Load jQuery from a CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>


    <!-- nouislider -->
    <!-- link href="{{ asset('storage/js/nouislider/nouislider.min.css') }}" rel="stylesheet" />
    <script type="text/javascript" src="{{ asset('storage/js/nouislider/nouislider.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('storage/js/nouislider/wNumb.min.js') }}"></script -->

    <!-- JSMOL -->
    <!-- script type="text/javascript" src="{{ asset('storage/js/jsmol/JSmol.min.js') }}"></script> {{--  Tiene que estar antes el jsmol que jquery--}} -->


   <!-- script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk=" crossorigin="anonymous"></script -->

   <!--  AUTOCOMPLETE -->
     <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>

  <!--SLIDER -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"   crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
   
     <!-- Chart -->
     <!--<script type="text/javascript"  src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
     <script type="text/javascript"  src="https://unpkg.com/chart.js@3.8.2"></script>

     <script type="text/javascript"  src="https://unpkg.com/chartjs-chart-error-bars@3"></script>
<style>
    /* Dark pill tabs */
    .nav-pills .nav-link {
        color: #ddd;
        background-color: transparent;
        border-radius: 50rem;
        margin: 0 0.3rem;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .nav-pills .nav-link:hover {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.15);
    }

    .nav-pills .nav-link.active {
        color: #fff;
        background-color: #0d6efd; /* Bootstrap primary */
        font-weight: 600;
        box-shadow: 0 0 10px rgba(13,110,253,0.5);
    }

    /* Tab content styling */
    .tab-content {
        background-color: #212529; /* Bootstrap dark */
        border-radius: 0 0 0.5rem 0.5rem;
        margin-top: 1rem;
        padding: 1.5rem;
    }
</style>


</head>
