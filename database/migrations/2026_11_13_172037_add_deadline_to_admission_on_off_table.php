<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_on_off', function (Blueprint $table) {
            $table->timestamp('deadline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admission_on_off', fn (Blueprint $table) => $table->dropColumn('deadline'));
    }
};
