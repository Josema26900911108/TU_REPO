<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use App\Models\Materialmanoobra;
use Illuminate\Http\Request;
use App\Http\Requests\StorePersonaRequest;
use App\Models\User;
use App\Models\Documento;
use App\Models\Eta;
use App\Models\Persona;
use App\Models\Tienda;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;


class etadirectController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-eta', ['only' => ['index']]);
        $this->middleware('permission:crear-eta', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-eta', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-eta', ['only' => ['destroy']]);

    }

    public function index()
    {




        return view('eta.index');
    }

        public function fetchrelacionEta(Request $request)
{
    try{
                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $fechain=$request->input('fechain');
                    $fechafin=$request->input('fechafin');



                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $eta=Eta::where('fkTienda',$fkTienda)
            ->whereBetween('created_at',[$fechain, $fechafin])
            ->paginate(10000000);

                } else {
            $eta=Eta::where('fkTienda',$fkTienda)
            ->whereBetween('created_at',[$fechain, $fechafin])
            ->paginate(10000000);
                };
                    }





    if ($request->ajax()) {
        return view('ETA.tabla.etatable', compact('eta'))->render();
    }
    }catch(Exception $e){
    return view('eta.index', compact('Error: '.$e->getMessage()));
    }


}


    public function create()
    {
        $materialmanoobra = Materialmanoobra::all();
        return view('materialmanoobra.create', compact('materialmanoobra'));
    }

public function show(){
    $materialmanoobra = Materialmanoobra::all();
}

public function importarMAMO(Request $request)
{
    $fkTienda = session('user_fkTienda');
    set_time_limit(300); // 5 minutos
    ini_set('memory_limit', '512M'); // Aumentar memoria

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file);

    // Contadores para estadísticas
    $insertados = 0;
    $actualizados = 0;
    $omitidos = 0;

    DB::beginTransaction();

    try {
        $batchSize = 1000; // Insertar en lotes de 1000
        $batchData = [];

        while (($linea = fgetcsv($file)) !== false) {
            // Combinar encabezados con datos
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos
            if (empty($data['Cantidad']) || empty($data['Orden']) || empty($data['SKU'])) {
                $omitidos++;
                continue;
            }

            // Convertir campos a UTF-8
            $descripcion = mb_convert_encoding($data['Descripcion'] ?? '', 'UTF-8', 'ISO-8859-1');
            $serie = mb_convert_encoding($data['Serie'] ?? '', 'UTF-8', 'ISO-8859-1');
            $mac1 = mb_convert_encoding($data['MAC1'] ?? '', 'UTF-8', 'ISO-8859-1');
            $mac2 = mb_convert_encoding($data['MAC2'] ?? '', 'UTF-8', 'ISO-8859-1');
            $mac3 = mb_convert_encoding($data['MAC3'] ?? '', 'UTF-8', 'ISO-8859-1');
            $tipo_serv = mb_convert_encoding($data['TIPO_DE_SERVICIO'] ?? '', 'UTF-8', 'ISO-8859-1');
            $tipo_orden = mb_convert_encoding($data['TIPO_DE_ORDEN'] ?? '', 'UTF-8', 'ISO-8859-1');
            $centro = mb_convert_encoding($data['CENTRO'] ?? '', 'UTF-8', 'ISO-8859-1');
            $empleado = mb_convert_encoding($data['EMPLEADO'] ?? '', 'UTF-8', 'ISO-8859-1');

            // Manejar fecha (con validación)
            try {
                $fecha = Carbon::createFromFormat('d/m/Y', $data['created_at'] ?? now()->format('d/m/Y'))->format('Y-m-d');
            } catch (\Exception $e) {
                $fecha = now()->format('Y-m-d');
            }

            // Preparar datos para inserción masiva
            $batchData[] = [
                'Orden' => $data['Orden'],
                'SKU' => $data['SKU'],
                'Descripcion' => $descripcion,
                'Cantidad' => $data['Cantidad'],
                'Serie' => $serie,
                'MAC1' => $mac1,
                'MAC2' => $mac2,
                'MAC3' => $mac3,
                'TIPO_DE_SERVICIO' => $tipo_serv,
                'TIPO_DE_ORDEN' => $tipo_orden,
                'CENTRO' => $centro,
                'EMPLEADO' => $empleado,
                'Naturaleza'=>'S',
                'Status'=>'Pe',
                'fkTienda' => $fkTienda,
                'created_at' => $fecha,
                'updated_at' => now(),
            ];

            $insertados++;

            // Insertar por lotes cuando alcance el tamaño
            if (count($batchData) >= $batchSize) {
                $this->insertOrUpdateBatch($batchData);
                $batchData = []; // Limpiar lote

                // Liberar memoria periódicamente
                if ($insertados % 5000 == 0) {
                    gc_collect_cycles();
                }
            }
        }

        // Insertar último lote si queda
        if (!empty($batchData)) {
            $this->insertOrUpdateBatch($batchData);
        }

        fclose($file);
        DB::commit();

        return back()->with('success',
            "Importación completada: {$insertados} insertados, {$actualizados} actualizados, {$omitidos} omitidos."
        );

    } catch (\Exception $e) {
        DB::rollBack();
        fclose($file);
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}

