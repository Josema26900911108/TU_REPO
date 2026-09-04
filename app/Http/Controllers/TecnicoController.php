<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller; // Asegúrate de que esta línea esté presente

use Illuminate\Http\Request;
use App\Http\Requests\UpdateTecnicoRequest;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Documento;
use App\Models\MovimientoMaterial;
use App\Models\Expedientetecnico;
use App\Models\Persona;
use App\Models\Pagotecnico;
use App\Models\Tienda;
use App\Models\Expedientefotograficotecnico;
use App\Models\MovimientoMateriales;
use App\Models\Producto;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Spatie\Permission\Models\Role;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use App\Models\Tecnico;
use App\Models\usuariotienda;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\Paginator;
use PhpParser\Node\Expr\BinaryOp\Mod;
use Yajra\DataTables\DataTables;
use App\Models\Materialmanoobra;
use App\Models\Arbmanoobra;
use App\Models\Treematerialescategoria;
use ZipArchive;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use App\Imports\ExpedienteImport;
use Maatwebsite\Excel\Facades\Excel; 

class TecnicoController extends Controller
{
    public function __construct()
    {
        // Aplicar middleware de permisos
        $this->middleware('permission:ver-tecnico', ['only' => ['index']]);
        $this->middleware('permission:crear-tecnico', ['only' => ['create', 'store', 'exist']]);
        $this->middleware('permission:editar-tecnico', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-tecnico', ['only' => ['destroy']]);

    }
    public function boot(): void
{
    Paginator::useBootstrap();
}



    public function index()
    {
        DB::connection()->disableQueryLog();

                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

            $sql = "SELECT t.id,
			t.fkTienda,
            td.Nombre as Tienda,
            p.razon_social as tecnico,
            t.especialidad,
            t.codigo FROM
            tecnico as t inner join personas as p
				on p.id=t.fkpersona
            inner join tienda as td
				on td.idTienda=t.fkTienda ";

                if ($Estatus == 'ER') {

                    $sql .= "";

                } else {
                    $sql .= " where t.fkTienda= ".$fkTienda ;

                }
            $parametros=['id'=>''];
            $tecnicos=$this->obtenerdetalles($sql,$parametros);

        return view('tecnico.index', compact('tecnicos'));
    }

public function BucketOrdenes(Request $request, $usbucket = null)
{
    DB::connection()->disableQueryLog();
                  
    if(!Auth::check()){
            return redirect()->route('login');
        }
    

        return view('buckettecnico.bucketgeneral');


}

  public function cambiarEstatus(Request $request)
    {
        // 1. Validar los datos recibidos del formulario/modal
        $request->validate([
            'id' => 'required|exists:expedientetecnico,id',
            'estatus' => 'required|string|max:1', // Ajusta según la longitud de tus códigos de estatus
            'comentario' => 'nullable|string|max:255', // Ajusta según la longitud máxima de tu campo de comentario
        ]);

        try {
            // 2. Buscar el expediente técnico por su ID
            $expediente = Expedientetecnico::findOrFail($request->id);

            // 3. Validación de seguridad en servidor: Impedir cambios si ya está en estatus 'C'
            if ($expediente->ESTATUS === 'C') {
                return redirect()->back()->with('error', 'No es posible modificar el estatus de un expediente que ya se encuentra en estatus C.');
            }

            // 4. Asignar y guardar el nuevo estatus (mapeado a la columna exacta de tu modelo)
            $expediente->ESTATUS = $request->estatus;
            if($request->estatus === 'B') {
                $expediente->Status = 'A'; // Asignar 'A' a la columna Status si el nuevo estatus es 'B'
            } else if($request->estatus === 'I') {
                $expediente->Status = 'I';
            } else{
                $expediente->Status = 'S';
                $expediente->ESTATUS = 'I';
            }
            if(!empty($request->comentario)) {
                $expediente->OBS = $expediente->OBS . " - Comentario Administrativo: " .$request->comentario;
            }
            
            $expediente->save();

            // 5. Redireccionar con un mensaje de éxito
            return redirect()->back()->with('success', 'El estatus del expediente se actualizó correctamente.');

        } catch (\Exception $e) {
            // Manejo de errores en caso de fallo en la base de datos
            return redirect()->back()->with('error', 'Ocurrió un error al intentar actualizar el estatus: ' . $e->getMessage());
        }
    }

public function importarExpedientes(Request $request)
{
    // Validar que realmente se suba un archivo de Excel o CSV
    $request->validate([
        'archivo' => 'required|mimes:xlsx,xls,csv,txt|max:10240',
    ]);

    try {
        // 1. Instanciamos la clase de importación de forma manual
        $importador = new ExpedienteImport;

        // 2. Ejecutar la importación pasándole nuestra instancia
        Excel::import($importador, $request->file('archivo'));

        // 3. 🛠️ SI ENCONTRÓ ERRORES, GENERA Y DESCARGA EL ARCHIVO CSV DE CONTROL
        if (count($importador->erroresReportados) > 0) {
            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=Errores_Masivo_Expedientes_" . date('Ymd_His') . ".csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $callback = function () use ($importador) {
                $output = fopen('php://output', 'w');
                // Agregar BOM UTF-8 para Excel
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Cabeceras del reporte
                fputcsv($output, ['No. Fila en Excel Original', 'Orden Relacionada', 'Descripción del Error']);
                
                foreach ($importador->erroresReportados as $error) {
                    fputcsv($output, [$error['fila'], $error['orden'], $error['motivo']]);
                }
                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        }

        return redirect()->back()->with('success', 'Los expedientes se importaron y asociaron a sus técnicos correctamente sin discrepancias.');

    } catch (\Exception $e) {
        Log::error('Error masivo crítico: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error al procesar el archivo masivo: ' . $e->getMessage());
    }
}


public function exportarExcel(Request $request)
{
    // 1. Capturar los mismos filtros que envía tu función de JavaScript
    $fkTienda = session('user_fkTienda');
    $fechain = $request->input('fechain');
    $fechafin = $request->input('fechafin');
    $search = $request->input('search');

    // 2. Consulta idéntica sin paginar (con el INNER JOIN correspondiente)
    $query = Expedientetecnico::join('tecnico', 'expedientetecnico.fkTecnico', '=', 'tecnico.id')
        ->where('expedientetecnico.fkTienda', $fkTienda);

    // Filtro por Técnico (Si aplica)
    if (!empty($idTecnico)) {
        $query->where('tecnico.id', $idTecnico);
    }

    // Filtro por Rango de Fechas (Si aplica)
    if (!empty($fechain) && !empty($fechafin)) {
        $query->whereBetween('expedientetecnico.FECHAINSTALACION', [$fechain . ' 00:00:00', $fechafin . ' 23:59:59']);
    }

    // Buscador General (Filtra en múltiples columnas en la Base de Datos)
    if (!empty($search)) {
        $query->where(function($q) use ($search) {
            $q->where('expedientetecnico.Orden', 'LIKE', "%{$search}%")
              ->orWhere('expedientetecnico.virtual', 'LIKE', "%{$search}%")
              ->orWhere('expedientetecnico.NOMBRECLIENTE', 'LIKE', "%{$search}%")
              ->orWhere('expedientetecnico.DIRECCION', 'LIKE', "%{$search}%")
              ->orWhere('expedientetecnico.ESTATUS',$search)
              ->orWhere('tecnico.nombre', 'LIKE', "%{$search}%")
              ->orWhere('tecnico.codigo', 'LIKE', "%{$search}%");
        });
    }

    // Seleccionamos los campos asegurando evitar colisiones de llaves duplicadas
    $query->select(
        'expedientetecnico.*', 
        'tecnico.nombre as nombre_tecnico', 
        'tecnico.codigo as codigo_tecnico'
    );

    // 3. Configurar cabeceras de descarga HTTP para Excel/CSV
    $fileName = 'Reporte_Ordenes_Bucket_' . date('Y-m-d_H-i') . '.csv';
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // 4. Generar el archivo en streaming línea por línea (Cuidado de la RAM)
    $callback = function() use($query) {
        $file = fopen('php://output', 'w');
        
        // Agregar BOM UTF-8 para que Excel reconozca tildes y eñes correctamente
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabeceras de las columnas del reporte de Órdenes
        fputcsv($file, [
            'Orden', 
            'Virtual', 
            'Estatus', 
            'Tipo Servicio', 
            'Tipo Orden', 
            'Cliente', 
            'Tecnico', 
            'Codigo Tecnico', 
            'Direccion', 
            'Observaciones', 
            'Siglas Central', 
            'Area', 
            'Fecha Instalacion'
        ]);

        // Procesar los registros en bloques de 1000 (Chunking) para no colapsar el servidor
        $query->chunk(1000, function($registros) use($file) {
            foreach ($registros as $row) {
                fputcsv($file, [
                    $row->Orden ?? $row->id,
                    $row->virtual ?? 'N/A',
                    $row->ESTATUS ?? 'Pendiente',
                    $row->Tipo_servicio ?? 'N/A',
                    $row->Tipo_orden ?? 'N/A',
                    $row->NOMBRECLIENTE ?? 'N/A',
                    $row->nombre_tecnico ?? 'N/A',
                    $row->codigo_tecnico ?? 'N/A',
                    $row->DIRECCION ?? 'N/A',
                    $row->OBS ?? 'Sin observaciones',
                    $row->SIGLASCENTRAL ?? 'N/A',
                    $row->AREA ?? 'N/A',
                    $row->FECHAINSTALACION ?? 'N/A'
                ]);
            }
        });

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function RelacionBucketOrdenes(Request $request, $usbucket = null)
{
    DB::connection()->disableQueryLog();
    
    // Inicializamos las variables principales
    $idtecnico = null; 

    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        
        // 1. Rescate de parámetros de identificación y tiempo alineados con tu vista

        $fechain = $request->input('fechain');
        $fechafin = $request->input('fechafin');
        
        // 2. Captura de los nuevos inputs de búsqueda global e individual por columna
        $searchGlobal  = $request->input('search');        

        // 3. Consulta base incorporando el INNER JOIN con la tabla de técnicos
        $query = Expedientetecnico::join('tecnico', 'expedientetecnico.fkTecnico', '=', 'tecnico.id')
            ->where('expedientetecnico.fkTienda', $fkTienda);


        // 5. Filtro base por Rango de Fechas (si se proporcionan)
        if (!empty($fechain) && !empty($fechafin)) {
            $query->whereBetween('expedientetecnico.FECHAINSTALACION', [$fechain . ' 00:00:00', $fechafin . ' 23:59:59']);
        }

     // 6. BUSCADOR GENERAL (Filtra en múltiples columnas en la Base de Datos)
if (!empty($searchGlobal)) {
    // Si el usuario escribe exactamente 1 carácter, realiza el filtro especializado por ESTATUS
    if (strlen($searchGlobal) == 1) {
        $query->where('expedientetecnico.ESTATUS', '=', $searchGlobal);
    } else {
        // Si escribe más de un carácter, busca normalmente de forma global
        $query->where(function($q) use ($searchGlobal) {
            $q->where('expedientetecnico.Orden', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.virtual', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.NOMBRECLIENTE', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.DIRECCION', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.Tipo_servicio', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.Tipo_orden', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.SIGLASCENTRAL', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.OBS', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.AREA', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('expedientetecnico.TECNOLOGIA', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('tecnico.nombre', 'LIKE', "%{$searchGlobal}%")
              ->orWhere('tecnico.codigo', 'LIKE', "%{$searchGlobal}%");
        });
    }
}


        // 8. Selección de campos evitando colisiones de llaves duplicadas e incorporando la Paginación (15 registros)
        $relacion = $query->select(
            'expedientetecnico.*', 
            'tecnico.nombre as nombre_tecnico', 
            'tecnico.codigo as codigo_tecnico'
        )
        ->paginate(15)
        ->appends($request->all()); // Mantiene todos los filtros activos al cambiar de página

        // 9. Retorno condicional si la petición es AJAX o carga inicial de vista
        if ($request->ajax()) {
            
            return view('buckettecnico.table.buckettable', compact('relacion'))->render();
        }

        return view('buckettecnico.table.buckettable', compact('relacion'));

    } catch (\Exception $e) {
        // En caso de fallo, capturamos el error en formato JSON si es AJAX o retornamos vista controlada
        if ($request->ajax()) {
            return response()->json(['error' => 'Error al filtrar: ' . $e->getMessage()], 500);
        }
        
        $relacion = collect(); 
        return view('buckettecnico.table.buckettable', compact('relacion'))->withErrors(['error' => $e->getMessage()]);
    }
}


public function InsertarMaterialesTecnico($id)
{
    // 1. Validar que el usuario esté autenticado
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    // 2. Validar que el técnico exista
    $tecnico = Tecnico::find($id);
    if (!$tecnico) {
        return back()->with('error', 'El técnico especificado no existe.');
    }

    // 3. Obtener los materiales asociados al técnico
    $materiales = MovimientoMateriales::where('contrata', $id)->get();

    // 4. Retornar la vista con los materiales del técnico
    return view('buckettecnico.trasladarmaterial', compact('tecnico', 'materiales'));

}

public function buscarMaterialesFlexibles(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'No autorizado'], 401);
    }

    $fkTienda = session('user_fkTienda');
    
    // Captura de parámetros del frontend
    $tipoOrigen = $request->input('origen'); // 'raiz' o 'bodega'
    $almacenSeleccionado = $request->input('almacen'); // Código o nombre de la bodega/almacén
    $criterioBusqueda = trim($request->input('buscar')); // Puede ser el SKU o la SERIE

    if (empty($criterioBusqueda)) {
        return response()->json(['materiales' => []], 200);
    }

    // Determinar de forma automática si es SKU o SERIE por patrón alfanumérico o longitud
    // (Si contiene letras o tiene más de 9 caracteres, asumimos que es una Serie)
    $esSerie = preg_match('/[A-Za-z]/', $criterioBusqueda) || strlen($criterioBusqueda) > 9;

    // =========================================================================
    // 🔹 ESCENARIO A: BÚSQUEDA A NIVEL RAÍZ (Tabla SAP: materialexistentesap)
    // =========================================================================
    if ($tipoOrigen === 'raiz') {
        
        $query = DB::table('materialexistentesap')
            ->where('fkTienda', $fkTienda);

        // Aplicar filtro adaptativo según el criterio detectado
        if ($esSerie) {
            $query->where('serie', $criterioBusqueda);
        } else {
            $query->where('SKU', $criterioBusqueda);
        }

        // Si el usuario especificó un almacén raíz en particular
        if (!empty($almacenSeleccionado)) {
            $query->where('almacen', $almacenSeleccionado);
        }

        $resultados = $query->select(
                'id',
                'SKU as sku',
                'serie',
                'Lote as lote',
                'almacen',
                'CENTRO as centro',
                'ESTATUS as estatus',
                'TIPO as tipo_material',
                'unidadmedida as unidad_medida',
                'COSTO as costo_unitario',
                DB::raw('COALESCE(cantidad, 1) as cantidad_disponible')
            )
            ->get();

        return response()->json(['origen' => 'raiz', 'materiales' => $resultados], 200);
    }

    // =========================================================================
    // 🔹 ESCENARIO B: BÚSQUEDA EN BODEGA ESPECÍFICA (Tabla: movimiento_materiales)
    // =========================================================================
    else {
        // En el Kardex calculamos el stock remanente real:
        // Entradas (clases de ingreso, compras, traslados positivos) sumando, y salidas restando.
        // Si el campo clase_movimiento define el signo, adaptamos con un condicional contable CASE WHEN.
        
        $query = DB::table('movimiento_materiales')
            ->join('productos', 'movimiento_materiales.fkMateriales', '=', 'productos.id')
            ->where('movimiento_materiales.fkTienda', $fkTienda);

        // Filtrar obligatoriamente por la bodega/almacén seleccionado
        if (!empty($almacenSeleccionado)) {
            $query->where('movimiento_materiales.almacen', $almacenSeleccionado);
        }

        // Aplicar el buscador híbrido SKU / SERIE conectando al catálogo o al lote
        if ($esSerie) {
            // Si es serie, buscamos la serie a través del documento o el lote asignado al movimiento
            $query->where('movimiento_materiales.documento_material', 'LIKE', '%' . $criterioBusqueda . '%')
                  ->orWhere('movimiento_materiales.referencia', 'LIKE', '%' . $criterioBusqueda . '%');
        } else {
            // Si es misceláneo, buscamos directo por el código SKU del catálogo de productos
            $query->where('productos.codigo', $criterioBusqueda)
                  ->orWhere('movimiento_materiales.fkMateriales', $criterioBusqueda);
        }

        // Agrupamos por producto y almacén para consolidar los saldos netos contables
        $resultados = $query->select(
                'movimiento_materiales.fkMateriales as id',
                'productos.codigo as sku',
                'productos.nombre as descripcion_material',
                'movimiento_materiales.almacen',
                'movimiento_materiales.centro',
                'movimiento_materiales.unidad_medida_base as unidad_medida',
                // 🔥 MATEMÁTICA KARDEX: Sumamos entradas y restamos salidas según la clase de movimiento de tu negocio
                DB::raw("SUM(
                    CASE 
                        WHEN movimiento_materiales.clase_movimiento IN ('641', '101', '501', 'saldo_inicial') THEN movimiento_materiales.cantidad
                        WHEN movimiento_materiales.clase_movimiento IN ('642', '201', '601') THEN -movimiento_materiales.cantidad
                        ELSE movimiento_materiales.cantidad 
                    END
                ) as cantidad_disponible")
            )
            ->groupBy(
                'movimiento_materiales.fkMateriales', 
                'productos.codigo', 
                'productos.nombre', 
                'movimiento_materiales.almacen', 
                'movimiento_materiales.centro',
                'movimiento_materiales.unidad_medida_base'
            )
            // Filtramos para mostrar únicamente materiales que tengan existencias reales en la bodega
            ->having('cantidad_disponible', '>', 0)
            ->get();

        return response()->json(['origen' => 'bodega', 'materiales' => $resultados], 200);
    }
}
public function generarMemoriaFotografica(Request $request)
{
    // 1. Validar la existencia del archivo cargado
    if (!$request->hasFile('excel_ordenes')) {
        return back()->with('error', 'No se recibió ningún archivo de órdenes.');
    }

    $file = $request->file('excel_ordenes');
    $path = $file->getRealPath();
    $ordenesRaw = [];
    
    try {
        $spreadsheetLoad = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $worksheetLoad = $spreadsheetLoad->getActiveSheet();
        $highestRow = $worksheetLoad->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $valorCelda = $worksheetLoad->getCell('A' . $row)->getCalculatedValue();
            $valorCelda = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $valorCelda));
            if ($valorCelda !== '' && !is_null($valorCelda)) {
                $ordenesRaw[] = (string)$valorCelda;
            }
        }
    } catch (\Exception $e) {
        return back()->with('error', 'Error al leer el archivo de órdenes: ' . $e->getMessage());
    }

    $ordenes = array_values(array_unique($ordenesRaw));
    if (empty($ordenes)) {
        return back()->with('error', 'El archivo no contiene órdenes legibles.');
    }

    // Palabras reservadas solicitadas para la auditoría de nombres
    // Palabras reservadas solicitadas en MAYÚSCULAS
    $palabrasClave = ['ANTENA', 'CONECTIVIDAD', 'MASTIL', 'SWITCH', 'POSTE_ANTES', 'POSTE_DESPUES', 'ANILLO_POSTES', 'ONT', 'OTT'];


    // Consultar el universo fotográfico cruzando con el árbol de tecnología
    $fotografiasUniverso = DB::table('expedientefotograficotecnico as ef')
        ->leftJoin('arbolmaterial as am', 'ef.fkTecnologia', '=', 'am.id')
        ->whereIn('ef.Orden', $ordenes)
        ->select(['ef.*', 'am.nombre as nombre_tecnologia'])
        ->get();

    if ($fotografiasUniverso->isEmpty()) {
        return back()->with('error', 'No se encontraron evidencias fotográficas para las órdenes suministradas.');
    }

    $fotosPorTecnologia = $fotografiasUniverso->groupBy('nombre_tecnologia');

