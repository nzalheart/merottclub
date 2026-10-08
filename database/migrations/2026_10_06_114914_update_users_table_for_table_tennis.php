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
            $table->string('role')->default('member')->after('email'); // admin, manager, member
            $table->string('phone')->nullable()->after('role');
            $table->string('address')->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('address');
            $table->string('status')->default('active')->after('date_of_birth'); // active, inactive
            $table->string('avatar_url')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'address', 'date_of_birth', 'status', 'avatar_url']);
        });
    }
};
