<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_installments', function (Blueprint $table) {
            $table->unique(
                ['invoice_id', 'id'],
                'payment_installments_invoice_id_id_unique'
            );
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('payments_payment_installment_id_foreign');

            $table->foreign(
                ['invoice_id', 'payment_installment_id'],
                'payments_invoice_installment_foreign'
            )
                ->references(['invoice_id', 'id'])
                ->on('payment_installments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('payments_invoice_installment_foreign');

            $table->foreign('payment_installment_id')
                ->references('id')
                ->on('payment_installments')
                ->nullOnDelete();
        });

        Schema::table('payment_installments', function (Blueprint $table) {
            $table->dropUnique('payment_installments_invoice_id_id_unique');
        });
    }
};
