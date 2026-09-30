<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Presentacione;
use App\Models\Producto;
use Exception;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Stmt\TryCatch;
use  App\Models\Caracteristica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductoController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-producto|crear-producto|editar-producto|eliminar-producto', ['only' => ['index']]);
        $this->middleware('permission:crear-producto', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-producto', ['only' => ['edit', 'update']]);
        $this->middleware('permission:eliminar-producto', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    $fkTienda = session('user_fkTienda');
    $Estatus = session('user_estatus');

    // Si el estatus es 'ER', cargar todos los productos
    if ($Estatus == 'ER') {
        $productos = Producto::with([
            'categorias.caracteristica',
            'marca.caracteristica',
            'presentacione.caracteristica',
            'tienda' // Incluye la tienda en la consulta
        ])->latest()->get();
    } else {
        // Filtrar los productos solo por la tienda del usuario
        $productos = Producto::with([
            'categorias.caracteristica',
            'marca.caracteristica',
            'presentacione.caracteristica',
            'tienda' // Incluye la tienda en la consulta
        ])->where('fkTienda', $fkTienda)
        ->latest()->get();
    }

    return view('producto.index', compact('productos'));
}


public function storeExpress(Request $request)
{
    if (!Auth::check()) {
        return response()->json(['error' => 'Sesión expirada'], 401);
    }

    // 🚀 DEFINICIÓN DEL LOCK KEY PARA CONTROL DE CONCURRENCIA
    $lockKey = 'submit_producto_' . auth()->id();
    
    // Intentar obtener el candado por 10 segundos. Si ya está bloqueado, rechaza la petición.
    $lock = Cache::lock($lockKey, 10);

    if (!$lock->get()) {
        return response()->json([
            'error' => 'Ya se está procesando una solicitud de registro. Por favor espere.'
        ], 423); // Código de estado 423: Locked (Bloqueado)
    }

    try {
        $fkTienda = session('user_fkTienda');
        DB::beginTransaction();

        // =======================================================
        // 1. PROCESAR MARCA (Evita duplicados)
        // =======================================================
        $marcaId = $request->input('modal_marca_id') ?? $request->input('nueva_marca_texto');
        
        if ($marcaId && !is_numeric($marcaId)) {
            $caracMarca = Caracteristica::firstOrCreate(
                ['nombre' => trim($marcaId)],
                ['estado' => 1, 'descripcion' => 'Creado por control express']
            );
            
            $nuevaM = $caracMarca->marca()->firstOrCreate([
                'caracteristica_id' => $caracMarca->id
            ]);
            $marcaId = $nuevaM->id;
        }


        // =======================================================
        // 2. PROCESAR PRESENTACIÓN (Evita duplicados)
        // =======================================================
        
        $presentacionId = $request->input('modal_presentacione_id') ?? $request->input('nueva_presentacion_texto') ?? $request->input('nueva_presentation_texto');


        if ($presentacionId && !is_numeric($presentacionId)) {
            $caracPres = Caracteristica::firstOrCreate(
                ['nombre' => trim($presentacionId)],
                ['estado' => 1, 'descripcion' => 'Creado por control express']
            );
            
            $nuevaP = $caracPres->presentaciones()->firstOrCreate([
                'caracteristica_id' => $caracPres->id
            ]); 
            $presentacionId = $nuevaP->id; 
        }

        // =======================================================
        // 3. PROCESAR IMAGEN COMPUESTA (Galería o Webcam)
        // =======================================================
        $nameImg = null;
        if ($request->hasFile('img_path')) {
            $nameImg = (new Producto())->handleUploadImage($request->file('img_path'));
        } elseif ($request->has('imagen_base64')) {
            $base64Data = $request->input('imagen_base64');
            @list($type, $fileData) = explode(';', $base64Data);
            @list(, $fileData)      = explode(',', $fileData);
            
            $imageName = 'express_' . time() . '.jpg';
            $path = public_path('img/productos/' . $imageName);
            
            if (!file_exists(public_path('img/productos'))) {
                mkdir(public_path('img/productos'), 0777, true);
            }
            file_put_contents($path, base64_decode($fileData));
            $nameImg = 'img/productos/' . $imageName; 
        }

        // =======================================================
        // 4. CREACIÓN / RECUPERACIÓN DEL PRODUCTO (Evita duplicados)
        // =======================================================
        $producto = Producto::firstOrCreate(
            [
                'codigo'   => $request->input('modal_codigo'),
                'fkTienda' => $fkTienda
            ],
            [
                'nombre'            => $request->input('nombre'),
                'descripcion'       => $request->input('descripcion'),
                'img_path'          => $nameImg,
                'marca_id'          => $marcaId,
                'presentacione_id'  => $presentacionId,
                'perecedero'        => $request->boolean('perecedero'),
                'stock'             => 0, 
                'estado'            => 1
            ]
        );

        // =======================================================
        // 5. ASOCIACIÓN DINÁMICA DE CATEGORÍAS (Evita duplicados)
        // =======================================================
        $categorias = [];



        $catsSeleccionadas = $request->input('categorias', []);
        foreach($catsSeleccionadas as $catS) {
            if (is_numeric($catS)) { $categorias[] = $catS; }
        }

        if ($request->has('nuevas_categorias_texto')) {
            foreach ($request->input('nuevas_categorias_texto') as $textoCat) {
                if (!empty(trim($textoCat))) {
                    $caracCat = Caracteristica::firstOrCreate(
                        ['nombre' => trim($textoCat)],
                        ['estado' => 1]
                    );
                    
                    $nuevaC = $caracCat->categoria()->firstOrCreate([
                        'caracteristica_id' => $caracCat->id
                    ]); 
                    $categorias[] = $nuevaC->id;
                }
            }
        }

        $producto->categorias()->sync($categorias);

      DB::commit();
        
        // 🚀 LIBERAR EL CANDADO MANUALMENTE TRAS EL ÉXITO
        $lock->release();

        $categoriasProcesadas = $producto->categorias->map(function($cat) {
            return [
                'id'     => $cat->id,
                // Obtenemos el nombre real desde la tabla de características relacionada
                'nombre' => $cat->caracteristica ? trim($cat->caracteristica->nombre) : ''
            ];
        });

        // Obtener los nombres limpios directamente de las variables procesadas arriba
        // para evitar que un fallo en las relaciones del modelo rompa la petición con un Error 500.
        $nombreMarcaReal = $request->input('modal_marca_id') ?? $request->input('nueva_marca_texto');
        $nombrePresentacionReal = $request->input('modal_presentacione_id') ?? $request->input('nueva_presentacion_texto');

        return response()->json([
            'status'   => 'success',
            'producto' => [
                'id'          => $producto->id,
                'nombre'      => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'img_path'    => $producto->img_path,
                'perecedero'  => (int) $producto->perecedero 
            ],
            'marca' => $marcaId ? [
                'id'     => $marcaId, 
                'nombre' => trim($nombreMarcaReal)
            ] : null,
            'presentacion' => $presentacionId ? [
                'id'     => $presentacionId, 
                'nombre' => trim($nombrePresentacionReal)
            ] : null,
            // 🚀 ENVIAR LAS CATEGORÍAS EN LA RESPUESTA JSON
            'categorias_procesadas' => $categoriasProcesadas
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        // 🚀 ASEGURAR LIBERACIÓN DEL CANDADO SI OCURRE UN ERROR
        $lock->release();

        // Esto te permitirá ver en la alerta de SweetAlert qué línea exacta falló en PHP
        return response()->json(['error' => 'Fallo en Registro: ' . $e->getMessage() . ' en línea ' . $e->getLine()], 500);
    }
}
   

public function shows($id)
{
                    if(!Auth::check()){
            return redirect()->route('login');
        }

    return Producto::findOrFail($id);
}


public function buscarProducto(HttpRequest $request)
{
    try {
                        if(!Auth::check()){
            return redirect()->route('login');
        }

        $fkTienda = session('user_fkTienda');
    $Estatus = session('user_estatus');
    $search = '%'.$request->input('search').'%';

    $sql = "
 WITH productosearch AS (
    select distinct p.id, p.codigo, p.nombre, p.stock, p.descripcion, c.nombre as cat
    from productos p
    inner join categoria_producto cp on cp.producto_id = p.id
    inner join categorias cat on cp.categoria_id = cat.id
    inner join caracteristicas c on cat.caracteristica_id = c.id
    where c.nombre like ? and p.fkTienda = ?

    union all

    select p.id, p.codigo, p.nombre, p.stock, p.descripcion, c.nombre as cat
    from productos p
    inner join categoria_producto cp on cp.producto_id = p.id
    inner join categorias cat on cp.categoria_id = cat.id
    inner join caracteristicas c on cat.caracteristica_id = c.id
    where p.descripcion like ? and p.fkTienda = ?

    union all

    select p.id, p.codigo, p.nombre, p.stock, p.descripcion, c.nombre as cat
    from productos p
    inner join categoria_producto cp on cp.producto_id = p.id
    inner join categorias cat on cp.categoria_id = cat.id
    inner join caracteristicas c on cat.caracteristica_id = c.id
    where c.descripcion like ? and p.fkTienda = ?
        union all
        select p.id, p.codigo, p.nombre, p.stock, p.descripcion, c.nombre as cat
    from productos p
    inner join categoria_producto cp on cp.producto_id = p.id
    inner join categorias cat on cp.categoria_id = cat.id
    inner join caracteristicas c on cat.caracteristica_id = c.id
    where p.nombre like ? and p.fkTienda = ?
            union all
        select p.id, p.codigo, p.nombre, p.stock, p.descripcion, c.nombre as cat
    from productos p
    inner join categoria_producto cp on cp.producto_id = p.id
    inner join categorias cat on cp.categoria_id = cat.id
    inner join caracteristicas c on cat.caracteristica_id = c.id
    where p.codigo like ? and p.fkTienda = ?
)
select distinct id, codigo, nombre, stock, descripcion from productosearch;

";

$productos = DB::select($sql, [$search, $fkTienda, $search, $fkTienda, $search, $fkTienda, $search, $fkTienda, $search, $fkTienda]);

return $productos;


    } catch (Exception $e) {
        return redirect()->back()->with('error', 'Ocurrió un error al registrar el producto: ' . $e->getMessage());
    }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $marcas = Marca::join('caracteristicas as c', 'marcas.caracteristica_id', '=', 'c.id')
            ->select('marcas.id as id', 'c.nombre as nombre')
            ->where('c.estado', 1)
            ->get();

        $presentaciones = Presentacione::join('caracteristicas as c', 'presentaciones.caracteristica_id', '=', 'c.id')
            ->select('presentaciones.id as id', 'c.nombre as nombre')
            ->where('c.estado', 1)
            ->get();

        $categorias = Categoria::join('caracteristicas as c', 'categorias.caracteristica_id', '=', 'c.id')
            ->select('categorias.id as id', 'c.nombre as nombre')
            ->where('c.estado', 1)
            ->get();

        return view('producto.create', compact('marcas', 'presentaciones', 'categorias'));
    }

    /**
     * Store a newly created resource in storage.
     */public function store(StoreProductoRequest $request)
{
        if (!Auth::check()) {
        return response()->json(['error' => 'Sesión expirada'], 401);
    }

    // 🚀 DEFINICIÓN DEL LOCK KEY PARA CONTROL DE CONCURRENCIA
    $lockKey = 'submit_compra_' . auth()->id();
    
    // Intentar obtener el candado por 10 segundos. Si ya está bloqueado, rechaza la petición.
    $lock = Cache::lock($lockKey, 10);

    if (!$lock->get()) {
        return response()->json([
            'error' => 'Ya se está procesando una solicitud de registro. Por favor espere.'
        ], 423); // Código de estado 423: Locked (Bloqueado)
    }

    try {
        // Recuperar la tienda de la sesión
        $fkTienda = session('user_fkTienda');

        DB::beginTransaction();

        // 1. Manejar la carga de la imagen (Simplificado para creación)
        $name = null;
        if ($request->hasFile('img_path')) {
            try {
                $name = (new Producto())->handleUploadImage($request->file('img_path'));
            } catch (Exception $e) {
                return redirect()->back()->with('error', 'Error al cargar la imagen en la nube: ' . $e->getMessage());
            }
        }

        // 2. Crear el producto usando asignación masiva (Fillable)
        // Se añade 'precio_base' asumiendo que viene en tu formulario
        $producto = Producto::create([
            'codigo'            => $request->codigo,
            'nombre'            => $request->nombre,
            'descripcion'       => $request->descripcion,
            'precio_base'       => $request->precio_base ?? 0, 
            'img_path'          => $name,
            'marca_id'          => $request->marca_id,
            'presentacione_id'  => $request->presentacione_id,
            'fkTienda'          => $fkTienda,
            'perecedero'        => $request->boolean('perecedero') // Convierte limpia y automáticamente a 1 o 0
        ]);

        // 3. Sincronizar categorías de forma segura
        // Usar sync() en lugar de attach() evita registros duplicados si el formulario se reenvía
        $categorias = $request->input('categorias', []);


        $producto->categorias()->sync($categorias);

        DB::commit();

        return redirect()->route('productos.index')->with('success', 'Producto registrado exitosamente.');

} catch (Exception $e) {
    DB::rollBack();

    // Esto te dirá exactamente qué columna o llave foránea falló en la nube
    dd([
        'Error BD' => $e->getMessage()
    ]);
}

}


public function obtenerCodigoUnicoAjax()
{
    $existe = true;
    $codigoUnico = '';
    $intentos = 0; // Candado de seguridad para evitar congelar el servidor

    while ($existe && $intentos < 100) {
        $intentos++;

        // 1. Tomamos los segundos del servidor (10 dígitos exactos en la época actual)
        $segundos = (string)time(); 
        
        // 2. Prefijo '20' (2 dígitos) + Segundos (10 dígitos) = 12 dígitos matemáticos exactos
        $base = "20" . $segundos;
        
        // Si por alguna razón la cadena no mide 12, la rellenamos con ceros a la derecha
        $base = str_pad($base, 12, "0", STR_PAD_RIGHT);

        // 3. Calcular el dígito verificador oficial EAN-13
        $suma = 0;
        for ($i = 0; $i < 12; $i++) {
            $numero = (int)$base[$i];
            // Posiciones impares se multiplican por 1, posiciones pares (índices 1, 3, 5...) por 3
            $suma += ($i % 2 === 0) ? $numero : $numero * 3;
        }
        $digitoVerificador = (10 - ($suma % 10)) % 10;
        
        // 4. Código final estructurado de 13 dígitos
        $codigoUnico = $base . $digitoVerificador;

        // 5. Validamos contra tu tabla real de productos
        // REVISTA ESTO: Cambia 'Producto' por tu Modelo y 'codigo_barras' por tu columna real de la BD
        $existe = Producto::where('codigo', $codigoUnico)->exists();
    }


    return response()->json(['codigo' => $codigoUnico]);
}
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        $marcas = Marca::join('caracteristicas as c', 'marcas.caracteristica_id', '=', 'c.id')
            ->select('marcas.id as id', 'c.nombre as nombre')
            ->where('c.estado', 1)
            ->get();

        $presentaciones = Presentacione::join('caracteristicas as c', 'presentaciones.caracteristica_id', '=', 'c.id')
            ->select('presentaciones.id as id', 'c.nombre as nombre')
            ->where('c.estado', 1)
            ->get();

        $categorias = Categoria::join('caracteristicas as c', 'categorias.caracteristica_id', '=', 'c.id')
            ->select('categorias.id as id', 'c.nombre as nombre')
            ->where('c.estado', 1)
            ->get();

        return view('producto.edit',compact('producto','marcas','presentaciones','categorias'));
    }

    /**
     * Update the specified resource in storage.
     */
public function update(UpdateProductoRequest $request, Producto $producto)
{



    if (!Auth::check()) {
        return redirect()->route('login');
    }

    try {
        DB::beginTransaction();

        // 1. Inicializamos el nombre con lo que ya tiene el producto
        $name = $producto->img_path;

        // 2. Procesamos la imagen únicamente si el usuario subió una nueva
        if ($request->hasFile('img_path')) {
            $imagenVieja = $producto->img_path;

            // Sube la nueva imagen al bucket y nos devuelve 'productos/nombre.jpg'
            $name = $producto->handleUploadImage($request->file('img_path'));

            // 3. Eliminamos la imagen anterior de forma segura
            if (!empty($imagenVieja)) {
                $rutaBorrado = str_contains($imagenVieja, 'productos/') 
                    ? $imagenVieja 
                    : 'productos/' . $imagenVieja;

                try {
                    if (Storage::disk('gcs_images')->exists($rutaBorrado)) {
                        Storage::disk('gcs_images')->delete($rutaBorrado);
                    }
                } catch (\Exception $e) {
                    \Log::warning("No se pudo borrar la imagen vieja del bucket: " . $e->getMessage());
                }
            }
        }

        // 4. Llenamos el modelo (aquí se inyecta el $name definitivo)
        $producto->fill([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'img_path' => $name, // 👈 Se guarda en la BD 'productos/nombre.png'
            'marca_id' => $request->marca_id,
            'presentacione_id' => $request->presentacione_id,
            'perecedero' => $request->perecedero ? 1 : 0
        ]);


        // 4. Guardamos los cambios en la base de datos
        $producto->save();

        // Tabla categoría producto
        $categorias = $request->get('categorias');
        $producto->categorias()->sync($categorias);

        DB::commit();

        return redirect()->route('productos.index')->with('success', 'Producto editado correctamente.');

        } catch (\Exception $e) {
        DB::rollBack();
        // 🚨 CAMBIA ESTO TEMPORALMENTE PARA VER EL ERROR REAL:
        dd($e->getMessage(), $e->getTraceAsString()); 
    }

}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $message = '';
        $producto = Producto::find($id);
        if ($producto->estado == 1) {
            Producto::where('id', $producto->id)
                ->update([
                    'estado' => 0
                ]);
            $message = 'Producto eliminado';
        } else {
            Producto::where('id', $producto->id)
                ->update([
                    'estado' => 1
                ]);
            $message = 'Producto restaurado';
        }

        return redirect()->route('productos.index')->with('success', $message);
    }
}
