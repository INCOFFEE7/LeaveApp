<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('leave_permissions', function (Blueprint $table) {
            $table->string('supporting_document', 255)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('leave_permissions', function (Blueprint $table) {
            $table->json('supporting_document')->nullable()->change();
        });
    }

};
