<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <!-- displays site properly based on user's device -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ asset('assets/reserve/assets/images/favicon-32x32.png') }}" />
    <!-- Bootstrap css file -->
    <link rel="stylesheet" href="{{ asset('assets/reserve/styles/bootstrap.min.css') }}" />
    <!-- main style file -->
    <link rel="stylesheet" href="{{ asset('assets/reserve/styles/main.css') }}" />
    <title>Frontend Mentor | Multi-step form</title>

    <!-- Feel free to remove these styles or customise in your own stylesheet 👍 -->
    <style>
        .attribution {
            font-size: 11px;
            text-align: center;
        }

        .attribution a {
            color: hsl(228, 45%, 44%);
        }
    </style>
    @stack('styles')

</head>

<body>
    <!-- main container start -->
    <div class="">
        {{ $slot }}

    </div>




    <div class="attribution">
        Challenge by
        <a href="https://www.frontendmentor.io?ref=challenge" target="_blank">Frontend Mentor</a>. Coded by <a
            href="#">Abdo</a>.
    </div>
    <!-- main js -->
    <script src="{{ asset('assets/reserve/js/main.js') }}" defer></script>
    <!-- Bootstrap js -->
    <script src="{{ asset('assets/reserve/js/bootstrap.min.js') }}"></script>

    @stack('scripts')

</body>

</html>
