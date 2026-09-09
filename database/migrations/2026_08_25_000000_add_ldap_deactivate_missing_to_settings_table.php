<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'ldap_deactivate_missing')) {
                $table->tinyInteger('ldap_deactivate_missing')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'ldap_deactivate_missing')) {
                $table->dropColumn('ldap_deactivate_missing');
            }
        });
    }
};
