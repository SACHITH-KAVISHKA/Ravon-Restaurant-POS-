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
            $table->dropColumn(['email', 'email_verified_at', 'employee_id', 'phone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->after('username');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('employee_id')->unique()->nullable()->after('password');
            $table->string('phone')->nullable()->after('employee_id');
        });
    }
};
