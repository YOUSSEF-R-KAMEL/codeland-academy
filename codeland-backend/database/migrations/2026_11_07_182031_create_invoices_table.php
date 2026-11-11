<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('invoice_number')->unique();

            $table->decimal('total_amount', 10, 2);

            $table->decimal('paid_amount', 10, 2)->default(0);

            $table->string('currency', 3)->default('EGP');

            $table->string('status', 30)->default('unpaid');

            $table->date('issued_at');

            $table->date('due_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();

            $table->index(['enrollment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
