<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Presentacione;
use App\Models\Producto;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
    try {
        // Recuperar la tienda de la sesión
        $fkTienda = session('user_fkTienda');

        DB::beginTransaction();

        // Inicializar el modelo Producto
        $producto = new Producto();

        // Manejar la carga de la imagen
        if ($request->hasFile('img_path')) {
            try {
                $name = $producto->handleUploadImage($request->file('img_path'));
            } catch (Exception $e) {
                return redirect()->back()->with('error', 'Error al cargar la imagen: ' . $e->getMessage());
            }
        } else {
            $name = null;
        }

        // Llenar los campos del producto con los datos del formulario
        $producto->fill([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'img_path' => $name,
            'marca_id' => $request->marca_id,
            'presentacione_id' => $request->presentacione_id,
            'fkTienda' => $fkTienda
        ]);

        // Guardar el producto
        $producto->save();

        // Manejar la relación de categorías
        $categorias = $request->get('categorias');
        if (!empty($categorias)) {
            $producto->categorias()->attach($categorias);
        }

        // Confirmar la transacción
        DB::commit();

        // Redirigir con éxito
        return redirect()->route('productos.index')->with('success', 'Producto registrado exitosamente.');

    } catch (Exception $e) {
        // En caso de error, revertir la transacción
        DB::rollBack();

        // Retornar el error para el usuario
        return redirect()->back()->with('error', 'Ocurrió un error al registrar el producto: ' . $e->getMessage());
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
        try{
            DB::beginTransaction();

            if ($request->hasFile('img_path')) {
                $name = $producto->handleUploadImage($request->file('img_path'));

                //Eliminar si existiese una imagen
                if(Storage::disk('public')->exists('productos/'.$producto->img_path)){
                    Storage::disk('public')->delete('productos/'.$producto->img_path);
                }

            } else {
                $name = $producto->img_path;
            }

            $producto->fill([
                'codigo' => $request->codigo,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'img_path' => $name,
                'marca_id' => $request->marca_id,
                'presentacione_id' => $request->presentacione_id
            ]);

            $producto->save();

            //Tabla categoría producto
            $categorias = $request->get('categorias');
            $producto->categorias()->sync($categorias);

            DB::commit();
        }catch(Exception $e){
            DB::rollBack();
        }

        return redirect()->route('productos.index')->with('success','Producto editado');
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
