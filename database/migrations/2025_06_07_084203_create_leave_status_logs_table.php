<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::create('leave_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_permission_id')->constrained('leave_permissions');
            $table->unsignedBigInteger('changed_by');
            $table->string('action');
            $table->enum('new_status', ['pending', 'in_progress', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('leave_status_logs');
    }
};
