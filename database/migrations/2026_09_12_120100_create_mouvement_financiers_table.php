<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvement_financiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journee_financiere_id')->constrained()->cascadeOnDelete();
            $table->enum('nature', ['fonds_initial', 'approvisionnement', 'paiement_achat', 'encaissement_vente', 'autre']);
            $table->enum('sens', ['entree', 'sortie']);
            $table->string('libelle');
            $table->decimal('montant', 16, 2);
            $table->dateTime('date_mouvement');
            $table->nullableMorphs('source');
            $table->foreignId('reversal_of_id')->nullable()->constrained('mouvement_financiers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['journee_financiere_id', 'sens']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvement_financiers');
    }
};
