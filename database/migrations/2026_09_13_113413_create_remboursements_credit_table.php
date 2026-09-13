<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remboursements_credit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bureau_id')->nullable()->constrained('bureaux')->nullOnDelete();
            $table->foreignId('credit_id')->constrained('credits')->cascadeOnDelete();
            $table->string('numero');
            $table->dateTime('date_remboursement');
            $table->enum('mode', ['especes', 'or', 'or_especes']);
            // Prix de base du marché au jour du remboursement (uniquement
            // renseigné quand le mode inclut de l'or — confirmé par
            // l'entreprise : la valorisation utilise la base du jour du
            // remboursement, jamais celle de l'achat d'origine du client).
            $table->decimal('prix_base', 14, 2)->nullable();
            $table->decimal('montant_especes', 16, 2)->default(0);
            $table->decimal('montant_or', 16, 2)->default(0);
            // Part effectivement imputée à la dette (espèces + or, hors reliquat).
            $table->decimal('montant_total', 16, 2);
            // Excédent de la valeur de l'or sur la dette, remis en espèces au client.
            $table->decimal('reliquat', 16, 2)->default(0);
            $table->text('observations')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bureau_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursements_credit');
    }
};
