<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_on_off', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_on')->default(true);
            $table->timestamps();
        });

        DB::table('admission_on_off')->insert([
            'id' => 1,
            'is_on' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_on_off');
    }
};
