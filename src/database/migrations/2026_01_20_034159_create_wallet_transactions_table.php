<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->integer('amount'); // Pure top-up amount that gets added to wallet
            $table->integer('total_amount'); // Total transfer amount (amount + service_fee - unique_code)
            $table->string('type');
            $table->string('status');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->string('proof_of_payment')->nullable();
            $table->integer('service_fee')->default(0);
            $table->integer('unique_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
