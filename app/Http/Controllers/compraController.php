<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompraRequest;
use App\Models\Compra;
use App\Models\Comprobante;
use App\Models\Producto;
use App\Models\Proveedore;
use App\Models\Tienda;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class compraController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-compra|crear-compra|mostrar-compra|eliminar-compra', ['only' => ['index']]);
        $this->middleware('permission:crear-compra', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-compra', ['only' => ['show']]);
        $this->middleware('permission:eliminar-compra', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        // Inicializa las variables que se pasarán a la vista
    // Inicializa las variables como arrays vacíos
    $compras = [];
    $productos = [];

        // Si el estatus es 'ER', cargar todas las compras
        if ($Estatus == 'ER') {
            $compras = Compra::with('comprobante', 'proveedore.persona', 'tienda')
                ->where('estado', 1)
                ->where('ClaveVista','DC')
                ->whereNotNull('proveedore_id')
                ->latest()
                ->get();

                            // Filtrar los productos solo por la tienda del usuario
            $productos = Producto::with('comprobante','proveedore.persona','tienda')
            ->where('fkTienda', $fkTienda)
            ->where('ClaveVista','DC')
            ->whereIn('estado', [1,2,3])
            ->latest()
            ->get();
        } else {
            $compras = Compra::with('comprobante', 'proveedore.persona', 'tienda')
            ->where('estado', 1)
            ->whereNotNull('proveedore_id')
            ->latest()
            ->get();
            // Filtrar los productos solo por la tienda del usuario
            $productos = Producto::with('comprobante','proveedore.persona','tienda')
            ->where('fkTienda', $fkTienda)
            ->whereIn('estado', [1,2,3])
            ->latest()
            ->get();
        }

        // Pasar tanto compras como productos a la vista (si ambos son necesarios)
        return view('compra.index', compact('compras', 'productos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

        $proveedores = Proveedore::whereHas('persona',function($query){
            $query->where('estado',1);
        })->get();
        $comprobantes = Comprobante::whereHas('tienda',function($query) use ($fkTienda){
            $query->where('fkTienda',$fkTienda);
        })->where('estado',1)
        ->where('ClaveVista','DC')->get();
            // Si el estatus es 'ER', cargar todos los productos
    if ($Estatus == 'ER') {
        $productos = Producto::where('estado',1)->get();
    } else {
        // Filtrar los productos solo por la tienda del usuario
        $productos = Producto::whereHas('tienda', function($query) use ($fkTienda) {
            $query->where('fkTienda', $fkTienda);
        })->where('estado', 1)->get();
    }
        return view('compra.create',compact('proveedores','comprobantes','productos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompraRequest $request)
    {
        try{
            DB::beginTransaction();
            $fkTienda = session('user_fkTienda');
            //1.Recuperar los arrays
            $arrayProducto_id = $request->get('arrayidproducto');
            $arrayCantidad = $request->get('arraycantidad');
            $arrayPrecioCompra = $request->get('arraypreciocompra');
            $arrayPrecioVenta = $request->get('arrayprecioventa');
            $arraysubiva = $request->get('arraysubiva');
            $total=$request->total;
            $proveedor_id=$request->proveedore_id;
            $comprobante_id=$request->comprobante_id;
            $numero_comprobante=$request->numero_comprobante;
            $impuestotal=$request->impuesto;
            $fecha=$request->fecha;
            $fecha_hora=$request->fecha_hora;

            //Llenar tabla compras
            $compra = Compra::create([
                'fecha_hora'=>$fecha_hora,
                'impuesto'=>$impuestotal,
                'numero_comprobante'=>$numero_comprobante,
                'total'=>$total,
                'estado'=>'I',
                'comprobante_id'=>$comprobante_id,
                'proveedore_id'=>$proveedor_id,
                'create_at'=>$fecha,
                'update_at'=>$fecha,
                'fkTienda'=>$fkTienda
            ]);

            //Llenar tabla compra_producto

            //2.Realizar el llenado
            $siseArray = count($arrayProducto_id);
            $cont = 0;
            while($cont < $siseArray){
                $compra->productos()->attach([
                    $arrayProducto_id[$cont] => [
                        'cantidad' => $arrayCantidad[$cont],
                        'precio_compra' => $arrayPrecioCompra[$cont],
                        'precio_venta' => $arrayPrecioVenta[$cont],
                        'impuesto'=>$arraysubiva[$cont],
                        'fkTienda'=>$fkTienda,
                        'Naturaleza'=>'D',
                        'Estado'=>'I'
                    ]
                ]);

                //3.Actualizar el stock
                $producto = Producto::find($arrayProducto_id[$cont]);
                $stockActual = $producto->stock;
                $stockNuevo = intval($arrayCantidad[$cont]);

                DB::table('productos')
                ->where('id',$producto->id)
                ->update([
                    'stock' => $stockActual + $stockNuevo
                ]);

                $cont++;


            }

            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
            dd($e->getMessage());
        }
        return redirect()->route('compras.index')->with('success','compra exitosa');

    }

    /**
     * Display the specified resource.
     */
    public function show(Compra $compra)
    {
        return view('compra.show',compact('compra'));
    }

    public function generarRecibo($arqueocaja)
{
    $fkTienda = session('user_fkTienda');
    $Tienda = Tienda::where('idTienda', $fkTienda)->first();
$arqueocaja = (int) $arqueocaja;

$plantilla = DB::table('compras as c')
    ->join('comprobantes as cm', 'c.comprobante_id', '=', 'cm.id')
    ->join('plantillahtml as ph', 'ph.id', '=', 'cm.fkPlantillaHtml')
    ->join('documentdesigns as dd', 'dd.id', '=', 'ph.fkDesignDocument')
    ->join('compra_producto as cp', 'cp.compra_id', '=', 'c.id')
    ->where('c.id', $arqueocaja)
    ->where('c.fkTienda', $fkTienda)
    ->select(
        'c.id as idcompra',
        'cp.id as idproducto',
        'cp.cantidad',
        'cp.precio_compra',
        'cp.precio_venta',
        'cp.producto_id',
        'dd.alto_pt',
        'dd.ancho_pt',
        'dd.orientacion_vertical',
        'cm.fkPlantillaHtml as idPlantilla',
        'ph.fkDesignDocument as fkDesignDocument',
        'ph.cabecera',
        'ph.detalle',
        'ph.pie',
        'ph.consulta',
        'c.fkTienda'
    )
    ->orderBy('cp.id', 'DESC')
    ->get();





    $cabecera = $plantilla->first()->cabecera;
    $detalle = $plantilla->first()->detalle;
    $pie = $plantilla->first()->pie;
    $consulta = $plantilla->first()->consulta;

    $tokens = ['idventa' => $plantilla->first()->idcompra, 'idtienda' => $fkTienda];
    $numFilas = $plantilla->count();

    // Si height_mm o width_mm es null, dar valor por defecto
    $altura = ($plantilla->first()->alto_pt ?? 205) + ($numFilas * 15);
    $ancho = $plantilla->first()->ancho_pt ?? 226.77;
    $orientacion = $plantilla->first()->orientation ?? 'portrait';

    $cons = $this->procesarConsulta($consulta, $tokens);
    $tokenss = $this->ejecutarconsulta($cons);

    $htmlFinal = $this->procesarPlantilla($cabecera, $detalle, $pie, $tokenss['columnas'], $tokenss['filas']);

    $pdf = Pdf::loadHTML($htmlFinal)->setPaper([0, 0, $ancho, $altura], $orientacion);

    // Crear carpeta si no existe
    $rutaCarpeta = storage_path('app/public/recibos');
    if (!file_exists($rutaCarpeta)) {
        mkdir($rutaCarpeta, 0777, true);
    }

    // Guardar PDF
    $rutaArchivo = $rutaCarpeta.'/recibocompra_'.$arqueocaja.'.pdf';
    $pdf->save($rutaArchivo);

    // Finalmente, abrir en el navegador
    return response()->file($rutaArchivo);
}

function procesarConsulta($consulta, $tokens)
{
    $consultaprocesada = $consulta;

    foreach ($tokens as $token => $valor) {
        $pattern = '/@{{\s*' . preg_quote($token, '/') . '\s*}}/';
        $consultaprocesada = preg_replace($pattern, $valor, $consultaprocesada);
    }

    return $consultaprocesada;
}
function procesarPlantilla($cab, $htmlDetalle, $pi, $variablesGlobales, $detalle)
{
    foreach ($variablesGlobales as $token => $valor) {

        $pattern = '/\{\{\s*' . preg_quote($valor, '/') . '\s*\}\}/';

        if($valor=="logo"){
            $compressed = $detalle[0][$valor];
            $compressed = trim($compressed);
            $compressed = str_replace(['"', "'"], '', $compressed);
            $compressed = preg_replace('/\s+/', '', $compressed);

       //     dd($compressed);
            $cab = preg_replace($pattern, $compressed, $cab);
        }
        else{
        $cab = preg_replace($pattern, $detalle[0][$valor], $cab);
        }
        $pi = preg_replace($pattern, $detalle[0][$valor], $pi);
    }
   $htmlFilas = "";

foreach ($detalle as $fila) {
    $row = $htmlDetalle;

    foreach ($variablesGlobales as $key => $value) {

        // Coincidir SOLO tokens como {{idventa}} (sin $)
        $pattern = '/{{\s*' . preg_quote($value, '/') . '\s*}}/';

        if (isset($fila[$value])) {
            $row = preg_replace($pattern, $fila[$value], $row);
        }
    }

    $htmlFilas .= $row;
}

    $htmlFinal = $cab . $htmlFilas . $pi;

    return $htmlFinal;
}

public function ejecutarconsulta($consulta)
    {
   $filas = DB::select($consulta);

    if (count($filas) == 0) {
        return [
            "columnas" => [],
            "filas" => []
        ];
    }

    // convierte los objetos stdClass en arrays
    $filasArray = array_map(function ($row) {
        return (array) $row;
    }, $filas);

    // columnas = keys del primer registro
    $columnas = array_keys($filasArray[0]);

    return [
        "columnas" => $columnas,
        "filas"    => $filasArray
    ];
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function Lista()
    {
        // Aquí ejecutamos la consulta
        $clientes = Compra::join('personas as p', 'clientes.persona_id', '=', 'p.id')
                           ->select('clientes.id', 'p.razon_social as nombre')
                           ->get();

        // Verifica si obtenemos resultados
        return response()->json($clientes);
    }
    public function update(Request $request, string $id)
    {
        //
    }


    public function mostrarDetalles($idComprobante)
    {
        // Consultar los detalles del comprobante junto con las cuentas contables
        $detalles = DB::table('detalle_comprobantes as dc')
            ->join('cuentas_contables as cc', 'dc.fkCuentaContable', '=', 'cc.id')
            ->join('comprobantes as c','c.id','=','dc.fkComprobante')
            ->where('dc.fkComprobante', $idComprobante)
            ->select(
                'cc.nombre as cuenta_contable_nombre',
                'dc.Naturaleza',
                'dc.valorminimo',
                'dc.formula',
                'c.formula as formuladoc',
                'cc.id'
            )
            ->get();

        // Devolver los detalles como respuesta JSON
        return response()->json([
            'detalles' => $detalles
        ]);
    }

        public function mostrarDetallesScanner($SKU)
    {
        $fkTienda = session('user_fkTienda');
                $pdo = DB::getPdo();
        $stmt = $pdo->prepare("
    SELECT
        p.nombre AS producto_nombre,
        cp.cantidad,
        cp.precio_venta,
        p.codigo AS producto_codigo,
        p.stock as existencia,
        p.descripcion,
        p.fecha_vencimiento,
        p.img_path as imagen_producto,
        p.id as producto_id
    FROM
        compras as c
    INNER JOIN
        compra_producto as cp ON c.id = cp.compra_id
    INNER JOIN
        productos as p ON cp.producto_id = p.id
    WHERE
        p.codigo = :id and c.fkTienda = $fkTienda AND c.estado = 1
");

$stmt->execute(['id' => $SKU]);


        $producto = $stmt->fetchAll(\PDO::FETCH_ASSOC);


return response()->json($producto);
    }


    public function destroy(string $id)
    {
        Compra::where('id',$id)
        ->update([
            'estado' => 0
        ]);

        return redirect()->route('compras.index')->with('success','Compra eliminada');
    }
}
