<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permet à une barre de provenir soit d'un achat (comme avant), soit d'un
 * remboursement de crédit en or (nouveau) : operation_achat_id devient
 * nullable, et remboursement_credit_id est ajouté. Exactement UNE des deux
 * colonnes est renseignée par barre — jamais les deux, jamais aucune.
 *
 * ->change() nécessiterait doctrine/dbal (non installé) : ALTER TABLE brut
 * à la place, compatible MySQL/MariaDB, sans toucher à la contrainte FK
 * existante (une colonne NULL reste compatible avec sa clé étrangère).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE barres_achat MODIFY operation_achat_id BIGINT UNSIGNED NULL');

        Schema::table('barres_achat', function (Blueprint $table) {
            $table->foreignId('remboursement_credit_id')->nullable()->after('operation_vente_id')
                ->constrained('remboursements_credit')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('barres_achat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('remboursement_credit_id');
        });

        DB::statement('ALTER TABLE barres_achat MODIFY operation_achat_id BIGINT UNSIGNED NOT NULL');
    }
};
