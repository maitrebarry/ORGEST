<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Couleur dominante extraite automatiquement du logo (cf.
 * App\Support\ExtracteurCouleur), utilisée pour harmoniser les écritures et
 * bordures des factures PDF avec le logo du bureau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bureaux', function (Blueprint $table) {
            $table->string('couleur', 7)->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('bureaux', function (Blueprint $table) {
            $table->dropColumn('couleur');
        });
    }
};
