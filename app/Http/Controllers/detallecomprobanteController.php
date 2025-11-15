<?php

namespace App\Http\Controllers;
use App\Models\Comprobante;
use App\Models\CuentaContable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\DetalleComprobante;
use Illuminate\Http\Request;

class detallecomprobanteController extends Controller
{
    public function create($comprobanteId)
    {
        // Obtener tienda y estatus del usuario desde la sesión
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        // Obtener el comprobante específico por su ID
        $comprobante = Comprobante::with(['tienda', 'detalles.cuentaContable'])
            ->where('id', $comprobanteId) // Filtra por ID
            ->where('fkTienda', $fkTienda)
            ->where('estado', 1)
            ->first(); // Cambia a first() si buscas un solo comprobante

        if (!$comprobante) {
            $comprobante = DB::table('comprobantes')
            ->where('id',$comprobanteId)
            ->get();
            $detallecomprobante=null;
        }else{
        // Consultar los detalles del comprobante
        $detallecomprobante = DetalleComprobante::where('fkComprobante', $comprobante->id)->get();
    }
        // Consultar las cuentas contables que tienen padre
        $cuentacontable = DB::table('cuentas_contables')
            ->whereNotNull('padre_id') // Asegurar que no sea NULL
            ->get();

        // Retornar la vista con los datos necesarios
        return view('detallecomprobante.create', compact('cuentacontable', 'detallecomprobante', 'comprobante','comprobanteId'));
    }


        public function obtenerdetalles(Request $request)
    {
        try{
        $comprobanteId=$request->idcomprobante;

                $pdo = DB::getPdo();
        $stmt = $pdo->prepare("
    SELECT
        cc.id as idcuentacontable,
        dc.formula,
        cc.nombre,
        dc.Naturaleza,
        dc.valorminimo as resultado
    FROM
        dbsistemaventa.detalle_comprobantes as dc
    INNER JOIN
        cuentas_contables as cc
    ON
        cc.id = dc.fkCuentaContable
    WHERE
        dc.fkComprobante = :id
");

$stmt->execute(['id' => $comprobanteId]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


return response()->json($detallecomprobante);

        // Consultar los detalles del comprobante

}catch(Exception $e){

            return response()->json([
            'error' => 'Error al ejecutar la consulta',
            'detalle' => $e->getMessage()
        ], 500);
}

    }

    public function create2()
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        if ($Estatus == 'ER') {
            $comprobante = Comprobante::all('tienda')
            ->where('estado',1)
            ->latest()
            ->get();
        } else {
            // Filtrar los productos solo por la tienda del usuario
            $comprobante = Comprobante::with([
                'tienda' // Incluye la tienda en la consulta
            ])->where('fkTienda', $fkTienda)
            ->where('estado',1)
            ->latest()->get();
        }
        //$comprobante = Comprobante::all();
        return view('detallecomprobante.create', compact('comprobante'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'arraynombre.*' => 'required|string|max:255',
            'arrayNaturaleza.*' => 'required|string|max:2',
            'arrayformula.*' => 'required|string|max:255',
            'arrayvalorminimo.*' => 'required|numeric|min:0',
            'arraycuentacontable_id.*' => 'nullable|exists:cuentas_contables,id',
        ]);

        $arraycomprobanteId = $request->input('arrayidcomprobante'); // Id del comprobante

        $arraynombres = $request->input('arraynombre');
        $arrayformulas = $request->input('arrayformula');
        $arrayvaloresminimos = $request->input('arrayvalorminimo');
        $arraycuentascontables = $request->input('arraycuentacontable_id');
        $arraynaturalezas = $request->input('arrayNaturaleza');

        DetalleComprobante::where('fkComprobante', $arraycomprobanteId[0])->delete();


        // Insertar múltiples detalles de comprobantes
        foreach ($arraynombres as $index => $nombre) {
            DB::table('detalle_comprobantes')->insert([
                'nombre' => (string)$nombre,
                'formula' => (string)$arrayformulas[$index],
                'Naturaleza' => (string)$arraynaturalezas[$index],
                'valorminimo' => (float)$arrayvaloresminimos[$index],
                'fkComprobante' => $arraycomprobanteId[$index], // El id del comprobante es el mismo para todos
                'fkCuentaContable' => $arraycuentascontables[$index] ?? null, // Permitir valor nulo
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        // Retorna una respuesta


        return redirect()->route('comprobante.index')->with('success', 'Se ha agregado Exitosamente');
    }
    public function edit(Comprobante $comprobante) {
        if (!$comprobante) {
            return redirect()->route('comprobante.index')->with('error', 'Comprobante no encontrado');
        }

        $Estatus = session('user_estatus');
        $comprobanteId = $comprobante->id;

        if ($Estatus == 'ER') {
            $detallecomprobante = DetalleComprobante::with('tienda')->where('fkComprobante',$comprobanteId)
                ->get();
        } else {
            $detallecomprobante = DetalleComprobante::with('tienda')->where('fkComprobante',$comprobanteId)
                ->where('fkComprobante', $comprobanteId)
                ->get();
        }

        if (!$detallecomprobante) {
            return redirect()->route('comprobante.index')->with('error', 'Detalle de comprobante no encontrado');
        }

        $cuentacontable = DB::table('cuentas_contables')
            ->whereNotNull('padre_id')
            ->get();

        return view('detallecomprobante.edit', compact('comprobante', 'cuentacontable', 'detallecomprobante', 'comprobanteId'));
    }


}
