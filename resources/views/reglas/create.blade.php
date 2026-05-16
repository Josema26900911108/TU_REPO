@extends('layouts.app')

@section('title','Crear centro')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Centro</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('centros.index')}}">Centros</a></li>
        <li class="breadcrumb-item active">Crear Centro</li>
    </ol>

<form action="{{ route('reglas.store') }}" method="POST">
    @csrf
    <div class="grid grid-cols-2 gap-4">
        <!-- Nombre y Tipo -->
        <div>
            <label>Nombre de la Regla</label>
            <input type="text" name="nombre" class="form-control" placeholder="Ej: Promo 3x2 Cerveza">
        </div>
        <div>
            <label>Tipo de Regla</label>
            <select name="tipo_regla" id="tipo_regla" class="form-control">
                <option value="escala_cantidad">Escala por Cantidad (Mayoreo)</option>
                <option value="bonificacion">Bonificación (3x2, etc)</option>
                <option value="descuento_fijo">Descuento Directo</option>
            </select>
        </div>

        <!-- Cantidades -->
        <div>
            <label>Cantidad Mínima para activar</label>
            <input type="number" name="cantidad_minima" class="form-control" value="1">
        </div>
        <div id="div_paso" style="display:none;">
            <label>Cada cuanto aplicar (Paso)</label>
            <input type="number" name="cantidad_paso" class="form-control" value="1">
        </div>

        <!-- Beneficio -->
        <div>
            <label>Tipo de Beneficio</label>
            <select name="tipo_beneficio" class="form-control">
                <option value="precio_fijo">Precio Fijo / Nuevo Precio</option>
                <option value="porcentaje">Porcentaje de Descuento (%)</option>
                <option value="unidad_gratis">Unidades de Regalo</option>
            </select>
        </div>
        <div>
            <label>Valor del Beneficio</label>
            <input type="number" step="0.0001" name="valor_beneficio" class="form-control" placeholder="Ej: 0.75 o 10.00">
        </div>

        <!-- Vigencia -->
        <div>
            <label>Desde</label>
            <input type="date" name="fecha_inicio" class="form-control">
        </div>
        <div>
            <label>Hasta</label>
            <input type="date" name="fecha_fin" class="form-control">
        </div>
    </div>

    <div class="mt-4">
        <label>Aplicar a Productos:</label>
        <select name="productos[]" multiple class="form-control select2">
            @foreach($productos as $p)
                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary mt-4">Guardar Regla</button>
</form>

<script>
    // JS sencillo para mostrar/ocultar campos según el tipo
    document.getElementById('tipo_regla').addEventListener('change', function() {
        const divPaso = document.getElementById('div_paso');
        divPaso.style.display = (this.value === 'bonificacion') ? 'block' : 'none';
    });
</script>

</div>
@endsection

@push('js')

@endpush
