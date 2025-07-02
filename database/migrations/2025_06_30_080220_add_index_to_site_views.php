<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // In new migration file
public function up()
{
    Schema::table('site_views', function (Blueprint $table) {
        $table->date('view_date')->nullable()->index();
        $table->index('ip_address');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_views', function (Blueprint $table) {
            //
        });
    }
};
