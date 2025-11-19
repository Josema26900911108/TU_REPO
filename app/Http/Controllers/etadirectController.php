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

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

                if ($Estatus == 'ER') {

                    $eta = Eta::all();
                    $tienda=Tienda::all();

                } else {
                    $eta = Eta::where('fkTienda',$fkTienda)->get();
                    $tienda=Tienda::where('idTienda',$fkTienda)->get();
                }



        return view('eta.index', compact('eta','tienda'));
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
    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); // leer encabezados

    DB::beginTransaction();

    try {
        while (($linea = fgetcsv($file)) !== false) {
            $data = array_combine($encabezado, $linea);

            // Validar campos mínimos
            if (!isset($data['Cantidad']) || !isset($data['Orden']) || !isset($data['SKU'])) continue;

// Convertir campos potencialmente con caracteres especiales a UTF-8
$descripcion = mb_convert_encoding($data['Descripcion'] ?? '', 'UTF-8', 'ISO-8859-1');
$serie       = mb_convert_encoding($data['Serie'] ?? '', 'UTF-8', 'ISO-8859-1');
$mac1        = mb_convert_encoding($data['MAC1'] ?? '', 'UTF-8', 'ISO-8859-1');
$mac2        = mb_convert_encoding($data['MAC2'] ?? '', 'UTF-8', 'ISO-8859-1');
$mac3        = mb_convert_encoding($data['MAC3'] ?? '', 'UTF-8', 'ISO-8859-1');
$tipo_serv   = mb_convert_encoding($data['TIPO_DE_SERVICIO'] ?? '', 'UTF-8', 'ISO-8859-1');
$tipo_orden  = mb_convert_encoding($data['TIPO_DE_ORDEN'] ?? '', 'UTF-8', 'ISO-8859-1');
$centro      = mb_convert_encoding($data['CENTRO'] ?? '', 'UTF-8', 'ISO-8859-1');
$empleado    = mb_convert_encoding($data['EMPLEADO'] ?? '', 'UTF-8', 'ISO-8859-1');
$fecha = Carbon::createFromFormat('d/m/Y', $data['created_at'])->format('Y-m-d');


// Insertar o actualizar
DB::table('eta')->updateOrInsert(
    [
        'Orden' => $data['Orden'],
        'SKU' => $data['SKU'],
        'Cantidad' => $data['Cantidad'],
        'Serie' => $serie,
    ],
    [
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
        'fkTienda' => $data['fkTienda'],
        'created_at' => $fecha,
        'updated_at' => now(),
    ]
);

        }

        DB::commit();
        return back()->with('success', 'Mano de Obra o Materiales de Eta importados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al importar: ' . $e->getMessage());
    }
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

    $columnas = ['Orden','SKU','Descripcion','Cantidad','Serie','MAC1','MAC2','MAC3','TIPO_DE_SERVICIO','TIPO_DE_ORDEN','CENTRO','EMPLEADO','fkTienda','created_at'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, [23450285,1005749,'SMART CARD DE NAGRAVISI¿¿N',1,"'142878214761",'','',"'NAGRAVISI¿¿N",'DF','DA',"'G817",'D087018',$fkTienda,'2/12/2024']);

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
    $csv = "Orden,SKU,Descripcion,Cantidad,Serie,MAC1,MAC2,MAC3,TIPO_DE_SERVICIO,TIPO_DE_ORDEN,CENTRO,EMPLEADO,fkTienda,created_at\n";

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
            $item->fkTienda,
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
