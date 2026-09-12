<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barres_achat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_achat_id')->constrained('operations_achat')->cascadeOnDelete();
            $table->unsignedInteger('numero_barre');
            $table->decimal('poids', 10, 3);
            $table->decimal('eau', 10, 4);
            $table->decimal('densite_brute', 8, 4);
            $table->decimal('densite_tronquee', 6, 2);
            $table->foreignId('bareme_ligne_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('carat', 5, 2)->nullable();
            $table->decimal('prix_unitaire', 14, 2)->nullable();
            $table->decimal('montant', 16, 2)->nullable();
            $table->enum('statut', ['en_stock', 'vendu', 'annulee'])->default('en_stock');
            $table->timestamps();

            $table->index(['operation_achat_id', 'numero_barre']);
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barres_achat');
    }
};
