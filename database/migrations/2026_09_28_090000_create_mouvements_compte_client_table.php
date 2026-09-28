<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « Comptes clients » : relevé continu par client (retraits remis,
 * règlements en or, transport…), distinct du « Cahier de crédit » (Credit)
 * qui porte sur un octroi ponctuel remboursable. Ici le solde est un
 * cumul permanent, à l'image d'un relevé bancaire — chaque ligne fige le
 * solde du client APRÈS elle (solde_apres), pour rester fidèle au suivi
 * papier/Excel existant de l'entreprise et éviter de tout recalculer à
 * chaque affichage.
 *
 * impacte_caisse distingue un mouvement réellement encaissé/décaissé (qui
 * alimente alors mouvement_financiers via TresorerieAuto, comme un crédit)
 * d'un simple règlement en nature (or) qui ne touche pas la caisse du jour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_compte_client', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bureau_id')->nullable()->constrained('bureaux')->nullOnDelete();
            $table->string('numero');
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['retrait', 'achat_or', 'transport', 'autre']);
            // sens exprimé du point de vue de la dette du client : « sortie »
            // = la caisse lui donne de la valeur (sa dette augmente),
            // « entree » = il rembourse/rapporte de la valeur (sa dette
            // diminue) — mêmes libellés que MouvementFinancier::SENS, pour
            // pouvoir les transmettre tels quels à TresorerieAuto.
            $table->enum('sens', ['sortie', 'entree']);
            $table->decimal('montant', 16, 2);
            $table->decimal('solde_apres', 16, 2);
            $table->boolean('impacte_caisse')->default(true);
            $table->string('mode_paiement')->nullable();
            $table->dateTime('date_mouvement');
            $table->text('observations')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('annule_at')->nullable();
            $table->timestamps();

            $table->unique(['bureau_id', 'numero']);
            $table->index(['client_id', 'date_mouvement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_compte_client');
    }
};
