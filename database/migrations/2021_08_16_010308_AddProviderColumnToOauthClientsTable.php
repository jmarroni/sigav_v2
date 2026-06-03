<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProviderColumnToOauthClientsTable extends Migration
{
    public function up()
    {
        // Passport >= 9.4 ya incluye la columna `provider` en su propia
        // migración de oauth_clients; solo la agregamos si todavía no existe.
        if (Schema::hasColumn('oauth_clients', 'provider')) {
            return;
        }

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->string('provider')->after('secret')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('oauth_clients');
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
}
