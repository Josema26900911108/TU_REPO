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
        'fkTienda'
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
        public function lotes()
    {
        return $this->hasMany(Lote::class);
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
}
