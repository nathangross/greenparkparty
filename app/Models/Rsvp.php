<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rsvp extends Model
{
    use HasFactory;

    protected $table = 'rsvps';

    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'party_id' => 'integer',
        'user_id' => 'integer',
        'attending_count' => 'integer',
        'volunteer' => 'boolean',
        'receive_email_updates' => 'boolean',
        'receive_sms_updates' => 'boolean',
        'show_on_homepage' => 'boolean',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForParty(Builder $query, Party $party): Builder
    {
        return $query->where('party_id', $party->id);
    }

    public function scopeForDashboard(Builder $query, bool $includeOrganizers = false): Builder
    {
        if ($includeOrganizers) {
            return $query;
        }

        return $query->whereHas('user', function (Builder $query) {
            $query->where(function (Builder $query) {
                $query
                    ->where('is_organizer', false)
                    ->orWhereNull('is_organizer');
            });
        });
    }
}
