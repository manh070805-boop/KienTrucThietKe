<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return;
        // Preserve existing users by backfilling before enforcing the domain's required full name.
        DB::statement("UPDATE users SET full_name=COALESCE(NULLIF(TRIM(full_name),''),NULLIF(TRIM(name),''),email) WHERE full_name IS NULL OR TRIM(full_name)=''");
        DB::statement('ALTER TABLE users MODIFY full_name VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') DB::statement('ALTER TABLE users MODIFY full_name VARCHAR(255) NULL');
    }
};
