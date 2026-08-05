@extends('layouts.app')

@section('content')
<div class="container mt-3 mb-5" style="max-width: 600px;">
    <!-- Encabezado de la Ruta Diaria -->
    <div class="card shadow-sm border-0 bg-dark text-white mb-3 rounded-3">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 text-muted small uppercase fw-bold">Mi Hoja de Ruta</h6>
                    <h5 class="mb-0 fw-black text-warning">🚚 {{ $centroCostosPiloto }}</h5>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary px-2 py-1 fs-7">
                        {{ \Carbon\Carbon::parse($hoy)->format('d/m/Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success p-2 small mb-3 rounded-2 shadow-xs border-0">
            <strong>¡Éxito!</strong> {{ session('success') }}
        </div>
    @endif

    <!-- LISTADO DE VISITAS DEL DÍA -->
    <div class="space-y-3">
            @forelse($visitas as $index => $visita)
                <div class="card shadow-sm border border-light-subtle rounded-3 overflow-hidden">
                    <!-- Barra de Estado Lateral según el Estatus Contable -->
                    <div class="p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <!-- Número correlativo de entrega de la secuencia Kanban -->
                                <span class="badge bg-secondary me-1" style="font-size: 0.7rem;">📍 Entrega #{{ $index + 1 }}</span>
                                <small class="text-muted text-xs">Ruta: {{ $visita->nombre_ruta }}</small>
                            </div>
                            
                            <!-- Badge de Estado en tiempo real -->
                            @if($visita->estatus_entrega === 'PENDIENTE')
                                <span class="badge bg-warning text-dark px-2 py-1 rounded-pill fw-bold" style="font-size: 0.65rem;">⚡ PENDIENTE</span>
                            @elseif($visita->estatus_entrega === 'ENTREGADO')
                                <span class="badge bg-success px-2 py-1 rounded-pill fw-bold" style="font-size: 0.65rem;">✅ ENTREGADO</span>
                            @else
                                <span class="badge bg-danger px-2 py-1 rounded-pill fw-bold" style="font-size: 0.65rem;">❌ RECHAZADO</span>
                            @endif
                        </div>
    <button type="button" 
        class="btn btn-sm btn-info text-white" 
        onclick="cargarHistorial({{ $visita->id }})" 
        title="Historial de Compras">
    <i class="fas fa-history"></i> Historial
</button>

<!-- Botón Top 10 Productos -->
<button type="button" 
        class="btn btn-sm btn-success" 
        onclick="cargarTopProductos({{ $visita->id }})" 
        title="Top 10 Productos Más Vendidos">
    <i class="fas fa-star"></i> Top 10
</button>

                        <!-- Información del Cliente (Farmacia / Persona) -->
                        <h5 class="text-dark fw-bold mb-1 fs-6">{{ $visita->cliente_nombre }}</h5>
                        <p class="text-secondary small mb-2 lh-sm"><i class="fa-solid fa-map-location-dot text-primary me-1"></i> {{ $visita->cliente_direccion }}</p>
                        <small class="text-muted d-block mb-3" style="font-size: 0.75rem;">NIT / Documento: <strong>{{ $visita->cliente_nit }}</strong></small>

                        @if($visita->observaciones)
                            <div class="bg-light p-2 rounded text-muted mb-3 small border-start border-secondary" style="font-size: 0.75rem;">
                                <strong>Obs:</strong> {{ $visita->observaciones }}
                            </div>
                        @endif

                        <!-- BOTÓN DE ACCIÓN: Abre el Formulario Modal para reportar la Entrega -->
                        @if($visita->estatus_entrega === 'PENDIENTE')
                            <button type="button" 
                                    onclick="abrirModalReporte({{ $visita->id }}, '{{ $visita->cliente_nombre }}')" 
                                    class="btn btn-primary btn-sm w-100 font-weight-bold py-2 rounded-2 shadow-xs">
                                Reportar Visita / Entrega
                            </button>
                            
                                        @can('vender-a-cliente')
                                        
    <div>
    <!-- Forzamos el mapeo estricto del ID numérico de la visita o cliente -->
    <a title="Vender a Cliente por móvil" 
       href="{{ route('ventas.posmobile', ['idcliente' => (int) $visita->cliente_id]) }}" 
       class="btn btn-link p-0 text-primary hover-shadow" 
       style="text-decoration: none;">
        
        <svg xmlns="http://w3.org" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <!-- Marco del Teléfono Móvil -->
            <rect x="5" y="2" width="14" height="20" rx="2" ry="2" stroke="currentColor" fill="none" stroke-width="1.5"/>
            <!-- Línea de la Pantalla Interna -->
            <path d="M5 18h14" stroke="currentColor" stroke-width="1"/>
            <!-- Carrito de Compras estilizado dentro de la pantalla -->
            <circle cx="9" cy="14" r="1" fill="currentColor" stroke="none"/>
            <circle cx="15" cy="14" r="1" fill="currentColor" stroke="none"/>
            <path d="M6 6h2.5l1.5 5h5l1.2-3.5H8.5" stroke="currentColor" stroke-width="1.5" fill="none"/>
            <!-- Botón Home o Lector del Celular -->
            <circle cx="12" cy="20" r="0.7" fill="currentColor" stroke="none"/>
        </svg>

    </a>
</div>




                                    @endcan
                    @else
                        <button type="button" 
                                onclick="abrirModalReporte({{ $visita->id }}, '{{ $visita->cliente_nombre }}')" 
                                class="btn btn-outline-secondary btn-xs py-1 w-100 border-0 text-decoration-underline" style="font-size: 0.75rem;">
                            Modificar reporte anterior
                        </button>
                    @endif
                    
                </div>
            </div>
        @empty
            <div class="card p-4 text-center border-0 bg-light rounded-3">
                <span class="fs-1 d-block mb-2">☕</span>
                <h6 class="text-dark fw-bold mb-1">¡Sin entregas programadas!</h6>
                <p class="text-muted small mb-0">No hay clientes asignados a tu Centro de Costos para el día de hoy.</p>
            </div>
        @endforelse
            <!-- Botón Historial de Compras -->

    </div>
</div>

<!-- MODAL FLOTANTE ACCESIBLE PARA REPORTAR ESTATUS -->
<div class="modal fade" id="modalReportePiloto" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalReportePilotoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered p-3">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h6 class="modal-title fs-6 fw-bold" id="modalReportePilotoLabel">Reportar Entrega</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- El action se inyecta dinámicamente con JavaScript -->
            <form id="formReportePiloto" action="" method="POST" class="m-0">
                @csrf
                <div class="modal-body text-sm p-3">
                    <p class="text-muted mb-3 small">Selecciona el resultado de la visita para el cliente: <br><strong id="modal-cliente-nombre" class="text-dark"></strong></p>
                    
                    <div class="mb-3">
                        <label class="form-label font-weight-bold small text-secondary mb-1">Estatus de la Visita</label>
                        <select name="estatus" class="form-select form-select-md fw-bold" required>
                            <option value="ENTREGADO">✅ ENTREGADO / VISITADO</option>
                            <option value="RECHAZADO">❌ RECHAZADO / CERRADO</option>
                            <option value="PENDIENTE">⚡ MARCAR PENDIENTE</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label font-weight-bold small text-secondary mb-1">Observaciones / Comentarios</label>
                        <textarea name="observaciones" class="form-control" rows="3" placeholder="Ej: Se entregó con Graciela Ixmay, local cerrado, reprogramar..."></textarea>
                    </div>
                </div>
                <div class="modal-footer p-2 bg-light border-top flex-nowrap">
                    <button type="button" class="btn btn-secondary btn-sm w-50" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-success btn-sm w-50 font-weight-bold">Guardar Reporte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let modalBootstrapInstance = null;
    
    function abrirModalReporte(visitaId, clienteNombre) {
        document.getElementById('modal-cliente-nombre').innerText = clienteNombre;
        
        // Construimos dinámicamente la URL del formulario apuntando al ID de la fila diaria
        const form = document.getElementById('formReportePiloto');
        form.action = `/mi-despacho/actualizar/${visitaId}`;
        
        const modalElement = document.getElementById('modalReportePiloto');
        modalBootstrapInstance = new bootstrap.Modal(modalElement);
        modalBootstrapInstance.show();
    }
function cargarHistorial(clienteId) {
    fetch(`/clientes/${clienteId}/historial`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="4" class="text-center">El cliente no registra compras anteriores.</td></tr>';
            } else {
                data.forEach(pedido => {
                    html += `<tr>
                        <td>#${pedido.id}</td>
                        <td>${pedido.fecha}</td>
                        <td>Q. ${pedido.total}</td>
                        <td><span class="badge bg-secondary">${pedido.estado}</span></td>
                    </tr>`;
                });
            }
            document.getElementById('cuerpoHistorial').innerHTML = html;
            $('#modalHistorial').modal('show'); // Requiere jQuery + Bootstrap
        });
}

function cargarTopProductos(clienteId) {
    fetch(`/clientes/${clienteId}/top-productos`)
        .then(response => response.json())
        .then(data => {
            let html = '';
            if(data.length === 0) {
                html = '<li class="list-group-item text-center">Sin datos de productos para este cliente.</li>';
            } else {
                data.forEach((item, index) => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><strong>#${index + 1}</strong> ${item.producto}</span>
                        <span class="badge bg-success rounded-pill">${item.total_cantidad} uds. (${item.veces_comprado} pedidos)</span>
                    </li>`;
                });
            }
            document.getElementById('listaTopProductos').innerHTML = html;
            $('#modalTopProductos').modal('show');
        });
}

</script>


<style>
    .space-y-3 > * + * { margin-top: 1rem !important; }
    .fs-7 { font-size: 0.8rem; }
    .fw-black { font-weight: 900; }
</style>
@endsection
