<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('teacher_name');
            $table->string('email');
            $table->date('date');
            $table->enum('status', ['present', 'absent']);
            $table->float('percentage')->default(0);
            $table->timestamps();
            $table->unique(['email', 'date']); // Ensure one record per teacher per day
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};