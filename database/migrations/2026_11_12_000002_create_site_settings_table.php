<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_title', 150)->default('School Management System');
            $table->string('site_subtitle', 255)->nullable();
            $table->string('site_tagline', 255)->nullable();
            $table->text('site_description')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('light_logo_path')->nullable();
            $table->string('dark_logo_path')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Keep one settings row with a fixed ID for the singleton model.
        DB::table('site_settings')->insert([
            'id' => 1,
            'site_title' => 'School Management System',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
