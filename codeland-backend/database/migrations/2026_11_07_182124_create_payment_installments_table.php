<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('installment_number');

            $table->decimal('amount', 10, 2);

            $table->date('due_at');

            $table->date('paid_at')->nullable();

            $table->string('status', 30)->default('pending');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['invoice_id', 'installment_number']);

            $table->index(['status', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_installments');
    }
};
