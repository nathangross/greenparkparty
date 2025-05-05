<?php

use function Livewire\Volt\{state, rules, computed, usesProperties};
use App\Models\Rsvp;
use App\Models\User;
use App\Services\PartyService;
use Illuminate\Support\Facades\DB;
use Spatie\Newsletter\Facades\Newsletter;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Notifications\RsvpConfirmation;
use Illuminate\Support\Facades\App;
use App\Notifications\AdminRsvpNotification;

// Inject the PartyService
$partyService = app(PartyService::class);

state([
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'street' => '',
    'showAttending' => true,
    'attending_count' => 1,
    'volunteer' => false,
    'message_text' => '',
    'showForm' => true,
    'receive_email_updates' => false,
    'receive_sms_updates' => false,
]);

$activeParty = computed(fn() => $partyService->getActiveParty());
$partyYear = computed(fn() => $partyService->getCurrentPartyYear());
$isAcceptingRsvps = computed(fn() => $partyService->isAcceptingRsvps());

rules([
    'first_name' => 'required|min:3',
    'last_name' => 'nullable|min:3',
    'email' => 'nullable|required_if:receive_email_updates,true|email',
    'phone' => 'nullable|required_if:receive_sms_updates,true|min:10',
    'street' => 'nullable',
    'attending_count' => 'required_if:showAttending,true|numeric|min:0',
    'volunteer' => 'nullable|boolean',
    'message_text' => 'nullable',
    'receive_email_updates' => 'nullable|boolean',
    'receive_sms_updates' => 'nullable|boolean',
]);

$save = function () {
    if (!$this->isAcceptingRsvps) {
        $this->dispatch('flash-error', 'RSVPs are not currently open.');
        return;
    }

    $this->validate();

    DB::transaction(function () {
        // Set attending_count to 0 if not attending
        if (!$this->showAttending) {
            $this->attending_count = 0;
        }

        // Find or create the user
        $user = User::updateOrCreate(
            ['email' => $this->email ?: null],
            [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone' => $this->phone,
                'street' => $this->street,
                'email' => $this->email ?: null,
            ],
        );

        // Find existing RSVP if any
        $existingRsvp = Rsvp::where('user_id', $user->id)->where('party_id', $this->activeParty->id)->first();

        // Combine messages if both exist
        $message = $this->message_text;
        if ($message && $existingRsvp?->message_text) {
            $message = $existingRsvp->message_text . "\n\nMessage update: " . $message . ' ';
        } elseif ($message) {
            $message = 'New message: ' . $message . ' ';
        } elseif (!$message) {
            $message = $existingRsvp?->message_text;
        }

        // Update or create the RSVP
        $rsvp = Rsvp::updateOrCreate(
            [
                'user_id' => $user->id,
                'party_id' => $this->activeParty->id,
            ],
            [
                'attending_count' => $this->attending_count,
                'volunteer' => $this->volunteer,
                'message_text' => $message,
                'receive_email_updates' => $this->receive_email_updates,
                'receive_sms_updates' => $this->receive_sms_updates,
            ],
        );

        // Send RSVP confirmation email if email is provided
        if ($this->email) {
            try {
                dispatch(function () use ($user, $rsvp) {
                    $user->notify(new RsvpConfirmation($rsvp));
                })->afterResponse();
            } catch (\Exception $e) {
                // Handle email sending error
            }
        }

        // Send admin notification
        try {
            $dispatchCallback = function () use ($rsvp) {
                // Get the admin user (you can modify this to get your specific user ID)
                $admin = User::where('email', config('app.admin_email'))->first();
                if ($admin) {
                    $admin->notify(new AdminRsvpNotification($rsvp));
                }
            };

            if (App::environment('testing')) {
                $dispatchCallback();
            } else {
                dispatch($dispatchCallback)->afterResponse();
            }
        } catch (\Exception $e) {
            // Handle admin notification error
        }

        // Update Mailchimp contact if email is provided and they've opted in for updates
        // Skip Mailchimp integration in local environment
        if (!App::environment('local') && $this->email && ($this->receive_email_updates || $this->receive_sms_updates)) {
            try {
                // Get the Mailchimp API instance
                $mailchimp = Newsletter::getApi();
                $listId = config('newsletter.lists.subscribers.id');

                // Try to get the subscriber first
                $subscriberHash = md5(strtolower($this->email));
                try {
                    $existingSubscriber = $mailchimp->get("lists/{$listId}/members/{$subscriberHash}");
                } catch (\Exception $e) {
                    // No existing subscriber found
                }

                // Subscribe or update the contact
                $result = Newsletter::subscribeOrUpdate($this->email, [
                    'FNAME' => $this->first_name,
                    'LNAME' => $this->last_name,
                    'PHONE' => $this->phone,
                ]);

                if (!$result) {
                    // Get the last error from Mailchimp
                    $lastError = $mailchimp->getLastError();
                    $lastResponse = $mailchimp->getLastResponse();

                    // Check if this is a permanently deleted contact
                    if (str_contains($lastError, 'permanently deleted and cannot be re-imported')) {
                        $this->dispatch('flash-error', 'Your email was previously unsubscribed. Please contact us to re-subscribe.');
                        return;
                    }

                    throw new \Exception('Failed to subscribe to Mailchimp: ' . json_encode($lastError));
                }

                // Add tags based on preferences
                $tags = [
                    [
                        'name' => $this->partyYear . ' - Attending',
                        'status' => $this->attending_count > 0 ? 'active' : 'inactive',
                    ],
                    [
                        'name' => $this->partyYear . ' - Volunteer',
                        'status' => $this->volunteer ? 'active' : 'inactive',
                    ],
                    [
                        'name' => $this->partyYear . ' - SMS Updates',
                        'status' => $this->receive_sms_updates ? 'active' : 'inactive',
                    ],
                ];

                $tagResult = $mailchimp->post("lists/{$listId}/members/{$subscriberHash}/tags", [
                    'tags' => $tags,
                ]);
            } catch (\Exception $e) {
                // Handle Mailchimp update error
            }
        }
    });

    if ($this->attending_count > 0) {
        $message = "Thanks {$this->first_name}, we have you down for {$this->attending_count}. We'll see you there!";
    } else {
        $message = "Thanks for letting us know, {$this->first_name}. We hope to see you next year!";
    }
    session()->flash('message', $message);
    $this->dispatch('flash-message');
    $this->showForm = false;
};

