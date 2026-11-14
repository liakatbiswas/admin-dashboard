<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('admission_application_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('guardian_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('student_number', 15)->unique();
            $table->unsignedSmallInteger('admission_year');
            $table->unsignedTinyInteger('admission_grade');
            $table->unsignedInteger('admission_serial');
            $table->string('name_bn');
            $table->string('name_en');
            $table->json('details');
            $table->json('guardian');
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->unique(['admission_year', 'admission_grade', 'admission_serial'], 'student_admission_identity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
