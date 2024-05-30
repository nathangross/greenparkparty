<div class="flex w-full flex-col @container">

    @if ($showForm)
        <form wire:submit.prevent="save">
            @csrf
            @method('post')
            <div class="flex w-full flex-col gap-8 @container">
                <x-forms.fieldset legend="Your Information">
                    <div class="grid gap-4 @xl:grid-cols-2">
                        <div class="flex w-full flex-col gap-1">
                            <x-input.label for="first_name">First Name </x-input.label>
                            <x-input type="text" name="first_name" wire:model="first_name" required />
                        </div>
                        <div class="flex w-full flex-col gap-1">
                            <x-input.label for="last_name">Last Name <span class="text-sm text-gray-700">(optional)</span></x-input.label>
                            <x-input type="text" name="last_name" wire:model="last_name" />
                        </div>
                    </div>
                </x-forms.fieldset>
                <x-forms.fieldset legend="Will you be attending?">
                    <div x-data="{ showAttending: @entangle('showAttending') }">
                        <div class="flex flex-col gap-8">
                            <div class="mt-4 grid gap-8 lg:grid-cols-2">
                                <label for="attending_yes" class="flex items-center gap-2 rounded-full bg-green-dark/10 px-4 py-2">
                                    <input type="radio" id="attending_yes" name="showAttending" value="1" x-model="showAttending" wire:model="showAttending">
                                    <span class="text-lg font-bold">Yes!</span>
                                </label>
                                <label for="attending_no" class="flex items-center gap-2 rounded-full bg-green-dark/10 px-4 py-2">
                                    <input type="radio" id="attending_no" name="showAttending" value="0" x-model="showAttending" wire:model="showAttending">
                                    <span class="text-lg font-bold">No</span>
                                </label>
                            </div>
                            <div x-show="showAttending == true" class="">
                                <!-- This input shows only if attending is true -->

                                <div class="grid gap-8">
                                    <div class="flex flex-col gap-1">
                                        <x-input.label for="attending_count">Including yourself, how many will be attending?</x-input.label>
                                        <x-input type="number" id="attending_count" name="attending_count" wire:model="attending_count" />
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" id="volunteer" name="volunteer" wire:model="volunteer">
                                            <x-input.label for="volunteer">Check if you are interested in volunteering</x-input.label>
                                        </div>
                                        <span class="mt-1 max-w-[65ch] text-gray-700">Since this is our first year, we're not sure yet how many volunteers we'll need. We'll probably need a few volunteers to help us get things set up and cleaned up when it's over.</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </x-forms.fieldset>

                <x-forms.fieldset legend="Optional Information" description="Share as much or as little as you'd like. We'll never sell your information.">

                    <div class="grid gap-4">
                        <div class="mt-2 grid gap-4 @xl:grid-cols-3">
                            <div class="flex w-full flex-col gap-1">
                                <x-input.label for="email">Email</x-input.label>
                                <x-input type="email" name="email" wire:model="email" />
                            </div>
                            <div class="flex w-full flex-col gap-1">
                                <x-input.label for="phone">Cell Phone</x-input.label>
                                <x-input type="tel" name="phone" wire:model="phone" />
                            </div>
                            <div class="flex w-full flex-col gap-1">
                                <x-input.label for="street">Street</x-input.label>
                                <x-input type="text" name="street" wire:model="street" />
                            </div>
                        </div>

                        <div class="">
                            <x-input.label for="message">Leave a note </x-input.label>
                            <textarea name="message" id="message" class="mt-1 h-32 w-full rounded-lg border border-green-dark" wire:model="message"></textarea>
                        </div>
                    </div>
                </x-forms.fieldset>

                <button class="rounded-md bg-black px-4 py-2 text-white" type="submit">Submit</button>

            </div>
        </form>
    @endif

    @if (session('error'))
        <div class="bg-red-500/10 p-4 text-center font-bold text-red-800">
            {{ session('error') }}
        </div>
    @endif
    @if (session('message'))
        <div class="bg-green/10 p-4 text-center font-bold text-green-dark">
            {{ session('message') }}
        </div>
    @endif
</div>
