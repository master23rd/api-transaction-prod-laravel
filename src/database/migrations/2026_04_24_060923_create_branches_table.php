<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('thumbnail')->nullable();
            $table->json('photos')->nullable();

            $table->foreignId('city_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('about')->nullable();
            $table->json('facilities')->nullable();

            $table->string('manager_name')->nullable();

            // contact
            $table->string('phone_number')->nullable()->index();
            $table->string('email')->nullable()->index();

            // bank info
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['city_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