$generateUniqueIdentifier = function () {
    return 'no_email_user_' . md5($this->first_name . $this->last_name . $this->phone . now()->timestamp);
};

?>

<div class="@container flex w-full flex-col">
    @if ($showForm)
        <form wire:submit="save">
            @csrf
            @method('post')

            <div class="@container flex w-full flex-col gap-8">
                <x-headings.hero class="text-center">
                    @if ($this->partyYear)
                        {{ $this->partyYear }} RSVP
                    @else
                        RSVP
                    @endif
                </x-headings.hero>
                @if ($errors->any())
                    <div class="mb-4 text-red-600">
                        See below for errors.
                    </div>
                @endif

                @if (!$this->isAcceptingRsvps)
                    <div class="text-center text-xl">
                        RSVPs are not currently open.
                    </div>
                @else
                    <x-forms.fieldset legend="Your Information">
                        <div class="@xl:grid-cols-2 grid gap-4">
                            <div class="flex w-full flex-col gap-1">
                                <x-input.label for="first_name">First Name </x-input.label>
                                <x-input type="text" name="first_name" wire:model="first_name" required />
                                @error('first_name')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="flex w-full flex-col gap-1">
                                <x-input.label for="last_name">Last Name <span
                                        class="text-sm text-gray-700">(optional)</span></x-input.label>
                                <x-input type="text" name="last_name" wire:model="last_name" />
                                @error('last_name')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </x-forms.fieldset>
                @endif
                <x-forms.fieldset
                    legend="Will you be attending this year?">
                    <div x-data="{ showAttending: @entangle('showAttending') }">
                        <div class="flex flex-col gap-8">
                            <div class="mt-4 grid gap-8 lg:grid-cols-2">
                                <label for="attending_yes"
                                    class="bg-green-dark/10 flex items-center gap-2 rounded-full px-4 py-2">
                                    <input type="radio" id="attending_yes" name="showAttending" value="1"
                                        x-model="showAttending" wire:model="showAttending">
                                    <span class="text-lg font-bold">Yes!</span>
                                </label>
                                <label for="attending_no"
                                    class="bg-green-dark/10 flex items-center gap-2 rounded-full px-4 py-2">
                                    <input type="radio" id="attending_no" name="showAttending" value="0"
                                        x-model="showAttending" wire:model="showAttending">
                                    <span class="text-lg font-bold">No</span>
                                </label>
                            </div>
                            <div x-show="showAttending == true" class="">
                                <div class="grid gap-8">
                                    <div class="flex flex-col gap-1">
                                        <x-input.label for="attending_count">
                                            Including yourself, how many will be attending?
                                        </x-input.label>
                                        <x-input type="number" id="attending_count" name="attending_count"
                                            wire:model="attending_count" />
                                        @error('attending_count')
                                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex items-baseline gap-2">
                                            <input type="checkbox" id="volunteer" name="volunteer"
                                                wire:model="volunteer">
                                            <x-input.label for="volunteer">
                                                Check if you are interested in volunteering
                                            </x-input.label>
                                        </div>
                                        <p class="ml-6 mt-1 text-balance text-sm italic text-gray-700">Our volunteer
                                            needs will
                                            be
                                            based on
                                            how many people RSVP as attending. We'll be in touch as we get closer to the
                                            party.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-forms.fieldset>

                <x-forms.fieldset legend="Optional Information" description="Share as much or as little as you'd like.">
                    <div class="grid gap-4">
                        <div class="@xl:grid-cols-3 mt-2 grid gap-4">
                            <div class="flex w-full flex-col gap-1">
                                <x-input.label for="email">Email</x-input.label>
                                <x-input type="email" name="email" wire:model="email" />
                                @error('email')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="flex w-full flex-col gap-1">
                                <x-input.label for="phone">Cell Phone</x-input.label>
                                <x-input type="tel" name="phone" wire:model="phone" />
                                @error('phone')
                                    <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="flex w-full flex-col gap-1">
                                <x-input.label for="street">Street</x-input.label>
                                <x-input type="text" name="street" wire:model="street" />
                            </div>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            <input type="checkbox" id="receive_email_updates" name="receive_email_updates"
                                wire:model="receive_email_updates">
                            <x-input.label for="receive_email_updates">
                                I'm ok to receive email updates about the party
                            </x-input.label>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <input type="checkbox" id="receive_sms_updates" name="receive_sms_updates"
                                wire:model="receive_sms_updates">
                            <x-input.label for="receive_sms_updates">
                                I'm ok to receive SMS updates about the party
                            </x-input.label>
                        </div>

                        <div class="">
                            <x-input.label for="message_text">Leave us a note </x-input.label>
                            <textarea name="message_text" id="message_text" class="border-green-dark mt-1 h-32 w-full rounded-lg border"
                                wire:model="message_text"></textarea>
                            @error('message_text')
                                <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </x-forms.fieldset>

                <button
                    class="flex items-center justify-center rounded-md bg-black px-4 py-2 text-white hover:cursor-pointer hover:bg-black/80 disabled:opacity-50"
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save">

                    <span wire:loading.remove wire:target="save" class="ml-2">
                        Submit
                    </span>

                    <span wire:loading wire:target="save" class="ml-2">
                        <svg class="h-5 w-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                            </path>
                        </svg>
                    </span>
                </button>
            </div>
        </form>
    @endif

    @if (session('error'))
        <x-card id="message-container"
            x-data="{ show: true }"
            x-show="show"
            x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }))"
            class="flex min-h-[400px] items-center justify-center bg-red-500/10 p-4 text-center font-bold text-red-800">
            {{ session('error') }}
        </x-card>
    @endif
    @if (session('message'))
        <x-card id="message-container"
            x-data="{ show: true }"
            x-show="show"
            x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }))"
            class="text-green-dark flex min-h-[400px] items-center justify-center text-center font-bold">
            {{ session('message') }}
        </x-card>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('flash-message', () => {
            const messageContainer = document.getElementById('message-container');
            if (messageContainer) {
                messageContainer.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
    });
</script>
