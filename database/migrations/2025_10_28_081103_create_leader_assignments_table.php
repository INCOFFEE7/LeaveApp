<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leader_assignments', function (Blueprint $table) {
            $table->id();
            // leader_id refers to the user who is the leader (users.id)
            $table->foreignId('leader_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('worker_id')->nullable()->constrained('workers')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level')->nullable(); // contoh: leader, dept_head, manager
            $table->date('effective_date')->default(now());
            $table->date('ended_at')->nullable(); // null artinya masih aktif
            $table->timestamps();

            $table->unique(['leader_id', 'worker_id', 'ended_at'], 'unique_active_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leader_assignments');
    }
};
