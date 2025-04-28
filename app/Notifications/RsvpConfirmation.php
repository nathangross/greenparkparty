<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class RsvpConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rsvp;

    public function __construct($rsvp)
    {
        $this->rsvp = $rsvp;
        Log::info('RsvpConfirmation notification constructed', [
            'rsvp_id' => $rsvp->id,
            'user' => $rsvp->user->toArray()
        ]);
    }

    public function via($notifiable)
    {
        Log::info('RsvpConfirmation via method called', [
            'notifiable' => $notifiable->toArray()
        ]);
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        Log::info('RsvpConfirmation toMail method called', [
            'notifiable' => $notifiable->toArray(),
            'rsvp' => $this->rsvp->toArray()
        ]);

        $message = $this->rsvp->attending_count > 0
            ? "Thanks, we have you down for **{$this->rsvp->attending_count}**. We'll see you there!"
            : "Thanks for letting us know. We hope to see you next year!";

        $party = $this->rsvp->party;
        $primaryStart = \Carbon\Carbon::parse($party->primary_date_start);
        $primaryEnd = \Carbon\Carbon::parse($party->primary_date_end);

        $mailMessage = (new MailMessage)
            ->subject('RSVP Confirmation - Green Park Party')
            ->greeting("Hello {$this->rsvp->user->first_name}!")
            ->line($message)
            ->line('**Party Details:**')
            ->line("* **Date:** {$primaryStart->format('l, F j, Y')}")
            ->line("* **Time:** {$primaryStart->format('g:i A')} to {$primaryEnd->format('g:i A')}")
            ->line("* **Location:** Green Park")
            ->line("* **Address:** 6661 Green Park Drive, Dayton OH 45459");

        if ($party->secondary_date_start) {
            $secondaryStart = \Carbon\Carbon::parse($party->secondary_date_start);
            $secondaryEnd = \Carbon\Carbon::parse($party->secondary_date_end);
            
            $mailMessage->line('')
                ->line('**Rainout Date:**')
                ->line("* **Date:** {$secondaryStart->format('l, F j, Y')}")
                ->line("* **Time:** {$secondaryStart->format('g:i A')} to {$secondaryEnd->format('g:i A')}");
        }

        if ($this->rsvp->volunteer) {
            $mailMessage->line('')
                ->line('**Thank you for volunteering!** We\'ll be in touch as we get closer to the party to discuss volunteer opportunities.');
        }

        return $mailMessage
            ->line('')
            ->line('We look forward to celebrating with you!')
            ->line('If you have any questions, please don\'t hesitate to reach out.');
    }
} 

