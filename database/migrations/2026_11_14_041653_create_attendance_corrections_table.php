<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->restrictOnDelete();
            $table->string('old_status', 20);
            $table->string('new_status', 20);
            $table->text('reason');
            $table->unsignedInteger('version');
            $table->foreignId('corrected_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['attendance_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
