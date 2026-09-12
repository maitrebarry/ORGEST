<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<link rel="icon" href="{{ asset('assets/images/icon-32.png') }}" type="image/png" />
<link rel="apple-touch-icon" href="{{ asset('assets/images/icon-180.png') }}" />

{{-- PWA : allure application mobile --}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}" />
<meta name="theme-color" content="#1d4e89" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="apple-mobile-web-app-title" content="ORGEST" />

<link href="{{ asset('assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/plugins/metismenu/css/metisMenu.min.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />

<link href="{{ asset('assets/css/pace.min.css') }}" rel="stylesheet" />
<script src="{{ asset('assets/js/pace.min.js') }}"></script>

<link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/css/app.css') }}?v={{ filemtime(public_path('assets/css/app.css')) }}" rel="stylesheet">
<link href="{{ asset('assets/css/icons.css') }}" rel="stylesheet">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Rye&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />

<link rel="stylesheet" href="{{ asset('assets/css/dark-theme.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/semi-dark.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/header-colors.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/orgest.css') }}?v={{ filemtime(public_path('assets/css/orgest.css')) }}" />

<title>@yield('title', 'Tableau de bord') - {{ config('app.name') }}</title>
