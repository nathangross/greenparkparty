<x-layouts.app :party="$party">

    <section class="w-full px-4 py-12 lg:py-24">

        <div class="mx-auto flex w-full max-w-2xl items-center justify-center py-16">
            <x-logo.reverse class="h-auto w-full max-w-[424px] lg:max-w-[500px]" />
        </div>
        <div class="mx-auto flex w-full max-w-2xl flex-col items-center justify-center px-4 text-center">
            @php
                $partyService = app(\App\Services\PartyService::class);
                $isAcceptingRsvps = $partyService->isAcceptingRsvps();
            @endphp

            @if (!$isAcceptingRsvps && $party)
                <p class="text-balance text-4xl">
                    Thank you to everyone who joined us at this year's Green Park Party!
                </p>
                <p class="mt-4 text-2xl">
                    We hope you had a wonderful time. See you in {{ $party->primary_date_start->copy()->addYear()->format('Y') }}!
                </p>
            @else
                <p class="text-balance text-4xl">
                    @if ($party)
                        It's a new year! Let's have another neighborhood party in our local park!
                    @else
                        Welcome to Green Park Party!
                    @endif
                </p>

                @if ($party && $party->getRsvpDeadline())
                    <p class="mt-4">Please RSVP by {{ $party->getRsvpDeadline()->format('F j, Y') }}.</p>
                @else
                    <p class="mt-4">RSVP deadline to be announced.</p>
                @endif

                <div class="group mt-12 flex flex-col items-center justify-center">
                    <a href="#rsvp"
                        class="border-green-dark/10 flex size-12 animate-bounce items-center justify-center rounded-full border bg-white shadow-sm">
                        <x-icons.arrow-down class="fill-green size-8" />
                    </a>
                    <div class="opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                        <span class="text-green-dark/50 text-sm">Go RSVP!</span>
                    </div>
                </div>
            @endif
        </div>
    </section>
    <section id="details" class="w-full bg-white/50 py-12 lg:py-24">
        <x-layouts.inner>
            <x-headings.hero class="text-center">Party Details</x-headings.hero>

            <div class="divide-green-dark/30 flex flex-col divide-y text-center">
                <div class="mx-auto flex max-w-lg flex-col gap-4 py-8">
                    <div class="flex flex-col">
                        @if ($party && $party->primary_date_start && $party->primary_date_end)
                            <span class="text-4xl font-bold">{{ $party->primary_date_start->format('F j, Y') }}</span>
                            <span class="text-2xl">{{ $party->primary_date_start->format('g:i A') }} to
                                {{ $party->primary_date_end->format('g:i A') }}</span>
                        @else
                            <span class="text-4xl font-bold">Date and time to be announced</span>
                        @endif
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl font-bold">Green Park Shelter</span>
                        <address>6661 Green Park Drive, Dayton OH 45459</address>
                    </div>
                </div>
                @if ($isAcceptingRsvps)
                <div class="mx-auto max-w-4xl">
                    {{-- Food truck details go here when confirmed for the new party --}}
                </div>
                @endif
                @if ((isset($publicRsvps) && $publicRsvps->isNotEmpty()) || (isset($privateAttendingCount) && $privateAttendingCount > 0))
                    <div class="mx-auto flex max-w-3xl flex-col gap-6 py-8">
                        <div class="text-center">
                            <span class="text-xl font-bold">Who's RSVPed?</span>
                            <p class="mt-1 text-sm text-green-dark/70">Neighbors who opted to share are listed below.</p>
                        </div>
                        <div class="bg-green-dark mx-auto grid w-full max-w-xl gap-4 rounded-2xl px-6 py-5 text-center text-white shadow-sm sm:grid-cols-2">
                            <div>
                                <div class="text-5xl font-bold">{{ number_format($expectedAttendeeCount ?? 0) }}</div>
                                <div class="mt-1 text-sm uppercase tracking-wide text-white/70">Expected so far this year</div>
                            </div>
                            <div class="border-white/20 pt-4 sm:border-l sm:pt-0">
                                <div class="text-5xl font-bold">
                                    {{ isset($lastYearAttendeeCount) ? number_format($lastYearAttendeeCount) : '-' }}
                                </div>
                                <div class="mt-1 text-sm uppercase tracking-wide text-white/70">
                                    {{ isset($lastYearParty) && $lastYearParty?->primary_date_start
                                        ? $lastYearParty->primary_date_start->format('Y') . ' yes RSVPs'
                                        : 'Last year yes RSVPs' }}
                                </div>
                            </div>
                        </div>
                        <div
                            x-data="{
                                page: 1,
                                perPage: 12,
                                total: {{ isset($publicRsvps) ? $publicRsvps->count() : 0 }},
                                get pages() {
                                    return Math.max(1, Math.ceil(this.total / this.perPage))
                                },
                                get start() {
                                    return this.total === 0 ? 0 : ((this.page - 1) * this.perPage) + 1
                                },
                                get end() {
                                    return Math.min(this.page * this.perPage, this.total)
                                },
                            }"
                            class="grid gap-4"
                        >
                            <div class="mx-auto grid w-full max-w-3xl justify-center gap-3 text-left sm:grid-cols-2">
                            @if (isset($publicRsvps))
                                @foreach ($publicRsvps as $rsvp)
                                    @php
                                        $index = $loop->index;
                                        $firstName = $rsvp->user->first_name;
                                        $lastInitial = filled($rsvp->user->last_name)
                                            ? ' ' . mb_substr($rsvp->user->last_name, 0, 1) . '.'
                                            : '';
                                        $attendingLabel = $rsvp->attending_count > 0
                                            ? $rsvp->attending_count . ' attending'
                                            : 'Not attending';
                                    @endphp
                                    <div
                                        x-show="{{ $index }} >= (page - 1) * perPage && {{ $index }} < page * perPage"
                                        class="w-full min-w-0 max-w-sm rounded-lg bg-white/70 px-4 py-3 shadow-sm"
                                    >
                                        <div class="font-bold">{{ $firstName }}{{ $lastInitial }}</div>
                                        <div class="text-sm text-green-dark/70">{{ $attendingLabel }}</div>
                                        @if (filled($rsvp->public_message))
                                            <div class="mt-2 text-sm italic text-green-dark/80">
                                                "{{ $rsvp->public_message }}"
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                            @if (isset($privateAttendingCount) && $privateAttendingCount > 0)
                                <div class="border-green-dark/10 bg-green-dark/5 w-full min-w-0 max-w-sm rounded-lg border px-4 py-3">
                                    <div class="font-bold">
                                        And {{ $privateAttendingCount }} more!
                                    </div>
                                    <div class="text-sm text-green-dark/70">Additional neighbors are planning to come.</div>
                                </div>
                            @endif
                            </div>
                            @if (isset($publicRsvps) && $publicRsvps->count() > 12)
                                <div class="flex flex-col items-center justify-between gap-3 text-sm text-green-dark/70 sm:flex-row">
                                    <div>
                                        Showing <span x-text="start"></span>-<span x-text="end"></span>
                                        of {{ $publicRsvps->count() }} shared RSVPs
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            x-on:click="page = Math.max(1, page - 1)"
                                            x-bind:disabled="page === 1"
                                            class="border-green-dark/20 rounded-full border bg-white/80 px-4 py-2 font-bold text-green-dark shadow-sm transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            Previous
                                        </button>
                                        <span class="min-w-16 text-center">
                                            <span x-text="page"></span> / <span x-text="pages"></span>
                                        </span>
                                        <button
                                            type="button"
                                            x-on:click="page = Math.min(pages, page + 1)"
                                            x-bind:disabled="page === pages"
                                            class="border-green-dark/20 rounded-full border bg-white/80 px-4 py-2 font-bold text-green-dark shadow-sm transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
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
                {{-- <div class="mx-auto max-w-lg py-8">
                    <span class="text-xl font-bold">Party Organizers</span>
                    <div class="flex flex-col">
                        <span class="">Bob & Monica Fry</span>
                        <span class="">Mark & Joan Eilers</span>
                        <span class="">Nathan & Macey Gross</span>
                    </div>
                </div> --}}
            </div>
        </x-layouts.inner>
    </section>
    @php
        $partyService = app(\App\Services\PartyService::class);
        $isAcceptingRsvps = $partyService->isAcceptingRsvps();
    @endphp

    @if ($isAcceptingRsvps)
        <section id="rsvp" class="w-full bg-white/90 py-12 lg:py-24">
            <x-layouts.inner class="max-w-5xl">
                <livewire:rsvp-form />
            </x-layouts.inner>
        </section>
    @else
        <section id="rsvp" class="w-full bg-white/90 py-12 lg:py-24">
            <x-layouts.inner class="max-w-5xl">
                <div class="flex flex-col items-center justify-center text-center">
                    <x-headings.hero>Thank You!</x-headings.hero>
                    <p class="mt-8 text-xl max-w-2xl">
                        RSVPs for this year's party have closed. We're grateful to all who joined us and made it a memorable celebration!
                    </p>
                    <p class="mt-4 text-lg text-gray-700">
                        Mark your calendars for next summer. See you in {{ $party ? $party->primary_date_start->copy()->addYear()->format('Y') : date('Y') + 1 }}!
                    </p>
                </div>
            </x-layouts.inner>
        </section>
    @endif

</x-layouts.app>
