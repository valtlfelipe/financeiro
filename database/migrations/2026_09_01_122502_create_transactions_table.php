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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('transaction_series_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->bigInteger('amount_minor');
            $table->string('description');
            $table->date('due_on');
            $table->timestampTz('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('installment_number')->nullable();
            $table->unsignedSmallInteger('installment_total')->nullable();
            $table->string('occurrence_key')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'due_on', 'type']);
            $table->index(['workspace_id', 'settled_at']);
            $table->unique(['transaction_series_id', 'occurrence_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
