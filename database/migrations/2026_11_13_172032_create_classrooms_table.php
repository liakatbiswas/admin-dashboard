<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('grade');
            $table->string('shift', 10);
            $table->string('section', 10);
            $table->unsignedSmallInteger('capacity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['academic_year_id', 'grade', 'shift', 'section'], 'classroom_identity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
