@extends('layouts.app') <!-- O tu layout base -->

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Listado de Reglas de Precio y Promociones</h4>
            <a href="{{ route('reglas.create') }}" class="btn btn-light btn-sm font-weight-bold">
    Crear Regla
</a>

        </div>
        <div class="card-body">
            
            <!-- Buscador rápido en la tabla -->
            <div class="mb-3">
                <input type="text" id="buscador-tabla-reglas" class="form-control" placeholder="Buscar por nombre de regla o tipo...">
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle" id="tabla-reglas">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo de Regla</th>
                            <th>Condición / Activador</th>
                            <th>Beneficio / Efecto</th>
                            <th>Vigencia</th>
                            <th>Modo Caja</th>
                            <th>Prioridad</th>
                            <th>Productos Vinculados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reglas as $regla)
                            <tr class="fila-regla-row">
                                <td class="fw-bold text-dark text-search">{{ $regla->nombre }}</td>
                                <td class="text-search">
                                    @if($regla->tipo_regla === 'escala_cantidad')
                                        <span class="badge bg-info text-dark">Escala (Mayoreo)</span>
                                    @elseif($regla->tipo_regla === 'bonificacion')
                                        <span class="badge bg-warning text-dark">Bonificación (3x2/4x3)</span>
                                    @elseif($regla->tipo_regla === 'descuento_fijo')
                                        <span class="badge bg-danger">Descuento Fijo</span>
                                    @elseif($regla->tipo_regla === 'combo_mixto')
                                        <span class="badge bg-purple" style="background-color: #6f42c1; color: white;">Combo Mixto</span>
                                    @endif
                                </td>
                                <td>
                                    Mínimo: <strong>{{ $regla->cantidad_minima }} und.</strong>
                                    @if($regla->cantidad_paso)
                                        <br><small class="text-muted">Paso: {{ $regla->cantidad_paso }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($regla->tipo_beneficio === 'precio_fijo')
                                        Precio Cerrado: <strong>${{ number_format($regla->valor_beneficio, 2) }}</strong>
                                    @elseif($regla->tipo_beneficio === 'porcentaje')
                                        Descuento: <strong>{{ number_format($regla->valor_beneficio, 2) }}%</strong>
                                    @elseif($regla->tipo_beneficio === 'unidad_gratis')
                                        Regalo: <strong>{{ (int)$regla->valor_beneficio }} und. Gratis</strong>
                                    @endif
                                </td>
                                <td class="small">
                                    @if($regla->fecha_inicio || $regla->fecha_fin)
                                        <span>Inicia: {{ $regla->fecha_inicio ? \Carbon\Carbon::parse($regla->fecha_inicio)->format('d/m/Y H:i') : 'Inmediato' }}</span><br>
                                        <span>Termina: {{ $regla->fecha_fin ? \Carbon\Carbon::parse($regla->fecha_fin)->format('d/m/Y H:i') : 'Indefinido' }}</span>
                                    @else
                                        <span class="text-success fw-bold">Siempre Activa</span>
                                    @endif
                                </td>
                                <td>
                                    @if($regla->requiere_confirmacion)
                                        <span class="badge bg-light text-warning border border-warning">⚠️ Preguntar antes</span>
                                    @else
                                        <span class="badge bg-light text-success border border-success">⚡ Directo (Auto)</span>
                                    @endif
                                </td>
                                <td>
                                    @if($regla->prioritaria)
                                        <span class="badge bg-dark">Alta ⭐</span>
                                    @else
                                        <span class="badge bg-secondary text-white">Normal</span>
                                    @endif
                                </td>
                                <td>
                                    <!-- Listado colapsable de productos asociados para no saturar la tabla -->
                                    <button class="btn btn-outline-secondary btn-xs py-0 px-1 font-size-sm" type="button" data-bs-toggle="collapse" data-bs-target="#prodList_{{ $regla->id }}">
                                        Ver ({{ $regla->productos->count() }})
                                    </button>
                                    <div class="collapse mt-1" id="prodList_{{ $regla->id }}">
                                        <div class="p-2 bg-light rounded border small" style="max-height: 120px; overflow-y: auto;">
                                            @foreach($regla->productos as $p)
                                                <div>• <span class="text-secondary">[{{ $p->codigo }}]</span> {{ $p->nombre }}</div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No hay ninguna regla de precio registrada para esta tienda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Script de filtrado nativo para la tabla (Adentro de la sección content) -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const inputBuscador = document.getElementById('buscador-tabla-reglas');
        
        if (inputBuscador) {
            inputBuscador.addEventListener('keyup', function () {
                const termino = this.value.toLowerCase().trim();
                const filas = document.querySelectorAll('.fila-regla-row');

                filas.forEach(function (fila) {
                    let coincidencia = false;
                    const celdasABuscar = fila.querySelectorAll('.text-search');
                    
                    celdasABuscar.forEach(function(celda) {
                        if (celda.textContent.toLowerCase().indexOf(termino) > -1) {
                            coincidencia = true;
                        }
                    });

                    if (coincidencia) {
                        fila.style.display = '';
                    } else {
                        fila.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endsection
