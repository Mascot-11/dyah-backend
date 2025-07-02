<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_payments', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->enum('customer_type', ['online', 'instagram', 'facebook', 'tiktok', 'walk-in']);
            $table->date('payment_date');
            $table->time('payment_time');
            $table->enum('payment_method', ['cash', 'online']);
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('admin_payments');
    }
};
