<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();

            // RELASI KE BRANCH (WAJIB)
            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete()
                ->index();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            // optional index tambahan
            $table->index(['branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};