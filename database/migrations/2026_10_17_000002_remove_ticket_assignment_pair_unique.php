<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) { $table->dropUnique('tickets_assignment_pair_unique'); });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) { $table->unique(['assigned_department_id', 'assigned_staff_id'], 'tickets_assignment_pair_unique'); });
    }
};
