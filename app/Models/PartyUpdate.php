<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartyUpdate extends Model
{
    use HasFactory;

    public const PUBLISH_TARGET_HOMEPAGE = 'homepage';

    public const PUBLISH_TARGET_EMAIL = 'email';

    public const PUBLISH_TARGET_BOTH = 'both';

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'mailchimp_segment_id' => 'integer',
        'mailchimp_sent_at' => 'datetime',
    ];

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereIn('publish_target', [
                self::PUBLISH_TARGET_HOMEPAGE,
                self::PUBLISH_TARGET_BOTH,
            ])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function publishesToEmail(): bool
    {
        return in_array($this->publish_target, [
            self::PUBLISH_TARGET_EMAIL,
            self::PUBLISH_TARGET_BOTH,
        ], true);
    }

    public function publishesToHomepage(): bool
    {
        return in_array($this->publish_target, [
            self::PUBLISH_TARGET_HOMEPAGE,
            self::PUBLISH_TARGET_BOTH,
        ], true);
    }
}
