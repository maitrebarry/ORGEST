<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('identifiant')->unique();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->boolean('actif')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('nom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
