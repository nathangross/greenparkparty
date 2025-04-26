<x-layouts.app :party="$party">

    <section class="w-full px-4 py-12 lg:py-24">
        <div class="mx-auto w-full max-w-[400px] py-16">
            <x-logo />
        </div>
        <div class="mx-auto flex w-full max-w-2xl flex-col items-center justify-center px-4 text-center">
            <p class="text-balance text-4xl">
                It's a new year! Let's have another neighborhood party in our local park!
                {{-- Let’s connect and have a neighborhood party in our local park! --}}
            </p>
            {{-- <p class="mt-4">Please RSVP by June 15th, 2024.</p> --}}
            <div class="group mt-12 flex flex-col items-center justify-center">
                <a href="#rsvp"
                    class="flex size-12 animate-bounce items-center justify-center rounded-full border border-green-dark/10 bg-white shadow-sm">
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
                            <span class="text-4xl font-bold">{{ $party->primary_date_start->format('F j, Y') }}</span>
                            <span class="text-2xl">{{ $party->primary_date_start->format('g:i A') }} to
                                {{ $party->primary_date_end->format('g:i A') }}</span>
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
                    <div
                        class="inline-flex rounded-full border border-orange-600 bg-orange-500 px-2 py-1 text-xs font-bold uppercase text-orange-900">
                        Updated: June 25th
                    </div>
                    <div class="mt-1 text-xl font-bold text-green-dark/70">
                        <span>-</span>
                        <span class="text-xl font-bold">Featuring</span>
                        <span>-</span>
                    </div>

                    <h2 class="mt-2 text-3xl font-black">
                        <span class="whitespace-nowrap">Claybourne Grille</span>
                    </h2>
                    <p>Serving from 5:30pm to 8pm</p>
                    <p class="mt-2 text-sm">Note: The Food Pitt™ was no longer able to do our party, but Claybourne
                        Grill jumped
                        in to save
                        the day. Thank you Robin!</p>
                    <p class="mt-2 text-sm">They have a larger menu. Click below to see their menu and prices</p>
                    <div class="mt-4">
                        <a href="{{ route('menu') }}"
                            class="inline-flex items-center justify-center rounded-sm bg-green px-4 py-2 text-lg font-bold italic text-green-dark shadow-xs transition-all duration-300 hover:scale-[102%] hover:shadow-lg">
                            Menu & Prices
                        </a>
                    </div>
                    {{-- <span>Bring cash or card for payment</span> --}}

                </div>
                <div class="flex flex-col gap-4 py-8">

                    <div class="text-balance">
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

</x-layouts.app>
