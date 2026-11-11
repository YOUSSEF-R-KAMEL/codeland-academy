<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('payment_installment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('recorded_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('transaction_reference')->nullable();

            $table->decimal('amount', 10, 2);

            $table->string('currency', 3)->default('EGP');

            $table->string('payment_method', 30);

            $table->string('status', 30)->default('pending');

            $table->string('receipt_url', 2048)->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['payment_installment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
