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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('ws_channel_key')->nullable()->unique()->after('id');
        });

        // Populate existing users
        \App\Models\User::all()->each(function ($user) {
            $user->update(['ws_channel_key' => (string) \Illuminate\Support\Str::uuid()]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ws_channel_key');
        });
    }
};
