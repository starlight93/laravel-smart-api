<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DefaultSchedules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_schedules', function (Blueprint $table) {
            $table->id()->from(1);
            $table->string('title')->unique();
            $table->string('every');
            $table->string('every_param')->nullable();
            $table->string('class_name');
            $table->string('func_name');
            $table->jsonb('parameter_values')->nullable();
            $table->jsonb('days')->nullable();
            $table->string('start_at')->nullable()->default("00:00");
            $table->string('end_at')->nullable()->default("23:59");
            $table->text('note')->nullable();

            $table->string('status')->nullable()->default('ACTIVE');
            $table->dateTime('last_executed_at')->nullable();
            $table->dateTime('end_executed_at')->nullable();
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
        Schema::dropIfExists('default_schedules');
    }
}
