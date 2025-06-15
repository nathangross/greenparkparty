<x-layouts.app :party="$party">

    <section class="w-full px-4 py-12 lg:py-24">

        <div class="mx-auto flex w-full max-w-2xl items-center justify-center py-16">
            <x-logo.reverse />
        </div>
        <div class="mx-auto flex w-full max-w-2xl flex-col items-center justify-center px-4 text-center">
            <p class="text-balance text-4xl">
                It's a new year! Let's have another neighborhood party in our local park!
            </p>
            <p class="mt-4">Please RSVP by {{ $party->getRsvpDeadline()->format('F j, Y') }}.</p>
            <div class="group mt-12 flex flex-col items-center justify-center">
                <a href="#rsvp"
                    class="border-green-dark/10 flex size-12 animate-bounce items-center justify-center rounded-full border bg-white shadow-sm">
                    <x-icons.arrow-down class="fill-green size-8" />
                </a>
                <div class="opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    <span class="text-green-dark/50 text-sm">Go RSVP!</span>
                </div>
            </div>
        </div>
    </section>
    <section id="details" class="w-full bg-white/50 py-12 lg:py-24">
        <x-layouts.inner>
            <x-headings.hero class="text-center">Party Details</x-headings.hero>

            <div class="divide-green-dark/30 flex flex-col divide-y text-center">
                <div class="mx-auto flex max-w-lg flex-col gap-4 py-8">
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
                <div class="mx-auto max-w-4xl">
                    <div class="pt-8">
                        <img src="{{ Vite::asset('resources/images/more-than-a-apron.jpg') }}" alt="More Than a Apron"
                            class="h-auto w-full">
                    </div>
                    <div class="flex-col items-center gap-2 py-8 italic">
                        <h2 class="text-2xl font-bold">We have a new food truck this year!</h2>
                        <p>
                            <stong>Chef Lewis</stong> (More than a Apron) will be there with a
                            select menu including <strong>tacos</strong>, <strong>burgers</strong> and
                            <strong>wings</strong>! The reviews are great and we can't wait to
                            get a taste! Come hungry!
                        </p>
                    </div>

                </div>
                <div class="mx-auto flex max-w-lg flex-col gap-4 py-8">

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
                <div class="mx-auto max-w-lg py-8">
                    <span class="text-xl font-bold">Party Organizers</span>
                    <div class="flex flex-col">
                        <span class="">Bob & Monica Fry</span>
                        <span class="">Mark & Joan Eilers</span>
                        <span class="">Nathan & Macey Gross</span>
                    </div>
                </div>
            </div>
        </x-layouts.inner>
    </section>
    <section id="rsvp" class="w-full bg-white/90 py-12 lg:py-24">
        <x-layouts.inner class="max-w-5xl">
            <livewire:rsvp-form />
        </x-layouts.inner>
    </section>

</x-layouts.app>
