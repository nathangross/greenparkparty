<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('party_updates', function (Blueprint $table) {
            $table->string('publish_target')->default('homepage')->after('published_at');
            $table->index('publish_target');
        });
    }

    public function down(): void
    {
        Schema::table('party_updates', function (Blueprint $table) {
            $table->dropIndex(['publish_target']);
            $table->dropColumn('publish_target');
        });
    }
};
