<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    
    Schema::table('productos', function (Blueprint $table) {
        // Si no existe la columna precio_base, la creamos
        if (!Schema::hasColumn('productos', 'precio_base')) {
            $table->decimal('precio_base', 15, 4)->default(0.00)->after('nombre');
        }
        // Agregamos stock_minimo para alertas en cualquier negocio
        if (!Schema::hasColumn('productos', 'stock_minimo')) {
            $table->integer('stock_minimo')->default(0);
        }
    });
}

public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            
        });
    }
};