function JoboCommand(){
    try {
        Eta::orderBy('Orden')->where('Status', 'Pe')

   ->chunk(500, function ($rows) {

      // Agrupar por orden dentro del lote
      $ordenes = $rows->where('Status', 'Pe')
      ->groupBy('Orden');

      foreach ($ordenes as $orden => $items) {

         $tipo = $items->first()->TIPO_DE_SERVICIO.$items->first()->TIPO_DE_ORDEN;

         // 1️⃣ Familias usadas en la orden
         $familias = $items->map(function ($item) {
            return DB::table('treematerialescategoria')
                     ->where('SKU', $item->SKU)
                     ->value('padre_id');
         })->filter()->unique()->values();

         // 2️⃣ Guardar aprendizaje por familia
         foreach ($familias as $familia) {
            DB::table('aprendizaje_familia')->updateOrInsert(
               ['tipo_servicio' => $tipo, 'familia_id' => $familia],
               ['veces_usado' => DB::raw('veces_usado + 1')]
            );
         }

         // 3️⃣ Guardar combinaciones
         $count = count($familias);
         for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
               DB::table('combinacion_familia')->updateOrInsert(
                  [
                    'tipo_servicio' => $tipo,
                    'familia_a' => $familias[$i],
                    'familia_b' => $familias[$j]
                  ],
                  ['veces_juntos' => DB::raw('veces_juntos + 1')]
               );
            }
         }

DB::table('eta')
    ->where('Orden', $orden)
    ->update(['Status' => 'Ok']);

         DB::table('aprendizaje_ordenes')->updateOrInsert(
   ['tipo_servicio' => $tipo],
   ['total_ordenes' => DB::raw('total_ordenes + 1')]
);

      }
   });


    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
}
/**
 * Método para inserción/actualización masiva
 */
private function insertOrUpdateBatch(array $batchData)
{
    // Opción 1: INSERT IGNORE (si no necesitas actualizar)
    // DB::table('eta')->insertOrIgnore($batchData);

    // Opción 2: UPSERT (Laravel 8.10+)
    DB::table('eta')->upsert(
        $batchData,
        ['Orden', 'SKU', 'Cantidad', 'Serie'], // Claves únicas
        ['Descripcion', 'MAC1', 'MAC2', 'MAC3', 'TIPO_DE_SERVICIO',
         'TIPO_DE_ORDEN', 'CENTRO', 'EMPLEADO', 'fkTienda', 'updated_at']
    );
}
    public function store(StorePersonaRequest $request)
    {
        try {
            DB::beginTransaction();
            $persona = Persona::create($request->validated());
            $persona->cliente()->create([
                'persona_id' => $persona->id
            ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }

    public function descargarFormeta()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato ETA.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['Orden','SKU','Descripcion','Cantidad','Serie','MAC1','MAC2','MAC3','TIPO_DE_SERVICIO','TIPO_DE_ORDEN','CENTRO','EMPLEADO','created_at'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, [23450285,1005749,'SMART CARD DE NAGRAVISI¿¿N',1,"'142878214761",'','',"'NAGRAVISI¿¿N",'DF','DA',"'G817",'D087018','2/12/2024']);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function exportar(Request $request)
{

            $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

            $request->validate([
        'fechaincio' => 'required|date',
        'fechafin' => 'required|date|after_or_equal:fechaincio',
        'fkTienda' => 'required|exists:tienda,idTienda',
    ]);

    $inicio = Carbon::parse($request->fechaincio)->startOfDay();
$fin = Carbon::parse($request->fechafin)->endOfDay();

$datos = Eta::where('fkTienda', $request->fkTienda)
    ->whereBetween('created_at', [$inicio, $fin])
    ->get();

    // Encabezado del CSV
    $csv = "Orden,SKU,Descripcion,Cantidad,Serie,MAC1,MAC2,MAC3,TIPO_DE_SERVICIO,TIPO_DE_ORDEN,CENTRO,EMPLEADO,created_at\n";

    // Agregar datos
    foreach ($datos as $item) {

        $csv .= implode(",", [
            $item->Orden,
            $item->SKU,
            '"' . str_replace('"', '""', $item->Descripcion) . '"', // Escapar comillas dobles
            $item->Cantidad,
            $item->Serie,
            $item->MAC1,
            $item->MAC2,
            $item->MAC3,
            $item->TIPO_DE_SERVICIO,
            $item->TIPO_DE_ORDEN,
            $item->CENTRO,
            $item->EMPLEADO,
            $item->created_at
        ]) . "\n";
    }

    // Retornar respuesta para descarga
    $nombreArchivo = 'etadirect_export_' . now()->format('Ymd_His') . '.csv';

    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$nombreArchivo\"",
    ]);
}

    public function destroy(string $id)
    {
        try {

            Eta::destroy('id',$id);

            return redirect()->route('materialmanoobra.index')->with('success', 'Eliminado Exitosamente');
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del MAMO - ID: ' . $id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del cliente.');
        }
    }
}
