<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use App\Models\Materialmanoobra;
use Illuminate\Http\Request;
use App\Http\Requests\StorePersonaRequest;
use App\Models\Eta;
use App\Models\Material_relaciones;
use App\Models\Persona;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;

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

                if(!Auth::check()){
            return redirect()->route('login');
        }
        return view('eta.index');
    }

        public function fetchrelacionEta(Request $request)
{
    try{

                    if(!Auth::check()){
            return redirect()->route('login');
        }

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

public function reporteTecnicos()
{
    return DB::table('movimiento_materiales')
        ->select(
            'contrata as tecnico',
            'fkMateriales',
            DB::raw('SUM(CASE WHEN clase_movimiento = "251" THEN cantidad ELSE -cantidad END) as saldo_pendiente'),
            DB::raw('MIN(created_at) as fecha_entrega_mas_antigua')
        )
        ->whereIn('clase_movimiento', ['251', '252', '221']) // Salida, Devolución, Consumo
        ->groupBy('contrata', 'fkMateriales')
        ->having('saldo_pendiente', '>', 0)
        ->get();
}

    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $materialmanoobra = Materialmanoobra::all();
        return view('materialmanoobra.create', compact('materialmanoobra'));
    }

public function show(){
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $materialmanoobra = Materialmanoobra::all();
}

public function importarMAMO(Request $request)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

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

public function AutomataValidarMamo(Request $request)
{
    try {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $procesados = [];
        $rastro = [];

        $limite = $request->input('Orden');
        $fechainicial = $request->input('fechaincio');
        $fechafinal = $request->input('fechafin');
        $mamoorden = Eta::whereBetween('created_at', [
    Carbon::parse($fechainicial)->startOfDay(),
    Carbon::parse($fechafinal)->endOfDay()
])
->select('Orden')
->groupBy('Orden')
->limit($limite)->get();

foreach($mamoorden as $ordenitem){


        $mamo=DB::table('eta')
    ->select('CENTRO','SKU', DB::raw('SUM(cantidad) as Cantidad'))
    ->where('Orden', $ordenitem->Orden)
    ->groupBy('SKU', 'CENTRO')
    ->get();

                $CANT = substr_count($ordenitem->Orden, "25336466");
      if($CANT>0){
        $a="25188580_34028679_1021133_102113334028679";
    }



        foreach ($mamo as $item) {


if(
    str_contains($item->CENTRO,"G845") ||
    str_contains($item->CENTRO,"G830") ||
    str_contains($item->CENTRO,"G840")
){
    $SKUV = $item->CENTRO.$item->SKU;

    $query = Material_relaciones::where('skufinal',$SKUV);

}else{

    $SKUV = $item->SKU;

    $query = Material_relaciones::where('depende_SKU',$SKUV)
    ->where('skufinal', 'not like', '%G845%')
    ->where('skufinal', 'not like', '%G830%')
    ->where('skufinal', 'not like', '%G888%')
    ->where('skufinal', 'not like', '%G840%');
}

$relacioness = $query->where('minimo',1)
                     ->orderBy('id','ASC')
                     ->get();

if($relacioness->isEmpty()){
    continue;
}

$ver = $relacioness->pluck('skufinal');

             $relaciones = Material_relaciones::where('depende_SKU', $item->SKU)
            ->whereIn('skufinal', $ver)
            ->where('minimo',1)
            ->orderBy('id', 'ASC')
            ->get();

            $askuactualselec=$item->SKU;
            $orden = $ordenitem->Orden;


                $padre = DB::selectOne("
            SELECT distinct e.SKU FROM Eta e
            inner join material_relaciones mr on e.SKU=mr.depende_SKU where e.Orden=? and mr.depende_SKU=?;
    ", [$ordenitem->Orden, $item->SKU]);



    if($padre){
            if ($relaciones->isNotEmpty()) {
                foreach ($relaciones as $relacion) {

                   $conteo = Material_relaciones::where('depende_SKU', $relacion->SKU)

    ->whereExists(function ($query) use ($orden) {
        $query->select(DB::raw(1))
            ->from('eta')
            ->whereColumn('eta.SKU', 'material_relaciones.SKU')
            ->where('eta.Orden', $orden);
    })
    ->count();

if($relacion->maximo==10000){
                                                $monto = DB::selectOne("
                    SELECT distinct SUM(e.Cantidad) AS total
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    INNER JOIN (SELECT distinct tmc.SKU
    FROM treematerialescategoria tm
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    WHERE tm.SKU =  ? ) as tmcp on tmc.SKU=tmcp.SKU
    WHERE e.Orden = ? ;
    ", [$item->SKU, $orden]);
    $item->Cantidad = $monto ? ($monto->total ?? 0) : 0;
}
                 $padre = DB::selectOne("
        SELECT
            tmc.SKU,
            tmc.nombre,
            tmc.minimo,
            tmc.limite,
            tmc.tipo,
            tmc.valor
        FROM treematerialescategoria tm
        INNER JOIN treematerialescategoria tmc
            ON tm.padre_id = tmc.id
        WHERE tm.SKU = ?
        LIMIT 1
    ", [$relacion->SKU]);

        if (!$padre) {
        continue;
    }

                    $this->AutomataRecursivo(
                        $relacion->SKU,
                        $ordenitem->Orden,
                        $conteo,
                        $item->Cantidad,
                        $relacion->maximo  ?? 0,
                        $relacion->minimo  ?? 0,
                        $relacion->formula,
                        $procesados,
                        $relacion->tipo_relacion,
                        0,
                     $rastro,
                   $askuactualselec,
                     $relacion->SKU,
                     0,
                        $relacion->skufinal,
                        $padre,
                     $item->CENTRO
                    );

                }
            }
        }
        }
        }

        $procesados = array_values($procesados);

        $validaciones = $this->quitarDuplicadosPorOrdenYSKU($procesados);

            // Construir árbol jerárquico
    //$arbol = $this->construirArbol(array_values($validaciones));

//      return response()->json(['validaciones' => $validaciones], 200, [], JSON_PRETTY_PRINT);

if (ob_get_level()) {
    ob_end_clean();
}

return response()->streamDownload(function () use ($validaciones) {

    $handle = fopen('php://output', 'w');

    fputcsv($handle, [
        'Orden',
        'SKU_Origen',
        'SKU_Destino',
        'NOMBRE_Destino',
        'ValorEntrada',
        'Resultado',
        'TipoRelacion',
        'formula',
        'Nivel',
        'skuOrigenraiz',
        'CENTRO',
    ]);

    foreach ($validaciones as $fila) {

        fputcsv($handle, [
            $fila->Orden ?? '',
            $fila->SKU_Origen ?? '',
            $fila->SKU_Destino ?? '',
            $fila->NOMBRE_Destino ?? '',
            $fila->ValorEntrada ?? '',
            $fila->Resultado ?? '',
            $fila->TipoRelacion ?? '',
            $fila->formula ?? '',
            $fila->Nivel ?? '',
            $fila->skuOrigenraiz ?? '',
            $fila->CENTRO ?? '',
        ]);
    }

    fclose($handle);

}, 'validaciones_mamo.csv');


    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

public function AutomataValidarMamoOrden(Request $request)
{
    try {
        $procesados = [];
        $rastro = [];

        $orden = $request->input('Orden');

        $mamo=DB::table('eta')
    ->select('CENTRO','SKU', DB::raw('SUM(cantidad) as Cantidad'))
    ->where('Orden', $orden)
    ->groupBy('SKU', 'CENTRO')
    ->get();





        foreach ($mamo as $item) {

    $padre = DB::selectOne("
    SELECT distinct e.SKU, e.Descripcion as nombre FROM Eta e
    inner join material_relaciones mr on e.SKU=mr.depende_SKU where e.Orden=? and mr.depende_SKU=?;
    ", [$orden, $item->SKU]);

            if($padre){

if(
    str_contains($item->CENTRO,"G845") ||
    str_contains($item->CENTRO,"G830") ||
    str_contains($item->CENTRO,"G840")
){
    $SKUV = $item->CENTRO.$item->SKU;

    $query = Material_relaciones::where('skufinal',$SKUV);

}else{

    $SKUV = $item->SKU;

    $query = Material_relaciones::where('depende_SKU',$SKUV)
        ->where('skufinal', 'not like', '%G845%')
    ->where('skufinal', 'not like', '%G830%')
    ->where('skufinal', 'not like', '%G888%')
    ->where('skufinal', 'not like', '%G840%');
}

$relacioness = $query->where('minimo',1)
                     ->orderBy('id','ASC')
                     ->get();

if($relacioness->isEmpty()){
    continue;
}

$ver = $relacioness->pluck('skufinal');


            if(!$relacioness){
                continue;
            }

             $relaciones = Material_relaciones::where('depende_SKU', $item->SKU)
            ->whereIn('skufinal', $ver)
            ->where('minimo',1)
            ->orderBy('id', 'ASC')
            ->get();

            $askuactualselec=$item->SKU;
                $CANT = substr_count($orden, "25336466");
      if($CANT>0){
        $a="25188580_34028679_1021133_102113334028679";
    }


            if ($relaciones->isNotEmpty()) {
                foreach ($relaciones as $relacion) {


            $conteo = Material_relaciones::where('skufinal', $relacion->skufinal)
            ->count();


                  $clave = $orden . '_' . $relacion->SKU. '_' .$relacion->depende_SKU . '_' . $relacion->skufinal.'_'.$relacion->tipo_relacion;

if($relacion->maximo==10000){
                                                $monto = DB::selectOne("
                    SELECT distinct SUM(e.Cantidad) AS total
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    INNER JOIN (SELECT distinct tmc.SKU
    FROM treematerialescategoria tm
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    WHERE tm.SKU =  ? ) as tmcp on tmc.SKU=tmcp.SKU
    WHERE e.Orden = ? ;
    ", [$item->SKU, $orden]);
    $item->Cantidad = $monto ? ($monto->total ?? 0) : 0;
}

    if (in_array($clave, $rastro)) {
    continue; // 🔁 ciclo detectado
}

                    $this->AutomataRecursivo(
                        $relacion->SKU,
                        $orden,
                        $conteo,
                        $item->Cantidad,
                        $relacion->maximo  ?? 0,
                        $relacion->minimo  ?? 0,
                        $relacion->formula,
                        $procesados,
                        $relacion->tipo_relacion,
                        0,
                     $rastro,
                   $askuactualselec,
                     $relacion->SKU,
                     0,
                     $relacion->skufinal,
                     $padre,
                     $item->CENTRO
                    );

                }
            }
        }
}



        $procesados = array_values($procesados);

        $validaciones = $this->quitarDuplicadosPorOrdenYSKU($procesados);

            // Construir árbol jerárquico
    $arbol = $this->construirArbol(array_values($validaciones));

    // Retornar como JSON con estructura de árbol
        return response()->json(['validaciones' => $validaciones], 200, [], JSON_PRETTY_PRINT);






    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

private function quitarDuplicadosPorOrdenYSKU(array $items): array
{
    $unicos = [];

    foreach ($items as $item) {
        $clave = $item->Orden.''.$item->NOMBRE_Destino;
        $unicos[$clave] = $item;
    }

    return array_values($unicos);
}


public function AutomataRecursivo(
    string $skuActual,
    int $orden,
    int $recuento,
    float $valor,
    float $maximo,
    float $minimo,
    string $formula,
    array &$procesados,
    string $tipoRelacion,
    float $val,
    array &$rastro,
    string $skuOrigen,
    string $skuOrigenraiz,
    int $nivel = 0,
    string $skufinal,
    ?object &$padre,
    string $Centro
) {
    try {

    // 🛑 CASO BASE 1
    if ($valor <= 0 && $nivel==0) {
        return;
    }
    $cantidad = substr_count($skuOrigen, ".");

  $clave = $orden . '_' . $skuActual. '_' .$skuOrigen . '_' . $skufinal.'_'.$tipoRelacion;
$CANT = substr_count($clave, "1007881");
      if($CANT>0){
        $a="25188580_34028679_1021133_102113334028679";
    }

if (isset($procesados[$clave])) {
    return; // 🚫 Ya fue procesado este Orden + SKU
}

if (in_array($clave, $rastro)) {
    return; // 🔁 ciclo detectado
}

$rastro[] = $clave;

    if($nivel>($recuento+1) && $tipoRelacion=='calculo' ){
        return;
    }

        $cantidad=0;
    $cantidad = substr_count($skuOrigen, ".");

     if($cantidad==1){


    $total = DB::selectOne("
    SELECT distinct SUM(e.Cantidad) AS total
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    WHERE e.Orden = ?
      AND tmc.SKU = ?
", [$orden, $skuOrigen]);
} else{
    $total = DB::selectOne("
    SELECT distinct SUM(e.Cantidad) AS total
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    WHERE e.Orden = ? AND tm.SKU = ?
", [$orden, $skuOrigen]);
}

    $valor = $total->total ?? 0;

    $cantidad=0;
    $cantidad = substr_count($skuActual, ".");


    if($cantidad==1){


    $total = DB::selectOne("
    SELECT distinct SUM(e.Cantidad) AS total
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    WHERE e.Orden = ?
      AND tmc.SKU = ?
", [$orden, $skuActual]);
} else{
    $total = DB::selectOne("
    	SELECT distinct (e.Cantidad) AS total
    FROM Eta e
    INNER JOIN treematerialescategoria tm ON e.SKU = tm.SKU
    INNER JOIN treematerialescategoria tmc ON tm.padre_id = tmc.id
    WHERE e.Orden = ? AND tm.SKU = ?
", [$orden, $skuActual]);
}



    $usado = $total->total ?? 0;

$variables = [
    'minimo' => $minimo ?? 0,
    'maximo' => $maximo ?? 0,
    'valor'  => $valor ?? 0,
    'usado'  => $usado ?? 0,
    'total'  => $nivel ?? 0,
    'valant' => $val ?? 0,
];

$resultado=0;
    $resultado = $this->evaluarFormulaexp($formula, $variables);

$resultadoMostrar = $resultado;
$resultado= $resultado == 20000 ? 0 : $resultado;

     if($resultado > 0|| $resultado < 0) {
        if ($tipoRelacion == 'requiere'  || $tipoRelacion == 'incompatible' ) {

                $padrerel = DB::selectOne("
            SELECT distinct e.SKU FROM Eta e
            inner join material_relaciones mr on e.SKU=mr.SKU where e.Orden=? and e.SKU=?;
    ", [$orden, $skuActual]);

        if($resultado <> 0){
            $nodo = (object)[
                'Orden'         => $orden,
                'SKU_Origen'    => $skuActual,
                'SKU_Destino'   => $skuOrigen,
                'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
                'ValorEntrada'  => $valor,
                'Resultado'     => $resultado == 10000 ? $val : $resultado,
                'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                'formula'       => $formula,
                'Nivel'         => $nivel,
                'children'      => [],
                'skuOrigenraiz' => $skufinal,
                'CENTRO'        => $Centro,
                'claveunica'     => $clave,
            ];

            $procesados[$clave] = $nodo;
        }
        elseif($padrerel){
            $nodo = (object)[
                'Orden'         => $orden,
                'SKU_Origen'    => $skuActual,
                'SKU_Destino'   => $skuOrigen,
                'NOMBRE_Destino' => $padre->nombre ?? "SKU materia analizado ".$tipoRelacion." ".$skuActual,
                'ValorEntrada'  => $valor,
                'Resultado'     => $resultado == 10000 ? $val : $resultado,
                'TipoRelacion'  => $resultado < 0 ? $tipoRelacion . " - Exceso" : $tipoRelacion,
                'formula'       => $formula,
                'Nivel'         => $nivel,
                'children'      => [],
                'skuOrigenraiz' => $skufinal,
                'CENTRO'        => $Centro,
                'claveunica'     => $clave,
            ];

            $procesados[$clave] = $nodo;
        }


        }


    }

        $relaciones = Material_relaciones::where('skufinal', $skufinal)
    ->orderBy('id', 'ASC')
    ->get();

    $conteo = Material_relaciones::where('skufinal', $skufinal)
    ->count();


    foreach ($relaciones as $relacion) {

            if($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion == "25221156_02.03_1021134_GRA1008443_calculo"){
        $a=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion;
    }

    if (in_array($orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion, $rastro)) {
    continue; // 🔁 ciclo detectado
}



    $aSKUFILTRO=$clave;
        $tipoRelacion=$relacion->tipo_relacion;

      if($aSKUFILTRO==$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal.'_'.$relacion->tipo_relacion){
        $a="25188580_34028673_34028677_102113334028679";
  $clave=$orden.'_'.$relacion->SKU.'_'.$relacion->depende_SKU.'_'.$relacion->skufinal;
  $rastro[] = $clave;
    continue; // 🚫 Evitar procesar el mismo nodo en esta ram
    }

    if($tipoRelacion=="calculo" ){
          $padre = DB::selectOne("
            SELECT distinct e.SKU FROM Eta e
            inner join material_relaciones mr on e.SKU=mr.depende_SKU where e.Orden=? and mr.depende_SKU=?;
    ", [$orden, $relacion->SKU]);

        if (!$padre) {
             $cantidad = substr_count($relacion->depende_SKU, ".");
        if($cantidad==0){
            $cantidad = substr_count($relacion->SKU, ".");
        if($cantidad==0){
             continue;
        }
        }
    }
    }



    // 1️⃣ Obtener SKU padre
    $padre = DB::selectOne("
        SELECT
            tmc.SKU,
            tmc.nombre,
            tmc.minimo,
            tmc.limite,
            tmc.tipo,
            tmc.valor
        FROM treematerialescategoria tm
        INNER JOIN treematerialescategoria tmc
            ON tm.padre_id = tmc.id
        WHERE tm.SKU = ?
        LIMIT 1
    ", [$relacion->SKU]);

    // 🛑 CASO BASE 2
    if (!$padre) {
        continue;
    }

    if($aSKUFILTRO=="25298449_34025712_4018238_401823834028673_calculo"){
        $a="25188580_34028673_34028677_102113334028679";
    }

        // 🔁 llamada recursiva
        $this->AutomataRecursivo(
            $relacion->SKU,
            $orden,
            $conteo,
            $resultadoMostrar == 20000 ? $val : $resultadoMostrar,
            $relacion->maximo   ?? 0,
            $relacion->minimo   ?? 0,
            $relacion->formula,
            $procesados,
            $relacion->tipo_relacion,
            $resultadoMostrar == 20000 ? $val : $resultadoMostrar,
    $rastro,
    $relacion->depende_SKU,
    $skuOrigenraiz,
    $nivel + 1,
    $relacion->skufinal,
    $padre,
                     $Centro
        );
    }  } catch (Exception $e) {

            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

}


private function evaluarFormulaexp(string $formula, array $variables)
{
    try {

        $exp = new \Symfony\Component\ExpressionLanguage\ExpressionLanguage();

        // round
        $exp->register(
            'round',
            fn ($value, $precision = 0) => sprintf('round(%s, %s)', $value, $precision),
            fn ($variables, $value, $precision = 0) => round($value, $precision)
        );

        // floor (ENTERO en Excel)
        $exp->register(
            'floor',
            fn ($value) => sprintf('floor(%s)', $value),
            fn ($variables, $value) => floor($value)
        );

        // ceil
        $exp->register(
            'ceil',
            fn ($value) => sprintf('ceil(%s)', $value),
            fn ($variables, $value) => ceil($value)
        );

        // max
        $exp->register(
            'max',
            fn (...$args) => sprintf('max(%s)', implode(',', $args)),
            fn ($variables, ...$args) => max(...$args)
        );

        // min
        $exp->register(
            'min',
            fn (...$args) => sprintf('min(%s)', implode(',', $args)),
            fn ($variables, ...$args) => min(...$args)
        );

        return $exp->evaluate($formula, $variables);

    } catch (\Throwable $e) {
        Log::error('Error al evaluar fórmula: ' . $e->getMessage());
        return 0;
    }
}

private function construirArbol(array $nodosPlano)
{
    $arbol = [];
    $referencias = [];

    // 1️⃣ Clonar nodos para evitar referencias compartidas
    foreach ($nodosPlano as $nodo) {

        $nuevoNodo = clone $nodo; // CLAVE 🔥
        $nuevoNodo->children = [];

        $referencias[$nuevoNodo->SKU_Destino] = $nuevoNodo;
    }

    // 2️⃣ Construir jerarquía
    foreach ($referencias as $nodo) {

        if ($nodo->Nivel == 0) {
            $arbol[] = $nodo;
        } else {
            if (isset($referencias[$nodo->SKU_Origen])) {

                $padre = $referencias[$nodo->SKU_Origen];

                // ⚠️ Evitar auto-referencia directa
                if ($padre->SKU_Destino !== $nodo->SKU_Destino) {
                    $padre->children[] = $nodo;
                }
            }
        }
    }

    return array_values($arbol);
}

function evaluarFormula(string $formula, array $variables)
{
    $parser = new StdMathParser();
    $ast = $parser->parse($formula);

    $evaluator = new Evaluator();

    // ✅ AQUÍ está la corrección
    $evaluator->setVariables(
        array_map('floatval', $variables)
    );

    return $ast->accept($evaluator);
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
                            if(!Auth::check()){
            return redirect()->route('login');
        }

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

                        if(!Auth::check()){
            return redirect()->route('login');
        }

            Eta::destroy('id',$id);

            return redirect()->route('materialmanoobra.index')->with('success', 'Eliminado Exitosamente');
        } catch (Exception $e) {
            Log::error('Error al cambiar estado del MAMO - ID: ' . $id . ' - Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cambiar el estado del cliente.');
        }
    }
}
