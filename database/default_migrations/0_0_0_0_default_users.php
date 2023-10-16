<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DefaultUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('username',60)->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('role',60)->nullable()->default('admin');
            $table->string('project')->nullable();
            $table->string('status',20)->default("ACTIVE");
            $table->rememberToken();
            $table->timestamps();
        });
        $hasher = app()->make('hash');
        DB::table('default_users')->insert(
            [
                'name' => "trial",
                'email' => "trial@trial.trial",
                'username'=> "trial",
                'email_verified_at' => DB::raw("NOW()"),
                'password' => $hasher->make("trial"),
                'created_at' => DB::raw("NOW()")
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('default_users');
    }
}
