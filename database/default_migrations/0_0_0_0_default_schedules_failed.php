<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DefaultSchedulesFailed extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_schedules_failed', function (Blueprint $table) {
            $table->id()->from(1);
            $table->bigInteger('default_schedules_id')->comment('{"fk":"default_schedules.id"}');
            $table->string('title');
            $table->longText('note')->nullable();
            $table->string('status')->nullable()->default('NEW');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('default_schedules_failed');
    }
}
