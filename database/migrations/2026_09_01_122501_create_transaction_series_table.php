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
        Schema::create('transaction_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('kind');
            $table->string('transaction_type');
            $table->bigInteger('amount_minor');
            $table->string('description');
            $table->text('notes')->nullable();
            $table->string('frequency')->nullable();
            $table->unsignedSmallInteger('interval')->default(1);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->unsignedSmallInteger('total_occurrences')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_series');
    }
};
