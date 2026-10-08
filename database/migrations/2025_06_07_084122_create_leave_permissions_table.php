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
        Schema::create('leave_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers');
            $table->foreignId('leave_type_id')->constrained('leave_types');

            $table->date('leave_date_from');
            $table->date('leave_date_to');

            // 🔑 PENENTU SALDO TAHUN
            $table->unsignedSmallInteger('leave_year')
                ->comment('Tahun saldo cuti yang digunakan');

            // Opsional untuk histori
            $table->unsignedSmallInteger('source_year')
                ->nullable()
                ->comment('Tahun asal histori cuti (jika manual)');

            $table->date('onsite_date_from')->nullable();
            $table->date('onsite_date_to')->nullable();
            $table->date('return_to_work_date')->nullable();
            $table->integer('onsite_total_days')->nullable();
            $table->integer('leave_earned')->nullable();
            $table->unsignedSmallInteger('total_leave')->default(0);
            $table->string('leave_reason', 500);

            $table->enum('status', [
                'pending',
                'in_progress',
                'approved',
                'rejected',
                'cancelled'
            ])->default('pending');

            $table->unsignedSmallInteger('current_level')->default(0);
            $table->boolean('is_manual')->default(false);
            $table->boolean('is_active')->default(1);

            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['worker_id', 'status']);
            $table->index(['worker_id', 'leave_year']); // 🔥 penting

            $table->string('supporting_document')->nullable();
        });

    }
    public function down() {
        Schema::dropIfExists('leave_permissions');
    }
};
