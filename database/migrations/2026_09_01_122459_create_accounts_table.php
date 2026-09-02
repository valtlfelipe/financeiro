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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->bigInteger('initial_balance_minor')->default(0);
            $table->date('balance_date');
            $table->string('icon')->default('wallet');
            $table->char('color', 7)->default('#148A62');
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['workspace_id', 'is_archived']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
