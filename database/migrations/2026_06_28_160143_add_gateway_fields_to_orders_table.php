<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: altera enum; SQLite: string é compatível
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_status ENUM('pendente','pago','cancelado') DEFAULT 'pendente'");
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('gateway_transaction_id')->nullable()->after('payment_status');
            $table->text('pix_qr_code')->nullable()->after('gateway_transaction_id');
            $table->text('pix_copy_paste')->nullable()->after('pix_qr_code');
            $table->text('pix_qr_code_base64')->nullable()->after('pix_copy_paste');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['gateway_transaction_id', 'pix_qr_code', 'pix_copy_paste', 'pix_qr_code_base64']);
        });

        // No SQL needed for SQLite — payment_status is a string column
        // MySQL rollback handled below
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY payment_status ENUM('pendente','confirmado') DEFAULT 'pendente'");
        }
    }
};
