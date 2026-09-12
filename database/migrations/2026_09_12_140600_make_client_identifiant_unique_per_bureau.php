<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La numérotation CL-0001 est désormais indépendante par bureau (chaque
 * bureau repart de 1) : l'unicité doit donc porter sur (bureau_id,
 * identifiant), plus sur identifiant seul — sinon deux bureaux ne pourraient
 * jamais avoir chacun un "CL-0001".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['identifiant']);
            $table->unique(['bureau_id', 'identifiant']);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['bureau_id', 'identifiant']);
            $table->unique('identifiant');
        });
    }
};
