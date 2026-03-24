<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();

            // Relasi ke users
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('nik')->unique();
            $table->date('birth_date');
            $table->string('job')->nullable();
            $table->string('office_name')->nullable();
            $table->string('positions')->nullable();
            $table->string('salary')->nullable();
            $table->string('martial')->nullable(); // (mungkin maksudnya marital)
            $table->string('contact_person')->nullable();
            $table->string('name_person')->nullable();
            $table->integer('kids')->default(0);
            $table->string('number_contact_person')->nullable();
            $table->string('ktp_photos')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};