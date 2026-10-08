<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_id');
            $table->foreign('worker_id')->references('id')->on('workers')->onDelete('cascade');
            $table->integer('year');
            $table->unsignedtinyInteger('hak_cuti');
            $table->tinyInteger('sisa_cuti');
            $table->unsignedsmallInteger('utang_cuti')->default(0);
            $table->date('reset_at')->nullable();
            $table->text('note')->nullable(); // Catatan dari admin
            $table->timestamps();
            $table->index('year');
            $table->unique(['worker_id', 'year']);
        });
    }

    public function down() {
        Schema::dropIfExists('leave_balances');
    }
};
