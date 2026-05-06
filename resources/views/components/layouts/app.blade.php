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
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="You're invited to the Green Park Party!" />
    <meta name="twitter:description" content="A neighborhood party in our local park!" />
    <meta name="twitter:image" content="{{ Vite::asset('resources/images/OG_image.png') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    {{--
    <link rel="stylesheet" href="https://use.typekit.net/vwo4amw.css"> --}}
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if (app()->environment('production'))
        <script defer data-domain="greenparkparty.com"
            src="https://plausible.io/js/script.file-downloads.hash.outbound-links.js"></script>
        <script>
            window.plausible = window.plausible || function() {
                (window.plausible.q = window.plausible.q || []).push(arguments)
            }
        </script>
    @endif

</head>

@php
    $shareTitle = $party ? $party->primary_date_start->format('F j, Y') . ' - Green Park Party' : 'Green Park Party';
    $shareText = $party
        ? 'Join us at the Green Park Party and RSVP online.'
        : 'Come join us at the Green Park Party!';
@endphp

<body id="top"
    x-data="{
        isScrolled: false,
        shareLabel: 'Share',
        shareTitle: @js($shareTitle),
        shareText: @js($shareText),
        shareUrl: @js(route('welcome')),
        shareResetTimer: null,
        setShareLabel(label) {
            this.shareLabel = label;

            if (this.shareResetTimer) {
                clearTimeout(this.shareResetTimer);
            }

            this.shareResetTimer = setTimeout(() => {
                this.shareLabel = 'Share';
            }, 2500);
        },
        async sharePage() {
            const shareData = {
                title: this.shareTitle,
                text: this.shareText,
                url: this.shareUrl,
            };

            try {
                if (navigator.share && (!navigator.canShare || navigator.canShare(shareData))) {
                    await navigator.share(shareData);
                    this.setShareLabel('Shared');
                    return;
                }
            } catch (error) {
                if (error?.name === 'AbortError') {
                    return;
                }
            }

            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(this.shareUrl);
                    this.setShareLabel('Link copied');
                    return;
                }
            } catch (error) {
                // Fall through to a manual copy prompt if clipboard access is unavailable.
            }

            window.prompt('Copy this link:', this.shareUrl);
            this.setShareLabel('Copy link');
        },
    }"
    class="bg-green text-green-dark font-sans antialiased">

    <nav :class="isScrolled ? 'shadow-lg grid-rows-[1fr] py-3' : ' grid-rows-[0fr]'"
        class="fixed top-0 z-20 grid w-full bg-white/80 px-4 backdrop-blur-sm transition-[grid-template-rows] transition-all duration-300 ease-out">
        <div class="mx-auto flex h-full min-h-0 max-w-lg items-center justify-center gap-4 overflow-hidden">
            <a href="{{ route('welcome') }}#top" x-transition :class="isScrolled ? 'flex' : 'hidden'" class="">
                <x-logo class="size-20 lg:size-24" />
            </a>
            <a href="{{ route('welcome') }}#details" class="group">
                <x-navigation.item>Details</x-navigation.item>
            </a>
            <a href="{{ route('welcome') }}#rsvp" class="group">
                <x-navigation.item>RSVP</x-navigation.item>
            </a>
            <button type="button" @click="sharePage()" class="group">
                <x-navigation.item>
                    <span x-text="shareLabel">Share</span>
                </x-navigation.item>
            </button>
        </div>
    </nav>
    <nav class="py-8">
        <div class="flex items-center justify-center gap-4">
            <a href="{{ route('welcome') }}#details" class="group">
                <x-navigation.item dark>Details</x-navigation.item>
            </a>
            <a href="{{ route('welcome') }}#rsvp" class="group">
                <x-navigation.item dark>RSVP</x-navigation.item>
            </a>
            <button type="button" @click="sharePage()" class="group">
                <x-navigation.item dark>
                    <span x-text="shareLabel">Share</span>
                </x-navigation.item>
            </button>
        </div>
    </nav>
    <div class="mt-16 flex min-h-screen w-full flex-col items-center justify-center">
        <div x-intersect:leave="isScrolled = true" x-intersect:enter="isScrolled = false"></div>
        {{ $slot }}
    </div>
</body>

</html>
