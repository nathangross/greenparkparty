<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('rsvps')->whereNotNull('message')->update([
            'message_text' => DB::raw('message')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('rsvps')->whereNotNull('message_text')->update([
            'message' => DB::raw('message_text')
        ]);
    }
};
