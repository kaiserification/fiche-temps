<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_entries', function (Blueprint $table) {
            $table->string('projet')->nullable()->after('day');
        });
    }

    public function down(): void
    {
        Schema::table('day_entries', function (Blueprint $table) {
            $table->dropColumn('projet');
        });
    }
};
