<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_applications');
    }
};
