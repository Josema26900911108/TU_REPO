<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('mb51_importaciones', function (Blueprint $table) {
        $table->index('created_at', 'idx_mb51_created');
        $table->index('clase_movimiento', 'idx_mb51_clase');
    });
}

public function down(): void
{
    Schema::table('mb51_importaciones', function (Blueprint $table) {
        $table->dropIndex('idx_mb51_created');
        $table->dropIndex('idx_mb51_clase');
    });
}
};
