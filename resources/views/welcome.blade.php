<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Green Park Party - June 29, 2024</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    {{-- <link rel="stylesheet" href="https://use.typekit.net/vwo4amw.css"> --}}
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-green-dark antialiased">
    <div class="my-16 flex min-h-screen flex-col items-center justify-center">
        <div class="mx-auto w-full max-w-[400px]">
            <x-logo />
        </div>
        <div class="mt-8 w-full bg-green-light/20 lg:mt-16">
            <livewire:rsvp-form />
        </div>
    </div>
</body>

</html>
