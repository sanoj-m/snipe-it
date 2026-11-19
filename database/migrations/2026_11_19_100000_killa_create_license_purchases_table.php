<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('license_purchases')) {
            Schema::create('license_purchases', function (Blueprint $table) {
                $table->id();
                // [license-spend addon] no FK constraints per project rule
                $table->unsignedBigInteger('license_id')->index();
                $table->date('purchase_date');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->unsignedSmallInteger('term_months')->nullable();
                $table->decimal('amount', 14, 2)->nullable();
                $table->char('currency', 3)->nullable();
                $table->decimal('amount_aed', 14, 2)->nullable();
                $table->decimal('ai_amount_aed', 14, 2)->nullable();
                $table->string('invoice_file')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('license_purchases');
    }
};