    $zipFileName = 'Memorias_Fotograficas_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;
    $zip = new ZipArchive;
    $imagenesTemporalesABorrar = [];

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return back()->with('error', 'No se pudo inicializar el empaquetador ZIP.');
    }
        $nombreBucket = 'sistema-pv-imagenes-tienda';

    foreach ($fotosPorTecnologia as $tecnologiaNombre => $fotosTecnologia) {
        
        $techClean = empty($tecnologiaNombre) ? 'OTRAS_TECNOLOGIAS' : str_replace(['/', '\\', '?', '*', ':', '[', ']'], '_', $tecnologiaNombre);
        $spreadsheet = new Spreadsheet();
        $sheetIndex = 0;

        foreach ($palabrasClave as $palabra) {
            
            // Filtrar las fotos por coincidencia con la palabra clave
            $fotosFiltradas = $fotosTecnologia->filter(function ($f) use ($palabra) {
                    return str_contains(strtoupper($f->fotografia), $palabra);
            });

            if ($fotosFiltradas->isEmpty()) {
                continue;
            }

            if ($sheetIndex === 0) {
                $sheet = $spreadsheet->getActiveSheet();
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $tituloPestana = substr(ucwords($palabra) . ' ' . strtoupper($techClean), 0, 31);
            $sheet->setTitle($tituloPestana);

            // --- TITULO SUPERIOR DE LA MEMORIA ---
            $sheet->mergeCells('B2:L2');
            $sheet->setCellValue('B2', 'MEMORIA FOTOGRAFICA');
            $sheet->getStyle('B2')->getFont()->setBold(true)->setSize(14)->setName('Arial');
            $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('B3:L3');
            $sheet->setCellValue('B3', strtoupper($palabra) . ' - SERVICIOS ' . strtoupper($techClean));
            $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(11)->setName('Arial');
            $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Bloque Informativo de la Obra (Fila 5 a Fila 8)
            $sheet->mergeCells('B5:L5');
            $sheet->setCellValue('B5', 'DATOS DE LA OBRA:');
            $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(10)->setName('Arial');
            $sheet->getStyle('B5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('000000');
            $sheet->getStyle('B5')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));

            $sheet->setCellValue('B6', 'DIVISION:');             $sheet->setCellValue('C6', strtoupper($techClean));
            $sheet->getStyle('C6')->getFont()->setUnderline(true);
            $sheet->setCellValue('B7', 'NOMBRE DEL COORDINADOR:');  $sheet->setCellValue('C7', 'ERICK RIVAS');
            $sheet->getStyle('C7')->getFont()->setUnderline(true);
            $sheet->setCellValue('B8', 'NOMBRE DEL CONTRATISTA:');  $sheet->setCellValue('C8', 'LGB OCCIDENTE');
            $sheet->getStyle('C8')->getFont()->setUnderline(true);
            
            $sheet->setCellValue('E6', 'AREA:');                 $sheet->setCellValue('F6', 'OCCIDENTE');
            $sheet->getStyle('F6')->getFont()->setUnderline(true);
            $sheet->setCellValue('G6', 'TIPO DE OBRA:');          $sheet->setCellValue('H6', 'INSTALACION DE ANTENAS');
            $sheet->getStyle('H6')->getFont()->setUnderline(true);

            $sheet->setCellValue('G7', 'FECHA INICIO:');          $sheet->setCellValue('H7', '01/04/2026');
            $sheet->getStyle('H7')->getFont()->setUnderline(true);
            $sheet->setCellValue('G8', 'FECHA TERMINACION:');     $sheet->setCellValue('H8', '30/04/2026');
            $sheet->getStyle('H8')->getFont()->setUnderline(true);
            
            $sheet->getStyle('B6:B8')->getFont()->setBold(true)->setSize(9)->setName('Arial');
            $sheet->getStyle('E6')->getFont()->setBold(true)->setSize(9)->setName('Arial');
            $sheet->getStyle('G6:G8')->getFont()->setBold(true)->setSize(9)->setName('Arial');
            
            // Aplicar contorno externo al bloque superior
            $sheet->getStyle('B5:L8')->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                        // --- GEOMETRÍA HORIZONTAL DE LOS 3 BLOQUES FOTOGRÁFICOS ---
            $bloquesX = [
                ['inicio' => 'B', 'medio' => 'C', 'fin' => 'D'], 
                ['inicio' => 'F', 'medio' => 'G', 'fin' => 'H'], 
                ['inicio' => 'J', 'medio' => 'K', 'fin' => 'L']  
            ];
            
            $fotoIndex = 0;
            $filaBaseFotos = 11; // La primera hilera arranca en la fila 11 exacta de la imagen

            foreach ($fotosFiltradas as $fotoItem) {
                
                $subColIndex = $fotoIndex % 3;
                $lineaMultiplo = floor($fotoIndex / 3);
                
                // Cada bloque de imágenes abarca 16 celdas de alto + 2 de espacio inferior = salto de 18
                $filaImagenInicio = $filaBaseFotos + ($lineaMultiplo * 18);
                $filaImagenFin    = $filaImagenInicio + 15; 
                $filaOrdenTexto   = $filaImagenFin + 2;     // Se posiciona en la fila 28 de la primera iteración

                $colLetras = $bloquesX[$subColIndex];
                $colIni = $colLetras['inicio'];
                $colMed = $colLetras['medio'];
                $colFin = $colLetras['fin'];

                // FUSIONAR CELDAS PARA CONTENER LA IMAGEN
                $sheet->mergeCells("{$colIni}{$filaImagenInicio}:{$colFin}{$filaImagenFin}");

                // Descarga binaria desde el Bucket de Google
                $urlCompleta = $fotoItem->fotografia;
                $pathBucket = $urlCompleta;

                if (str_contains($urlCompleta, $nombreBucket)) {
                    $posicionBucket = strpos($urlCompleta, $nombreBucket);
                    $pathBucket = substr($urlCompleta, $posicionBucket + strlen($nombreBucket));
                    $pathBucket = ltrim($pathBucket, '/');
                } else {
                    $pathBucket = ltrim(parse_url($urlCompleta, PHP_URL_PATH), '/');
                }

                if (Storage::disk('gcs_images')->exists($pathBucket)) {
                    $imageBinary = Storage::disk('gcs_images')->get($pathBucket);
                    $tempImageName = 'temp_img_' . uniqid() . '.jpg';
                    $tempImagePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempImageName;
                    
                    file_put_contents($tempImagePath, $imageBinary);

                    // Configurar el motor de dibujo e inyectarlo centrado en el merge
                    $drawing = new Drawing();
                    $drawing->setName('Evidencia_' . $fotoItem->Orden);
                    $drawing->setPath($tempImagePath);
                    $drawing->setHeight(230); // Alto idóneo para llenar las 16 filas verticales proporcionalmente
                    $drawing->setCoordinates($colIni . $filaImagenInicio);
                    $drawing->setOffsetX(15);
                    $drawing->setOffsetY(10);
                    $drawing->setWorksheet($sheet);

                    $imagenesTemporalesABorrar[] = $tempImagePath;
                }

                // Aplicar el marco de borde negro alrededor de la celda de la imagen
                $sheet->getStyle("{$colIni}{$filaImagenInicio}:{$colFin}{$filaImagenFin}")
                      ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                // --- DISEÑO PIE DE FOTO: CAJA DE LA ORDEN ---
                // Celda 1: Etiqueta "ORDEN"
                $sheet->setCellValue($colIni . $filaOrdenTexto, 'ORDEN');
                $sheet->getStyle($colIni . $filaOrdenTexto)->getFont()->setBold(true)->setSize(9)->setName('Arial');
                $sheet->getStyle($colIni . $filaOrdenTexto)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle($colIni . $filaOrdenTexto)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                // Celda 2 y 3 fusionadas: Número correlativo de la Orden técnica
                $sheet->mergeCells("{$colMed}{$filaOrdenTexto}:{$colFin}{$filaOrdenTexto}");
                $sheet->setCellValue("{$colMed}{$filaOrdenTexto}", $fotoItem->Orden);
                $sheet->getStyle("{$colMed}{$filaOrdenTexto}")->getFont()->setSize(9)->setName('Arial');
                $sheet->getStyle("{$colMed}{$filaOrdenTexto}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$colMed}{$filaOrdenTexto}:{$colFin}{$filaOrdenTexto}")
                      ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                $fotoIndex++;
            }

            // --- FORMATEO EXPENDIDO DE ANCHOS DE COLUMNA (Efecto de Calles Separadoras) ---
            $sheet->getColumnDimension('A')->setWidth(4);  // Margen izquierdo
            $sheet->getColumnDimension('B')->setWidth(12); $sheet->getColumnDimension('C')->setWidth(12); $sheet->getColumnDimension('D')->setWidth(12); // Bloque 1
            $sheet->getColumnDimension('E')->setWidth(4);  // Calle intermedia
            $sheet->getColumnDimension('F')->setWidth(12); $sheet->getColumnDimension('G')->setWidth(12); $sheet->getColumnDimension('H')->setWidth(12); // Bloque 2
            $sheet->getColumnDimension('I')->setWidth(4);  // Calle intermedia
            $sheet->getColumnDimension('J')->setWidth(12); $sheet->getColumnDimension('K')->setWidth(12); $sheet->getColumnDimension('L')->setWidth(12); // Bloque 3
            $sheet->getColumnDimension('M')->setWidth(4);  // Margen derecho
            
            $sheetIndex++;
        }

        // Si la tecnología generó reportes válidos, guardar el archivo .xlsx
        if ($sheetIndex > 0) {
            $writer = new Xlsx($spreadsheet);
            $excelPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Memoria_Fotografica_' . $techClean . '.xlsx';
            $writer->save($excelPath);
            $zip->addFile($excelPath, 'Memoria_Fotografica_' . $techClean . '.xlsx');
        }
    }
    $zip->close();

    // Eliminar de la carpeta temporal los binarios residuales de imágenes utilizados para calcular dimensiones
    if (!empty($imagenesTemporalesABorrar)) {
        foreach ($imagenesTemporalesABorrar as $p) {
            if (file_exists($p)) { 
                @unlink($p); 
            }
        }
    }

    // Validación final de existencia física del empaquetado final
    if (!file_exists($zipPath) || filesize($zipPath) <= 22) {
        @unlink($zipPath);
        return back()->with('error', 'No se generaron memorias fotográficas. Ninguna imagen cumplió con las palabras clave.');
    }

    // Enviar el archivo binario comprimido al navegador y purgarlo de XAMPP tras finalizar la transferencia
    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
}


    public function extraccionMasiva(Request $request)
    {
        // 1. Validar la existencia del archivo cargado
        if (!$request->hasFile('excel_ordenes')) {
            return back()->with('error', 'No se recibió ningún archivo en el servidor.');
        }

        $file = $request->file('excel_ordenes');
        $path = $file->getRealPath();
        $ordenesRaw = [];
        
        try {
            $spreadsheet = IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {
                $valorCelda = $worksheet->getCell('A' . $row)->getCalculatedValue();
                $valorCelda = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $valorCelda));

                if ($valorCelda !== '' && !is_null($valorCelda)) {
                    $ordenesRaw[] = (string)$valorCelda;
                }
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error al leer el formato del archivo Excel: ' . $e->getMessage());
        }

            // 3. Consultar Base de Datos mediante el Triple Cruce de Tablas usando la lista completa
    $registrosPagos = DB::table('pagotecnico')->whereIn('Orden', $ordenesRaw)->get();
    
    if ($registrosPagos->isEmpty()) {
        return back()->with('error', 'Ninguna de las órdenes ingresadas en tu archivo existe en la tabla pagotecnico.');
    }

    // Volver a mapear las órdenes que sí se encontraron para amarrar los expedientes y fotos
    $ordenesEncontradas = $registrosPagos->pluck('Orden')->unique()->toArray();

// 1. Procesamiento y limpieza del lote de órdenes recibidas
$ordenes = array_values(array_unique($ordenesEncontradas));
if (empty($ordenes)) {
    return back()->with('error', 'El archivo Excel no contiene órdenes legibles.');
}
$tiendaId = session('user_fkTienda');

// 2. Extraer el logotipo guardado en Base64 de la tienda
$tienda = DB::table('tienda')->where('idTienda', $tiendaId)->first();
$logoBase64Completo = null;
if ($tienda && !empty($tienda->logo)) {
    $logoBase64Completo = 'data:image/png;base64,' . trim($tienda->logo);
}

// 3. Extraer expedientes únicos aplicando DISTINCT
$expedientesRaw = DB::table('expedientetecnico as ex')
    ->leftJoin('tecnico as t', 'ex.fkTecnico', '=', 't.id')
    ->leftJoin('users as u', 't.nombre', '=', 'u.name')
    ->whereIn(DB::raw("SUBSTRING(REGEXP_REPLACE(ex.Orden, '[^0-9]', ''), 1, 8)"), $ordenes)
    ->where('ex.fkTienda', $tiendaId)
    ->select([
        'ex.id',  
        DB::raw("SUBSTRING(REGEXP_REPLACE(ex.Orden, '[^0-9]', ''), 1, 8) as Orden"), 
        'ex.virtual', 'ex.Tipo_orden', 'ex.Tipo_servicio',
        'ex.NOMBRECLIENTE', 'ex.DIRECCION', 'ex.FECHAINSTALACION', 'ex.OBS',
        'ex.firma_cliente', 'ex.SIGLASCENTRAL', 'ex.AREA',
        't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo',
        'u.firma as firma_usuario'
    ])
    ->distinct() 
    ->get();

if ($expedientesRaw->isEmpty()) {
    return back()->with('error', 'No se encontraron expedientes válidos.');
}

$expedientesIds = $expedientesRaw->pluck('id')->toArray();
$nombreBucket = 'sistema-pv-imagenes-tienda';


/* ===================================================================== */
/* 🚀 PASO A: SUBCONSULTA PARA LA TECNOLOGÍA FÍSICA REAL DE LA ORDEN      */
/* ===================================================================== */
$expresionTecnologiaReal = "COALESCE(
    MAX(CASE WHEN abmamPa.padre_id IS NULL OR abmamPa.padre_id = '' THEN abmamPa.nombre END),
    MAX(CASE WHEN abmamP.padre_id IS NULL OR abmamP.padre_id = '' THEN abmamP.nombre END),
    MAX(CASE WHEN abmam.padre_id IS NULL OR abmam.padre_id = '' THEN abmam.nombre END),
    MAX(abm_mat.nombre),
    'OTRAS_TECNOLOGIAS'
)";

$subqueryTecnologia = DB::table('movimientomateriales as mm_t')
    ->join('expedientetecnico as ex_t', 'ex_t.id', '=', 'mm_t.fkExpediente')
    ->leftJoin('arbolmaterial as abm_mat', 'mm_t.fkTecnologiaarbol', '=', 'abm_mat.id')
    ->join('arbolmanoobra as abmam', 'abmam.id', '=', 'mm_t.fkTecnologiaarbol')
    ->leftJoin('arbolmanoobra as abmamP', 'abmamP.id', '=', 'abmam.padre_id')
    ->leftJoin('arbolmanoobra as abmamPa', 'abmamPa.id', '=', 'abmamP.padre_id')
    ->join('MaterialManoObra as mamo_t', 'mm_t.SKU', '=', 'mamo_t.SKU')
    ->where('mamo_t.CATEGORIA', '!=', 'MANO DE OBRA')
    ->select([
        DB::raw("SUBSTRING(REGEXP_REPLACE(ex_t.Orden, '[^0-9]', ''), 1, 8) as orden_limpia"),
        DB::raw("{$expresionTecnologiaReal} as tecnologia_real")
    ])
    ->groupBy(DB::raw("SUBSTRING(REGEXP_REPLACE(ex_t.Orden, '[^0-9]', ''), 1, 8)"));


