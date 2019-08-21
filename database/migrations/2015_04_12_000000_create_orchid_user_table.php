<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrchidUserTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('user', function (Blueprint $table) {
            $table->timestamp('last_login')->nullable();
            $table->jsonb('permissions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        // Fallback for sqlite
        if ($driver === 'sqlite') {
            Schema::dropIfExists('user');

            return;
        }

        Schema::table('user', function (Blueprint $table) {
            $table->dropColumn('last_login');
            $table->dropColumn('permissions');
        });
    }
}
