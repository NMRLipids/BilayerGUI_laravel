<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script>
    // Apply high-contrast class early to prevent flash of unstyled content
    (function(){var s=localStorage.getItem('high-contrast');if(s==='on')document.documentElement.classList.add('high-contrast');else if(s==='off')document.documentElement.classList.add('no-high-contrast');})();
    </script>
    
    <meta name="description" content="FAIRMD Lipids Databank" />
    <meta name="author" content="NMRLipids Consortium" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="doi-concurrent-fetches" content="{{ config('app.doi_concurrent_fetches', 10) }}" />
    <meta name="doi-max-retries" content="{{ config('app.doi_max_retries', 3) }}" />

    {{-- MCP (Model Context Protocol) discovery: advertise the read-only MCP endpoint --}}
    <meta name="mcp-server" content="{{ url('/mcp/fairmd-lipids') }}" />
    <link rel="mcp-server" type="application/json" href="{{ url('/mcp/fairmd-lipids') }}" />
    <link rel="alternate" type="text/plain" title="llms.txt" href="{{ url('/llms.txt') }}" />

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="FAIRMD Lipids Databank">
    <meta property="og:title" content="FAIRMD Lipids Databank">
    <meta property="og:description" content="FAIRMD Lipids Databank">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:image" content="{{ asset('images/fairmd_w_letras.png') }}">

        <!-- Include bioschemas for the data catalog only in production-->
     {{-- app.env is set in config/app.php and can be overridden in .env --}}
    @if(config('app.env') === 'production')
        @include('bioschemas.dataCatalog')
    @endif
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
    
    <!-- Jquery UI plugin CSS-->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

    <link rel="stylesheet" href="{{ asset('css/welcome-mobile.css') }}">
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="{{ asset('css/styles.css') }}?v={{ config('app.version', '1') }}" rel="stylesheet" />
    <!--  SLIDER -->
    <link href="{{ asset('css/multislider.css') }}" rel="stylesheet" />

    @yield('meta-tags')
    
     <!-- Styles -->
     <link href="{{ asset('css/app.css') }}" rel="stylesheet" />
     <link href="{{ asset('css/custom.css') }}" rel="stylesheet"/>

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

    <!-- JSMOL has been removed -->


   <!--  AUTOCOMPLETE -->
     <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>

  <!-- Bootstrap 5 JS loaded via Vite -->
    @vite(['resources/js/app.js'])
   
     

</head>
