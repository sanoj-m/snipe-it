<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('licenses', 'is_subscription')) {
                $table->boolean('is_subscription')->default(false)->after('perpetual');
            }
            if (! Schema::hasColumn('licenses', 'is_ai')) {
                $table->boolean('is_ai')->default(false)->after('is_subscription');
            }
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (Schema::hasColumn('licenses', 'is_ai')) {
                $table->dropColumn('is_ai');
            }
            if (Schema::hasColumn('licenses', 'is_subscription')) {
                $table->dropColumn('is_subscription');
            }
        });
    }
};
