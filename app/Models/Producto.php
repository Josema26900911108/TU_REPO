<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'fecha_vencimiento',
        'marca_id',
        'presentacione_id',
        'img_path',
        'fkTienda',
        'perecedero'
    ];

    public function compras()
    {
        return $this->belongsToMany(Compra::class)->withTimestamps()
            ->withPivot('cantidad', 'precio_compra', 'precio_venta');
    }

    public function ventas()
    {
        return $this->belongsToMany(Venta::class)->withTimestamps()
            ->withPivot('cantidad', 'precio_venta', 'descuento');
    }
    public function modificadores()
{
    return $this->belongsToMany(MenuModificador::class, 'menu_modificador_producto')
                ->withPivot('precio_override', 'orden_visualizacion', 'predefinido')
                ->withTimestamps();
}

    public function ingredientes()
{
    return $this->hasMany(Receta::class, 'producto_padre_id');
}
// Para saber qué ingredientes lleva este producto
public function receta()
{
    return $this->hasMany(Receta::class, 'producto_padre_id');
}

// Para saber en qué recetas se usa este ingrediente (opcional)
public function usadoEnRecetas()
{
    return $this->hasMany(Receta::class, 'ingrediente_id');
}


    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'fkTienda', 'idTienda');
    }
    public function categorias()
    {
        return $this->belongsToMany(Categoria::class)->withTimestamps();
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }
    public function presentacione()
    {
        return $this->belongsTo(Presentacione::class);
    }
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class, 'fkComprobante'); // Asegúrate de usar la clave foránea correcta
    }

    public function proveedore()
    {
        return $this->belongsTo(Proveedore::class, 'fkProveedor'); // Cambia 'fkProveedor' por la clave foránea correcta
    }
    public function cliente(){
        return $this->belongsTo(Cliente::class);
    }
public function lotes() {
    return $this->hasMany(Lotesalarma::class)->where('cantidad', '>', 0);
}

    public function movimientos()
    {
        return $this->hasMany(MovimientoMaterial::class);
    }
    public function handleUploadImage($image)
    {
        $file = $image;
        $name = time() . $file->getClientOriginalName();
        //$file->move(public_path() . '/img/productos/', $name);
        Storage::putFileAs('/public/productos/',$file,$name,'public');

        return $name;
    }

  public function reglasPrecios()
    {
        // Relación muchos a muchos con las reglas
        return $this->belongsToMany(ReglaPrecio::class, 'producto_regla_precio');
    }

  public function calcularPrecioVenta($cantidad)
    {
        // Usamos la relación reglasPrecios que definimos arriba
        $regla = $this->reglasPrecios()
            ->where('cantidad_minima', '<=', $cantidad)
            ->where(function($q) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
            })
            ->orderBy('prioritaria', 'desc')
            ->orderBy('cantidad_minima', 'desc')
            ->first();

        if (!$regla) return $this->precio_base;

        if ($regla->tipo_beneficio == 'precio_fijo') return $regla->valor_beneficio;
        if ($regla->tipo_beneficio == 'porcentaje') return $this->precio_base * (1 - ($regla->valor_beneficio / 100));
        
        return $this->precio_base;
    }

public function obtenerCotizacionFinal($cantidad, $modificadoresIds = [], $precioDesdeConsulta = null)
{
    // PRIORIDAD: 
    // 1. Precio que viene del JOIN (cpr.precio_venta)
    // 2. precio_base de la tabla productos
    // 3. 0 si no hay nada
    $base = $precioDesdeConsulta ?? ($this->precio_base ?? 0);
    $precioAcumulado = $base;

    // 1. Sumar Modificadores (si existen)
    if (!empty($modificadoresIds) && $this->relationLoaded('modificadores')) {
        $adicionales = $this->modificadores->whereIn('id', $modificadoresIds);
        foreach ($adicionales as $mod) {
            $precioAcumulado += $mod->pivot->precio_override ?? $mod->precio_adicional;
        }
    }

    // 2. Aplicar Reglas (si existen)
    $reglaActiva = null;
    if ($this->relationLoaded('reglasPrecios')) {
        $reglaActiva = $this->reglasPrecios
            ->where('cantidad_minima', '<=', $cantidad)
            ->filter(function($r) {
                return (is_null($r->fecha_fin) || $r->fecha_fin >= now());
            })
            ->sortByDesc('prioritaria')
            ->sortByDesc('cantidad_minima')
            ->first();
    }

    $precioUnitarioFinal = $precioAcumulado;
    $unidadesBonificadas = 0;
    $mensajePromo = "Precio Regular";

    if ($reglaActiva) {
        if ($reglaActiva->tipo_regla == 'escala_cantidad') {
            $precioUnitarioFinal = ($reglaActiva->tipo_beneficio == 'precio_fijo') 
                ? $reglaActiva->valor_beneficio 
                : $precioAcumulado * (1 - ($reglaActiva->valor_beneficio / 100));
            $mensajePromo = $reglaActiva->nombre;
        } elseif ($reglaActiva->tipo_regla == 'bonificacion') {
            $ciclos = floor($cantidad / ($reglaActiva->cantidad_minima + $reglaActiva->valor_beneficio));
            $unidadesBonificadas = $ciclos * $reglaActiva->valor_beneficio;
            $mensajePromo = "Promo: " . $reglaActiva->nombre;
        }
    }

    return [
        'precio_unitario' => round($precioUnitarioFinal, 2),
        'subtotal'        => round(($cantidad - $unidadesBonificadas) * $precioUnitarioFinal, 2),
        'promocion'       => $mensajePromo,
        'unidades_gratis' => $unidadesBonificadas
    ];
}


}
