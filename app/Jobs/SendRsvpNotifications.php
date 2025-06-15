<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Rsvp;
use App\Notifications\RsvpConfirmation;
use App\Notifications\AdminRsvpNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendRsvpNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rsvp;
    protected $shouldNotifyAdmin;
    protected $shouldNotifyUser;
    protected $userToNotify;

    public function __construct(Rsvp $rsvp, bool $shouldNotifyAdmin, bool $shouldNotifyUser, ?User $userToNotify)
    {
        $this->rsvp = $rsvp;
        $this->shouldNotifyAdmin = $shouldNotifyAdmin;
        $this->shouldNotifyUser = $shouldNotifyUser;
        $this->userToNotify = $userToNotify;
    }

    public function handle()
    {
        if ($this->shouldNotifyAdmin) {
            $adminEmails = config('app.admin_emails', []);
            foreach ($adminEmails as $email) {
                $admin = User::where('email', $email)->first();
                if ($admin) {
                    $admin->notify(new AdminRsvpNotification($this->rsvp));
                } else {
                    Log::warning('Admin notification skipped - no user found for email', [
                        'email' => $email,
                        'rsvp_id' => $this->rsvp->id
                    ]);
                }
            }
        }

        if ($this->shouldNotifyUser && $this->userToNotify) {
            try {
                $this->userToNotify->notify(new RsvpConfirmation($this->rsvp));
            } catch (\Exception $e) {
                Log::error('Failed to send RSVP confirmation email', [
                    'user_id' => $this->userToNotify->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
} 