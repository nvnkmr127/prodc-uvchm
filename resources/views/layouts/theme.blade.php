<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(Auth::check() && method_exists(Auth::user(), 'tokens') && Auth::user()->tokens()->count() > 0)
        <meta name="api-token" content="{{ Auth::user()->tokens()->first()?->name ?? '' }}">
    @endif
    <title>@yield('title', 'Dashboard') - {{ setting('app_name', 'College Management System') }}</title>

    <!-- Font Awesome -->
    <link href="{{ asset('admin_theme/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    
    <!-- Google Fonts (Deferred & Preconnected) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Telugu:wght@300;400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Telugu:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    
    <!-- Bootstrap & Theme -->
    <link href="{{ asset('admin_theme/css/sb-admin-2.min.css') }}" rel="stylesheet">
    
    <!-- External Libraries (Deferred & Preconnected) -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"></noscript>
    
    <link href="{{ asset('admin_theme/vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/gridstack@10.1.2/dist/gridstack.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/gridstack@10.1.2/dist/gridstack.min.css" rel="stylesheet"></noscript>
    
    <link href="{{ asset('css/modern-theme.css') }}?v={{ time() }}" rel="stylesheet">
    <link href="{{ asset('css/mobile-overrides.css') }}?v={{ time() }}" rel="stylesheet">

    <!-- Essential Scripts (Loaded early for inline script support) -->
    <script src="{{ asset('admin_theme/vendor/jquery/jquery.js') }}"></script>
    <script src="{{ asset('admin_theme/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('admin_theme/vendor/jquery-easing/jquery.easing.min.js') }}"></script>
    <script src="{{ asset('admin_theme/js/sb-admin-2.min.js') }}"></script>



    @stack('styles')
</head>

<body id="page-top">


    <!-- Toast Notification Container -->
    <div id="notificationToastContainer" aria-live="polite" aria-atomic="true"></div>

    <div id="wrapper">
        <!-- Sidebar -->
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                @include('layouts.partials.topbar')

                <!-- Main Content Area -->
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>

            <!-- Modern Footer -->
            @include('layouts.partials.footer')
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    @include('layouts.partials.scripts')

    @stack('scripts')
</body>

</html>