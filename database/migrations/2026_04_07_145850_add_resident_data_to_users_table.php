<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add GDPR-protected resident fields (full_address, birth_date) to users.
 *
 * @spec-link [[upsilonbattle:entity_users]]
 * @spec-link [[shared:requirement_customer_user_id_privacy]]
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('full_address')->nullable();
            $table->date('birth_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_address', 'birth_date']);
        });
    }
};
