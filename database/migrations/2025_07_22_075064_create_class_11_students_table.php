<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClass11StudentsTable extends Migration
{
    public function up()
    {
        Schema::create('class_11_students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('roll_number', 6)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_11_students');
    }
}