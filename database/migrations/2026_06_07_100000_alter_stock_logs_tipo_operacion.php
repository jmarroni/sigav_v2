<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `stock_logs.tipo_operacion` era varchar(20), pero el reverso de stock guarda
 * "REVERSO VENTA POR ERROR" (23 chars) -> "Data too long". Se amplía a 50.
 * Se usa SQL crudo para no depender de doctrine/dbal.
 */
class AlterStockLogsTipoOperacion extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE stock_logs MODIFY tipo_operacion VARCHAR(50) NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE stock_logs MODIFY tipo_operacion VARCHAR(20) NULL');
    }
}
