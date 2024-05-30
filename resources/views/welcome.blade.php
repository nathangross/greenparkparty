<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- {{ $party = App\Models\Party::find(1) }} --}}
    <title>
        @if ($party)
            {{ $party->primary_date_start->format('F j, Y') }}
            -
        @endif
        Green Park Party
    </title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    {{-- <link rel="stylesheet" href="https://use.typekit.net/vwo4amw.css"> --}}
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body id="top" x-data="{
    isScrolled: false
}" class="font-sans text-green-dark antialiased">
    <nav :class="isScrolled ? ' shadow-lg' : ''" class="fixed top-0 z-20 w-full bg-white px-4 py-3 transition-all">
        <div class="mx-auto flex h-full max-w-lg items-center justify-center gap-4">
            <a href="#top" x-transition :class="isScrolled ? 'flex' : 'hidden'" class="">
                <x-logo class="lg:size-24 size-20" />
            </a>
            <a href="#details" class="rounded-full px-4 py-2 text-lg font-bold italic text-green hover:bg-green/10">
                Details
            </a>
            <a href="#rsvp" class="rounded-full px-4 py-2 text-lg font-bold italic text-green hover:bg-green/10">
                RSVP
            </a>
        </div>
    </nav>
    <div class="mt-16 flex min-h-screen w-full flex-col items-center justify-center">
        <div x-intersect:leave="isScrolled = true" x-intersect:enter="isScrolled = false"></div>
        <section class="px-4 py-12 lg:py-24">
            <div class="mx-auto w-full max-w-[400px] py-16">
                <x-logo />
            </div>
            <div class="flex w-full max-w-2xl flex-col items-center justify-center px-4 text-center">
                <p class="text-2xl">Let’s connect and have a neighborhood party in our local park!</p>
                <p>Please RSVP by June 15th, 2024.</p>
                <div class="group mt-12 flex flex-col items-center justify-center">
                    <a href="#rsvp" class="size-12 flex animate-bounce items-center justify-center rounded-full border border-green-dark/10 bg-white shadow">
                        <x-icons.arrow-down class="size-8 fill-green" />
                    </a>
                    <div class="opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                        <span class="text-sm text-green-dark/50">Go RSVP!</span>
                    </div>
                </div>
            </div>
        </section>
        <section id="details" class="w-full bg-green/25 py-12 lg:py-24">
            <x-layouts.inner>
                <x-headings.hero class="text-center">Party Details</x-headings.hero>

                <div class="mx-auto flex max-w-lg flex-col divide-y divide-green-dark/30 text-center">
                    <div class="flex flex-col gap-4 py-8">
                        <div class="flex flex-col">
                            @if ($party)
                                <span class="text-4xl font-bold">{{ $party->primary_date_start->format('F j') }}</span>
                                <span class="text-2xl">{{ $party->primary_date_start->format('h:i A') }} to {{ $party->primary_date_end->format('h:i A') }}</span>
                            @else
                                <span class="text-4xl font-bold">No party found.</span>
                            @endif
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl font-bold">Green Park Shelter</span>
                            <address>6661 Green Park Drive, Dayton OH 45459</address>
                        </div>
                    </div>
                    <div class="py-8 italic">
                        <div class="text-xl font-bold">
                            <span>-</span>
                            <span class="text-xl font-bold">Featuring</span>
                            <span>-</span>
                        </div>

                        <h2 class="text-3xl font-black">The Food Pitt™ Food Truck</h2>
                        <span>Serving from 6pm to 8pm</span>
                        <ul class="my-2 text-xl font-bold">
                            <li>burgers</li>
                            <li>mac-n-cheese</li>
                            <li>coleslaw</li>
                        </ul>
                        <span>bring cash or card for payment</span>

                    </div>
                    <div class="flex flex-col gap-4 py-8">

                        <div class="">
                            Feel free to bring your own snacks, beverages, chairs & lawn games.
                        </div>

                        <div class="">
                            <p class="font-bold">The Parks Department prohibits alcohol.</p>
                            <p class="">Please leave pets at home.</p>
                        </div>

                        <div class="font-bold italic">
                            <p class="text-4xl">Invite your neighbors!</p>
                            <p class="">We hope to see you there!</p>
                        </div>
                    </div>
                    <div class="py-8">
                        <span class="text-xl font-bold">Party Organizers</span>
                        <div class="flex flex-col">
                            <span class="">Monica Fry</span>
                            <span class="">Joan Eilers</span>
                            <span class="">Macey Riese</span>
                        </div>
                    </div>
                </div>
            </x-layouts.inner>
        </section>
        <section id="rsvp" class="w-full py-12 lg:py-24">
            <x-headings.hero class="text-center">RSVP</x-headings.hero>
            <x-layouts.inner class="max-w-5xl">
                <livewire:rsvp-form />
            </x-layouts.inner>
        </section>
</body>

</html>
