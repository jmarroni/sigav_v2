<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSitesSucursalesOpencartTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sites_sucursales_opencart', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('id_sucursal');
            $table->string('url');
            $table->string('user');
            $table->longText('password',255);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sites_sucursales_opencart');
    }
}
