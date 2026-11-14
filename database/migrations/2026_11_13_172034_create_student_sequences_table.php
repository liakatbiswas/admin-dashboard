<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('grade');
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['year', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_sequences');
    }
};
