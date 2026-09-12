<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mouvement_financiers', function (Blueprint $table) {
            $table->string('mode_paiement')->nullable()->after('libelle');
        });
    }

    public function down(): void
    {
        Schema::table('mouvement_financiers', function (Blueprint $table) {
            $table->dropColumn('mode_paiement');
        });
    }
};
