<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La numérotation ACH-2026-0001 est désormais indépendante par bureau : même
 * raisonnement que pour clients.identifiant (cf. migration jumelle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operations_achat', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->unique(['bureau_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::table('operations_achat', function (Blueprint $table) {
            $table->dropUnique(['bureau_id', 'numero']);
            $table->unique('numero');
        });
    }
};
