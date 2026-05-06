<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_updates', function (Blueprint $table) {
            $table->string('mailchimp_campaign_id')->nullable()->after('published_at');
            $table->timestamp('mailchimp_sent_at')->nullable()->after('mailchimp_campaign_id');
            $table->text('mailchimp_error')->nullable()->after('mailchimp_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('party_updates', function (Blueprint $table) {
            $table->dropColumn([
                'mailchimp_campaign_id',
                'mailchimp_sent_at',
                'mailchimp_error',
            ]);
        });
    }
};
