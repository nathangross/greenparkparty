<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'secondary_date_end' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function rsvps()
    {
        return $this->hasMany(Rsvp::class);  // Assuming 'Rsvp' is the model name
    }

    public function scopeCurrentFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_active')
            ->orderByDesc('primary_date_start')
            ->orderByDesc('id');
    }

    public static function currentForDashboard(): ?self
    {
        return self::query()->currentFirst()->first();
    }

    public static function previousBefore(self $party): ?self
    {
        return self::query()
            ->whereKeyNot($party->id)
            ->where('primary_date_start', '<', $party->primary_date_start)
            ->orderByDesc('primary_date_start')
            ->orderByDesc('id')
            ->first();
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
        return $this->primary_date_start?->copy()->subWeek();
    }
}
