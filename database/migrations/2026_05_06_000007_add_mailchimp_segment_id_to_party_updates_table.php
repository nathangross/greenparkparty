<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_updates', function (Blueprint $table) {
            $table->unsignedBigInteger('mailchimp_segment_id')->nullable()->after('mailchimp_list_id');
        });
    }

    public function down(): void
    {
        Schema::table('party_updates', function (Blueprint $table) {
            $table->dropColumn('mailchimp_segment_id');
        });
    }
};
