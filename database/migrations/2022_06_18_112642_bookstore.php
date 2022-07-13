<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Bookstore extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookstores', function (Blueprint $table) {
            $table->id();
            $table->string('bookname');
            $table->string('authorname');
            $table->integer('bookprice');
            $table->timestamps();
            $table->rememberToken();
        });
    }
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bookstores');
    }
}
