<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE mouvement_financiers MODIFY nature ENUM(
            'fonds_initial', 'approvisionnement', 'paiement_achat', 'encaissement_vente',
            'credit_octroi', 'credit_remboursement', 'credit_reliquat', 'credit_annulation',
            'autre'
        )");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE mouvement_financiers MODIFY nature ENUM(
            'fonds_initial', 'approvisionnement', 'paiement_achat', 'encaissement_vente', 'autre'
        )");
    }
};
