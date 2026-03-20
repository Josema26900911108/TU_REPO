<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
    {
        $tablas = [
            'ventas',
            'compras',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    if (!Schema::hasColumn($tabla, 'fkUserCreate')) {
                        $table->unsignedBigInteger('fkUserCreate')->nullable()->after('id');
                        $table->foreign('fkUserCreate')
                            ->references('id')
                            ->on('users')
                            ->onDelete('set null');

                        $table->unsignedBigInteger('fkUserEdit')->nullable()->after('id');
                        $table->foreign('fkUserEdit')
                            ->references('id')
                            ->on('users')
                            ->onDelete('set null');

                        $table->unsignedBigInteger('fkUserCC')->nullable()->after('id');
                        $table->foreign('fkUserCC')
                            ->references('id')
                            ->on('users')
                            ->onDelete('set null');

                        $table->unsignedBigInteger('fkUserAnular')->nullable()->after('id');
                        $table->foreign('fkUserAnular')
                            ->references('id')
                            ->on('users')
                            ->onDelete('set null');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        $tablas = [
            'ventas',
            'compras',
        ];

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla)) {
                Schema::table($tabla, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'fkUserCreate')) {
                        $table->dropForeign(['fkUserCreate']);
                        $table->dropColumn('fkUserCreate');
                    }
                    if (Schema::hasColumn($table->getTable(), 'fkUserEdit')) {
                        $table->dropForeign(['fkUserEdit']);
                        $table->dropColumn('fkUserEdit');
                    }
                    if (Schema::hasColumn($table->getTable(), 'fkUserCC')) {
                        $table->dropForeign(['fkUserCC']);
                        $table->dropColumn('fkUserCC');
                    }

                    if (Schema::hasColumn($table->getTable(), 'fkUserAnular')) {
                        $table->dropForeign(['fkUserAnular']);
                        $table->dropColumn('fkUserAnular');
                    }
                });
            }
        }
    }
};
