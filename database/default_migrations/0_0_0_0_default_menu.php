<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DefaultMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_menu', function (Blueprint $table) {
            $table->id()->from(1);

            $table->string('project');
            $table->string('modul');
            $table->string('submodul')->nullable();
            $table->string('menu');
            $table->string('path');
            $table->string('endpoint');
            $table->string('icon')->nullable();
            $table->decimal('sequence')->nullable()->default(1);
            $table->string('description', 255)->nullable();
            $table->string('note', 255)->nullable();
            $table->boolean('truncatable')->default(0); 
            $table->boolean('is_active')->default(1); 

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
        Schema::dropIfExists('default_menu');
    }
}