/* ===================================================================== */
/* 🚀 PASO B: RAMA 1 - MATERIALES FÍSICOS (Desde movimientomateriales)    */
/* ===================================================================== */
$queryMateriales = DB::table('movimientomateriales as mm')
    ->join('expedientetecnico as ex', 'ex.id', '=', 'mm.fkExpediente')
    ->leftJoin('tecnico as t', 'mm.fkTecnico', '=', 't.id')
    ->leftJoin('MaterialManoObra as mamo', function ($join) use ($tiendaId) {
        $join->on('mm.SKU', '=', 'mamo.SKU')
             ->where(function ($query) use ($tiendaId) {
                 $query->whereColumn('mamo.centrocostoespecifico', '=', 't.codigo') 
                       ->orWhere('mamo.centrocostoespecifico', '=', $tiendaId)    
                       ->orWhereNull('mamo.centrocostoespecifico')               
                       ->orWhere('mamo.centrocostoespecifico', '=', '');         
             });
    })
    ->leftJoinSub($subqueryTecnologia, 't_ord', function ($join) {
        $join->on('t_ord.orden_limpia', '=', DB::raw("SUBSTRING(REGEXP_REPLACE(ex.Orden, '[^0-9]', ''), 1, 8)"));
    })
    ->where('mm.fkTienda', $tiendaId)
    ->where('mamo.CATEGORIA', '!=', 'MANO DE OBRA')
    ->whereIn(DB::raw("SUBSTRING(REGEXP_REPLACE(ex.Orden, '[^0-9]', ''), 1, 8)"), $ordenes)
    ->select([
        DB::raw("COALESCE(MAX(CASE WHEN ex.firma_cliente IS NOT NULL THEN ex.id END), MAX(ex.id)) as expediente_id"),
        DB::raw("SUBSTRING(REGEXP_REPLACE(ex.Orden, '[^0-9]', ''), 1, 8) as orden_tecnica"),
        DB::raw("MAX(ex.virtual) as virtual"),
        DB::raw("MAX(ex.Status) as expediente_status"),
        DB::raw("MAX(ex.Tipo_servicio) as Tipo_servicio"),
        DB::raw("MAX(ex.Tipo_orden) as Tipo_orden"),
        DB::raw("MAX(ex.NOMBRECLIENTE) as NOMBRECLIENTE"),
        DB::raw("MAX(ex.DIRECCION) as DIRECCION"),
        DB::raw("MAX(ex.OBS) as expediente_obs"),
        DB::raw("MAX(ex.SIGLASCENTRAL) as SIGLASCENTRAL"),
        DB::raw("MAX(ex.AREA) as AREA"),
        DB::raw("MAX(ex.FECHAINSTALACION) as FECHAINSTALACION"),
        DB::raw("CASE 
            WHEN t_ord.tecnologia_real IS NOT NULL AND t_ord.tecnologia_real != 'OTRAS_TECNOLOGIAS' THEN t_ord.tecnologia_real
            WHEN MIN(mamo.Descripcion) LIKE '%DTH%' THEN 'DTH'
            WHEN MIN(mamo.Descripcion) LIKE '%WTT%' OR MIN(mamo.Descripcion) LIKE '%CPE%' THEN 'WTTx'
            WHEN MIN(mamo.Descripcion) LIKE '%GPON%' OR MIN(mamo.Descripcion) LIKE '%FIBRA%' THEN 'GPON'
            WHEN MIN(mamo.Descripcion) LIKE '%HFC%' OR MIN(mamo.Descripcion) LIKE '%COAXIAL%' THEN 'HFC'
            ELSE 'OTRAS_TECNOLOGIAS'
        END as Tecnologia"),
        DB::raw("MAX(mm.ESTATUS) as movimiento_estatus"),
        'mm.SKU',
        DB::raw("MIN(mamo.Descripcion) as Descripcion"),
        'mamo.TIPO',
        'mamo.CATEGORIA',
        DB::raw("MAX(mm.id) as movimiento_id"), 
        'mm.serie',
        /* 🛡️ ALIAS ASIGNADOS CORRECTAMENTE */
        DB::raw("MAX(t.nombre) as tecnico_nombre"), 
        DB::raw("MAX(t.codigo) as tecnico_codigo"), 
        DB::raw('SUM(mm.cantidad) as cantidad'),
        DB::raw("CAST(MAX(mamo.CATEGORIACOBRO) AS DECIMAL(10,2)) as COSTO"),
        DB::raw("(SUM(mm.cantidad) * MAX(mamo.CATEGORIACOBRO)) as Subtotal")
    ])
    ->groupBy([
        'mm.SKU', 'mm.serie', 'mamo.TIPO', 'mamo.CATEGORIA', 
        DB::raw("SUBSTRING(REGEXP_REPLACE(ex.Orden, '[^0-9]', ''), 1, 8)"), 
        't_ord.tecnologia_real'
    ]);


/* ===================================================================== */
/* 🚀 PASO C: RAMA 2 - MANO DE OBRA (Desde pagotecnico - CORREGIDO)       */
/* ===================================================================== */
$queryManoObra = DB::table('pagotecnico as pt')
    ->join('MaterialManoObra as mamo', 'pt.SKU', '=', 'mamo.SKU')
    ->leftJoin('expedientetecnico as ex', function($join) {
        $join->on(DB::raw("SUBSTRING(REGEXP_REPLACE(ex.Orden, '[^0-9]', ''), 1, 8)"), '=', DB::raw("SUBSTRING(REGEXP_REPLACE(pt.Orden, '[^0-9]', ''), 1, 8)"))
             ->where('ex.ESTATUS', '=', 'C');
    })
    ->leftJoin('tecnico as t_pt', 'pt.fkTecnico', '=', 't_pt.id')
    ->leftJoinSub($subqueryTecnologia, 't_ord', function ($join) {
        $join->on('t_ord.orden_limpia', '=', DB::raw("SUBSTRING(REGEXP_REPLACE(pt.Orden, '[^0-9]', ''), 1, 8)"));
    })
    ->leftJoin('arbolmanoobra as abmam_mo', 'abmam_mo.SKU', '=', 'pt.SKU') 
    ->leftJoin('arbolmanoobra as abmamP_mo', 'abmamP_mo.id', '=', 'abmam_mo.padre_id') 
    ->leftJoin('arbolmanoobra as abmamPa_mo', 'abmamPa_mo.id', '=', 'abmamP_mo.padre_id') 
    ->where('pt.fkTienda', $tiendaId)
    ->where('pt.Status', 'S')
    ->whereIn(DB::raw("SUBSTRING(REGEXP_REPLACE(pt.Orden, '[^0-9]', ''), 1, 8)"), $ordenes)
    ->select([
        DB::raw("COALESCE(MAX(CASE WHEN ex.firma_cliente IS NOT NULL THEN ex.id END), MAX(ex.id)) as expediente_id"),
        DB::raw("SUBSTRING(REGEXP_REPLACE(pt.Orden, '[^0-9]', ''), 1, 8) as orden_tecnica"),
        DB::raw("MAX(ex.virtual) as virtual"),
        DB::raw("MAX(ex.Status) as expediente_status"),
        DB::raw("MAX(ex.Tipo_servicio) as Tipo_servicio"),
        DB::raw("MAX(ex.Tipo_orden) as Tipo_orden"),
        DB::raw("MAX(ex.NOMBRECLIENTE) as NOMBRECLIENTE"),
        DB::raw("MAX(ex.DIRECCION) as DIRECCION"),
        DB::raw("MAX(ex.OBS) as expediente_obs"),
        DB::raw("MAX(ex.SIGLASCENTRAL) as SIGLASCENTRAL"),
        DB::raw("MAX(ex.AREA) as AREA"),
        DB::raw("MAX(ex.FECHAINSTALACION) as FECHAINSTALACION"),
        DB::raw("CASE 
            WHEN COALESCE(
                MAX(CASE WHEN abmamPa_mo.padre_id IS NULL OR abmamPa_mo.padre_id = '' THEN abmamPa_mo.nombre END),
                MAX(CASE WHEN abmamP_mo.padre_id IS NULL OR abmamP_mo.padre_id = '' THEN abmamP_mo.nombre END),
                MAX(CASE WHEN abmam_mo.padre_id IS NULL OR abmam_mo.padre_id = '' THEN abmam_mo.nombre END)
            ) IS NOT NULL THEN 
                COALESCE(
                    MAX(CASE WHEN abmamPa_mo.padre_id IS NULL OR abmamPa_mo.padre_id = '' THEN abmamPa_mo.nombre END),
                    MAX(CASE WHEN abmamP_mo.padre_id IS NULL OR abmamP_mo.padre_id = '' THEN abmamP_mo.nombre END),
                    MAX(CASE WHEN abmam_mo.padre_id IS NULL OR abmam_mo.padre_id = '' THEN abmam_mo.nombre END)
                )
            WHEN t_ord.tecnologia_real IS NOT NULL AND t_ord.tecnologia_real != 'OTRAS_TECNOLOGIAS' THEN t_ord.tecnologia_real
            WHEN UPPER(MIN(mamo.Descripcion)) LIKE '%WTT%' OR UPPER(MIN(mamo.Descripcion)) LIKE '%CPE%' THEN 'WTTx'
            WHEN UPPER(MIN(mamo.Descripcion)) LIKE '%DTH%' THEN 'DTH'
            WHEN UPPER(MIN(mamo.Descripcion)) LIKE '%XDSL%' OR UPPER(MIN(mamo.Descripcion)) LIKE '%ADSL%' OR UPPER(MIN(mamo.Descripcion)) LIKE '%VDSL%' THEN '01-xDSL'
            WHEN UPPER(MIN(mamo.Descripcion)) LIKE '%GPON%' OR UPPER(MIN(mamo.Descripcion)) LIKE '%FIBRA%' THEN 'GPON'
            WHEN UPPER(MIN(mamo.Descripcion)) LIKE '%HFC%' OR UPPER(MIN(mamo.Descripcion)) LIKE '%COAXIAL%' THEN 'HFC'
            ELSE '01-xDSL' 
        END as Tecnologia"),
        DB::raw("'A' as movimiento_estatus"),
        'pt.SKU',
        DB::raw("MIN(mamo.Descripcion) as Descripcion"),
        'mamo.TIPO',
        
        /* 🛡️ CORRECCIÓN SUPREMA: Extrae el valor real de la base de datos ('MATERIAL' o 'MANO DE OBRA') */
        'mamo.CATEGORIA', 
        
        DB::raw("CONCAT('PAGO_', pt.id) as movimiento_id"), 
        DB::raw("NULL as serie"),
        DB::raw("MAX(t_pt.nombre) as tecnico_nombre"), 
        DB::raw("MAX(t_pt.codigo) as tecnico_codigo"), 
        'pt.Cantidad as cantidad',
        DB::raw("CAST(pt.COSTOPAGO AS DECIMAL(10,2)) as COSTO"),
        DB::raw("(pt.Cantidad * pt.COSTOPAGO) as Subtotal")
    ])
    ->groupBy([
        'pt.SKU', 'pt.id', 'pt.Cantidad', 'pt.COSTOPAGO', 'mamo.TIPO', 'mamo.CATEGORIA', // 🛡️ Agregada al groupBy obligatorio
        DB::raw("SUBSTRING(REGEXP_REPLACE(pt.Orden, '[^0-9]', ''), 1, 8)"), 
        't_ord.tecnologia_real'
    ]);


/* ===================================================================== */
/* 🚀 PASO D: UNION ALL COMPILACIÓN FINAl                                */
/* ===================================================================== */
$movimientosRaw = $queryMateriales->unionAll($queryManoObra)->get();

        // 🚀 CORRECCIÓN CRUCIAL: Colapsar duplicados evaluando de forma estricta la dupla ID + Tecnología
        $movimientos = $movimientosRaw->unique(function ($item) {
            return $item->movimiento_id . '-' . $item->Tecnologia;
        });
     
        // Obtener las evidencias fotográficas ligadas de Google Cloud
        $fotografias = DB::table('expedientefotograficotecnico')->whereIn(DB::raw('SUBSTRING(Orden, 1, 8)'), $ordenesEncontradas)->get();

        // Agrupar los datos por tecnología identificada
        $movimientosPorTecnologia = $movimientos->groupBy('Tecnologia');

        // Inicializar el ZIP temporal en el servidor
        $zipFileName = 'Extraccion_Pivot_Tecnologias_' . date('Ymd_His') . '.zip';
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName; 
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo inicializar la librería de compresión ZipArchive.');
        }

        // Columnas base comunes para las cabeceras horizontales
        $columnasBaseGenerales = [
            'No', 'Orden', 'virtual', 'Status', 'Tipo_servicio', 'Tipo_orden', 
            'NOMBRECLIENTE', 'DIRECCION', 'OBS', 'SIGLASCENTRAL', 'AREA', 'FECHAINSTALACION', 'TECNICO'
        ];

        // Iterar por cada tecnología para construir sus archivos independientes
        foreach ($movimientosPorTecnologia as $nombreTecnologia => $registrosTecnologia) {
            
            // 🚀 ASIGNACIÓN HOMOLOGADA: Evita el error de variable indefinida
            $nombreTecnologiaLimpia = empty($nombreTecnologia) || trim($nombreTecnologia) === '' ? 'OTRAS_TECNOLOGIAS' : $nombreTecnologia;
            
            // Homologamos micro-variaciones de nombres
            if (str_contains(mb_strtoupper($nombreTecnologiaLimpia), 'DTH')) {
                $nombreTecnologiaLimpia = 'DTH';
            } elseif (str_contains(mb_strtoupper($nombreTecnologiaLimpia), 'WTTX')) {
                $nombreTecnologiaLimpia = 'WTTx';
            }

            // 🛡️ CORRECCIÓN AQUÍ: Cambiado el nombre para que coincida exactamente con lo que busca tu script abajo
            $nombreTecnologiaLimpio = str_replace(['/', '\\', '?', '*', ':', '[', ']'], '_', $nombreTecnologiaLimpia);
            
            $spreadsheet = new Spreadsheet();
            
            // --- CONFIGURACIÓN HOJA 1: MANO DE OBRA ---
            $sheetMO = $spreadsheet->getActiveSheet();
            $sheetMO->setTitle('Mano de Obra');
            

            // =========================================================================
            // INYECCIÓN CORREGIDA: Agrupar y aplanar múltiples manos de obra de pagotecnico
            // =========================================================================
            // 1. Agrupamos los pagos por su orden corta para soportar múltiples conceptos por orden
            $pagosAgrupadosPorOrden = collect($registrosPagos)->groupBy('orden_corta');
            
            $nuevosRegistrosVirtuales = collect();

            // 2. Agrupamos los movimientos actuales por orden para analizar qué les falta
            $movimientosPorOrdenLocal = $registrosTecnologia->groupBy('orden_tecnica');

            foreach ($movimientosPorOrdenLocal as $ordenCorta => $movimientosDeEstaOrden) {
                // Verificamos si los movimientos ya traen algo catalogado como MANO DE OBRA
                $tieneManoObraEnMovimientos = $movimientosDeEstaOrden->where('CATEGORIA', 'MANO DE OBRA')->isNotEmpty();

                // Si no tiene mano de obra en movimientos pero SÍ existen registros en la tabla pagotecnico
                if (!$tieneManoObraEnMovimientos && $pagosAgrupadosPorOrden->has($ordenCorta)) {
                    // Tomamos el primer registro de la orden para heredar los datos del cliente
                    $primerMovimiento = $movimientosDeEstaOrden->first();
                    $listaDePagos = $pagosAgrupadosPorOrden->get($ordenCorta);

                    // Iteramos por cada una de las manos de obra que tenga registradas en pagotecnico
                    foreach ($listaDePagos as $pago) {
                        if (!empty($pago->Descripcion)) {
                            $clonMO = clone $primerMovimiento;
                            $clonMO->CATEGORIA = 'MANO DE OBRA';
                            $clonMO->SKU = $pago->SKU ?? 'S_SKU';
                            $clonMO->Descripcion = trim($pago->Descripcion);
                            $clonMO->cantidad = floatval($pago->Cantidad ?? 1.00);
                            $clonMO->COSTO = floatval($pago->COSTOPAGO ?? 0.00);
                            
                            $nuevosRegistrosVirtuales->push($clonMO);
                        }
                    }
                }
            }

            // 3. Fusionamos los movimientos originales con todos los conceptos rescatados de pagotecnico
            $registrosTecnologiaUnificados = $registrosTecnologia->concat($nuevosRegistrosVirtuales);

            // 4. Extraemos las descripciones únicas para la CABECERA horizontal (Garantiza ver las nuevas columnas)
            $descripcionesMOUnicas = $registrosTecnologiaUnificados
                ->where('CATEGORIA', 'MANO DE OBRA') 
                ->pluck('Descripcion')
                ->filter() 
                ->unique()
                ->toArray();
            
            $cabeceraMOCompleta = array_merge($columnasBaseGenerales, $descripcionesMOUnicas);
            $sheetMO->fromArray($cabeceraMOCompleta, NULL, 'A1');
            
            $sheetMO->getStyle('A1:' . $sheetMO->getHighestColumn() . '1')->getFont()->setBold(true);
            $sheetMO->getStyle('A1:' . $sheetMO->getHighestColumn() . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D0E1F9');
            
            // 5. Agrupamos la colección unificada final para proceder con el dibujo de filas
            $expedientesMO = $registrosTecnologiaUnificados->groupBy('orden_tecnica');
            $filaMO = 2;
            
            foreach ($expedientesMO as $orden => $detallesOrden) {
                $primerItem = $detallesOrden->first();
                
                // Evaluamos si el expediente cuenta con Mano de Obra (física o virtualizada)
                $tieneManoObra = $detallesOrden->where('CATEGORIA', 'MANO DE OBRA')->isNotEmpty();
                
                $observaciones = $primerItem->expediente_obs;
                if (!$tieneManoObra) {
                    $observaciones = empty($observaciones) 
                        ? 'SIN ESPECIFICAR MANO DE OBRA' 
                        : trim($observaciones . ' | SIN ESPECIFICAR MANO DE OBRA');
                }

                $datosFilaMO = [
                    $filaMO - 1,
                    $primerItem->orden_tecnica,
                    $primerItem->virtual,
                    $primerItem->expediente_status,
                    $primerItem->Tipo_servicio,
                    $primerItem->Tipo_orden,
                    $primerItem->NOMBRECLIENTE,
                    $primerItem->DIRECCION,
                    $observaciones,
                    $primerItem->SIGLASCENTRAL,
                    $primerItem->AREA,
                    $primerItem->FECHAINSTALACION,
                    $primerItem->tecnico_nombre
                ];
                
                // 6. Rellenamos las cantidades alineadas bajo la columna exacta de su descripción
                foreach ($descripcionesMOUnicas as $moColumna) {
                    $matchConcepto = $detallesOrden
                        ->where('CATEGORIA', 'MANO DE OBRA')
                        ->where('Descripcion', $moColumna)
                        ->first();
                    
                    // Si encontramos el concepto inyectamos su cantidad numérica, de lo contrario un 0
                    $datosFilaMO[] = $matchConcepto ? $matchConcepto->cantidad : 0;
                }
                
                if (empty($descripcionesMOUnicas) && !$tieneManoObra) {
                    $datosFilaMO[] = 'SIN ESPECIFICAR MANO DE OBRA';
                }

                $sheetMO->fromArray($datosFilaMO, NULL, 'A' . $filaMO);
                $filaMO++;
            }


            
            foreach (range('A', $sheetMO->getHighestColumn()) as $col) {
                $sheetMO->getColumnDimension($col)->setAutoSize(true);
            }

            // --- CONFIGURACIÓN HOJA 2: MATERIALES ---
            $sheetMat = $spreadsheet->createSheet();
            $sheetMat->setTitle('Materiales');
            
            $itemsMateriales = $registrosTecnologia->where('CATEGORIA', '!=', 'MANO DE OBRA');
            $skusMaterialesUnicos = $itemsMateriales->pluck('SKU')->unique()->toArray();
            
            $cabeceraMatCompleta = array_merge($columnasBaseGenerales, $skusMaterialesUnicos);
            $sheetMat->fromArray($cabeceraMatCompleta, NULL, 'A1');
            
            $sheetMat->getStyle('A1:' . $sheetMat->getHighestColumn() . '1')->getFont()->setBold(true);
            $sheetMat->getStyle('A1:' . $sheetMat->getHighestColumn() . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D1E7DD');
            
            $expedientesMat = $itemsMateriales->groupBy('orden_tecnica');
            $filaMat = 2;

            foreach ($expedientesMat as $orden => $detallesMateriales) {
                $primerMat = $detallesMateriales->first();
                
                $datosBaseOrden = [
                    $primerMat->expediente_id,
                    $primerMat->orden_tecnica,
                    $primerMat->virtual,
                    $primerMat->expediente_status,
                    $primerMat->Tipo_servicio,
                    $primerMat->Tipo_orden,
                    $primerMat->NOMBRECLIENTE,
                    $primerMat->DIRECCION,
                    $primerMat->expediente_obs,
                    $primerMat->SIGLASCENTRAL,
                    $primerMat->AREA,
                    $primerMat->FECHAINSTALACION,
                    $primerMat->tecnico_nombre
                ];

                $registroSkusSeriados = [];
                $registroSkusMiscelaneos = [];
                $maxFilasNecesarias = 1;

                foreach ($skusMaterialesUnicos as $skuColumna) {
                    $movimientosSku = $detallesMateriales->where('SKU', $skuColumna);

                    if ($movimientosSku->isEmpty()) {
                        continue;
                    }

                    $seriesValidas = [];
                    foreach ($movimientosSku as $mov) {
                        $serieLimpia = trim($mov->serie);
                        $upperSerie = strtoupper($serieLimpia);
                        
                        if ($serieLimpia !== '' && $upperSerie !== 'N/A' && $upperSerie !== '0' && !is_null($mov->serie)) {
                            $seriesValidas[] = $serieLimpia;
                        }
                    }

                    if (!empty($seriesValidas)) {
                        $registroSkusSeriados[$skuColumna] = $seriesValidas;
                        $maxFilasNecesarias = max($maxFilasNecesarias, count($seriesValidas));
                    } else {
                        $registroSkusMiscelaneos[$skuColumna] = $movimientosSku->sum('cantidad');
                    }
                }

                for ($i = 0; $i < $maxFilasNecesarias; $i++) {
                    $datosFilaMat = $datosBaseOrden;

                    foreach ($skusMaterialesUnicos as $skuColumna) {
                        if (isset($registroSkusSeriados[$skuColumna])) {
                            $datosFilaMat[] = isset($registroSkusSeriados[$skuColumna][$i]) 
                                ? $registroSkusSeriados[$skuColumna][$i] 
                                : 0;
                        } else if (isset($registroSkusMiscelaneos[$skuColumna])) {
                            $datosFilaMat[] = ($i === 0) ? $registroSkusMiscelaneos[$skuColumna] : 0;
                        } else {
                            $datosFilaMat[] = 0;
                        }
                    }

                    $sheetMat->fromArray($datosFilaMat, NULL, 'A' . $filaMat);
                    $filaMat++;
                }
            }
            
            foreach (range('A', $sheetMat->getHighestColumn()) as $col) {
                $sheetMat->getColumnDimension($col)->setAutoSize(true);
            }
            // --- CONFIGURACIÓN HOJA 3: RESUMEN DE COBROS ---
            $sheetResumen = $spreadsheet->createSheet();
            $sheetResumen->setTitle('Resumen de Cobros');
            
            // Títulos e informativos superiores del cuadro de costos
            $sheetResumen->setCellValue('B2', 'CUADRO DE COSTOS INSTALACIONES SERVICIOS ' . strtoupper($nombreTecnologiaLimpio));
            $sheetResumen->setCellValue('B3', 'REGION: OCCIDENTE');
            $sheetResumen->setCellValue('B4', 'PERIODO DEL ' . (request('fecha_inicio') ? Carbon::parse(request('fecha_inicio'))->format('d/m/Y') : '01/05/2026') . ' AL ' . (request('fecha_fin') ? Carbon::parse(request('fecha_fin'))->format('d/m/Y') : '31/05/2026'));
            $sheetResumen->getStyle('B2:B4')->getFont()->setBold(true);
            
            // Banner Rojo de Sección
            $sheetResumen->mergeCells('B6:G6');
            $sheetResumen->setCellValue('B6', 'REPORTE DE MANO DE OBRA');
            $sheetResumen->getStyle('B6')->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
            $sheetResumen->getStyle('B6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
            $sheetResumen->getStyle('B6')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Encabezados de la tabla liquidación
            $cabeceraTablaResumen = ['No', 'DESCRIPCION', 'UNIDAD', 'CANTIDAD REALIZADA', 'PRECIO MANO DE OBRA/UNIDAD', 'TOTAL DE MANO DE OBRA'];
            $sheetResumen->fromArray($cabeceraTablaResumen, NULL, 'B7');
            
            $sheetResumen->getStyle('B7:G7')->getFont()->setBold(true);
            $sheetResumen->getStyle('B7:G7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
            $sheetResumen->getStyle('B7:G7')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // 🛡️ FILTRADO ESTRICTO DE CATEGORÍA: Evaluamos solo registros donde CATEGORIA = 'MANO DE OBRA'
            // Esto garantiza que el bloque 1 (Materiales) sea completamente ignorado en esta hoja
            $resumenCobrosConceptos = $registrosTecnologiaUnificados
                ->where('CATEGORIA', 'MANO DE OBRA')
                ->groupBy('Descripcion');

            $filaResumen = 8;
            $numNo = 1;
            
            foreach ($resumenCobrosConceptos as $conceptoTexto => $movimientosConcepto) {
                $sumaCantidad = $movimientosConcepto->sum('cantidad');
                $precioUnitario = $movimientosConcepto->first()->COSTO ?? 0;
                $unidadMedida = $movimientosConcepto->first()->unidadmedida_auditada ?? 'UNIDAD';
                
                $sheetResumen->setCellValue('B' . $filaResumen, $numNo);
                $sheetResumen->setCellValue('C' . $filaResumen, $conceptoTexto);
                $sheetResumen->setCellValue('D' . $filaResumen, $unidadMedida);
                
                // Alineación correcta de columnas para las fórmulas horizontales del Excel
                $sheetResumen->setCellValue('E' . $filaResumen, $sumaCantidad);     // Columna E: Cantidad Realizada
                $sheetResumen->setCellValue('F' . $filaResumen, $precioUnitario);   // Columna F: Precio Unitario
                
                // Fórmula de Excel: Cantidad (E) * Precio Unitario (F) = Total (G)
                $sheetResumen->setCellValue('G' . $filaResumen, "=E{$filaResumen}*F{$filaResumen}");
                
                $sheetResumen->getStyle("B{$filaResumen}:G{$filaResumen}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheetResumen->getStyle("F{$filaResumen}:G{$filaResumen}")->getNumberFormat()->setFormatCode('"Q"#,##0.00');
                
                $filaResumen++;
                $numNo++;
            }
            
            // Bloque dinámico adaptativo de Impuestos y Liquidación Final
            $fTotalMO  = $filaResumen + 2;
            $fTotalMes = $fTotalMO + 2;
            $fIva      = $fTotalMes + 1;
            $fConIva   = $fIva + 1;
            
            // Inyección de Fórmulas de Cierre Financiero (Suma el rango de la columna G)
            $sheetResumen->mergeCells("E{$fTotalMO}:F{$fTotalMO}");
            $sheetResumen->setCellValue("E{$fTotalMO}", 'TOTAL MANO DE OBRA');
            $sheetResumen->setCellValue("G{$fTotalMO}", "=SUM(G8:G" . ($filaResumen - 1) . ")");
            
            $sheetResumen->mergeCells("E{$fTotalMes}:F{$fTotalMes}");
            $sheetResumen->setCellValue("E{$fTotalMes}", 'TOTAL DEL MES');
            $sheetResumen->setCellValue("G{$fTotalMes}", "=G{$fTotalMO}");
            
            $sheetResumen->mergeCells("E{$fIva}:F{$fIva}");
            $sheetResumen->setCellValue("E{$fIva}", 'IVA 12%');
            $sheetResumen->setCellValue("G{$fIva}", "=G{$fTotalMes}*0.12");
            
            $sheetResumen->mergeCells("E{$fConIva}:F{$fConIva}");
            $sheetResumen->setCellValue("E{$fConIva}", 'TOTAL CON IVA');
            $sheetResumen->setCellValue("G{$fConIva}", "=G{$fTotalMes}+G{$fIva}");
            
            $filasTotalesFinales = [$fTotalMO, $fTotalMes, $fIva, $fConIva];
            foreach ($filasTotalesFinales as $f) {
                $sheetResumen->getStyle("E{$f}:G{$f}")->getFont()->setBold(true);
                $sheetResumen->getStyle("E{$f}:G{$f}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                $sheetResumen->getStyle("G{$f}")->getNumberFormat()->setFormatCode('"Q"#,##0.00');
            }
            
            // Dimensionamiento de anchos fijos de la liquidación
            $sheetResumen->getColumnDimension('B')->setWidth(6);
            $sheetResumen->getColumnDimension('C')->setWidth(50);
            $sheetResumen->getColumnDimension('D')->setWidth(12);
            $sheetResumen->getColumnDimension('E')->setWidth(22);
            $sheetResumen->getColumnDimension('F')->setWidth(26);
            $sheetResumen->getColumnDimension('G')->setWidth(26);


            // 5. Guardar libro Excel de la tecnología actual e insertarlo en la raíz del ZIP
            $writer = new Xlsx($spreadsheet);
            $excelTemporalPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Reporte_' . $nombreTecnologiaLimpio . '.xlsx';
            $writer->save($excelTemporalPath);
            
            $zip->addFile($excelTemporalPath, 'Reporte_Tecnologia_' . $nombreTecnologiaLimpio . '.xlsx');
        }

        // --- SECCIÓN DE EVIDENCIAS FOTOGRÁFICAS ---
        $nombreBucket = 'sistema-pv-imagenes-tienda';
        $fotosContador = 0;

        foreach ($fotografias as $foto) {
            $urlCompleta = $foto->fotografia;
            $pathBucket = $urlCompleta;

            if (str_contains($urlCompleta, $nombreBucket)) {
                $posicionBucket = strpos($urlCompleta, $nombreBucket);
                $pathBucket = substr($urlCompleta, $posicionBucket + strlen($nombreBucket));
                $pathBucket = ltrim($pathBucket, '/');
            } else {
                $pathBucket = ltrim(parse_url($urlCompleta, PHP_URL_PATH), '/');
            }

            if (Storage::disk('gcs_images')->exists($pathBucket)) {
                $imageContent = Storage::disk('gcs_images')->get($pathBucket);
                $nombreArchivoOriginal = pathinfo($pathBucket, PATHINFO_BASENAME);
                
                // Almacenamiento clasificado en subcarpetas internas por Número de Orden
                $nombreArchivoInterno = "fotografias/Orden_{$foto->Orden}/" . $nombreArchivoOriginal;
                
                $zip->addFromString($nombreArchivoInterno, $imageContent);
                $fotosContador++;
            }
        }

        $zip->close();

        // Eliminar los archivos Excel temporales del disco del servidor para no saturar almacenamiento
        foreach ($movimientosPorTecnologia as $nombreTecnologia => $registrosTecnologia) {
            $nombreTecnologiaLimpio = empty($nombreTecnologia) ? 'OTRAS_TECNOLOGIAS' : str_replace(['/', '\\', '?', '*', ':', '[', ']'], '_', $nombreTecnologia);
            $excelTemporalPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Reporte_' . $nombreTecnologiaLimpio . '.xlsx';
            if (file_exists($excelTemporalPath)) {
                @unlink($excelTemporalPath);
            }
        }

        // Validar peso y consistencia del entregable
        if (!file_exists($zipPath) || filesize($zipPath) <= 22) { 
            @unlink($zipPath);
            return back()->with('error', 'El proceso concluyó sin datos empaquetables.');
        }

        // Alertas informativas de la bitácora Flash
        $fotosStatus = $fotosContador > 0 ? "Fotografías OK ({$fotosContador} descargadas)" : "Fotografías: No descargado";
        session()->flash('notificacion_extraccion', [
            'pago' => 'Pago Técnico OK',
            'materiales' => 'Reportes de Tecnologías Generados OK',
            'fotos' => $fotosStatus
        ]);

        // Descarga inmediata del archivo binario y purga automática del temporal del servidor
        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }



public function extraccionMasiva1(Request $request)
{
    // 1. Validar la existencia del archivo cargado
    if (!$request->hasFile('excel_ordenes')) {
        return back()->with('error', 'No se recibió ningún archivo en el servidor.');
    }

    $file = $request->file('excel_ordenes');
    $path = $file->getRealPath();
    $ordenesRaw = [];
    
    try {
        // 2. Cargar el lector de PhpSpreadsheet para abrir el archivo .xlsx nativo
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        // Recorrer TODAS las filas de la columna A (desde la fila 2 hasta la última)
        for ($row = 2; $row <= $highestRow; $row++) {
            // getCalculatedValue garantiza leer el valor real aunque la celda tenga formato numérico o de texto
            $valorCelda = $worksheet->getCell('A' . $row)->getCalculatedValue();
            
            // Limpieza absoluta de espacios en blanco normales e invisibles (caracteres no-rompibles)
            $valorCelda = trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $valorCelda));

            if ($valorCelda !== '' && !is_null($valorCelda)) {
                $ordenesRaw[] = (string)$valorCelda; // Forzar a tipo String para evitar fallos en base de datos
            }
        }
    } catch (\Exception $e) {
        return back()->with('error', 'Error al leer el formato del archivo Excel: ' . $e->getMessage());
    }

    // Quitar duplicados y limpiar el índice del arreglo
    $ordenes = array_values(array_unique($ordenesRaw));

    if (empty($ordenes)) {
        return back()->with('error', 'El archivo Excel no contiene ninguna orden legible en la primera columna.');
    }

    // 3. Consultar Base de Datos mediante el Triple Cruce de Tablas usando la lista completa
    $registrosPagos = DB::table('pagotecnico')->whereIn('Orden', $ordenes)->get();
    
    if ($registrosPagos->isEmpty()) {
        return back()->with('error', 'Ninguna de las órdenes ingresadas en tu archivo existe en la tabla pagotecnico.');
    }

    // Volver a mapear las órdenes que sí se encontraron para amarrar los expedientes y fotos
    $ordenesEncontradas = $registrosPagos->pluck('Orden')->unique()->toArray();

    // Obtener los IDs de la tabla puente 'expedientetecnico' usando las órdenes validadas
// Extraer materiales incluyendo la Orden del expediente y el ID del árbol de materiales (tecnología)
$movimientos = DB::table('movimientomateriales as mm')
    ->join('expedientetecnico as ex', 'ex.id', '=', 'mm.fkExpediente')
    ->leftJoin('MaterialManoObra as mamo', 'mm.SKU', '=', 'mamo.SKU')
    ->leftJoin('arbolmaterial as abmamo', 'mm.fkTecnologiaarbol', '=', 'abmamo.id')
    ->leftJoin('tecnico as t', 'mm.fkTecnico', '=', 't.id')
    ->where('mm.fkTienda', session('user_fkTienda'))
    ->whereIn('ex.Orden', $ordenesEncontradas)
    // 1. Añadimos distinct() para obligar a MySQL a limpiar los duplicados del JOIN
    ->distinct() 
    ->select([
        'ex.id as expediente_id',
        'ex.Orden as orden_tecnica',
        'ex.virtual',
        'ex.Status as expediente_status',
        'ex.Tipo_servicio',
        'ex.Tipo_orden',
        'ex.NOMBRECLIENTE',
        'ex.DIRECCION',
        'ex.OBS as expediente_obs',
        'ex.SIGLASCENTRAL',
        'ex.AREA',
        'ex.FECHAINSTALACION',
        'abmamo.nombre as Tecnologia',
        'mm.ESTATUS as movimiento_estatus',
        'mm.SKU',
        'mamo.Descripcion',
        'mamo.TIPO',
        'mamo.CATEGORIA',
        // 2. IMPORTANTE: Si la duplicación persiste, remueve 'mm.id' o aplica un MAX(mm.id)
        'mm.id as movimiento_id', 
        'mm.serie',
        'mm.MAC1',
        'mm.MAC2',
        't.nombre as tecnico_nombre', 't.codigo as tecnico_codigo', 't.especialidad as tecnico_esp', // Datos adicionales del técnico para enriquecer el reporte
        'mm.MAC3',
        // COSTO basado en la auditoría
        DB::raw("CASE 
            WHEN mamo.SKU IS NULL THEN NULL 
            WHEN mamo.CATEGORIA = 'MANO DE OBRA' THEN mamo.COSTOPAGO 
            ELSE mamo.CATEGORIACOBRO 
        END AS COSTO"),
        // EVALUACIÓN DE UNIDAD DE MEDIDA DESDE EL CATÁLOGO MAESTRO (mamo)
        DB::raw("CASE 
            WHEN mamo.SKU IS NULL THEN NULL 
            WHEN mamo.unidadmedida = '' OR mamo.unidadmedida IS NULL THEN 'UNIDAD' 
            ELSE mamo.unidadmedida 
        END AS unidadmedida_auditada")
    ])
    ->get();





    
    // Obtener TODAS las evidencias fotográficas del grupo de órdenes
    $fotografias = DB::table('expedientefotograficotecnico')->whereIn('Orden', $ordenesEncontradas)->get();

    // Variables de control de estados para la notificación Flash
    $pagoTecnicoStatus = 'Pago Técnico OK';
    $movimientosStatus = $movimientos->count() > 0 ? 'Movimiento Materiales OK' : 'Movimiento Materiales: No descargado (Sin registros)';
    $fotosContador = 0;

    // 4. Crear el archivo ZIP temporal
    $zipFileName = 'Extraccion_Masiva_GCS_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName; 

    $zip = new ZipArchive;

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        
        // --- ARCHIVO 1: CSV de Reporte Órdenes de Pago ---
        $csvPagosHandle = fopen('php://memory', 'r+');
        fprintf($csvPagosHandle, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($csvPagosHandle, ['id', 'Orden', 'SKU', 'Descripcion', 'Cantidad', 'COSTOPAGO', 'Naturaleza', 'Status']);
        foreach ($registrosPagos as $p) {
            fputcsv($csvPagosHandle, [$p->id, $p->Orden, $p->SKU, $p->Descripcion, $p->Cantidad, $p->COSTOPAGO, $p->Naturaleza, $p->Status]);
        }
        rewind($csvPagosHandle);
        $zip->addFromString('reporte_ordenes.csv', stream_get_contents($csvPagosHandle));
        fclose($csvPagosHandle);

        // --- ARCHIVO 2: CSV de Reporte de Materiales ---
            if ($movimientos->count() > 0) {
            $csvMatHandle = fopen('php://memory', 'r+');
             fprintf($csvMatHandle, chr(0xEF).chr(0xBB).chr(0xBF));
   // 1. Cabecera expandida con toda la estructura de columnas solicitada
   // 1. Cabecera incluyendo las nuevas columnas del Técnico
    fputcsv($csvMatHandle, [
        'id_expediente', 'Orden', 'virtual', 'Status', 'Tipo_servicio', 'Tipo_orden', 
        'NOMBRECLIENTE', 'DIRECCION', 'OBS', 'SIGLASCENTRAL', 'AREA', 'FECHAINSTALACION', 
        'Tecnologia', 'Estatus_Mov', 'SKU', 'Descripcion', 'TIPO', 'COSTO', 
        'CATEGORIA', 'id_movimiento', 'serie', 'MAC1', 'MAC2', 'MAC3',
        'Tecnico_Nombre', 'Tecnico_Codigo', 'Tecnico_Especialidad', 'unidadmedida' // Nuevas columnas en cabecera
    ]);
    
    // 2. Volcado de datos mapeando los nuevos alias del Técnico
    foreach ($movimientos as $m) {
        fputcsv($csvMatHandle, [
            $m->expediente_id,
            $m->orden_tecnica,
            $m->virtual,
            $m->expediente_status,
            $m->Tipo_servicio,
            $m->Tipo_orden,
            $m->NOMBRECLIENTE,
            $m->DIRECCION,
            $m->expediente_obs,
            $m->SIGLASCENTRAL,
            $m->AREA,
            $m->FECHAINSTALACION,
            $m->Tecnologia,
            $m->movimiento_estatus,
            $m->SKU,
            $m->Descripcion,
            $m->TIPO,
            $m->COSTO,
            $m->CATEGORIA,
            $m->movimiento_id,
            $m->serie,
            $m->MAC1,
            $m->MAC2,
            $m->MAC3,
            $m->tecnico_nombre, // Variable agregada
            $m->tecnico_codigo, // Variable agregada
            $m->tecnico_esp,    // Variable agregada
            $m->unidadmedida_auditada
        ]);
    }
    
            rewind($csvMatHandle);
            $zip->addFromString('reporte_movimientos.csv', stream_get_contents($csvMatHandle));
            fclose($csvMatHandle);
        }

        // --- SECCIÓN 3: Descarga de Evidencias desde Google Cloud Storage (gcs_images) ---
        $nombreBucket = 'sistema-pv-imagenes-tienda';

        foreach ($fotografias as $foto) {
            $urlCompleta = $foto->fotografia;
            $pathBucket = $urlCompleta;

            if (str_contains($urlCompleta, $nombreBucket)) {
                $posicionBucket = strpos($urlCompleta, $nombreBucket);
                $pathBucket = substr($urlCompleta, $posicionBucket + strlen($nombreBucket));
                $pathBucket = ltrim($pathBucket, '/');
            } else {
                $pathBucket = ltrim(parse_url($urlCompleta, PHP_URL_PATH), '/');
            }

            if (Storage::disk('gcs_images')->exists($pathBucket)) {
                $imageContent = Storage::disk('gcs_images')->get($pathBucket);
                $nombreArchivoOriginal = pathinfo($pathBucket, PATHINFO_BASENAME);
                
                // Clasificación interna organizada en subcarpetas por número de Orden
                $nombreArchivoInterno = "fotografias/Orden_{$foto->Orden}/" . $nombreArchivoOriginal;
                
                $zip->addFromString($nombreArchivoInterno, $imageContent);
                $fotosContador++;
            }
        }

        $zip->close();
    } else {
        return back()->with('error', 'Error del sistema: No se pudo inicializar la librería de compresión ZipArchive.');
    }

    if (!file_exists($zipPath) || filesize($zipPath) <= 22) { 
        @unlink($zipPath);
        return back()->with('error', 'El archivo final se generó vacío.');
    }

    $fotosStatus = $fotosContador > 0 ? "Fotografías OK ({$fotosContador} descargadas)" : "Fotografías: No descargado";
    
    session()->flash('notificacion_extraccion', [
        'pago' => $pagoTecnicoStatus,
        'materiales' => $movimientosStatus,
        'fotos' => $fotosStatus
    ]);

    return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
}




        public function bucket($id)
    {
        DB::connection()->disableQueryLog();
        try {
                            if(!Auth::check()){
            return redirect()->route('login');
        }

            DB::beginTransaction();

            $fkTienda = session('user_fkTienda');
            $tecnicos=Tecnico::where('id',$id)->get();
            $expediente=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$id)->get();

            DB::commit();

            return view('buckettecnico.index', compact('tecnicos','expediente'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }


    }

    
    public function show($id)
    {
        // Lógica para mostrar un cliente específico
        $cliente = Cliente::find($id);
        return redirect()->route('clientes.index')->with('success', 'Cliente registrado');
    }
    public function edit($id){
                try{
                                    if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
        $rol = Role::all();
        $documentos=Documento::all();
        $tecnico=Tecnico::where('id',$id)
        ->first();

$tienda = DB::table('usuario_tienda as us')
    ->select('ti.Nombre', 'ti.idTienda')
    ->join('users as u', 'u.id', '=', 'us.fkUsuario')
    ->join('tecnico as t', 't.fkTienda', '=', 'us.fkTienda')
    ->join('tienda as ti', 'ti.idTienda', '=', 't.fkTienda')
    ->where('t.id', $id)
    ->distinct()
    ->get();

// Verificar si hay resultados
if ($tienda->isEmpty()) {
    // Manejar el caso cuando no hay tiendas
    $tienda = collect(); // Crear colección vacía
}


    $users=DB::table('users as u')
    ->join('usuario_tienda as ut', 'ut.fkUsuario','=','u.id','left')
    ->where('fkTienda',$fkTienda)
    ->get();


        return view('tecnico.edit', compact('tecnico','rol','documentos','users','tienda','id'));
                }catch(Exception $e){
                return response()->json(['error' => $e->getMessage()], 400);

        }
    }
    public function create()
    {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');
        $rol = Role::all();
        $documentos=Documento::all();


    $users=DB::table('users as u')
    ->join('usuario_tienda as ut', 'ut.fkUsuario','=','u.id','left')
    ->where('fkTienda',$fkTienda)
    ->get();


        if ($Estatus == 'ER') {
                    $tecnico = Tienda::all();
                } else {
                    $tecnico = Tienda::where('idTienda',$fkTienda)->get();
                };



        return view('tecnico.create', compact('tecnico','rol','documentos','users'));
    }

public function prepararimagen($request){

    $request->validate([
        'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);
    
    $file = $request->file('image');
    $manager = new ImageManager(new Driver());

    $image = $manager->read($file->getPathname())
                     ->resize(800, 800, function ($constraint) {
                         $constraint->aspectRatio();
                         $constraint->upsize();
                     });

    // Convert to WebP y definir la ruta virtual del bucket
    $filename = 'tecnico_' . time() . '.webp';
    $path = 'tecnicos/' . $filename; // Se guardará en gs://sistema-pv-imagenes-tienda/tecnicos/
    $webpEncoder = new WebpEncoder(quality: 80);

    // 1. Guardar la imagen procesada directamente en Google Cloud Storage
    Storage::disk('gcs_images')->put($path, (string) $image->encode($webpEncoder));

    // 2. OBLIGATORIO: Retornamos la ruta para que tu controlador la guarde en la BD
    return $path;
}


    public function store(Request $request)
    {
$lockKey = 'tecnico_create' . auth()->id();
    if (!Cache::add($lockKey, true, 10)) {
        return redirect()->back()->with('error', 'La venta ya se está procesando. Por favor, espera.');
    }

        try {

            DB::beginTransaction();
         // Procesar imagen y convertir a BLOB
        $file = $request->file('image');
        $manager = new ImageManager(new Driver());

        $image = $manager->read($file->getPathname())
                         ->resize(800, 800, function ($constraint) {
                             $constraint->aspectRatio();
                             $constraint->upsize();
                         });

        // Convertir a WebP y obtener como cadena binaria
        $webpEncoder = new WebpEncoder(quality: 80);
        $imageBlob = $image->encode($webpEncoder);

        // Crear persona
        $persona = Persona::create([
            'razon_social' => $request->razon_social,
            'direccion' => $request->direccion,
            'tipo_persona' => $request->tipo_persona,
            'estado' => 1,
            'documento_id' => $request->documento_id,
            'numero_documento' => $request->numero_documento,
            'created_at' => now()
        ]);


    //creacion de tecnico
            $persona->tecnico()->create([
                'fkpersona' => $persona->id,
                'nombre' => $persona->razon_social,
                'fkTienda' => $request->tienda,
                'codigo' => $request->numero_eta,
                'especialidad' => $request->especialidad,
                'logo' => $imageBlob
            ]);

            //Encriptar contraseña
            $fieldHash = Hash::make($request->password);
            //Modificar el valor de password en nuestro request
            $request->merge(['password' => $fieldHash]);

            //Crear usuario
            $user = User::create(array_merge([
                'fkTienda' => $request->tienda,
                'logo'=>$imageBase64??null,
                'name' => $request->razon_social,
                'email' => $request->email,
                'password'=> $request->password,
                'created_at'=>now()
                ]));

            //Asignar su rol
            $user->assignRole($request->role);

            usuariotienda::create(array_merge([
                'fkUsuario'=>$user->id,
                'fkTienda'=>$request->tienda,
                'Estatus'=>$request->Estatus,
                'FechaIngreso'=>now(),
                'created_at'=>now()
            ]));

            DB::commit();
            Cache::forget($lockKey);
            return redirect()->route('tecnico.lista')->with('success', 'Tecnico registrado');

        } catch (Exception $e) {
            Cache::forget($lockKey);
            DB::rollBack();
            Log::error('Error al registrar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar el cliente.');
        }

        

    }

    public function exist(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Sesión expirada.'], 401);
    }

    // 1. Validación correcta de Laravel antes de abrir transacciones
    $request->validate([
        'idtecnico' => 'required',
        'tienda'    => 'required',
        'email'     => 'nullable|email|unique:users,email,' . $request->user, // Evita colisiones de correo
    ], [
        'email.unique' => 'El correo electrónico ya existe en el sistema, por favor elige uno nuevo.'
    ]);

    try {
        DB::beginTransaction();

        // 2. Buscar la Persona (esta sí debe existir obligatoriamente)
        $idpersona = Tecnico::where('id', $request->idtecnico)->value('fkpersona');
        $persona = Persona::findOrFail($idpersona);

        // 3. BUSCAR O CREAR el técnico vinculado a esa persona
        $tecnico = Tecnico::updateOrCreate(
            ['fkpersona' => $persona->id], 
            [
                'nombre'       => $persona->razon_social, 
                'fkTienda'     => $request->tienda,
                'codigo'       => $request->numero_eta,
                'especialidad' => $request->especialidad,
                'fkuser'       => $request->user,
                'updated_at'   => now()
            ]
        );

        // 4. PROCESAR IMAGEN: Únicamente si el archivo fue enviado y es válido
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            
            $file = $request->file('image');
            $manager = new ImageManager(new Driver());

            // Lectura mediante RealPath e Intervention Image V3 API
            $image = $manager->read($file->getRealPath());
            
            // Redimensionar proporcionalmente a 300x300 (Reemplaza de forma nativa a upsize y aspectRatio)
            $image->resizeDown(300, 300);

            // Codificación a WebP con calidad al 50%
            $encoded = $image->toWebp(50);

            // Convertimos el buffer codificado de Intervention V3 a Base64 para guardarlo en la columna 'logo'
            $imageBase64 = 'data:image/webp;base64,' . base64_encode((string)$encoded);

            // Actualizamos el campo logo con el nuevo WebP Base64 optimizado
            $tecnico->update(['logo' => $imageBase64]);
        }
        // 💡 ELSE SILENCIOSO: Si no se envía imagen, el campo 'logo' conserva intacto su valor previo en la DB.

        DB::commit();

        return redirect()->route('tecnico.lista')->with('success', 'Técnico registrado correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error en Exist: ' . $e->getMessage());
        return response()->json(['error' => 'Error interno en el servidor: ' . $e->getMessage()], 500);
    }
}



    public function obtenerdetalless(Request $request){

        try {
            DB::connection()->disableQueryLog();
                            if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
        $param = $request->input('parametros');


$materiales = MovimientoMaterial::join('treematerialescategoria as tmc', 'tmc.sku', '=', 'movimientomateriales.SKU')
    ->join('expedientetecnico as et', 'et.id', '=', 'movimientomateriales.fkExpediente')
    ->where('et.id', $param)
    ->select([
        'movimientomateriales.serie',
        'tmc.nombre as Descripcion',
        'tmc.sku as sku',
        'movimientomateriales.id as id',  
        
        // RESTRUCTURACIÓN: Cambiamos el nombre del alias para romper la confusión de objetos
        DB::raw("COALESCE(NULLIF(movimientomateriales.fkTecnologiaarbol, ''), 0) AS fkTecnologiaarbol"),
        
        DB::raw("IFNULL(movimientomateriales.cantidad, 1) as cantidad")
    ])
    ->get();


    
    return response()->json($materiales);
            } catch (Exception $e) {
            dd($e);
            DB::rollBack();
        }
    }

    public function AutomataValidarMamoOrdenTecnico(Request $request)
{
    DB::connection()->disableQueryLog();

    if(!Auth::check()) return response()->json(['error' => 'No autorizado'], 401);
    $procesados = []; 
    $rastro = [];
    $orden = $request->input('Orden');
    
    // Captura de datos virtuales del frontend
    $skuNuevo = trim($request->input('SKU_Nuevo'));
    $cantidadNueva = (float)$request->input('Cantidad_Nueva');

    // Carga de ítems consolidados actuales en base de datos
    $items = DB::table('ETA')->select('CENTRO', 'SKU', DB::raw('SUM(cantidad) as Cantidad'))
                             ->where('fkTienda', session('user_fkTienda'))         
                             ->where('Orden', $orden)->groupBy('SKU', 'CENTRO')->get();

    $itemsSimulados = $items->toArray();
    $skuEncontradoEnOrden = false;

    // Si el SKU ya está reportado en la orden, sumamos la cantidad temporalmente
    foreach ($itemsSimulados as $key => $item) {
        if (trim($item->SKU) == $skuNuevo) {
            $itemsSimulados[$key]->Cantidad += $cantidadNueva;
            $skuEncontradoEnOrden = true;
        }
    }

    // Si es un material nuevo que no se ha guardado en DB, simulamos su fila con el centro de la orden
    if (!$skuEncontradoEnOrden) {
        $centroBase = DB::table('ETA')
        ->where('fkTienda', session('user_fkTienda'))
        ->where('Orden', $orden)->value('CENTRO') ?? "'G888";
        $itemsSimulados[] = (object)[
            'CENTRO' => $centroBase,
            'SKU' => $skuNuevo,
            'Cantidad' => $cantidadNueva
        ];
    }

    // Ejecución del autómata con la lista combinada (DB + Simulado)
    foreach ($itemsSimulados as $item) {
        $this->ejecutarLogicaInterna($orden, $item, $procesados, $rastro);
    }

    $validaciones = $this->quitarDuplicadosPorOrdenYSKU($procesados);
    
    // Evaluamos el resultado del autómata únicamente para el SKU que se está interactuando
    foreach ($validaciones as $val) {
        if (trim($val->SKU) == $skuNuevo) {
            
            $calculado = (float)($val->valor_calculado ?? 0);
            $minimo = (float)($val->minimo_calculado ?? 0);
            $maximo = (float)($val->maximo_calculado ?? 0);
            $nombreMaterial = $val->nombre_material ?? "Material técnico";

            // Validación de Exceso
            if ($maximo > 0 && $calculado > $maximo) {
                $diff = $calculado - $maximo;
                return response()->json([
                    'sugerencia' => [
                        'status' => 'exceso',
                        'mensaje' => "El sistema detectó que estás reportando de más para '{$nombreMaterial}'. El tope máximo según la norma del centro es de {$maximo} unidades. Estás excedido por {$diff}."
                    ]
                ], 200);
            }

            // Validación de Faltante
            if ($minimo > 0 && $calculado < $minimo) {
                $diff = $minimo - $calculado;
                return response()->json([
                    'sugerencia' => [
                        'status' => 'falta',
                        'mensaje' => "Atención: Según las reglas de cubicación para '{$nombreMaterial}', faltan insumos obligatorios para cerrar la instalación. El mínimo técnico es de {$minimo} unidades (te hacen falta {$diff})."
                    ]
                ], 200);
            }
        }
    }

    // Si pasa todas las reglas del árbol jerárquico de validación
    return response()->json(['sugerencia' => null], 200);
}
    public function validarMaterialesTecnicos(Request $request) {
        
    $materialesInput = $request->input('materiales', []);
    $procesados = [];
    $rastro = [];

    foreach ($materialesInput as $item) {
        // Convertimos a objeto para que sea compatible con tu lógica de ejecutarLogicaInterna
        $objItem = (object)[
            'SKU' => $item['sku'],
            'Cantidad' => $item['cantidad'],
            'CENTRO' => 'TEMP' // Opcional si no filtras por centro aquí
        ];
        
        $this->ejecutarLogicaInterna(0, $objItem, $procesados, $rastro);
    }

    return response()->json(['validaciones' => array_values($procesados)]);
}


    public function inventariotecnicoorden($tecbucket)
    {
        DB::connection()->disableQueryLog();

        try {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $orden = Expedientetecnico::where('id', $tecbucket)
            ->where(function($query) {
                $query->where('Estatus', 'I')
                    ->orWhere('Estatus', 'S')
                    ->orWhere('Estatus', 'A')
                    ->orWhere('Estatus', 'O')
                    ->orWhere('Estatus', 'C');
            })
            ->first();


        $tecnico = Tecnico::where('id',$orden->fkTecnico)->first();


        return view('buckettecnico.edit', compact('tecbucket', 'orden','tecnico'));
      }  catch (Exception $e) {         
            return response()->json(['error, es posible que esta orden ya no cuente con registro para modificar' => $e->getMessage()], 400);
        }
    }

            public function fillEstructura()
    {
try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $sqlll='
        SELECT DISTINCT am.nombre, am.id, am.SKU FROM arbolmaterial as amo
        inner join (select ams.id, ams.SKU, ams.nombre from arbolmaterial as ams where isnull(ams.padre_id)) AS am on am.id=amo.padre_id
        where fkTienda=:id
        ';
        $stmt = $pdo->prepare($sqlll);

        $stmt->execute(['id' => $fkTienda]);


        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


    return response()->json($detallecomprobante);

            } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
            DB::rollBack();
        }

    }
    public function fetch2(Request $request)
    {
        $id = $request->input('id');
        // Inicializamos el ID de la categoría padre como NULL para empezar desde el nodo raíz.
        $data = $this->get_node_data($id);

        // Codificamos los datos en formato JSON para enviarlos al frontend.
        echo json_encode(array_values($data));
    }

function get_node_data($parent_category_id)
{
    $result = DB::table('arbolmaterial')
        ->where('padre_id', $parent_category_id) 
        ->where('fkTienda', session('user_fkTienda'))        
        ->orderBy('nombre', 'asc') // <-- NUEVO: Desempata por nombre de material
        ->orderBy('SKU', 'asc') 
        ->get();

    $output = []; 

    foreach ($result as $row) {
        $sub_array = [];
        $sub_array['nodeId'] = $row->id; 
        $sub_array['Cid'] = $row->id; 
        $sub_array['padre_id'] = $row->padre_id; 
        $sub_array['cuenta_id'] = $row->SKU; 
        $sub_array['text'] = $row->SKU."-".$row->nombre; 
        $sub_array['nombre'] = $row->nombre; 
        $sub_array['aplicafotografia'] = $row->aplicafotografia; 
        $sub_array['Tipo_servicio'] = $row->Tipo_servicio; 
        $sub_array['nodes'] = $this->get_node_data($row->id); 
        $sub_array['idpivote'] = $row->idpivote; 
        $output[] = $sub_array; 
    }

    return $output;
}


    public function obtenerExistenciasSap(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        $fkTienda = session('user_fkTienda');

        // 🌟 1. Subconsulta para procesar, limpiar y agrupar lo que ya se extrajo en el sistema local
        $subconsultaLocal = DB::table('movimientomateriales')
            ->select(
                'SKU',
                DB::raw("
                    CASE 
                        WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                        WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                        ELSE 'N/A'
                    END as serie_maestra
                "),
                // Netear las entradas ('E') y salidas ('H') locales
                DB::raw("
                    SUM(
                        CASE 
                            WHEN Naturaleza = 'E' THEN IFNULL(cantidad, 0)
                            WHEN Naturaleza = 'H' THEN -IFNULL(cantidad, 0)
                            ELSE IFNULL(cantidad, 0)
                        END
                    ) as total_local
                ")
            )
            ->where('fkTienda', $fkTienda)
            ->whereIn('Status', ['I', 'A'])
            ->groupBy('SKU', DB::raw("
                CASE 
                    WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                    WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                    ELSE 'N/A'
                END
            "));

        // 🌟 2. Subconsulta para limpiar y estandarizar las series registradas en la tabla de SAP
        $subconsultaSap = DB::table('materialexistentesap')
            ->select(
                'SKU',
                'nombre as descripcion',
                'cantidad',
                DB::raw("
                    CASE 
                        WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                        WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                        ELSE 'N/A'
                    END as serie_sap
                ")
            )
            ->where('fkTienda', $fkTienda);

        // 🌟 3. Consulta Principal: Restamos el histórico local al inventario inicial de SAP
        $disponibles = DB::table($subconsultaSap, 'sap')
            ->leftJoinSub($subconsultaLocal, 'local', function ($join) {
                $join->on('local.SKU', '=', 'sap.SKU')
                     ->on('local.serie_maestra', '=', 'sap.serie_sap');
            })
            ->select(
                'sap.SKU as sku',
                'sap.descripcion',
                'sap.serie_sap as serie',
                // Operación: Inventario inicial de SAP menos lo que ya se asignó localmente
                DB::raw("
                    SUM(IFNULL(sap.cantidad, 0)) - IFNULL(local.total_local, 0) as cantidad_disponible
                ")
            )
            ->groupBy('sap.SKU', 'sap.descripcion', 'sap.serie_sap', 'local.total_local')
            // Solo muestra filas donde SAP tenga más stock del que se ha asignado localmente (las diferencias)
            ->having('cantidad_disponible', '>', 0)
            ->get();

        return response()->json($disponibles);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function obtenerItemsSap(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        $fkTienda = session('user_fkTienda');

        // Subconsulta de lo extraído localmente (E suma, H resta) agrupado por SKU y Serie Maestra
        $subconsultaLocal = DB::table('movimientomateriales')
            ->select(
                'SKU',
                DB::raw("
                    CASE 
                        WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                        WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                        ELSE 'N/A'
                    END as serie_maestra
                "),
                DB::raw("
                    SUM(
                        CASE 
                            WHEN Naturaleza = 'E' THEN IFNULL(cantidad, 0)
                            WHEN Naturaleza = 'H' THEN -IFNULL(cantidad, 0)
                            ELSE IFNULL(cantidad, 0)
                        END
                    ) as total_local
                ")
            )
            ->where('fkTienda', $fkTienda)
            ->whereIn('Status', ['I', 'A'])
            ->groupBy('SKU', DB::raw("
                CASE 
                    WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                    WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                    ELSE 'N/A'
                END
            "));

        // Subconsulta para normalizar los registros cargados inicialmente de SAP
        $subconsultaSap = DB::table('materialexistentesap')
            ->select(
                'SKU',
                'nombre as descripcion',
                'cantidad',
                DB::raw("
                    CASE 
                        WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                        WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                        ELSE 'N/A'
                    END as serie_sap
                ")
            )
            ->where('fkTienda', $fkTienda);

        // Neteo final de remanentes
        $disponibles = DB::table($subconsultaSap, 'sap')
            ->leftJoinSub($subconsultaLocal, 'local', function ($join) {
                $join->on('local.SKU', '=', 'sap.SKU')
                     ->on('local.serie_maestra', '=', 'sap.serie_sap');
            })
            ->select(
                'sap.SKU as sku',
                'sap.descripcion',
                'sap.serie_sap as serie',
                DB::raw("SUM(IFNULL(sap.cantidad, 0)) - SUM(IFNULL(local.total_local, 0)) as cantidad_disponible")
            )
            ->groupBy('sap.SKU', 'sap.descripcion', 'sap.serie_sap')
            ->having('cantidad_disponible', '>', 0)
            ->get();

        return response()->json($disponibles);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function obtenerCentrosLocales(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        $fkTienda = session('user_fkTienda');

        $centrosConMovimientos = DB::table('movimientomateriales')
            ->where('fkTienda', $fkTienda)
            ->whereNotNull('CENTRO')
            ->where('CENTRO', '<>', '')
            ->distinct()
            ->pluck('CENTRO');

        $bodegas = DB::table('centro')
            ->select('codigo', 'nombre')
            ->where('fkTienda', $fkTienda)
            ->whereIn('codigo', $centrosConMovimientos)
            ->orderBy('codigo', 'asc')
            ->get();

        return response()->json($bodegas);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function obtenerProductosInventario(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        $fkTienda = session('user_fkTienda');
        $tipo = $request->input('tipo');
        $centro = $request->input('centro');

        // Subconsulta catálogo base para descripciones limpias
        $subconsultaCatalogo = DB::table('treematerialescategoria')
            ->select('SKU', DB::raw('MIN(nombre) as descripcion'))
            ->where('fkTienda', $fkTienda)
            ->groupBy('SKU');

        if ($tipo === 'raiz') {
            // ==========================================
            // LÓGICA DE NEGOCIO: EXTRACCIÓN DESDE RAÍZ / SAP
            // ==========================================
            $subconsultaLocal = DB::table('movimientomateriales')
                ->select(
                    'SKU',
                    DB::raw("
                        CASE 
                            WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                            WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                            ELSE 'N/A'
                        END as serie_maestra
                    "),
                    DB::raw("SUM(CASE WHEN Naturaleza = 'E' THEN IFNULL(cantidad, 0) WHEN Naturaleza = 'H' THEN -IFNULL(cantidad, 0) ELSE IFNULL(cantidad, 0) END) as total_local")
                )
                ->where('fkTienda', $fkTienda)
                ->whereIn('Status', ['I', 'A'])
                ->groupBy('SKU', DB::raw("CASE WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie) WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1) ELSE 'N/A' END"));

            $subconsultaSap = DB::table('materialexistentesap')
                ->select(
                    'SKU',
                    'nombre as descripcion',
                    'cantidad',
                    DB::raw("CASE WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie) WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1) ELSE 'N/A' END as serie_sap")
                )
                ->where('fkTienda', $fkTienda);

            $productos = DB::table($subconsultaSap, 'sap')
                ->leftJoinSub($subconsultaLocal, 'local', function ($join) {
                    $join->on('local.SKU', '=', 'sap.SKU')->on('local.serie_maestra', '=', 'sap.serie_sap');
                })
                ->select(
                    'sap.SKU as sku',
                    'sap.descripcion',
                    'sap.serie_sap as serie',
                    DB::raw("SUM(IFNULL(sap.cantidad, 0)) - SUM(IFNULL(local.total_local, 0)) as cantidad")
                )
                ->groupBy('sap.SKU', 'sap.descripcion', 'sap.serie_sap')
                ->having('cantidad', '>', 0)
                ->get();

            return response()->json($productos);

        } else {
            // ==========================================
            // LÓGICA DE NEGOCIO: BODEGA FÍSICA ESPECÍFICA (Filtra por CENTRO)
            // ==========================================
            $subconsultaMovimientos = DB::table('movimientomateriales')
                ->select(
                    'SKU',
                    'Naturaleza',
                    'cantidad',
                    // Prioridad absoluta a columna serie, si no MAC1, de lo contrario 'N/A'
                    DB::raw("
                        CASE 
                            WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                            WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                            ELSE 'N/A'
                        END as serie_maestra
                    ")
                )
                ->where('fkTienda', $fkTienda)
                ->where('CENTRO', $centro) // Filtro dinámico según la bodega seleccionada
                ->whereIn('Status', ['I', 'A']);

            $productos = DB::table($subconsultaMovimientos, 'mov')
                ->leftJoinSub($subconsultaCatalogo, 'tmc_unica', function ($join) {
                    $join->on('tmc_unica.SKU', '=', 'mov.SKU');
                })
                ->select(
                    'mov.SKU as sku',
                    'tmc_unica.descripcion',
                    'mov.serie_maestra as serie',
                    DB::raw("
                        SUM(
                            CASE 
                                WHEN mov.Naturaleza = 'E' THEN IFNULL(mov.cantidad, 0)
                                WHEN mov.Naturaleza = 'H' THEN -IFNULL(mov.cantidad, 0)
                                ELSE IFNULL(mov.cantidad, 0)
                            END
                        ) as cantidad
                    ")
                )
                ->groupBy('mov.SKU', 'tmc_unica.descripcion', 'mov.serie_maestra')
                ->having('cantidad', '>', 0)
                ->get();

            return response()->json($productos);
        }
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


public function fillEstructuraMO($id)
{
    DB::connection()->disableQueryLog();

    try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        
        // 🌟 Usamos GROUP BY en lugar de DISTINCT para fusionar los SKUs duplicados 
        // de este ID tecnológico específico (:id)
        $sqlll = '
            SELECT 
                TRIM(am.nombre) as nombre, 
                MAX(am.id) as id, 
                TRIM(am.SKU) as SKU 
            FROM arbolmaterial as am 
            WHERE am.padre_id = :id 
              AND am.fkTienda = :id2
            GROUP BY 
                TRIM(am.nombre), 
                TRIM(am.SKU)
        ';
        
        $stmt = $pdo->prepare($sqlll);
        $stmt->execute(['id' => $id, 'id2' => $fkTienda]);
        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return response()->json($detallecomprobante);

    } catch (\Exception $e) { 
        return response()->json(['error' => $e->getMessage()], 400);
    }
}

public function scanMaterialGlobal(Request $request, $sku)
{
    DB::connection()->disableQueryLog();

    try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        
        $idManoObra = $request->input('id_manoobra'); 
        $idtecnico = $request->input('id_tecnico');   
        $codigoEscaneado = trim($sku);

        if (empty($idManoObra) || empty($codigoEscaneado)) {
            return response()->json(['error' => 'Parámetros incompletos. Seleccione Mano de Obra.'], 400);
        }

        // =================================================================
        // PASO 1: BUSQUEDA PRIORITARIA POR SERIE FISICA
        // =================================================================
        $subconsultaCatalogoSerie = DB::table('treematerialescategoria')
            ->select('SKU', DB::raw('MIN(nombre) as nombre'))
            ->where('fkTienda', $fkTienda)
            ->groupBy('SKU');

        $buscarPorSerie = \App\Models\MovimientoMaterial::joinSub($subconsultaCatalogoSerie, 'tmc_unica', function ($join) {
                $join->on('tmc_unica.SKU', '=', 'movimientomateriales.SKU');
            })
            ->where('movimientomateriales.fkTienda', $fkTienda)
            ->where('movimientomateriales.fkTecnico', $idtecnico)
            ->where('movimientomateriales.serie', $codigoEscaneado) // Filtro estricto por Serie
            ->where('movimientomateriales.STATUS', 'I')
            ->select(
                'movimientomateriales.id',
                'movimientomateriales.serie',
                'movimientomateriales.CENTRO',
                'tmc_unica.nombre as categoria_nombre',
                'movimientomateriales.SKU as sku',
                'movimientomateriales.cantidad'
            )
            ->first();

        // Si se encuentra por SERIE, se retorna inmediatamente con bandera de pase directo
        if ($buscarPorSerie) {
            return response()->json([
                'tipo' => 'serie',
                'data' => $buscarPorSerie
            ]);
        }

        // =================================================================
        // PASO 2: SI NO ES SERIE, BUSCAR COINCIDENCIAS POR SKU EN TU ARBOL
        // =================================================================
        $sqlArbol = "
            SELECT amo.id, amo.nombre, TRIM(amo.SKU) as sku, amo.aplicafotografia 
            FROM arbolmaterial mat
            INNER JOIN arbolmanoobra am ON mat.idpivote = am.id
            INNER JOIN arbolmanoobra amo ON am.id = amo.padre_id
            WHERE mat.fkTienda = ? AND mat.padre_id = ? AND TRIM(amo.SKU) = ?;";

        $stmt = $pdo->prepare($sqlArbol);
        $stmt->execute([$fkTienda, $idManoObra, $codigoEscaneado]);
        $autorizadoEnArbol = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($autorizadoEnArbol)) {
            return response()->json(['status' => 'no_permitido']); 
        }

        // Consultar todos los materiales físicos en bodega que tengan este SKU
        $subconsultaCatalogoSku = DB::table('treematerialescategoria')
            ->select('SKU', DB::raw('MIN(nombre) as nombre'))
            ->where('fkTienda', $fkTienda)
            ->where('SKU', $codigoEscaneado)
            ->groupBy('SKU');

        $materialesPorSku = \App\Models\MovimientoMaterial::joinSub($subconsultaCatalogoSku, 'tmc_unica', function ($join) {
                $join->on('tmc_unica.SKU', '=', 'movimientomateriales.SKU');
            })
            ->where('movimientomateriales.fkTienda', $fkTienda)
            ->where('movimientomateriales.fkTecnico', $idtecnico)
            ->where('movimientomateriales.SKU', $codigoEscaneado)
            ->where('movimientomateriales.STATUS', 'I')
            ->select(
                'movimientomateriales.id',
                'movimientomateriales.serie',
                'movimientomateriales.CENTRO',
                'tmc_unica.nombre as categoria_nombre',
                'movimientomateriales.SKU as sku',
                'movimientomateriales.cantidad'
            )
            ->get();

        if ($materialesPorSku->isEmpty()) {
            return response()->json(['status' => 'sin_stock', 'sku' => $codigoEscaneado]);
        }

        // Retornar lista completa de opciones encontradas para el SKU
        return response()->json([
            'tipo' => 'sku',
            'data' => $materialesPorSku
        ]);

    } catch (\Exception $e) { 
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


public function InventarioLista(Request $request) 
{
    DB::connection()->disableQueryLog();

    try {
        $fkTienda = session('user_fkTienda');
        $pdo = DB::getPdo();
        $idPadre = $request->input('id1'); 
        $idtecnico = $request->input('id2');
        
        $idTecnoligiaSelect = $request->input('id_tecnologia'); 

        // Consulta SQL recursiva original para extraer el árbol de categorías
        $sqlll = "
WITH RECURSIVE nodo_padre AS (
    SELECT id, padre_id, nombre, SKU, aplicafotografia as apf, Tipo_servicio as TP
    FROM arbolmanoobra
    WHERE id = ? AND fkTienda = ?    
    UNION ALL    
    SELECT a.id, a.padre_id, a.nombre, a.SKU, a.aplicafotografia as apf, a.Tipo_servicio as TP
    FROM arbolmanoobra a
    INNER JOIN nodo_padre np ON a.padre_id = np.id
    WHERE a.fkTienda = ?
),
cte_hijos AS ( 
    SELECT id, padre_id, TRIM(nombre) as nombre, TRIM(SKU) as sku_hijo, apf, TP 
    FROM nodo_padre 
    WHERE id <> ?
)
SELECT DISTINCT
    am.nombre, 
    am.SKU AS sku, 
    am.limite, 
    am.minimo, 
    am.fkTienda, 
    am.padre_id, 
    r.apf, 
    r.TP, 
    am_padre.nombre AS categoria_nombre
FROM cte_hijos AS r
JOIN treematerialescategoria AS am 
    ON TRIM(am.SKU) = r.sku_hijo 
    AND am.fkTienda = ?
LEFT JOIN treematerialescategoria AS am_padre 
    ON am.padre_id = am_padre.id;";

        $stmt = $pdo->prepare($sqlll);
        $stmt->execute([$idPadre, $fkTienda, $fkTienda, $idPadre, $fkTienda]);
        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $contieneMO = collect($detallecomprobante)->contains('TP', 'MO');

        if ($contieneMO) {
            $final = [];
            $skusProcesadosMO = [];

            foreach ($detallecomprobante as $value) {
                if (in_array($value['sku'], $skusProcesadosMO)) {
                    continue; 
                }
                $skusProcesadosMO[] = $value['sku'];

                $final[] = [
                    'id'               => 0,
                    'serie'            => '',
                    'categoria_nombre' => $value['nombre'], 
                    'sku'              => $value['sku'],
                    'cantidad'         => $value['limite']
                ];
            }
        } else {
            $skusValidos = array_values(array_unique(collect($detallecomprobante)->pluck('sku')->filter()->toArray()));
            
            if (empty($skusValidos)) {
                return response()->json([]);
            }

            $subconsultaCatalogo = DB::table('treematerialescategoria')
                ->select('SKU', DB::raw('MIN(nombre) as nombre'))
                ->where('fkTienda', $fkTienda)
                ->groupBy('SKU');

            // 🌟 1. Subconsulta Maestra: Mantiene tu lógica de unificación de series intacta
            $subconsultaMovimientos = DB::table('movimientomateriales')
                ->select(
                    'id',
                    'fkTienda',
                    'fkTecnico',
                    'SKU',
                    'Status',
                    'CENTRO',
                    'cantidad',
                    // 🌟 MANTENEMOS TU LÓGICA DE SERIES ORIGINAL: Omite y agrupa de forma correcta
                    DB::raw("
                        CASE 
                            WHEN TRIM(serie) IN ('', '-', '0', 'N/A') AND TRIM(MAC1) IN ('', '-', '0', 'N/A') THEN 'N/A'
                            WHEN TRIM(serie) IN ('', '-', '0', 'N/A') AND TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                            ELSE TRIM(serie)
                        END as serie_unificada
                    ")
                )
                ->where('movimientomateriales.fkTienda', $fkTienda)
                ->where('movimientomateriales.fkTecnico', $idtecnico)
                ->whereIn('movimientomateriales.SKU', $skusValidos)
                ->where('movimientomateriales.Status', 'I') 
                ->where('movimientomateriales.ESTATUS', 'DISPONIBLE');

            // 🌟 2. Consulta Final: Agrupa por la serie unificada aplicando la suma directa sin Naturaleza
            $final = DB::table($subconsultaMovimientos, 'mov')
                ->leftJoinSub($subconsultaCatalogo, 'tmc_unica', function ($join) {
                    $join->on('tmc_unica.SKU', '=', 'mov.SKU');
                })
                ->select(
                    DB::raw('MAX(mov.id) as id'),
                    'mov.serie_unificada as serie', // Mantiene la serie agrupada/filtrada para el Blade
                    DB::raw('MAX(mov.CENTRO) as CENTRO'),
                    'tmc_unica.nombre as categoria_nombre',
                    'mov.SKU as sku',
                    // 🌟 LA CORRECCIÓN SOLICITADA: Suma directa limpia del campo cantidad rebajado
                    DB::raw("SUM(IFNULL(mov.cantidad, 0)) as cantidad")
                )
                ->groupBy(
                    'tmc_unica.nombre',
                    'mov.SKU',
                    'mov.serie_unificada' // Agrupa de forma estricta por la serie unificada
                )
                ->having('cantidad', '>', 0) 
                ->get();
        }

        return response()->json(is_array($final) ? $final : $final->toArray());

    } catch (\Exception $e) { 
        return response()->json(['error' => $e->getMessage()], 500);
    }
}




    public function update(UpdateTecnicoRequest $request, Tecnico $tecnico)
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            DB::beginTransaction();
            $tecnico->load('persona');

            $id = $tecnico->fkpersona;
            Persona::where('id', $id)
                ->update([
                    'razon_social' => $request->name
                ]);

            Tecnico::where('id', $tecnico->id)
                ->update(array_merge($request->validated(), ['nombre' => $request->name]));

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

        return redirect()->route('tecnico.lista')->with('success', 'Tecnico editado');
    }

public function operartrabajo(Request $request, Tecnico $tecnico, Expedientetecnico $expediente)
{
    try {

      DB::connection()->disableQueryLog();


        if (!Auth::check()) {
            return redirect()->route('login');
        }

        DB::beginTransaction();

        // 1. RECUPERACIÓN DE ARRAYS DEL FORMULARIO
        $iditemsInput    = $request->input('arrayiditem', []);
        $skusInput       = $request->input('arraysku', []);
        $cantidadesInput = $request->input('arraycantidad', []);
        $seriesInput     = $request->input('arrayserie', []);
        $nombresInput    = $request->input('arraynameProducto', []);
        $id_tecnico      = $request->input('id_tecnico');
        $eliminadosInput = $request->input('arrayEliminados', []); 
        $iditemsTecnologia = $request->input('arrayidTecnologia', []);
        $fkTienda      = session('user_fkTienda') ?? $expediente->fkTienda;
        $nombreUsuario = session('nombreUsuario') ?? Auth::user()->name ?? 'SISTEMA';
        $ahora         = now();
        $centroTecnico = Tecnico::where('id', $id_tecnico)->value('codigo') ?? 'N/A';

        // =================================================================
        // SECCIÓN A: PROCESAR ELEMENTOS BORRADOS (DEVOLUCIÓN FIFO)
        // =================================================================
        if (!empty($eliminadosInput)) {
            // Buscamos las salidas eliminadas asegurando que apunte al TIPOMOVIMIENTO o ESTATUS real
            $salidasAEliminar = MovimientoMaterial::whereIn('id', $eliminadosInput)
                ->where('fkExpediente', $expediente->id)
                ->where(function($query) {
                    $query->where('TIPOMOVIMIENTO', 'CONSUMO') // La bandera real de tus inserts anteriores
                          ->orWhere('TIPOMOVIMIENTO', 'INSTALADO')
                          ->orWhere('TIPOMOVIMIENTO', 'CONSUMO_INSTALACION')
                          ->Where('ESTATUS', 'INSTALADO'); // Por si se guardó en la columna de estatus
                })
                ->get();

            foreach ($salidasAEliminar as $salida) {
                
                // 1. Identificamos si el ítem que estamos borrando de la orden del cliente es seriado o misceláneo
                $serieLimpia = preg_replace('/\s+/', '', strtoupper($salida->serie));
                $esSeriadoEliminado = !in_array($serieLimpia, ['-', '0', 'N/A', 'NA', ''], true);

                if ($esSeriadoEliminado) {
                    // =================================================================
                    // CASO A: REVERTIR MATERIAL SERIADO (Revivir fila original)
                    // =================================================================
                    // Buscamos la fila original del técnico que el sistema apagó como 'AGOTADO'
                    $origenSeriado = DB::table('movimientomateriales')
                        ->where('fkTecnico', $id_tecnico)
                        ->where('SKU', $salida->SKU)
                        ->where('id', $salida->id)
                        ->where('fkTienda', $fkTienda)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($origenSeriado) {
                        // Devolvemos la cantidad exacta, y la volvemos a encender en 'I' y 'DISPONIBLE'
                        DB::table('movimientomateriales')
                            ->where('id', $origenSeriado->id)
                            ->update([
                                'cantidad'       => 1,
                                'Status'         => 'I', // Vuelve a estar activo en el bucket
                                'ESTATUS'        => 'DISPONIBLE', // Vuelve a estar disponible para usar
                                'Modificado_el'  => $ahora,
                                'fkExpediente'   => null, 
                                'Modificado_por' => $nombreUsuario,
                                'updated_at'     => $ahora
                            ]);
                    }
                } else {
                    // =================================================================
                    // CASO B: REVERTIR MATERIAL MISCELÁNEO (Adición de volumen por FIFO)
                    // =================================================================
                    // Buscamos la fila base activa del técnico donde reside el volumen del SKU
         $origenMiscelaneo = DB::table('movimientomateriales')
                        ->where('SKU', $salida->SKU)
                        ->where('CENTRO', $salida->CENTRO)
                        ->where('fkTienda', $fkTienda)
                        ->where('Naturaleza', 'E') // Solo sumamos a entradas legítimas
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($origenMiscelaneo) {
                        // Le sumamos de vuelta la cantidad que se había descontado
                        DB::table('movimientomateriales')
                            ->where('id', $origenMiscelaneo->id)
                            ->update([
                                'cantidad'       => $origenMiscelaneo->cantidad + floatval($salida->cantidad),
                                'ESTATUS'        => 'DISPONIBLE', // Aseguramos que vuelva a estar disponible
                                'TIPOMOVIMIENTO' => 'ENTRADA', // Revertimos a entrada para FIFO
                                'Status'         => 'I', // Vuelve a estar activo en el bucket
                                'Modificado_el'  => $ahora,
                                'fkExpediente'   => null,
                                'fkTecnico'      => $id_tecnico,
                                'Modificado_por' => $nombreUsuario,
                                'updated_at'     => $ahora
                            ]);
                    }

                    // LIMPIEZA DE BITÁCORA: Eliminamos la fila clonada de 'TRANSITO_INSTALACION' 
                    // que tu código inyectó en el insert del Caso B de materiales para no dejar basura.
                    DB::table('movimientomateriales')
                        ->where('fkExpediente', $expediente->id)
                        ->where('fkTecnico', $id_tecnico)
                        ->where('SKU', $salida->SKU)
                        ->where('id', $salida->id)
                        ->delete();

                    DB::table('pagotecnico')
                    ->where('id', $salida->id)
                    ->delete();
                

                }


                // 3. Eliminamos el registro de pago o destajo asociado a esta acción borrada
                DB::table('pagotecnico')
                    ->where('Orden', $expediente->Orden)
                    ->where('fkTecnico', $id_tecnico)
                    ->where('SKU', $salida->SKU)
                    ->delete();

            } // <<< FIN DEL BUCLE FOREACH DE ELIMINADOS >>>

        }

                if (empty($skusInput)) {
            DB::commit();
            
            Log::info("Proceso concluido: Se procesaron únicamente eliminaciones. El archivo/formulario no contenía nuevos SKUs.");
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Devoluciones de inventario procesadas y guardadas correctamente.'
            ]);
        }
        
        // =================================================================
        // SECCIÓN B: PROCESAR ITEMS ACTUALES (AGREGAR NUEVO O MANTIENE)
        // =================================================================
        foreach ($skusInput as $contar => $sku) {
            $cantidadRequerida = floatval($cantidadesInput[$contar] ?? 1);
            $serie             = ($seriesInput[$contar] ?? null) ?: '-';
            $iditem            = $iditemsInput[$contar] ?? 0;
            $skuActual         = strtoupper(trim($sku));
            $serieBusqueda     = trim($serie); 

            // CORRECCIÓN 1: Filtro limpio sin "str_contains" para no matar materiales legítimos
            if (empty($skuActual)) {
                continue; 
            }

            // CORRECCIÓN 2: El validador inicial busca todas las variantes de inserción del cliente
            $yaExisteEnBD = MovimientoMaterial::where('fkExpediente', $expediente->id)
                ->where('SKU', $skuActual)
                ->where('serie', $serieBusqueda)
                ->where('cantidad', $cantidadRequerida)
                ->whereIn('TIPOMOVIMIENTO', ['INSTALADO', 'CONSUMO_INSTALACION', 'CONSUMO_INSTALACION_SIN_STOCK'])
                ->first();

            if ($yaExisteEnBD) {
                continue; // Saltar si ya existe para evitar duplicación
            }

            // Buscar en el maestro de artículos
            $maestroItem = DB::table('MaterialManoObra')
                ->where('SKU', $skuActual)
                ->where('fkTienda', $fkTienda)
                ->first();

            $tipoItem = $maestroItem ? $maestroItem->CATEGORIA : 'MATERIAL'; 

            if ($tipoItem === 'MANO DE OBRA' || $tipoItem === 'MANO OBRA') {
                $tipoItem = 'MO';
            }

            // Jerarquía de precios basada en el código alfanumérico del técnico
            $tecnicoCodigo = DB::table('tecnico')->where('id', $id_tecnico)->value('codigo') ?? '';
            $costoUnidad = Materialmanoobra::where('SKU', $skuActual)
                ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
                    $query->where('centrocostoespecifico', '=', $tecnicoCodigo) 
                          ->orWhere('centrocostoespecifico', '=', $fkTienda)    
                          ->orWhereNull('centrocostoespecifico')               
                          ->orWhere('centrocostoespecifico', '=', '');         
                })
                ->select('CATEGORIA', 'CATEGORIACOBRO', 'COSTOPAGO', 'TIPO', 'unidadmedida', 'centrocostoespecifico', 'SKU', 'Descripcion')
                ->orderByRaw("CASE 
                    WHEN centrocostoespecifico = ? AND ? != '' THEN 1
                    WHEN centrocostoespecifico = ? THEN 2
                    ELSE 3 
                END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
                ->latest() 
                ->first();

            // Extracción segura de costos iniciales globales
            $costoFinal = 0;
            $unidadMedidaFinal = 'PZA';
            if ($costoUnidad) {
                $costoFinal = ($costoUnidad->CATEGORIA === 'MANO DE OBRA' || $costoUnidad->TIPO === 'MO') 
                    ? $costoUnidad->COSTOPAGO 
                    : ($costoUnidad->CATEGORIACOBRO ?? 0);
                $unidadMedidaFinal = $costoUnidad->unidadmedida ?? 'PZA';
            }

            $serieLimpia = preg_replace('/\s+/', '', strtoupper($serie));
            $esSeriado   = !in_array($serieLimpia, ['-', '0', 'N/A', 'NA', ''], true);

            // -------------------------------------------------------------
            // B.2. BIFURCACIÓN: CASO A - MANO DE OBRA PURA DIRECTA
            // -------------------------------------------------------------
            if ($tipoItem === 'MO') {
                $manoObraInstalada = MovimientoMaterial::firstOrNew([
                    'fkExpediente' => $expediente->id,
                    'SKU'          => $skuActual,
                    'serie'        => $serieBusqueda,
                ]);

                if (!$manoObraInstalada->exists) {
                    $manoObraInstalada->Creado_el  = $ahora;
                    $manoObraInstalada->Creado_por = $nombreUsuario;
                    $manoObraInstalada->TIPO       = 'MO';
                    $manoObraInstalada->TIPOMOVIMIENTO = 'INSTALADO';
                }

                $manoObraInstalada->fill([
                    'fkTecnico'      => $id_tecnico,
                    'fkTienda'       => $fkTienda,
                    'cantidad'       => $cantidadRequerida,
                    'CENTRO'         => 'CF',
                    'ESTATUS'        => 'INSTALADO',
                    'almacen'        => 'TRANSITO_INSTALACION',
                    'Naturaleza'     => 'H',
                    'Status'         => 'S', 
                    'Lote'           => 'A000',
                    'MAC1'           => '-', 
                    'MAC2'           => '-', 
                    'MAC3'           => '-', 
                    'COSTO'          => $costoFinal,
                    'unidadmedida'   => $unidadMedidaFinal,
                    'Modificado_el'  => $ahora,
                    'Modificado_por' => $nombreUsuario,
                    'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                ]);

                $manoObraInstalada->save();
                continue; 
            } 
            // -------------------------------------------------------------
            // B.2. BIFURCACIÓN: CASO B - MATERIALES (CORREGIDO PARA SERIES)
            // -------------------------------------------------------------
            else {
                $entradasDisponibles = MovimientoMaterial::where('fkTecnico', $id_tecnico)
                    ->where('SKU', $skuActual)
                    ->where('TIPOMOVIMIENTO', '!=', 'INSTALADO')
                    ->where('ESTATUS', '!=', 'INSTALADO')
                    ->where('cantidad', '>', 0)
                    ->where('Status', 'I')
                    ->when($esSeriado, function ($query) use ($serieBusqueda) {
                        return $query->where('serie', $serieBusqueda);
                    })
                    ->orderBy('created_at', 'asc')
                    ->get();

                $porDescontar = $cantidadRequerida;

                foreach ($entradasDisponibles as $entrada) {
                    if ($porDescontar <= 0) {
                        break;
                    }

                    $cantidadAExtraer = min($entrada->cantidad, $porDescontar);

                    $costoSeguroCliente  = 0;
                    $tipoSeguroCliente   = $entrada->TIPO ?? $tipoCatalogoMaestro ?? 'MATERIAL';
                    $unidadSeguraCliente = $costoUnidad->unidadmedida ?? $entrada->unidadmedida ?? 'PZA';

                    if ($costoUnidad) {
                        $costoSeguroCliente = (isset($costoUnidad->TIPO) && $costoUnidad->TIPO === 'MANO DE OBRA') 
                            ? $costoUnidad->COSTOPAGO 
                            : ($costoUnidad->CATEGORIACOBRO ?? 0);
                    }
                    if ($esSeriado) {
                        // ============================================================================
                        // 🌟 REGLA PARA SERIES: ACTUALIZACIÓN DIRECTA DE LA FILA (SIN DUPLICAR)
                        // ============================================================================
                        DB::table('movimientomateriales')
                            ->where('id', $entrada->id)
                            ->update([
                                'fkExpediente'      => $expediente->id,
                                'fkTecnico'         => $id_tecnico,
                                'fkTienda'          => $fkTienda,
                                'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                                'almacen'           => 'CLIENTE_FINAL',
                                'Lote'              => $entrada->Lote ?? 'VALORADO',
                                'COSTO'             => $costoSeguroCliente,
                                'TIPO'              => $tipoSeguroCliente,
                                'ESTATUS'           => 'INSTALADO',
                                'Status'            => 'S', 
                                'Naturaleza'        => 'H', 
                                'CENTRO'            => $centroTecnico ?? 'CF',
                                'cantidad'          => $cantidadAExtraer,
                                'unidadmedida'      => $unidadSeguraCliente,
                                'TIPOMOVIMIENTO'    => 'CONSUMO_INSTALACION',
                                'MAC1'              => $entrada->MAC1 ?? '0',
                                'MAC2'              => $entrada->MAC2 ?? '0',
                                'MAC3'              => $entrada->MAC3 ?? '0',
                                'Modificado_el'     => $ahora->format('Y-m-d'),
                                'Modificado_por'    => $nombreUsuario,
                                'updated_at'        => $ahora
                            ]);

                    } else {
                        // ============================================================================
                        // 📦 REGLA PARA MISCELÁNEOS: DESCUENTO PARCIAL Y UN SOLO INSERT DE CONSUMO
                        // ============================================================================
                        if ($entrada->cantidad > $cantidadAExtraer) {
                            // Si le sobra stock al pozo activo, solo descontamos la fracción usada
                            $entrada->decrement('cantidad', $cantidadAExtraer, [
                                'Modificado_el'  => $ahora,
                                'Modificado_por' => $nombreUsuario
                            ]);
                        } else {
                            // Si se consume completo, se pasa a histórico 'A' (Agotado)
                            $entrada->decrement('cantidad', $cantidadAExtraer);
                            $entrada->refresh();
                            $entrada->update([
                                'Status'         => 'A',
                                'ESTATUS'        => 'AGOTADO',
                                'Modificado_el'  => $ahora,
                                'Modificado_por' => $nombreUsuario
                            ]);
                        }

                        // 🌟 CORRECCIÓN CLAVE: Eliminamos el insert de TRANSITO_INSTALACION (Fila 1721).
                        // Solo dejamos el insert definitivo de consumo en la orden del cliente (Fila 1722).
                        DB::table('movimientomateriales')->insert([
                            'fkExpediente'      => $expediente->id,
                            'serie'             => $serieBusqueda,
                            'SKU'               => $skuActual,
                            'fkTienda'          => $fkTienda,
                            'fkTecnico'         => $id_tecnico,
                            'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                            'almacen'           => 'CLIENTE_FINAL',
                            'Lote'              => 'VALORADO',
                            'COSTO'             => $costoSeguroCliente, 
                            'TIPO'              => $tipoSeguroCliente,
                            'ESTATUS'           => 'INSTALADO',
                            'Status'            => 'S', // Estado de salida definitivo
                            'Naturaleza'        => 'H', // Egreso contable para el Kardex
                            'CENTRO'            => $centroTecnico ?? 'CF',
                            'cantidad'          => $cantidadAExtraer,
                            'unidadmedida'      => $unidadSeguraCliente, 
                            'TIPOMOVIMIENTO'    => 'CONSUMO_INSTALACION',
                            'MAC1'              => $entrada->MAC1 ?? '0',
                            'MAC2'              => $entrada->MAC2 ?? '0',
                            'MAC3'              => $entrada->MAC3 ?? '0',
                            'Creado_el'         => $ahora,
                            'Creado_por'        => $nombreUsuario,
                            'Modificado_el'     => $ahora->format('Y-m-d'),
                            'Modificado_por'    => $nombreUsuario,
                            'created_at'        => $ahora,
                            'updated_at'        => $ahora
                        ]);
                    }

                    $porDescontar -= $cantidadAExtraer;
                } // Fin del bucle foreach ($entradasDisponibles)

                // =================================================================
                // FAILSAFE AUTOMÁTICO (SOLO SI FALTÓ STOCK EN EL PROCESO)
                // =================================================================
                if ($porDescontar > 0) {
                    $tipoSeguroFailsafe   = ($costoUnidad && isset($costoUnidad->TIPO)) ? $costoUnidad->TIPO : $tipoCatalogoMaestro;
                    $costoSeguroFailsafe  = $costoFinal ?? 0;
                    $unidadSeguraFailsafe = $unidadMedidaFinal ?? 'PZA';

                    DB::table('movimientomateriales')->updateOrInsert(
                        [
                            'fkExpediente'      => $expediente->id,
                            'serie'             => $serieBusqueda,
                            'SKU'               => $skuActual,
                        ],
                        [
                            'fkTienda'          => $fkTienda,
                            'fkTecnologiaarbol' => $iditemsTecnologia[$contar] ?? null,
                            'almacen'           => 'CLIENTE_FINAL',
                            'Lote'              => 'VALORADO',
                            'COSTO'             => $costoSeguroFailsafe,
                            'TIPO'              => $tipoSeguroFailsafe, 
                            'ESTATUS'           => 'INSTALADO',
                            'Status'            => 'S',
                            'Naturaleza'        => 'H',
                            'CENTRO'            => $centroTecnico ?? 'CF',
                            'cantidad'          => $porDescontar,
                            'unidadmedida'      => $unidadSeguraFailsafe,
                            'TIPOMOVIMIENTO'    => 'CONSUMO_INSTALACION_SIN_STOCK',
                            'MAC1'              => '0',
                            'MAC2'              => '0',
                            'MAC3'              => '0',
                            'Creado_el'         => $ahora,         
                            'Creado_por'        => $nombreUsuario, 
                            'Modificado_el'     => $ahora->format('Y-m-d'),
                            'Modificado_por'    => $nombreUsuario,
                            'created_at'        => $ahora,
                            'updated_at'        => $ahora
                        ]
                    );
                }
            } // Fin de la bifurcación del Caso B (Materiales)

        } // <<< FIN COMPLETO DEL FOREACH GENERAL DE SKUS >>>





                    // -------------------------------------------------------------
            // B.4. PROCESAMIENTO DE IMÁGENES / EVIDENCIAS
            // -------------------------------------------------------------
            $photos = $request->input("items.{$contar}.photos", []);
            $names  = $request->input("items.{$contar}.names", []);
            $iditemsTecnologia = $request->input("items.{$contar}.fkTecnologia", []);

            if (!empty($photos) && is_array($photos)) {
                foreach ($photos as $i => $photoBase64) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64, $typeMatch)) {
                        $extension = strtolower($typeMatch[1]); 
                        $fileData = base64_decode(substr($photoBase64, strpos($photoBase64, ',') + 1));

                        if ($fileData) {
                            $nombreFotoLetras = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $names[$i] ?? 'foto');
                            $nombreProductoLetras = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombreProducto ?? $skuActual);
                            $idtec=$iditemsTecnologia[$i] ?? '0';

                            $nombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $nombreFotoLetras);
                            $productoNombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $nombreProductoLetras);
                            $nombreLimpio = preg_replace('/_+/', '_', $nombreLimpio);
                            $productoNombreLimpio = preg_replace('/_+/', '_', $productoNombreLimpio);

                            $fileName = trim($nombreLimpio, '_') . "_" . trim($productoNombreLimpio, '_') . "_" . uniqid() . ".{$extension}";
                            $gcsPath = "fotos/ordenes/{$expediente->Orden}/{$fileName}";

                            Storage::disk('gcs_images')->put($gcsPath, $fileData, 'public');
                            $urlFotografia = Storage::disk('gcs_images')->url($gcsPath);
             
                            Expedientefotograficotecnico::create([
                                'fkTienda'   => $fkTienda,
                                'Orden'      => $expediente->Orden,
                                'fotografia' => $urlFotografia, 
                                'fkTecnologia' => $idtec,
                            ]);
                            unset($fileData); 
                        }
                    }
                }
            }

        // =================================================================
        // SECCIÓN C: FINALIZACIÓN Y AUDITORÍA DEL EXPEDIENTE
        // =================================================================

if ($request->input('estatus') === 'S') {
    foreach ($skusInput as $contar => $sku) {
        $cantidadRequerida = floatval($cantidadesInput[$contar] ?? 1);
        $serie             = ($seriesInput[$contar] ?? null) ?: '-';
        $iditem            = $iditemsInput[$contar] ?? 0;
        
        $skuActual         = strtoupper(trim($sku));
        $docRef            = 'INS-' . $expediente->Orden . ';' .$skuActual.';' . $ahora->format('dmY:H:i:s') . ';' . $serie;
        $POSICION          = str_pad($contar+1, 4, '0', STR_PAD_LEFT);

        // Identificar el tipo de ítem de forma segura
        $tipoItem = DB::table('movimientomateriales')
            ->where('SKU', $skuActual)
            ->where('fkTecnico', $id_tecnico)
            ->where('fkTienda', $fkTienda)
            ->where('fkExpediente', $expediente->id)
            ->value('TIPO') ?? 'MO';
        
        $producto = Producto::where('codigo', $skuActual)->where('fkTienda', $fkTienda)->first();
                    
        // Cálculo seguro del costo a pagar de Mano de Obra (Evita duplicados)
        // 1. Obtenemos el código alfanumérico del técnico de forma rápida
        $tecnicoCodigo = DB::table('tecnico')
            ->where('id', $id_tecnico)
            ->value('codigo') ?? '';

        // 2. Buscamos el registro en el catálogo respetando la estricta jerarquía de prioridades
        $costoUnidad = Materialmanoobra::where('SKU', $skuActual)
            ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
                $query->where('centrocostoespecifico', '=', $tecnicoCodigo) 
                      ->orWhere('centrocostoespecifico', '=', $fkTienda)    
                      ->orWhereNull('centrocostoespecifico')               
                      ->orWhere('centrocostoespecifico', '=', '');         
            })
            // Agregado 'Descripcion' al select para poder usarlo en la creación del producto
            ->select('id', 'SKU', 'Descripcion', 'CATEGORIA', 'CATEGORIACOBRO', 'COSTOPAGO', 'TIPO', 'unidadmedida', 'centrocostoespecifico')
            ->orderByRaw("CASE 
                WHEN centrocostoespecifico = ? AND ? != '' THEN 1
                WHEN centrocostoespecifico = ? THEN 2
                ELSE 3 
            END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
            ->latest() 
            ->first();

        // =================================================================
        // CORRECCIÓN PROTEGIDA: Evita el error "Attempt to read property on null"
        // =================================================================
        $costoFinal = 0;
        $unidadMedidaFinal = 'PZA';
        $tipoCatalogoMaestro = 'MATERIAL';

        if ($costoUnidad) {
            // Si el registro existe en el catálogo, extraemos sus valores reales
            $costoFinal = ($costoUnidad->CATEGORIA === 'MANO DE OBRA' || $costoUnidad->TIPO === 'MO') 
                ? $costoUnidad->COSTOPAGO 
                : ($costoUnidad->CATEGORIACOBRO ?? 0);
            
            $unidadMedidaFinal   = $costoUnidad->unidadmedida ?? 'PZA';
            $tipoCatalogoMaestro = $costoUnidad->TIPO ?? 'MATERIAL';
        }

        // 3. Historial de Movimiento de Servicio (Solo si el estatus es 'MO')
        if ($tipoItem === "MO") {
            // Calculamos el precio unitario base de forma segura protegiendo contra nulos
            $precioUnitario = 0;
            if ($costoUnidad) {
                $precioUnitario = ($costoUnidad->CATEGORIA === 'MANO DE OBRA') 
                    ? $costoUnidad->COSTOPAGO 
                    : ($costoUnidad->CATEGORIACOBRO ?? 0);
            }

            // Ejecutamos el updateOrCreate con el cálculo matemático corregido
            Pagotecnico::updateOrCreate(
                [
                    'Orden'     => $expediente->Orden,
                    'SKU'       => $skuActual,
                    'fkTienda'  => $fkTienda,
                    'fkTecnico' => $id_tecnico,
                    'Naturaleza' => 'H',
                ], 
                [
                    'Descripcion' => $costoUnidad->Descripcion ?? "Servicio $skuActual",
                    'OBS'         => 'Pago por servicio tecnico (Mano de Obra)',
                    'Cantidad'    => $cantidadRequerida,
                    'COSTOPAGO'   => $cantidadRequerida * $precioUnitario,
                    'Status'      => 'S',
                ]
            );  
        }

        // Si es mano de obra, saltamos el bloque de inventario físico
        if (empty($tipoItem) || str_contains($tipoItem, 'MO') || str_contains($tipoItem, 'MANO')) {
            continue; 
        }

        // =================================================================
        // NUEVA VALIDACIÓN: Creación dinámica del producto ausente
        // =================================================================
        if (!$producto) {
            // Generamos un nombre por defecto en caso de que tampoco exista en el catálogo maestro
            $nombreProducto = $costoUnidad->Descripcion ?? "Producto SKU $skuActual";
            
            // Cortamos el nombre a 80 caracteres para evitar errores de truncado en la base de datos
            $nombreProducto = mb_substr($nombreProducto, 0, 80);

            $producto = Producto::create([
                'codigo'          => $skuActual,
                'nombre'          => $nombreProducto,
                'precio_base'     => $costoUnidad ? ($costoUnidad->CATEGORIACOBRO ?? 0) : 0,
                'stock'           => 0,
                'descripcion'     => mb_substr($nombreProducto, 0, 255),
                'estado'          => 1, // Habilitado por defecto
                'fkTienda'        => $fkTienda,
                'perecedero'      => 0,
                'stock_minimo'    => 0,
                'marca_id'        => null, // Modificar si posees un ID genérico
                'presentacione_id'=> null  // Modificar si posees un ID genérico
            ]);
        }

        // Registrar Historial de Salida Negativa (Clase 221)
        // (Ahora siempre entrará aquí porque si no existía, fue creado arriba)
        if ($producto) {
            MovimientoMateriales::create([
                'fkTienda'               => $fkTienda,
                'fkMateriales'           => $producto->id, // Aquí toma el ID existente o el recién creado
                'contrata'               => $id_tecnico,
                'clase_movimiento'       => '221',
                'cantidad'               => $cantidadRequerida * -1,
                'referencia'             => "CONSUMO INSTALACION | EXPEDIENTE: " . $expediente->id . " | SERIE: $serie",
                'tipo_movimiento'        => 'CONSUMO_INSTALACION',
                'documento_material'     => $docRef,
                'posicion_documento'     => $POSICION,
                'fecha_contabilizacion'  => $ahora->format('Y-m-d'),
                'almacen'                => 'CLIENTE_FINAL',
                'centro'                 => $centroTecnico,
                'unidad_medida_base'     => $costoUnidad->unidadmedida ?? 'UNIDAD',
                'centro_sap'             => session('centro'),
                'origen_uso'             => 'consumo_instalacion',
                'texto_clase_movimiento' => 'Salida por instalación a cliente final'
            ]);                   
        }    
}  // <--- AQUÍ TERMINA EL FOREACH

    // =========================================================================
    // CORRECCIÓN: Las actualizaciones masivas se ejecutan una sola vez al terminar todo el ciclo
    // =========================================================================
    $updateData = [
        'Status'           => 'A',
        'ESTATUS'          => 'C',
        'AUTORIZA'         => $id_tecnico,
        'FECHAINSTALACION' => $ahora,
    ];      

    DB::table('movimientomateriales')
        ->where('fkExpediente', $expediente->id)
        ->where('fkTecnico', $id_tecnico)
        ->update([
            'ESTATUS'        => 'INSTALADO_CERRADO',
            'Status'         => 'A',
            'ALMACEN'        => 'CLIENTE_FINAL',
            'Modificado_el'  => $ahora,
            'Modificado_por' => $nombreUsuario,
            'updated_at'     => $ahora
        ]);                                               
    // =========================================================================

} else {
    $updateData = [
        'Status'           => 'S',
        'ESTATUS'          => 'I',
        'AUTORIZA'         => $id_tecnico,
        'FECHAINSTALACION' => $ahora,
    ];
}

if ($request->filled('obs') && trim($request->input('obs')) !== '') {
    $nuevaObs = 'OBS TECNICO: ' . trim($request->input('obs'));
    
    // Si ya existe una observación previa, se concatena con ' || '; de lo contrario, se asigna limpia
    $updateData['OBS'] = !empty($expediente->OBS) 
        ? $expediente->OBS . ' || ' . $nuevaObs 
        : $nuevaObs;
}

$expediente->update($updateData);

        

        DB::commit();

        // Forzamos que la variable sea el ID numérico limpio enviado por el request
        $idBucketDestino = intval($id_tecnico); 

        // Calculamos la URL exacta de forma explícita
        $urlDestino = route('tecnico.bucket', ['usbucket' => $idBucketDestino]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'   => 'success', 
                'message'  => 'Orden actualizada con éxito vía FIFO.',
                'redirect' => $urlDestino // Enviará: verbtecnico/{id}/ver-bucket
            ], 200);
        }
        
        return redirect()->to($urlDestino)
            ->with('success', 'Orden actualizada con éxito vía FIFO.');
        
    } catch (Exception $e) {
        DB::rollBack();
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'error', 
                'message' => $e->getMessage()
            ], 500);
        }
        
        return redirect()->back()
            ->withInput()
            ->with('error', 'Ocurrió un error en el proceso: ' . $e->getMessage());
    }
}

public function bajaorden(Request $request, $etadirect)
{
    // 1. Validamos que el motivo haya sido redactado de forma obligatoria
    $request->validate([
        'motivo' => 'required|string|max:500'
    ]);

    // 2. Buscamos el expediente técnico correspondiente
    $orden = Expedientetecnico::findOrFail($etadirect);
    $orden->ESTATUS = 'B';
    $orden->Status = 'A';
    
    // 3. CAPTURA DEL TEXTAREA: Guardamos el string en la columna correspondiente
    // Reemplaza 'MOTIVO_BAJA' por el nombre exacto de la columna en tu base de datos
    $orden->OBS = $request->input('motivo'); 
    
    $orden->save();

    // 4. Cargamos de nuevo el listado según hayamos configurado el sistema
    $tecnicos = Tecnico::all(); 

    return view('tecnico.index', compact('tecnicos'))
        ->with('success', 'Orden dada de baja con motivo registrado exitosamente.');
}


public function guardarFotografiaAjax(Request $request)
{
    // 1. Modificar Validación para aceptar Arreglos (Arrays)
    $request->validate([
        'photos'              => 'required|array',
        'photos.*'            => 'required|string', // Cada elemento del array debe ser Base64
        'names'               => 'required|array',
        'names.*'             => 'required|string', // Cada nombre generado
        'iditemsTecnologia'   => 'required|array',
        'orden'               => 'required|string',
    ]);

    try {
        // Obtener variables generales de la petición
        $photosArray      = $request->input('photos', []);
        $namesArray       = $request->input('names', []);
        $iditemsTecnologia = $request->input('iditemsTecnologia', []);
        
        $orden            = $request->input('orden');
        $fkTienda         = session('user_fkTienda');
        
        $fotosGuardadasContador = 0;

        // 2. Iterar el lote de imágenes mediante un foreach
        foreach ($photosArray as $i => $photoBase64) {
            
            // Validar formato y decodificar el Base64 de forma individual
            if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64, $typeMatch)) {
                $extension = strtolower($typeMatch[1]); 
                $fileData = base64_decode(substr($photoBase64, strpos($photoBase64, ',') + 1));

                if ($fileData) {
                    // Extraer los datos correspondientes al índice actual del bucle
                    $photoName = $namesArray[$i] ?? 'EVIDENCIA_FOTO';
                    $idtec     = $iditemsTecnologia[$i] ?? 0;

                    // 3. Sanitizar nombres (Tu lógica limpia exacta)
                    $nombreFotoLetras = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $photoName);
                    $nombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $nombreFotoLetras);
                    $nombreLimpio = preg_replace('/_+/', '_', $nombreLimpio);

                    // Generar nombre de archivo único para evitar colisiones en GCS
                    $fileName = trim($nombreLimpio, '_') . "_" . uniqid() . ".{$extension}";
                    $gcsPath  = "fotos/ordenes/{$orden}/{$fileName}";

                    // 4. Subir de forma independiente a Google Cloud Storage
                    Storage::disk('gcs_images')->put($gcsPath, $fileData, 'public');
                    $urlFotografia = Storage::disk('gcs_images')->url($gcsPath);

                    // 5. Registrar registro único en la Base de Datos
                    Expedientefotograficotecnico::create([
                        'fkTienda'     => $fkTienda,
                        'Orden'        => $orden,
                        'fotografia'   => $urlFotografia, 
                        'fkTecnologia' => $idtec,
                    ]);

                    // Liberar memoria ram de este archivo inmediatamente en el ciclo
                    unset($fileData); 
                    $fotosGuardadasContador++;
                }
            }
        }

        // Retornar respuesta de éxito si se procesó al menos una imagen
        if ($fotosGuardadasContador > 0) {
            return response()->json([
                'success' => true, 
                'message' => "Se almacenaron ({$fotosGuardadasContador}) fotografías con éxito en la nube de forma exclusiva."
            ]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'Ninguna imagen pudo ser procesada correctamente o el formato Base64 era inválido.'
        ], 400);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => 'Error crítico interno en el servidor.',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    public function fetchrelacionTecnico(Request $request)
{
    try{

    DB::connection()->disableQueryLog();
    
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                     $idtecnico = Tecnico::where('fkuser', Auth()->id())->value('id');
                    $fechain=$request->input('fechain');
                    $fechafin=$request->input('fechafin');

                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->where('ESTATUS','I')
            ->paginate(10);
                };
                    }else{
                if ($Estatus == 'ER') {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$request->input('id'))->paginate(10);
                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)->paginate(10);
                };
                    }





    if ($request->ajax()) {
        return view('buckettecnico.table.tabla', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

public function fetchrelacionS(Request $request)
{
    DB::connection()->disableQueryLog();
    try{
                        if(!Auth::check()){
            return redirect()->route('login');
        }

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');
                    $idtecnico= $request->input('id');
                    $fechain=$request->input('fechainS');
                    $fechafin=$request->input('fechafinS');

                    if(isset($fechain) or isset($fechafin)){
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->where('Status','S')
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->paginate(25);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)->where('fkTecnico',$idtecnico)
            ->where('Status','S')
            ->whereBetween('FECHAINSTALACION',[$fechain, $fechafin])
            ->paginate(25);
                };
                    }else{
                if ($Estatus == 'ER') {

            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)
            ->where('Status','S')
            ->where('fkTecnico',$idtecnico)->paginate(25);

                } else {
            $relacion=Expedientetecnico::where('fkTienda',$fkTienda)
            ->where('Status','S')
            ->where('fkTecnico',$idtecnico)->paginate(25);
                };
                    }


    if ($request->ajax()) {
        return view('buckettecnico.table.tablaexpediente', compact('relacion'))->render();
    }
    }catch(Exception $e){
    return view('tecnico.index', compact('relacion','Error: '.$e->getMessage()));
    }


}

public function fetchrelacionP(Request $request)
{
    DB::connection()->disableQueryLog();
    
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $Estatus   = session('user_estatus');
        $fkTienda  = session('user_fkTienda') ?? session('user_fktienda'); 
        $idtecnico = $request->input('id');
        $fechain   = $request->input('fechainP');
        $fechafin  = $request->input('fechafinP');

        $query = Pagotecnico::with(['arbolmanoobra' => function($q) {
                $q->select('SKU', 'nombre as descripcion'); 
            }])
            ->where('fkTecnico', $idtecnico)
            ->whereNotNull('fkTecnico');

        $query->where(function($q) {
            $q->where('Naturaleza', 'H')
              ->orWhere('Status', 'S');
        })
        ->whereNotIn('Status', ['B', 'C']); 

        if ($Estatus !== 'ER') {
            $query->where('fkTienda', $fkTienda);
        }

        if ($fechain && $fechafin) {
            $inicio = \Carbon\Carbon::parse($fechain)->startOfDay();
            $fin = \Carbon\Carbon::parse($fechafin)->endOfDay();
            $query->whereBetween('created_at', [$inicio, $fin]);
        }

        // 1. Calculamos los acumulados GLOBALES del técnico antes de paginar
        $totalDineroPagado = (clone $query)->sum('COSTOPAGO');
        $totalManoObra     = (clone $query)->sum('Cantidad');

        // 2. Ejecutamos la paginación e inyectamos los filtros del request en las URLs
        $relacion = $query->orderBy('created_at', 'desc')
                          ->paginate(15)
                          ->appends($request->all());

        // 3. Enviamos todo estructurado a la vista
        return view('buckettecnico.table.tablapago', compact('relacion', 'totalDineroPagado', 'totalManoObra'))->render();

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function fetchrelacionC(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $Estatus   = session('user_estatus');
        $fkTienda  = session('user_fkTienda') ?? session('user_fktienda');
        $idtecnico = $request->input('id');
        $fechain   = $request->input('fechainC');
        $fechafin  = $request->input('fechafinC');

        // Consulta base optimizada
        $query = Pagotecnico::where('fkTecnico', $idtecnico)
            ->whereNotNull('fkTecnico')
            ->where('Status', 'S') 
            ->where('Naturaleza', 'D');

        if ($Estatus !== 'ER') {
            $query->where('fkTienda', $fkTienda);
        }

        if ($fechain && $fechafin) {
            $inicio = \Carbon\Carbon::parse($fechain)->startOfDay();
            $fin = \Carbon\Carbon::parse($fechafin)->endOfDay();
            $query->whereBetween('created_at', [$inicio, $fin]);
        }

        // 1. Calculamos los acumulados GLOBALES de cobros antes de paginar
        $totalDineroCobrado = (clone $query)->sum('COSTOPAGO');
        $totalManoObraCobro = (clone $query)->sum('Cantidad');

        // 2. Ejecutamos la paginación inyectando los filtros del request en las URLs
        $relacion = $query->orderBy('created_at', 'desc')
                          ->paginate(10)
                          ->appends($request->all());

        // 3. Enviamos los totales globales de forma independiente a la vista de cobros
        return view('buckettecnico.table.tablacobro', compact('relacion', 'totalDineroCobrado', 'totalManoObraCobro'))->render();

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

public function fetchrelacioninv(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $fkTienda  = session('user_fkTienda');
        $idtecnico = $request->input('id');

        // 🌟 1. Subconsulta Maestra: Solo lee el STOCK DISPONIBLE ACTUAL (Status = 'I')
        // Mantiene intacta la lógica de unificación y prioridad de tus series
        $subconsultaBase = DB::table('movimientomateriales')
            ->select(
                'id',
                'SKU',
                'ESTATUS',
                'CENTRO',
                'created_at',
                'cantidad',
                // EVALUACIÓN DE PRIORIDAD DE SERIES ORIGINAL (Se mantiene al 100%)
                DB::raw("
                    CASE 
                        WHEN TRIM(serie) NOT IN ('', '-', '0', 'N/A') THEN TRIM(serie)
                        WHEN TRIM(MAC1) NOT IN ('', '-', '0', 'N/A') THEN TRIM(MAC1)
                        ELSE 'N/A'
                    END as serie_maestra
                ")
            )
            ->where('fkTienda', $fkTienda)
            ->where('fkTecnico', $idtecnico)
            ->where('Status', 'I') // Filtro estricto para ignorar bitácoras históricas duplicadas
            ->where('ESTATUS', 'DISPONIBLE'); // Solo lo que el técnico tiene físicamente listo para usar
        // 🌟 2. Consulta que consolida y agrupa los saldos para tu Blade
        $relacion = MovimientoMaterial::with(['treematerialcategoria' => function($query) {
                $query->select('SKU', 'nombre as descripcion');
            }])
            ->fromSub($subconsultaBase, 'mov')
            ->select(
                DB::raw('MAX(mov.id) as id'),
                'mov.SKU',
                'mov.serie_maestra as serie', // Se expone con el nombre 'serie' que tu vista Blade necesita
                DB::raw("MAX(mov.ESTATUS) as ESTATUS"),
                DB::raw("MAX(mov.CENTRO) as CENTRO"),
                DB::raw("MAX(mov.created_at) as created_at"),
                // SUMATORIA DIRECTA REPARADA: Suma limpia del campo cantidad rebajado previamente en vivo
                DB::raw("SUM(IFNULL(mov.cantidad, 0)) as cantidad")
            )
            ->groupBy(
                'mov.SKU',
                'mov.serie_maestra' // Agrupamos por la serie unificada de forma estricta
            )
            ->having('cantidad', '>', 0) // Remueve de la vista los saldos que ya queden en cero
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('count', 15));

        if ($request->ajax()) {
            return view('buckettecnico.table.tablainv', compact('relacion'))->render();
        }

        // Retorno de seguridad si no es una petición AJAX
        return view('buckettecnico.index', compact('relacion'));

    } catch (\Exception $e) {
        if ($request->ajax()) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return redirect()->back()->with('error', $e->getMessage());
    }
}


public function exportar(Request $request)
{
DB::connection()->disableQueryLog();
                if(!Auth::check()){
            return redirect()->route('login');
        }


        $fkTienda = session('user_fkTienda');
        $Estatus = session('user_estatus');

                $request->validate([
                    'fechaincio' => 'required|date',
                    'fechafin' => 'required|date|after_or_equal:fechaincio',
                    ]);

                $inicio = Carbon::parse($request->fechaincio)->startOfDay();
                $fin = Carbon::parse($request->fechafin)->endOfDay();

                  if ($Estatus == 'ER') {

                $datos = Expedientetecnico::whereBetween('FECHAINSTALACION', [$inicio, $fin])
                ->get();

                } else {
                $datos = Expedientetecnico::where('fkTienda', $fkTienda)
                ->whereBetween('FECHAINSTALACION', [$inicio, $fin])
                ->get();
                }



    // Encabezado del CSV
    $csv = "fkTienda,Orden,virtual,Status,Tipo_servicio,Tipo_orden,NOMBRECLIENTE,DIRECCION,OBS,SIGLASCENTRAL,AREA,FECHAINSTALACION,created_at,updated_at,fkTecnico,AUTORIZA,ESTATUS,TECNOLOGIA\n";

    // Agregar datos
    foreach ($datos as $item) {

        $csv .= implode(",", [
            $item->fkTienda,
            $item->Orden,
            $item->virtual,
            $item->Status,
            $item->Tipo_servicio,
            $item->Tipo_orden,
            $item->NOMBRECLIENTE,
            $item->DIRECCION,
            $item->OBS,
            $item->SIGLASCENTRAL,
            $item->AREA,
            $item->FECHAINSTALACION,
            $item->created_at,
            $item->updated_at,
            $item->fkTecnico,
            $item->Autoriza,
            $item->ESTATUS,
            $item->TECNOLOGIA
        ]) . "\n";
    }

    // Retornar respuesta para descarga
    $nombreArchivo = 'tecnicosordenes_export_' . now()->format('Ymd_His') . '.csv';

    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => "attachment; filename=\"$nombreArchivo\"",
    ]);
}

public function exportarPagoTecnico(Request $request, $naturaleza) 
{
    DB::connection()->disableQueryLog();
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    // Validar que la naturaleza sea estrictamente D o H
    if (!in_array($naturaleza, ['D', 'H'])) {
        abort(404, 'Naturaleza no válida.');
    }

    $fkTienda = session('user_fktienda');
    $estatus = session('user_estatus');

    $request->validate([
        'fechaincio' => 'required|date',
        'fechafin' => 'required|date|after_or_equal:fechaincio',
    ]);

    $inicio = Carbon::parse($request->fechaincio)->startOfDay();
    $fin = Carbon::parse($request->fechafin)->endOfDay();

    // Construcción de la consulta para la tabla pagotecnico
    $query = DB::table('pagotecnico')
        ->select([
            'id', 'Orden', 'SKU', 'Descripcion', 'OBS', 'Cantidad', 
            'COSTOPAGO', 'created_at', 'updated_at', 'fkTienda', 
            'fkTecnico', 'Naturaleza', 'Status'
        ])
        ->where('Naturaleza', $naturaleza)
        ->whereBetween('created_at', [$inicio, $fin]); // O cambia a updated_at si es preferible

    // Filtrado por tienda si el usuario no tiene estatus 'er'
    if ($estatus !== 'er') {
        $query->where('fkTienda', $fkTienda);
    }

    $datos = $query->get();
    $nombreArchivo = 'pagotecnico_' . strtolower($naturaleza) . '_export_' . now()->format('Ymd_His') . '.csv';

    // Generar el contenido del CSV de forma segura en memoria
    $handle = fopen('php://memory', 'r+');
    
    // UTF-8 BOM para soporte de acentos en Excel
    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

    // Encabezados del CSV
    fputcsv($handle, [
        'id', 'Orden', 'SKU', 'Descripcion', 'OBS', 'Cantidad', 
        'COSTOPAGO', 'created_at', 'updated_at', 'fkTienda', 
        'fkTecnico', 'Naturaleza', 'Status'
    ]);

// 1. Cargamos todos los códigos de técnicos en un mapa en memoria (Colección asociativa)
// Esto reduce cientos de consultas SQL a una sola consulta ultrarápida antes del bucle
$tecnicosCodigos = DB::table('tecnico')
    ->whereIn('id', collect($datos)->pluck('fkTecnico')->filter()->unique())
    ->pluck('codigo', 'id')
    ->toArray();

// 2. Procesamos e insertamos las filas en el archivo CSV
foreach ($datos as $item) {
    
    // Obtenemos el código del técnico desde nuestro mapa en memoria
    $tecnicoCodigo = $tecnicosCodigos[$item->fkTecnico] ?? '';

    // 3. Buscamos el costo aplicando de forma idéntica la jerarquía de prioridades
    $costoUnidad = Materialmanoobra::where('SKU', $item->SKU)
        ->where(function ($query) use ($fkTienda, $tecnicoCodigo) {
            $query->where('centrocostoespecifico', '=', $tecnicoCodigo) // Prioridad 1: Técnico
                  ->orWhere('centrocostoespecifico', '=', $fkTienda)    // Prioridad 2: Tienda
                  ->orWhereNull('centrocostoespecifico')               // Prioridad 3: Global (NULL)
                  ->orWhere('centrocostoespecifico', '=', '');         // Prioridad 3: Global (Vacío)
        })
        ->select('CATEGORIACOBRO', 'COSTOPAGO', 'TIPO', 'centrocostoespecifico')
        ->orderByRaw("CASE 
            WHEN centrocostoespecifico = ? AND ? != '' THEN 1
            WHEN centrocostoespecifico = ? THEN 2
            ELSE 3 
        END ASC", [$tecnicoCodigo, $tecnicoCodigo, $fkTienda])
        ->latest()
        ->first();

    // 4. Determinamos el costo final validando de forma segura contra nulos
    $costoFinal = $item->COSTOPAGO; // Valor por defecto si no existe en el catálogo
    if ($costoUnidad) {
        $costoFinal = ($costoUnidad->CATEGORIA === 'MANO DE OBRA') 
            ? $costoUnidad->COSTOPAGO 
            : ($costoUnidad->CATEGORIACOBRO ?? $item->COSTOPAGO);
    }

    // 5. Escribimos la fila directamente en el puntero del archivo CSV
    fputcsv($handle, [
        $item->id, 
        $item->Orden, 
        $item->SKU, 
        $item->Descripcion, 
        $item->OBS, 
        $item->Cantidad, 
        $costoFinal, // Costo auditado con la prioridad correcta
        $item->created_at, 
        $item->updated_at, 
        $item->fkTienda, 
        $item->fkTecnico, 
        $item->Naturaleza, 
        $item->Status
    ]);
}


    // Leer el contenido generado
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    // Retornar la respuesta tal como la necesitas
    return Response::make($csv, 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => "attachment; filename=\"$nombreArchivo\"",
        'Pragma' => 'no-cache',
        'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        'Expires' => '0',
    ]);
}


    public function bucketlista()
    {
        DB::connection()->disableQueryLog();
            if(!Auth::check()){
                return redirect()->route('login');
            }
        try {
            DB::beginTransaction();

                    $Estatus = session('user_estatus');
                    $fkTienda = session('user_fkTienda');

            $idtecnico = Tecnico::where('fkuser', Auth()->id())
            ->value('id');
            $tecnicos = Tecnico::where('fkuser', Auth()->id())
            ->get();


                if ($Estatus == 'ER') {
                    $tecnicos=Tecnico::where('fkTienda',$fkTienda)->get();
                    $expediente=Expedientetecnico::where('fkTienda',$fkTienda)
                    ->where('ESTATUS','A')
                    ->get();
                    $tecnico=null;
                } else {
                    $tecnico=null;
                    $tecnico=Tecnico::where('fkTienda',$fkTienda)
                    ->where('id',$idtecnico)->first();
                    $expediente=Expedientetecnico::where('fkTienda',$fkTienda)
                    ->where('ESTATUS','A')
                    ->where('fkTecnico',$idtecnico)->get();
                };

            DB::commit();

            return view('buckettecnico.index', compact('tecnicos','tecnico','expediente','Estatus'));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }


    }

    
public function importarMAMO(Request $request)
{
    DB::connection()->disableQueryLog();

    if (!Auth::check()) return redirect()->route('login');

    // Forzamos que la tienda de la sesión sea un entero limpio
    $fkTienda = session('user_fkTienda') ? (int)session('user_fkTienda') : null;
    $idDestino = $request->input('id'); 
    $nombreUsuario = session('nombreUsuario');

    if (empty($fkTienda)) {
        Log::error('Importación MAMO rechazada: El usuario no tiene fkTienda en su sesión.');
        return back()->with('error', 'Error de seguridad: Tu sesión no tiene una tienda asignada.');
    }

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt',
    ]);

    $file = fopen($request->file('archivo')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); 

    DB::beginTransaction();
    try {
        $fila = 1;
        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            
            if (count($encabezado) !== count($linea)) {
                Log::warning("Fila $fila saltada: El número de columnas no coincide con el encabezado.");
                continue;
            }
            
            $data = array_combine($encabezado, $linea);

            if (empty($data['Orden']) || empty($data['virtual'])) {
                Log::warning("Fila $fila saltada: Campo 'Orden' o 'virtual' vacío.");
                continue;
            }

            $orden = trim($data['Orden']);
            $virtual = trim($data['virtual']);
            $ahora = now();

            $fechaInst = null;
            if (!empty($data['FECHAINSTALACION'])) {
                try {
                    $fechaInst = \Carbon\Carbon::createFromFormat('d/m/Y', $data['FECHAINSTALACION'])->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $fechaInst = $ahora; 
                }
            }

            // 3. LOGICA DE REASIGNACIÓN Y ACTUALIZACIÓN (Filtrada por la tienda actual)
            $expedientePrevio = DB::table('expedientetecnico')
                ->where('orden', $orden)
                ->where('virtual', $virtual)
                ->where('fkTienda', $fkTienda) 
                ->where('Estatus', '!=', 'RE') 
                ->first();

            if ($expedientePrevio) {
                // Si el técnico asignado en el CSV es idéntico al actual en la misma tienda -> SE ACTUALIZA
                if ((int)$expedientePrevio->fkTecnico === (int)$idDestino) {
                    Log::info("Fila $fila: Actualizando datos de orden existente para el mismo técnico en tienda $fkTienda.");
                    
                    DB::table('expedientetecnico')
                        ->where('id', $expedientePrevio->id)
                        ->update([
                            'status'           => isset($data['Status']) ? trim($data['Status']) : $expedientePrevio->status,
                            'Estatus'          => isset($data['ESTATUS']) ? trim($data['ESTATUS']) : $expedientePrevio->status,
                            'FECHAINSTALACION' => $fechaInst,
                            'updated_at'       => $ahora
                        ]);
                        
                    continue; // Pasa a la siguiente fila del CSV sin duplicar ni insertar
                }

                // Si es un técnico diferente dentro de la MISMA tienda, se marca como reasignado (RE)
                Log::info("Fila $fila: Reasignando orden internamente dentro de la tienda $fkTienda.");
                DB::table('expedientetecnico')
                    ->where('id', $expedientePrevio->id)
                    ->update([
                        'Estatus' => 'RE',
                        'obs' => (($expedientePrevio->obs ?? '') . " | Reasignada a técnico ID: $idDestino por $nombreUsuario"),
                        'updated_at' => $ahora
                    ]);
            } else {
                Log::info("Fila $fila: No se encontró registro previo activo en la tienda $fkTienda. Se procederá a insertar.");
            }
            // 4. INSERCIÓN ABSOLUTA EN LA TIENDA DE LA SESIÓN (Si no existía o si cambió de tienda)
            DB::table('expedientetecnico')->insert([
                'orden'            => $orden,
                'virtual'          => $virtual,
                'fkTienda'         => $fkTienda,
                'fkTecnico'        => $idDestino,
                'status'           => isset($data['Status']) ? trim($data['Status']) : 'I',
                'tipo_servicio'    => mb_convert_encoding($data['Tipo_servicio'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'tipo_orden'       => mb_convert_encoding($data['Tipo_orden'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'nombrecliente'    => mb_convert_encoding($data['NOMBRECLIENTE'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'direccion'        => mb_convert_encoding($data['DIRECCION'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'obs'              => mb_convert_encoding($data['OBS'] ?? '', 'UTF-8', 'ISO-8859-1'),
                'SIGLASCENTRAL'    => $data['SIGLASCENTRAL'] ?? '',
                'area'             => $data['AREA'] ?? '',
                'FECHAINSTALACION' => $fechaInst,
                'autoriza'         => $data['AUTORIZA'] ?? '',
                'Estatus'          => isset($data['ESTATUS']) ? trim($data['ESTATUS']) : 'I',
                'TECNOLOGIA'       => $data['TECNOLOGIA'] ?? '',
                'created_at'       => $ahora,
                'updated_at'       => $ahora,
            ]);
            
            Log::info("Fila $fila: Inserción exitosa de la orden $orden en la tienda $fkTienda.");
        } // Fin del bucle while

        fclose($file);
        DB::commit();
        return back()->with('success', 'Expedientes técnicos procesados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        if (isset($file)) fclose($file);
        Log::error('Error crítico al importar Expediente en fila ' . $fila . ': ' . $e->getMessage());
        return back()->with('error', 'Error en fila ' . $fila . ': ' . $e->getMessage());
    }
}



public function importarInvTecnico(Request $request)
{
    DB::connection()->disableQueryLog();

    if (!Auth::check()) return redirect()->route('login');

    $fkTienda = session('user_fkTienda');
    $idDestino = $request->input('id'); 
    $nombreUsuario = session('nombreUsuario');
    $CentroDestino=Tecnico::where('id', $idDestino)->value('codigo') ?? 'N/A';
    
    $request->validate(['archivoinv' => 'required|file|mimes:csv,txt']);
    $file = fopen($request->file('archivoinv')->getRealPath(), 'r');
    $encabezado = fgetcsv($file); 

    DB::beginTransaction();
    try {
        $fila = 1;
        $instaladosContador = 0;

        while (($linea = fgetcsv($file)) !== false) {
            $fila++;
            $data = array_combine($encabezado, $linea);
            if (empty($data['SKU']) || empty($data['cantidad'])) continue;

            $sku = trim($data['SKU']);
            $serie = trim($data['serie'] ?? '');
            $cantidad = floatval($data['cantidad']);
            $docRef = 'IMP-' . $CentroDestino . ';' . now()->format('dmY:H:i:s') . ';' . $serie;
            $ahora = now();

            // 1. OBTENER O CREAR PRODUCTO
            $producto = Producto::firstOrCreate(
                ['codigo' => $sku],
                [
                    'nombre' => mb_convert_encoding($data['descripcion'] ?? "Producto $sku", 'UTF-8', 'ISO-8859-1'),
                    'fkTienda' => $fkTienda, 'estado' => 1, 'marca_id' => 1, 'presentacione_id' => 1,
                    'stock' => 0, 'precio_base' => 0, 'stock_minimo' => 1, 'perecedero' => 0
                ]
            );

            // 2. IMPEDIR TRASPASO SI ESTÁ INSTALADO
            $stockActual = DB::table('movimientomateriales')
                ->where('serie', $serie)
                ->where('SKU', $sku)
                ->where('fkTienda', $fkTienda)
                ->where('Status', 'A')
                ->first();

            if ($stockActual && $stockActual->ESTATUS == 'INSTALADO') {
                $instaladosContador++;
                continue; 
            }

            // 3. BUSCAR ÚLTIMO DUEÑO (Historial)
            $ultimoMov = MovimientoMateriales::where('fkMateriales', $producto->id)
                ->where('referencia', 'LIKE', "%$serie%")
                ->where('fkTienda', $fkTienda)
                ->orderBy('id', 'desc')->first();

            $idOrigen = $ultimoMov ? $ultimoMov->contrata : null;
            $CentroOrigen=Tecnico::where('id', $idOrigen)->value('codigo') ?? 'N/A';
            if ($idOrigen == $idDestino) continue;

            // 4. REGISTRAR SALIDA DEL ANTERIOR
            if ($idOrigen) {
                MovimientoMateriales::create([
                    'fkTienda' => $fkTienda, 'fkMateriales' => $producto->id, 'contrata' => $idOrigen,
                    'clase_movimiento' => '221', 'cantidad' => $cantidad * -1,
                    'referencia' => "SALIDA SERIE: $serie | TRASPASO A $idDestino",
                    'tipo_movimiento' => 'TRASPASO_SALIDA', 'documento_material' => $docRef,
                    'posicion_documento' => '0001', 'fecha_contabilizacion' => $ahora->format('Y-m-d'),
                    'almacen' => $CentroOrigen, 'centro' => $data['CENTRO'] ?? 'G817',
                    'unidad_medida_base' => $data['unidadmedida'] ?? 'PZA'
                ]);

                DB::table('movimientomateriales')
                    ->where('serie', $serie)
                    ->where('SKU', $sku)
                    ->where('fkTecnico', $idOrigen)
                    ->update([
                        'ESTATUS' => 'TRASLADADO',
                        'Status' => 'I',
                        'updated_at' => $ahora
                    ]);
            }

            // 5. REGISTRAR ENTRADA EN HISTORIAL (Destino)
            MovimientoMateriales::create([
                'fkTienda' => $fkTienda, 'fkMateriales' => $producto->id, 'contrata' => $idDestino,
                'clase_movimiento' => '641' ? '641' : '101', 'cantidad' => $cantidad,
                'referencia' => "ENTRADA SERIE: $serie | ORIGEN: " . ($idOrigen ?? 'BODEGA'),
                'tipo_movimiento' => 'TRASPASO_ENTRADA', 'documento_material' => $docRef,
                'posicion_documento' => '0001', 'fecha_contabilizacion' => $ahora->format('Y-m-d'),
                'centro' => $data['CENTRO'] ?? 'G817', 'almacen' => $CentroDestino,
                'unidad_medida_base' => $data['unidadmedida'] ?? 'PZA'
            ]);

                                $costoUnidad = Materialmanoobra::where('SKU', $sku)
                                    ->where('fkTienda', $fkTienda)
                                    ->select('CATEGORIACOBRO','COSTOPAGO', 'Descripcion', 'TIPO', 'unidadmedida') // Agrega aquí las columnas que ocupes
                                    ->latest()
                                    ->first();                 
            // 6. ASIGNAR STOCK AL NUEVO TÉCNICO (Blindado contra Error 1364)
            DB::table('movimientomateriales')->updateOrInsert(
                [
                    'serie' => $serie,
                    'SKU' => $sku,
                    'fkTecnico' => $idDestino,
                    'fkTienda' => $fkTienda,
                ],
                [
                    'almacen' => $data['almacen'] ?? 'A000',
                    'Lote' => $data['Lote'] ?? 'N/A',
                    'MAC1' => $data['MAC1'] ?? '', // <-- Evita error si el CSV no lo trae
                    'MAC2' => $data['MAC2'] ?? '',
                    'MAC3' => $data['MAC3'] ?? '',
                    'COSTO' =>  ($costoUnidad->TIPO === 'MANO DE OBRA') ? $costoUnidad->COSTOPAGO : ($costoUnidad->CATEGORIACOBRO ?? $data['COSTO']),
                    'TIPO' => $data['TIPO'] ?? 'MA',
                    'ESTATUS' => 'DISPONIBLE',
                    'Status' => 'I',
                    'Naturaleza'=> 'E',
                    'CENTRO' => $data['CENTRO'] ?? 'G817',
                    'cantidad' => $cantidad,
                    'unidadmedida' => $data['unidadmedida'] ?? 'PZA',
                    'TIPOMOVIMIENTO' => 'TRASPASO_ENTRADA',
                    'Modificado_el' => $ahora->format('Y-m-d'),
                    'Modificado_por' => $nombreUsuario,
                    'Creado_el' => $ahora->format('Y-m-d'),
                    'Creado_por' => $nombreUsuario,
                    'updated_at' => $ahora
                ]
            );
        }

        fclose($file);
        DB::commit();
        
        $msg = "Inventario procesado.";
        if($instaladosContador > 0) $msg .= " Se omitieron $instaladosContador series ya instaladas.";
        
        return back()->with('success', $msg);

    } catch (\Exception $e) {
        DB::rollBack();
        if (isset($file)) fclose($file);
        return back()->with('error', 'Error en fila ' . $fila . ': ' . $e->getMessage());
    }
}



        public function descargarFormeta()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Expediente Ruta.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['Orden','virtual','Status','Tipo_servicio','Tipo_orden','NOMBRECLIENTE','DIRECCION','OBS','SIGLASCENTRAL','AREA','FECHAINSTALACION','AUTORIZA','ESTATUS','TECNOLOGIA'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, [23450285,1005749,'I','DT',"DA",'JUAN PEREZ','Canton camoja, Huehuetanango, Huehuetenango',"ORDEN QUE SOLO SE AGREGAN CAJAS ADICIONALES",'HUE0301','OC3',"15/06/2025",'1T','I','WTTx']);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function descargarMasivaFormeta()
{
    $headers = [
        "Content-type"        => "text/csv; charset=UTF-8", // 🛠️ Recomendado agregar UTF-8 para tildes y caracteres especiales
        "Content-Disposition" => "attachment; filename=Formato Masivo Expediente Ruta.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    // 🛠️ Se añadió 'CENTRO/CODIGO' al final del encabezado
    $columnas = ['Orden','virtual','Status','Tipo_servicio','Tipo_orden','NOMBRECLIENTE','DIRECCION','OBS','SIGLASCENTRAL','AREA','FECHAINSTALACION','AUTORIZA','ESTATUS','TECNOLOGIA','CENTRO/CODIGO'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        
        // 🛠️ Esto ayuda a que Excel reconozca correctamente los caracteres en español al abrir el CSV
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 

        fputcsv($file, $columnas); // Encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        
        // 🛠️ Se añadió el código del técnico de ejemplo 'D080817' al final de la fila
        fputcsv($file, [
            23450285,
            1005749,
            'I',
            'DT',
            "DA",
            'JUAN PEREZ',
            'Canton camoja, Huehuetanango, Huehuetenango',
            "ORDEN QUE SOLO SE AGREGAN CAJAS ADICIONALES",
            'HUE0301',
            'OC3',
            "15/06/2025",
            '1T',
            'I',
            'WTTx',
            'D080817' // 👈 Código del técnico correspondiente
        ]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}


        public function descargarinventario()
{
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=Formato Expediente inventario.csv",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $columnas = ['serie','SKU','almacen','Lote','MAC1','MAC2','MAC3','ESTATUS','COSTO','CENTRO','TIPO','unidadmedida','TIPOMOVIMIENTO','Naturaleza','Status', 'cantidad'];

    $callback = function () use ($columnas) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columnas); // encabezado

        $fkTienda = session('user_fkTienda') ?? 0;
        // Línea de ejemplo opcional:
        fputcsv($file, ['fajJSJJDF4013896',4013896,'ALMA','A000',"N/A",'N/A','N/A',"A",350,'G817',"MA/MO",'UNIDAD',231,'D','I',1]);

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

      public function pagocobro($id)
    {
        DB::connection()->disableQueryLog();
            try {
                                if(!Auth::check()){
            return redirect()->route('login');
        }

            DB::beginTransaction();


            $tecnico=Tecnico::where('id',$id)->first();

            DB::commit();
            return redirect()->route('buckettecnico.index')->with('success', 'Puede filtrar por fechas');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

    }

       public function produccion($id)
    {
        try {

                        if(!Auth::check()){
            return redirect()->route('login');
        }
            DB::beginTransaction();
            $tecnico=Tecnico::where('id',$id)->first();

            DB::commit();
            return redirect()->route('buckettecnico.index')->with('success', 'Puede filtrar por fechas');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar tecnico: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el tecnico.');
        }

    }


    public function obtenerClientes()
    {
        $clientes = Cliente::select('id', 'persona_id')
        ->get();
        return response()->json($clientes);
    }
public function ejecutarconsulta(Request $request)
    {
        DB::connection()->disableQueryLog();
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
public function fetchrelacion(Request $request)
{
    DB::connection()->disableQueryLog();
    try {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $Estatus = session('user_estatus');
        $fkTienda = session('user_fkTienda');
        $idtecnico = $request->input('id');
        
        // Capturar fechas y el nuevo término de búsqueda global
        $fechain = $request->input('fechain') ? $request->input('fechain') . ' 00:00:00' : null;
        $fechafin = $request->input('fechafin') ? $request->input('fechafin') . ' 23:59:59' : null;
        $searchTerm = $request->input('search'); // <--- NUEVO: Captura el texto de búsqueda

        // Inicializamos el query builder base aplicando los alcances obligatorios de tienda y técnico
        $fkTecnicoFiltro = ($Estatus == 'ER') ? $request->input('id') : $idtecnico;
        
        $query = Expedientetecnico::where('fkTienda', $fkTienda)
            ->where('fkTecnico', $fkTecnicoFiltro)
            ->where('ESTATUS', 'I');

        // Condición de Fechas
        if ($fechain && $fechafin) {
            $query->whereBetween('FECHAINSTALACION', [$fechain, $fechafin]);
        }

        // NUEVO: Filtro de Búsqueda Avanzada en el Servidor
        $query->when($searchTerm, function ($q) use ($searchTerm) {
            $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('Orden', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('virtual', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('Status', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('Tipo_servicio', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('Tipo_orden', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('NOMBRECLIENTE', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('DIRECCION', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('OBS', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('SIGLASCENTRAL', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('AREA', 'LIKE', "%{$searchTerm}%")
                         ->orWhere('AUTORIZA', 'LIKE', "%{$searchTerm}%");
            });
        });

        // Ejecutamos la paginación final estructurada
        $relacion = $query->paginate(15);

        if ($request->ajax()) {
            return view('buckettecnico.table.tabla', compact('relacion'))->render();
        }
        
    } catch (Exception $e) {
        return view('tecnico.index', ['Error' => $e->getMessage()]);
    }
}


public function guardarFirmaRapida(Request $request)
{
    try {
        $request->validate([
            'id' => 'required|exists:expedientetecnico,id',
            'firma_base64' => 'required'
        ]);

        $expediente = Expedientetecnico::findOrFail($request->input('id'));

        if ($request->has('firma_base64') && !empty($request->input('firma_base64'))) {
            
            // 1. Obtener la cadena de texto Base64 que envía el JavaScript
            $image_data = $request->input('firma_base64');
            
            // 2. Limpiar el encabezado y decodificar el texto a datos binarios de imagen
            $image_split = explode(',', $image_data);
            $image_base64 = base64_decode($image_split[1]); 

            // 3. Inicializar Intervention Image V3 igual que en tu función base
            $manager = new ImageManager(new Driver());

            // Leemos los datos binarios directamente de la memoria
            $image = $manager->read($image_base64)
                             ->resize(800, 800, function ($constraint) {
                                 $constraint->aspectRatio();
                                 $constraint->upsize();
                             });

            // 4. Definir nombre y ruta virtual Fuera de las otras carpetas
            // Esto creará una carpeta llamada "firmas" en la raíz de tu Bucket
            $filename = 'firma_orden_' . $expediente->Orden . '_' . time() . '.webp';
            $path = 'firmas/' . $filename; 
            
            $webpEncoder = new WebpEncoder(quality: 80);

            // 5. Guardar bypass directo sin pasar por la librería Intervention
            Storage::disk('gcs_images')->put($path, $image_base64);

            // 6. Obtener la URL pública del archivo en Google Cloud
            $urlPublicaGoogle = Storage::disk('gcs_images')->url($path);

            // 7. Guardar la URL final en tu base de datos y actualizar
            $expediente->firma_cliente = $urlPublicaGoogle;
            $expediente->save(); 

            return response()->json([
                'status' => 'success',
                'message' => 'Firma procesada y guardada en la carpeta firmas con éxito.',
                'url' => $urlPublicaGoogle
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'No se recibieron datos de la firma.'], 400);

    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}


    public function obtenerdetalles(string $sql, array $parametros)
    {
        DB::connection()->disableQueryLog();
        try{

                $pdo = DB::getPdo();
        $stmt = $pdo->prepare($sql);
        if($parametros['id']==''){
        $stmt->execute();
        }else{
        $stmt->execute($parametros);
        };

        $detallecomprobante = $stmt->fetchAll(\PDO::FETCH_ASSOC);


        return $detallecomprobante;

        // Consultar los detalles del comprobante

}catch(Exception $e){

    $detallecomprobante[0]="Error: ".$e->getMessage();
}

    }

    public function listaClientes(Request $request)
    {
        DB::connection()->disableQueryLog();

        $query = Cliente::with('persona')->orderBy('persona.nombre', 'asc');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('persona', function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%");
            });
        }

        $clientes = $query->paginate(10); // Paginar los resultados
        return response()->json($clientes);
    }

    public function destroy(string $id)
{
    DB::connection()->disableQueryLog();

    if(!Auth::check()){
        return redirect()->route('login');
    }

    // Buscamos al técnico y su usuario relacionado directamente
    // Nota: $id aquí debe ser el ID del Técnico o de la Persona según tu tabla
    $tecnico = Tecnico::where('id', $id)->first();

    if (!$tecnico || !$tecnico->fkuser) {
        return back()->with('error', 'No se encontró el usuario asociado a este técnico.');
    }

    try {
        DB::beginTransaction();

        // 1. Desactivar técnico
        $tecnico->update(['especialidad' => 'INACTIVO']); 

        // 2. Desactivar usuario
        $user = User::findOrFail($tecnico->fkuser);
        $user->status = 0; 
        $user->save();

        // 3. Quitar roles (Spatie)
        $user->roles()->detach();

        DB::commit();
        return redirect()->route('tecnico.lista')->with('success', 'Técnico y usuario desactivados correctamente.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->with('error', 'Error al procesar la baja: ' . $e->getMessage());
    }
}

}
