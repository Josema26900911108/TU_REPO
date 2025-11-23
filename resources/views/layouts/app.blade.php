<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Sistema de ventas de abarrotes" />
        <meta name="author" content="SakCode" />
        <title>PV-CodeErp - @yield('title')</title>

        @stack('css-datatable')
        <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
        <script src="{{ asset('js/all.js') }}"></script>
        @stack('css')
        <script src="{{ asset('js/jquery.min.js') }}"></script>
    </head>


<body class="sb-nav-fixed sb-sidenav-toggled">

    <x-navigation-header />

    <div id="layoutSidenav">

        <x-navigation-menu />

        <div id="layoutSidenav_content">

            <main>
                @yield('content')
            </main>

            <x-footer />

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    @stack('js')

</body>


</html>
