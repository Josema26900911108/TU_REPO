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
     */
    
public function store(StoreProductoRequest $request)
{
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    // 1. Iniciar la transacción de inmediato
    DB::beginTransaction();
    dd($request->all(), gettype($request->img_path), $request->file('img_path'));

    try {
        $fkTienda = session('user_fkTienda');
        $producto = new Producto();
        $name = null; // En store, por defecto la imagen empieza en null o vacío

        // 2. Manejar la carga de la imagen de forma segura
if ($request->filled('img_path') && str_starts_with($request->img_path, 'data:image')) {
    try {
        $name = $producto->handleUploadImage($request->img_path);
    } catch (Exception $e) {
        DB::rollBack();
        return redirect()->back()->withInput()->with('error', 'Error al cargar la imagen en la nube: ' . $e->getMessage());
    }
}

        // 3. Llenar los campos del producto
        $producto->fill([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'img_path' => $name, // Se asigna el nombre obtenido de GCS
            'marca_id' => $request->marca_id,
            'presentacione_id' => $request->presentacione_id,
            'fkTienda' => $fkTienda,
            'perecedero' => $request->perecedero ? 1 : 0
        ]);

        // 4. Guardar el producto
        $producto->save();

        // 5. Manejar la relación de categorías
        $categorias = $request->get('categorias');
        if (!empty($categorias)) {
            $producto->categorias()->attach($categorias);
        }

        // 6. Confirmar la transacción si todo salió bien
        DB::commit();

        return redirect()->route('productos.index')->with('success', 'Producto registrado exitosamente.');

    } catch (Exception $e) {
        // 7. SIEMPRE revertir la transacción si algo falla (incluyendo Google Cloud)
        DB::rollBack();

        return redirect()->back()
            ->withInput() // Mantiene los datos del formulario que llenó el usuario
            ->with('error', 'Ocurrió un error al registrar el producto: ' . $e->getMessage());
    }
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
