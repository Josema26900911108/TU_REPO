<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lotesalarma;
use App\Models\User;
use Illuminate\Notifications\Notification;


use App\Notifications\LoteVencimientoNotification;


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

public function handle() {
    $lotesProximos = Lotesalarma::where('cantidad', '>', 0)
        ->whereDate('fecha_vencimiento', '<=', now()->addDays(15))
        ->get();

    $admins = User::where('rol', 'admin')->get();

    foreach ($lotesProximos as $lote) {
        foreach ($admins as $admin) {
            $admin->notify(new LoteVencimientoNotification($lote));
        }
    }
}


}
