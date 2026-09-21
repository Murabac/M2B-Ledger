<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('qb_list_id');
            $table->string('full_name');
            $table->boolean('is_active')->default(true);
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_balance', 15, 2)->default(0);
            $table->string('sales_rep_name')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'qb_list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
