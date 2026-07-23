@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Crear Regla de Precio / Promoción</h4>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ url('/reglas/guardar') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Nombre de la Regla / Oferta</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Mayoreo de refrescos o Promo 3x2" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Regla</label>
                        <select name="tipo_regla" id="tipo_regla" class="form-select" required>
                            <option value="escala_cantidad">Escala por Cantidad (Mayoreo)</option>
                            <option value="bonificacion">Bonificación (Ej: 3x2, 4x3)</option>
                            <option value="descuento_fijo">Descuento Fijo (Oferta directa)</option>
                            <option value="combo_mixto">Combo Mixto (Paquete)</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Cantidad Mínima Requerida</label>
                        <input type="number" name="cantidad_minima" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cantidad de Paso (Para 3x2 colocar 3)</label>
                        <input type="number" name="cantidad_paso" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo de Beneficio</label>
                        <select name="tipo_beneficio" class="form-select" required>
                            <option value="precio_fijo">Precio Fijo Rebajado ($)</option>
                            <option value="porcentaje">Porcentaje de Descuento (%)</option>
                            <option value="unidad_gratis">Unidad Gratis (Regalo)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valor del Beneficio</label>
                        <input type="number" step="0.0001" name="valor_beneficio" class="form-control" placeholder="Ej: 10.50 o 15%" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Fecha Inicio (Vigencia)</label>
                        <input type="datetime-local" name="fecha_inicio" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha Fin (Vigencia)</label>
                        <input type="datetime-local" name="fecha_fin" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">¿Es prioritaria?</label>
                        <select name="prioritaria" class="form-select" required>
                            <option value="0">No (Baja)</option>
                            <option value="1">Sí (Alta - Mata otras reglas)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label bg-light p-1 rounded d-block font-weight-bold">Modo de Aplicación en Caja</label>
                        <div class="form-check form-check-inline mt-1">
                            <input class="form-check-input" type="radio" name="requiere_confirmacion" id="app_directa" value="0" checked>
                            <label class="form-check-label text-success" for="app_directa"><strong>Aplicar directo</strong></label>
                        </div>
                        <div class="form-check form-check-inline mt-1">
                            <input class="form-check-input" type="radio" name="requiere_confirmacion" id="app_pregunta" value="1">
                            <label class="form-check-label text-warning" for="app_pregunta"><strong>Preguntar antes</strong></label>
                        </div>
                    </div>
                </div>

                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <h5>Asociar Productos a esta Regla</h5>
                        <p class="text-muted small">Escribe en el buscador para filtrar y selecciona uno o varios productos.</p>
                        
                        <!-- Buscador -->
                        <div class="input-group mb-3">
                            <span class="input-group-text bg-white">🔍</span>
                            <input type="text" id="buscador-productos" class="form-control" placeholder="Buscar por nombre o código de barras...">
                        </div>

                        <!-- Contenedor con Scroll -->
                        <div id="lista-productos-container" class="rounded" style="max-height: 250px; overflow-y: auto; border: 1px solid #ced4da; padding: 12px; background: white;">
                            @foreach($productos as $prod)
                                <div class="form-check p-1 item-producto-row">
                                    <input class="form-check-input check-producto-item" type="checkbox" name="productos[]" value="{{ $prod->id }}" id="prod_{{ $prod->id }}">
                                    <label class="form-check-label w-100 label-producto-texto" for="prod_{{ $prod->id }}">
                                        <strong class="text-secondary">[{{ $prod->codigo }}]</strong> {{ $prod->nombre }} 
                                        <span class="badge bg-light text-dark float-end">Original: ${{ number_format($prod->precio_base, 2) }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success px-4">Guardar Regla de Precio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EL SCRIPT SE COLOCA ADENTRO DE LA SECCIÓN CONTENT PARA OBLIGAR A LARAVEL A CARGARLO -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const inputBuscador = document.getElementById('buscador-productos');
        
        if (inputBuscador) {
            inputBuscador.addEventListener('keyup', function () {
                const terminoFiltrado = this.value.toLowerCase().trim();
                const bloquesProductos = document.querySelectorAll('.item-producto-row');

                bloquesProductos.forEach(function (bloque) {
                    // Leemos directamente el texto visible dentro del label para evitar errores de atributos HTML
                    const textoCelda = bloque.querySelector('.label-producto-texto').textContent.toLowerCase();
                    
                    if (textoCelda.indexOf(terminoFiltrado) > -1) {
                        bloque.style.setProperty('display', 'block', 'important');
                    } else {
                        bloque.style.setProperty('display', 'none', 'important');
                    }
                });
            });
        }
    });
</script>
@endsection
