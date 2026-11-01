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
        Schema::table('parametres_plateforme', function (Blueprint $table) {
            $table->string('kkiapay_public_key', 255)->nullable()->after('copyright_texte');
            $table->string('kkiapay_private_key', 255)->nullable()->after('kkiapay_public_key');
            $table->string('kkiapay_secret_key', 255)->nullable()->after('kkiapay_private_key');
            $table->boolean('kkiapay_sandbox')->default(true)->after('kkiapay_secret_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parametres_plateforme', function (Blueprint $table) {
            $table->dropColumn([
                'kkiapay_public_key',
                'kkiapay_private_key',
                'kkiapay_secret_key',
                'kkiapay_sandbox',
            ]);
        });
    }
};
