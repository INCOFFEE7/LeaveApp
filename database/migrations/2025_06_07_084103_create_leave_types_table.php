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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('leave_type_name', 100);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });


    }
    public function down() {
        Schema::dropIfExists('leave_types');
    }
};
