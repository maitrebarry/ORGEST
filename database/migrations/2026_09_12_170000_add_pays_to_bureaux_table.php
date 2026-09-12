<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le pays du bureau détermine automatiquement sa devise d'affichage
 * (cf. config/pays_devises.php et App\Models\Bureau::getDeviseSymboleAttribute()),
 * pour que l'application reste utilisable par des bureaux hors zone FCFA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bureaux', function (Blueprint $table) {
            $table->string('pays')->nullable()->after('email');
        });

        // Le bureau réel déjà en production est au Mali : on le renseigne
        // explicitement plutôt que de compter sur le seul repli par défaut.
        DB::table('bureaux')->whereNull('pays')->update(['pays' => 'Mali']);
    }

    public function down(): void
    {
        Schema::table('bureaux', function (Blueprint $table) {
            $table->dropColumn('pays');
        });
    }
};
