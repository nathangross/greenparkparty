<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $adminEmails = array_values(array_filter(config('app.admin_emails', [])));

        if ($adminEmails === []) {
            return;
        }

        User::query()
            ->whereIn('email', $adminEmails)
            ->update(['is_organizer' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left as a no-op to avoid removing organizer access
        // from users that may have been updated manually after deployment.
    }
};
