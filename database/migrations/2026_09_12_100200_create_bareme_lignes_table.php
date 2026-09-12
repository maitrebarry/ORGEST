<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bareme_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bareme_version_id')->constrained()->cascadeOnDelete();
            $table->decimal('densite_min', 6, 2);
            $table->decimal('densite_max', 6, 2);
            $table->decimal('carat', 5, 2);
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['bareme_version_id', 'densite_min', 'densite_max']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bareme_lignes');
    }
};
