<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leader_assignments', function (Blueprint $table) {
            // Attempt to drop existing foreign key if present.
            try {
                $table->dropForeign(['leader_id']);
            } catch (\Throwable $e) {
                // ignore if it doesn't exist or cannot be dropped
            }

            // Recreate fk to users.id
            // Ensure column exists and correct type (unsignedBigInteger)
            if (!Schema::hasColumn('leader_assignments', 'leader_id')) {
                $table->unsignedBigInteger('leader_id')->nullable();
            }

            // Add FK
            try {
                $table->foreign('leader_id')->references('id')->on('users')->onDelete('cascade');
            } catch (\Throwable $e) {
                // in some DB platforms, adding FK may fail if inconsistencies exist; surface by logging if needed
            }
        });
    }

    public function down(): void
    {
        Schema::table('leader_assignments', function (Blueprint $table) {
            try {
                $table->dropForeign(['leader_id']);
            } catch (\Throwable $e) {
            }

            // restore to pointing to workers (best-effort)
            try {
                $table->foreign('leader_id')->references('id')->on('workers')->onDelete('cascade');
            } catch (\Throwable $e) {
            }
        });
    }
};
