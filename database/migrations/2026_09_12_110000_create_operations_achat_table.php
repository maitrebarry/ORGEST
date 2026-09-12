<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_achat', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->dateTime('date_operation');
            $table->decimal('prix_base', 14, 2);
            $table->foreignId('bareme_version_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('poids_total', 12, 3)->default(0);
            $table->decimal('eau_total', 12, 4)->default(0);
            $table->decimal('montant_total', 16, 2)->default(0);
            $table->decimal('montant_paye', 16, 2)->default(0);
            $table->enum('statut', ['validee', 'annulee'])->default('validee');
            $table->text('observations')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('validee_at')->nullable();
            $table->foreignId('validee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['statut', 'date_operation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_achat');
    }
};
