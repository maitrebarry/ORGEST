<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module « Cahier de crédit » : sommes avancées à un client pour lui
 * permettre d'aller acheter de l'or, remboursables en espèces, en or, ou
 * les deux. Pas de date d'échéance ni d'intérêt/commission (règles
 * métier confirmées par l'entreprise).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bureau_id')->nullable()->constrained('bureaux')->nullOnDelete();
            $table->string('numero');
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->dateTime('date_credit');
            $table->decimal('montant_accorde', 16, 2);
            $table->decimal('montant_remis', 16, 2);
            $table->decimal('montant_rembourse', 16, 2)->default(0);
            $table->text('observations')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('annule_at')->nullable();
            $table->timestamps();

            $table->unique(['bureau_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
