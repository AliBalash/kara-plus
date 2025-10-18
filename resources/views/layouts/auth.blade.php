<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" data-theme="theme-default" data-assets-path="{{ asset('/assets/') }}" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <title>Login Basic - Sneat</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/panel/assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('assets/panel/assets/vendor/fonts/boxicons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/panel/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/panel/assets/vendor/css/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/panel/assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/panel/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

    <!-- Page CSS -->
    <link rel="stylesheet" href="{{ asset('assets/panel/assets/vendor/css/pages/page-auth.css') }}" />


    <link href="http://127.0.0.1:8000/livewire/livewire.css" rel="stylesheet">

    <!-- Helpers -->
    <script src="{{ asset('assets/panel/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/panel/assets/js/config.js') }}"></script>

    <!-- Livewire Styles -->
    @stack('styles')
</head>

<body>
    <!-- Content -->
    <div class="container-xxl">
        {{ $slot }}
    </div>

    <x-panel.toast-manager :validation-errors="$errors" />

    <!-- Core JS -->
    <script src="{{ asset('assets/panel/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/panel/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/panel/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/panel/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/panel/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/panel/assets/js/main.js') }}"></script>

    <!-- Livewire Scripts -->
    @stack('scripts')
</body>
</html>
