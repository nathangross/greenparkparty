<?php

namespace App\Livewire;

use App\Models\Rsvp;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class RsvpForm extends Component
{
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $street;
    public $showAttending = true;
    public $attending_count = 1;  // Default attending
    public $volunteer = false;
    public $message;
    public $showForm = true;

    protected $rules = [
        'first_name' => 'required|min:3',
        'last_name' => 'nullable|min:3',
        'email' => 'nullable|email',
        'phone' => 'nullable|min:10',
        'street' => 'nullable',
        'attending_count' => 'required|numeric',
        'volunteer' => 'nullable|boolean',
        'message' => 'nullable|max:255',
    ];

    public function save()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            // Create a user regardless of email
            $user = User::firstOrCreate(
                [
                    'email' => $this->email ?? $this->generateUniqueIdentifier()
                ],
                [
                    'first_name' => $this->first_name,
                    'last_name' => $this->last_name,
                    'phone' => $this->phone,
                    'street' => $this->street,
                    'password' => null  // Assuming you want password to be null
                ]
            );

            // Create the RSVP and associate it with the user
            Rsvp::create([
                'user_id' => $user->id,  // Assuming you have a user_id in your RSVPs table
                'attending_count' => $this->attending_count,
                'party_id' => 1,  // Update as necessary
                'volunteer' => $this->volunteer ? 1 : 0,
                'message' => $this->message,
            ]);

            DB::commit();  // Commit the transaction if all is well
            session()->flash('message', 'Got it. Thank you!');
            $this->reset();
            $this->showForm = false;
        } catch (\Exception $e) {
            DB::rollback();  // Roll back the transaction on error
            session()->flash('error', 'Failed to create RSVP and User. ' . $e->getMessage());
        }
    }

    protected function generateUniqueIdentifier()
    {
        // Generate a unique identifier using first_name, last_name, and phone
        return 'no_email_user_' . md5($this->first_name . $this->last_name . $this->phone . now()->timestamp);
    }

    public function render()
    {
        return view('livewire.rsvp-form');
    }
}
