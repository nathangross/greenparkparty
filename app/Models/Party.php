<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'primary_date_start' => 'datetime',
        'primary_date_end' => 'datetime',
        'secondary_date_start' => 'datetime',
        'secondary_end_date' => 'datetime',
    ];

    public function rsvps()
    {
        return $this->hasMany(Rsvp::class);  // Assuming 'Rsvp' is the model name
    }

    /**
     * Get the RSVP deadline for this party.
     * The deadline is set to 1 week before the party start date.
     * TODO: Make this configurable in the admin panel.
     *
     * @return \Carbon\Carbon
     */
    public function getRsvpDeadline()
    {
        return $this->primary_date_start->subWeeks(1);
    }
}
