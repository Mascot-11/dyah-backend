<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArtistIdToTattooGalleriesTable extends Migration
{
    public function up()
    {
        Schema::table('tattoo_galleries', function (Blueprint $table) {
            $table->unsignedBigInteger('artist_id')->after('id'); // or after another column
            $table->foreign('artist_id')->references('id')->on('users')->onDelete('cascade');
            // Change 'users' to your artists table if different
        });
    }

    public function down()
    {
        Schema::table('tattoo_galleries', function (Blueprint $table) {
            $table->dropForeign(['artist_id']);
            $table->dropColumn('artist_id');
        });
    }
}
