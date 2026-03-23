<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lotesalarma;

class RevisarVencimientos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:revisar-vencimientos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

public function handle()
{
    // Buscamos lotes que venzan en los próximos 30 días
    $fechaLimite = now()->addDays(30)->format('Y-m-d');

    $lotesProximos = Lotesalarma::where('fecha_vencimiento', '<=', $fechaLimite)
        ->where('cantidad', '>', 0)
        ->where('notificado', false)
        ->get();

    foreach ($lotesProximos as $lote) {
        // Opción A: Enviar Email al administrador
        // Mail::to('admin@tuempresa.com')->send(new AlertaVencimiento($lote));

        // Opción B: Crear una notificación en la DB para el Dashboard
        // Notification::send($user, new LotePorVencer($lote));

        $lote->update(['notificado' => true]);
    }
}

}
