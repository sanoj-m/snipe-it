<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('incident_tickets', 'maintenance_id')) {
            Schema::table('incident_tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('maintenance_id')->nullable()->index()->after('asset_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('incident_tickets', function (Blueprint $table) {
            $table->dropColumn('maintenance_id');
        });
    }
};
