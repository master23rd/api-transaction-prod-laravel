<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_branch', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wallet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            // prevent duplicate relation
            $table->unique(['wallet_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_branch');
    }
};
