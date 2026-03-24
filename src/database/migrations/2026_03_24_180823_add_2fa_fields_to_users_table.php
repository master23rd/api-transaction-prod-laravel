<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_2fa_enabled')->default(true)->after('is_verified');
            $table->timestamp('two_factor_verified_at')->nullable()->after('is_2fa_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_2fa_enabled',
                'two_factor_verified_at'
            ]);
        });
    }
};
