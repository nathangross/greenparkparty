<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_updates', function (Blueprint $table) {
            $table->string('email_subject')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('party_updates', function (Blueprint $table) {
            $table->dropColumn('email_subject');
        });
    }
};
