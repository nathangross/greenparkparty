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
        'primary_date_start' => 'date',
        'primary_date_end' => 'date',
        'secondary_date_start' => 'date',
        'secondary_end_date' => 'date',
        'rainout_date' => 'date',
    ];

    public function rsvps()
    {
        return $this->hasMany(Rsvp::class);  // Assuming 'Rsvp' is the model name
    }
}
