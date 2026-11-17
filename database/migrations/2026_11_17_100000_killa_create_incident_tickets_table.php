<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('incident_tickets')) {
            return;
        }

        Schema::create('incident_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->string('requester_name')->nullable();
            $table->unsignedInteger('requester_user_id')->nullable();
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('category')->nullable();
            $table->string('task_type')->nullable();
            $table->text('issue')->nullable();
            $table->text('details');
            $table->string('status', 32)->default('Open');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('created_at');
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_tickets');
    }
};
