<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel leave_permissions
            $table->foreignId('leave_permission_id')
                ->constrained('leave_permissions')
                ->cascadeOnDelete();

            // User yang melakukan approval
            $table->foreignId('approved_by')
                ->constrained('users') // asumsi approver ada di tabel users
                ->cascadeOnDelete();

            // Level urutan approval (1 = supervisor, 2 = manager, dst)
            $table->unsignedTinyInteger('level');

            // Status per level approval
            $table->enum('status', ['pending', 'in_progress', 'approved', 'rejected', 'cancelled'])
                ->default('pending');

            $table->timestamp('approved_at')->nullable();
            $table->string('note', 500)->nullable();

            $table->timestamps();

            // Supaya 1 leave_permission hanya punya 1 approval per level
            $table->unique(['leave_permission_id', 'level', 'approved_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_approvals');
    }
};
