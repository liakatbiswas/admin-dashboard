<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->boolean('is_current')->nullable()->unique();
            $table->unsignedInteger('application_sequence')->default(0);
            $table->timestamps();
        });
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
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('grade');
            $table->string('name_bn');
            $table->string('name_en');
            $table->string('preferred_shift', 10);
            $table->string('preferred_group', 30)->nullable();
            $table->json('details');
            $table->json('guardian');
            $table->string('photo_path')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->string('selected_shift', 10)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        Schema::create('student_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('grade');
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['year', 'grade']);
        });
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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('classroom_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('roll');
            $table->string('group', 30)->nullable();
            $table->text('override_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'roll']);
            $table->unique(['student_id', 'academic_year_id']);
        });
        Schema::table('admission_on_off', function (Blueprint $table) {
            $table->timestamp('deadline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admission_on_off', fn (Blueprint $table) => $table->dropColumn('deadline'));
        foreach (['enrollments', 'students', 'student_sequences', 'admission_applications', 'classrooms', 'academic_years'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
