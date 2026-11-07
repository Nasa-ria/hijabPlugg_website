<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('role');
            $table->json('admin_permissions')->nullable()->after('is_super_admin'); // admin pages this admin may use
            $table->boolean('must_change_password')->default(false)->after('admin_permissions');
            $table->foreignId('invited_by')->nullable()->after('must_change_password')->constrained('users')->nullOnDelete();
        });

        // Existing admins keep full access and can manage the team
        DB::table('users')->where('role', 'admin')->update(['is_super_admin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invited_by');
            $table->dropColumn(['is_super_admin', 'admin_permissions', 'must_change_password']);
        });
    }
};
