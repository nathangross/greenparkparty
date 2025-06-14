<?php

namespace App\Notifications;

use App\Models\Rsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
class AdminRsvpNotification extends Notification implements ShouldQueue
{
    use Queueable;
    protected $rsvp;

    public function __construct(Rsvp $rsvp)
    {
        $this->rsvp = $rsvp->load(['user', 'party']);
        Log::info('AdminRsvpNotification constructed', [
            'rsvp_id' => $rsvp->id,
            'user_id' => $rsvp->user_id,
            'party_id' => $rsvp->party_id,
            'attending_count' => $rsvp->attending_count,
        ]);
    }

    public function via($notifiable): array
    {
        Log::info('AdminRsvpNotification via method called', [
            'notifiable_id' => $notifiable->id,
            'notifiable_email' => $notifiable->email
        ]);
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        Log::info('AdminRsvpNotification toMail method called', [
            'notifiable_id' => $notifiable->id,
            'notifiable_email' => $notifiable->email
        ]);

        $user = $this->rsvp->user;
        $party = $this->rsvp->party;

        $message = (new MailMessage)
            ->subject('New RSVP Submission - Green Park Party')
            ->greeting('New RSVP Received!')
            ->line('**Attendee Details:**')
            ->line("* **Name:** {$user->first_name} {$user->last_name}")
            ->line("* **Email:** {$user->email}")
            ->line("* **Phone:** {$user->phone}")
            ->line("* **Street:** {$user->street}")
            ->line('')
            ->line('**RSVP Details:**')
            ->line("* **Attending:** " . ($this->rsvp->attending_count > 0 ? 'Yes' : 'No'))
            ->line("* **Number of Guests:** {$this->rsvp->attending_count}")
            ->line("* **Volunteer:** " . ($this->rsvp->volunteer ? 'Yes' : 'No'))
            ->line("* **Email Updates:** " . ($this->rsvp->receive_email_updates ? 'Yes' : 'No'))
            ->line("* **SMS Updates:** " . ($this->rsvp->receive_sms_updates ? 'Yes' : 'No'));

        if ($this->rsvp->message_text) {
            $message->line('')
                ->line('**Message from Attendee:**')
                ->line($this->rsvp->message_text);
        }

        return $message;
    }

    public function toArray($notifiable): array
    {
        Log::info('AdminRsvpNotification toArray method called', [
            'notifiable_id' => $notifiable->id,
            'notifiable_email' => $notifiable->email
        ]);

        return [
            'rsvp_id' => $this->rsvp->id,
            'user_id' => $this->rsvp->user_id,
            'party_id' => $this->rsvp->party_id,
            'attending_count' => $this->rsvp->attending_count,
        ];
    }
} 