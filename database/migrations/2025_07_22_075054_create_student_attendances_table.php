<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->string('roll_number');
            $table->date('date');
            $table->enum('status', ['present', 'absent']);
            $table->float('percentage')->default(0);
            $table->timestamps();
            $table->unique(['roll_number', 'date']); // Ensure one record per student per day
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};