<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if ($party)
    <title>{{ $party->primary_date_start->format('F j, Y') }} - Green Park Party</title>
    @else
    <title>Green Park Party</title>
    @endif

    @if ($party)
    <meta property="og:title" content="{{ $party->primary_date_start->format('F j, Y') }} - Green Park Party">
    @else
    <meta property="og:title" content="Green Park Party">
    @endif
    <meta property="og:description" content="A neighborhood party in our local park!">
    <meta property="og:image" content="{{ Vite::asset('resources/images/OG_image.png') }}">
    <meta property="og:url" content="https://greenparkparty.com">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    {{--
    <link rel="stylesheet" href="https://use.typekit.net/vwo4amw.css"> --}}
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body id="top" x-data="{
    isScrolled: false
}" class="font-sans text-green-dark antialiased">

    <nav :class="isScrolled ? ' shadow-lg' : ''" class="fixed top-0 z-20 w-full bg-white px-4 py-3 transition-all">
        <div class="mx-auto flex h-full max-w-lg items-center justify-center gap-4">
            <a href="{{route('welcome')}}#top" x-transition :class="isScrolled ? 'flex' : 'hidden'" class="">
                <x-logo class="lg:size-24 size-20" />
            </a>
            <a href="{{route('welcome')}}#details"
                class="rounded-full px-4 py-2 text-lg font-bold italic text-green hover:bg-green/10">
                <div class="flex gap-1 items-center">
                    <span>Details</span>
                    <span class="bg-orange-500 border border-orange-600 rounded-full size-2 flex"></span>
                </div>
            </a>
            <a href="{{route('welcome')}}#rsvp"
                class="rounded-full px-4 py-2 text-lg font-bold italic text-green hover:bg-green/10">
                RSVP
            </a>
        </div>
    </nav>
    <div class="mt-16 flex min-h-screen w-full flex-col items-center justify-center">
        <div x-intersect:leave="isScrolled = true" x-intersect:enter="isScrolled = false"></div>
        {{ $slot }}
    </div>
</body>

</html>