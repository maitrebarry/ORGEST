<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une vente porte sur des barres PRÉCISES déjà en stock (lot suivi
 * individuellement, cf. §17.7 non tranché — mais c'est le seul mode de
 * valorisation cohérent avec BarreAchat qui existe déjà comme lot unitaire) :
 * pas de nouvelle table de lignes de vente, on complète directement la barre
 * avec son prix/montant de VENTE, sans jamais toucher aux colonnes d'ACHAT
 * déjà enregistrées (traçabilité du coût d'origine préservée pour un futur
 * calcul de marge, cf. §17.12 non tranché).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barres_achat', function (Blueprint $table) {
            $table->foreignId('operation_vente_id')->nullable()->after('operation_achat_id')->constrained('operations_vente')->nullOnDelete();
            $table->decimal('prix_unitaire_vente', 14, 2)->nullable()->after('prix_unitaire');
            $table->decimal('montant_vente', 16, 2)->nullable()->after('montant');
        });
    }

    public function down(): void
    {
        Schema::table('barres_achat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operation_vente_id');
            $table->dropColumn(['prix_unitaire_vente', 'montant_vente']);
        });
    }
};
