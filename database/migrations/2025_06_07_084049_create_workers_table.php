<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');

            $table->string('company', 100)->nullable();
            $table->string('worker_name', 255);
            $table->string('position', 100)->nullable();
            $table->string('cabang', 100)->nullable();
            $table->string('site', 100)->nullable();
            $table->enum('employment_status', ['P', 'C', 'O'])->default('P'); // P=Permanent, C=Contract, O=Outsource
            $table->date('date_of_hire')->nullable();

            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
