<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('site_views', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->nullable()->index();
            $table->string('user_agent')->nullable();
            $table->string('path')->nullable();
            $table->date('view_date')->nullable()->index();
            $table->json('location')->nullable(); // optional, for geo IP data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_views');
    }
};
