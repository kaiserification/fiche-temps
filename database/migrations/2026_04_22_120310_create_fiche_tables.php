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
        // Une fiche par période mensuelle
        Schema::create('fiches', function (Blueprint $table) {
            $table->id();
            $table->string('matricule');
            $table->date('period_start');    // 20 du mois précédent
            $table->date('period_end');      // 19 du mois courant
            $table->string('projet')->nullable();
            $table->string('business_unit')->nullable();
            $table->string('repo_path')->nullable(); // chemin vers le dépôt git local
            $table->timestamps();
        });

        // Une entrée par jour ouvrable
        Schema::create('day_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->json('tasks');            // ["tâche 1", "tâche 2", ...]
            $table->json('commits')->nullable();
            $table->json('meetings')->nullable();
            $table->timestamps();
        });

        // Templates de réunions récurrentes
        Schema::create('meeting_templates', function (Blueprint $table) {
            $table->id();
            $table->string('label');          // "Daily standup"
            $table->boolean('default_checked')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_entries');
        Schema::dropIfExists('fiches');
        Schema::dropIfExists('meeting_templates');
    }
};
