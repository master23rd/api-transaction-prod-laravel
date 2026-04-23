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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payment_status');
            $table->string('order_status');
            $table->integer('grand_total_amount');
            $table->integer('total_tax_amount')->default(0);
            $table->integer('service_fee_amount')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('tax_percentage_amount')->default(0);
            $table->integer('total_items');
            $table->string('proof_of_payment')->nullable();
            $table->foreignId('cafe_id')->constrained()->onDelete('cascade');
            $table->string('payment_method');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
