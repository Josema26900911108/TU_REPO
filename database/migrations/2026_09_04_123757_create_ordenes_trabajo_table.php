<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_trabajo', function (Blueprint $table) {
            // 🚀 ID MANUAL: Al no usar id() o bigIncrements(), evitamos que la BD genere números automáticos
            $table->bigInteger('id')->primary(); 
            $table->string('Orden_Trabajo', 255);
            $table->text('Descripcion')->nullable();
            $table->string('Id_Contratista', 100)->nullable();
            $table->string('Id_Servicio', 255); // 📌 Este actuará como tu SKU para el Match
            $table->double('Cantidad', 8, 2);
            $table->string('Centro_Mano_Obra', 100)->nullable();
            $table->double('COSTO', 8, 2);
            $table->double('SUBTOTAL', 12, 2);
            $table->string('TECNO', 100)->nullable();
            $table->timestamps();
            
            // Índices para acelerar el reporte de conciliación masivo
            $table->index('Orden_Trabajo');
            $table->index('Id_Servicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_trabajo');
    }
};
