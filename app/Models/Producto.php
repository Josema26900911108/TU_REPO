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
        'precio_base', // CORREGIDO: Añadido para que funcionen las cotizaciones
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

    // CORREGIDO: Se unificaron los métodos duplicados en este único método descriptivo
    public function receta()
    {
        return $this->hasMany(Receta::class, 'producto_padre_id');
    }

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
    // Forzamos a Laravel a usar exactamente la tabla 'categoria_producto'
    return $this->belongsToMany(Categoria::class, 'categoria_producto')->withTimestamps();
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
        return $this->belongsTo(Comprobante::class, 'fkComprobante'); 
    }

    public function proveedore()
    {
        return $this->belongsTo(Proveedore::class, 'fkProveedor'); 
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function lotes() 
    {
        return $this->hasMany(Lotesalarma::class)->where('cantidad', '>', 0);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoMaterial::class);
    }

    public function reglasPrecios()
    {
        return $this->belongsToMany(ReglaPrecio::class, 'producto_regla_precio');
    }

    public function handleUploadImage($image)
    {
        $file = $image;
        $name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
        $path = 'productos/' . $name;

        try {
            $stream = fopen($file->getRealPath(), 'r');
            Storage::disk('gcs_images')->put($path, $stream);
            
            if (is_resource($stream)) {
                fclose($stream);
            }
        } catch (\Exception $e) {
            dd([
                'Mensaje' => 'Error crítico en stream',
                'Error' => $e->getMessage()
            ]);
        }

        return $path;
    }

    public function calcularPrecioVenta($cantidad)
    {
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
        $base = $precioDesdeConsulta ?? ($this->precio_base ?? 0);
        $precioAcumulado = $base;

        if (!empty($modificadoresIds) && $this->relationLoaded('modificadores')) {
            $adicionales = $this->modificadores->whereIn('id', $modificadoresIds);
            foreach ($adicionales as $mod) {
                $precioAcumulado += $mod->pivot->precio_override ?? $mod->precio_adicional;
            }
        }

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
