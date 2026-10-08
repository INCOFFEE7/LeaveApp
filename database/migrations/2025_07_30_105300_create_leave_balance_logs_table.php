<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_balance_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // nilai sebelum dan sesudah
            $table->smallInteger('old_hak_cuti')->nullable();
            $table->smallInteger('new_hak_cuti')->nullable();

            $table->smallInteger('old_sisa_cuti')->nullable();
            $table->smallInteger('new_sisa_cuti')->nullable();

            $table->smallInteger('old_utang_cuti')->nullable();
            $table->smallInteger('new_utang_cuti')->nullable();

            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_logs');
    }
};
