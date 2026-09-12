<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journee_financieres', function (Blueprint $table) {
            $table->id();
            $table->enum('statut', ['ouverte', 'fermee'])->default('ouverte');
            $table->dateTime('date_ouverture');
            $table->dateTime('date_fermeture')->nullable();
            $table->decimal('solde_theorique_fermeture', 16, 2)->nullable();
            $table->decimal('solde_physique_fermeture', 16, 2)->nullable();
            $table->decimal('ecart_fermeture', 16, 2)->nullable();
            $table->text('observations')->nullable();
            $table->foreignId('ouverte_par')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('fermee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journee_financieres');
    }
};
