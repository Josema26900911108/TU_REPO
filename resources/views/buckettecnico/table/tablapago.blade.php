<div id="tabla_pago_content" class="fade-in animate__animated animate__fadeIn">
    <!-- Fila de Tarjetas Informativas / Acumulados -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-gradient-success text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #198754 0%, #0f5132 100%); min-height: 110px;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white-50 text-uppercase fw-bold fs-11 mb-1 tracking-wider">Total Dinero Pagado</h6>
                        <h3 class="fw-extrabold mb-0">Q{{ number_format($relacion->sum('COSTOPAGO'), 2) }}</h3>
                        <small class="text-white-50 fs-11">Muestra acumulado actual</small>
                    </div>
                    <div class="opacity-25" style="font-size: 3.5rem; line-height: 1;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white position-relative overflow-hidden" style="border-left: 4px solid #198754 !important; min-height: 110px;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold fs-11 mb-1 tracking-wider">Mano de Obra (MO)</h6>
                        <h3 class="fw-extrabold text-dark mb-0">{{ number_format($relacion->sum('Cantidad'), 2) }}</h3>
                        <small class="text-muted fs-11">Servicios ejecutados en total</small>
                    </div>
                    <div class="text-success opacity-25" style="font-size: 3.5rem; line-height: 1;">
                        <i class="bi bi-person-gear"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white position-relative overflow-hidden" style="border-left: 4px solid #6c757d !important; min-height: 110px;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold fs-11 mb-1 tracking-wider">Órdenes Autorizadas</h6>
                        <h3 class="fw-extrabold text-dark mb-0">{{ $relacion->total() }}</h3>
                        <small class="text-muted fs-11">Transacciones procesadas</small>
                    </div>
                    <div class="text-secondary opacity-25" style="font-size: 3.5rem; line-height: 1;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Buscador y Tabla -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 border-0">
            <div class="row align-items-center g-3">
                <div class="col-md-4">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="bi bi-search fs-13"></i>
                        </span>
                        <input type="text" id="globalSearchPago" class="form-control form-control-sm bg-light border-start-0 fs-13" placeholder="Filtrar pagos en tiempo real...">
                    </div>
                </div>
                <div class="col-md-8 text-md-end">
                    <span class="badge bg-success-subtle text-success px-2.5 py-1.5 rounded-pill fs-11">
                        <i class="bi bi-shield-check me-1"></i> Ventana de Pagos (Naturaleza H)
                    </span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="datatablesSimplePago" class="table table-hover align-middle mb-0 fs-12 text-secondary">
                <thead class="table-light text-uppercase fs-11 fw-bold tracking-wider border-bottom text-muted">
                    <tr>
                        <th class="ps-3 py-3">Orden</th>
                        <th>Estatus</th>
                        <th>SKU</th>
                        <th>Descripción</th>
                        <th>OBS</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end text-success">Pago Dinero</th>
                        <th class="text-center">Nat.</th>
                        <th class="text-center pe-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @if($relacion->count() > 0)
                        @foreach ($relacion as $item)
                            <tr class="border-bottom">
                                <td class="ps-3 fw-bold text-dark">
                                    <span class="font-monospace text-secondary">{{ $item->Orden }}</span>
                                </td>
                                <td>
                                    @if($item->Status == 'S')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Reportando, pendiente autorización</span>
                                    @else
                                        <span class="badge bg-light text-muted border rounded-pill px-2">{{ $item->Status }}</span>
                                    @endif
                                </td>
                                <!-- Relación Arbolmanoobra integrada con fallback nativo -->
                                <td><code class="text-success fw-semibold bg-light px-1.5 py-0.5 rounded fs-11">{{ $item->arbolmanoobra->SKU ?? $item->SKU }}</code></td>
                                <td class="text-dark max-w-250 text-truncate" title="{{ $item->arbolmanoobra->descripcion ?? $item->Descripcion }}">{{ $item->arbolmanoobra->descripcion ?? $item->Descripcion }}</td>
                                <td>
                                    <span class="text-muted fs-11 d-inline-block text-truncate" style="max-width: 150px;" title="{{ $item->OBS }}">
                                        {{ $item->OBS ?? 'Sin observaciones' }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-dark">{{ number_format($item->Cantidad, 2) }}</td>
                                <td class="text-end fw-bold text-success">Q{{ number_format($item->COSTOPAGO, 2) }}</td>
                                <td class="text-center"><span class="badge bg-success rounded-circle p-1.5 fs-10" title="Credito / Pago">{{ $item->Naturaleza }}</span></td>
                                <td class="text-center pe-3">
                                    <button class="btn btn-light btn-sm border-0 text-muted rounded-circle p-1.5" type="button" data-bs-toggle="dropdown" style="width: 28px; height: 28px;">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 fs-12">
            <button type="button" 
                    class="dropdown-item py-2 btn-ver-detalle" 
                    data-bs-toggle="modal" 
                    data-bs-target="#modalDetalleTecnico"
                    data-orden="{{ $item->Orden }}"
                    data-sku="{{ $item->SKU }}"
                    data-descripcion="{{ $item->Descripcion }}"
                    data-obs="{{ $item->OBS ?? 'Sin observaciones' }}"
                    data-cantidad="{{ number_format($item->Cantidad, 2) }}"
                    data-pago="${{ number_format($item->COSTOPAGO, 2) }}"
                    data-status="{{ $item->Status }}"
                    data-naturaleza="{{ $item->Naturaleza }}">
                <i class="bi bi-eye me-2 text-primary"></i>Ver detalle
            </button>

                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted bg-light-subtle">
                                <div class="mb-2 fs-3 text-secondary-subtle"><i class="bi bi-inbox"></i></div>
                                No se encontraron registros de pagos coincidentes.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Footer / Paginación -->
        <div class="card-footer bg-white border-0 py-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
            <div class="fs-12 text-muted">
                Mostrando del <span class="fw-semibold text-dark">{{ $relacion->firstItem() ?? 0 }}</span> al <span class="fw-semibold text-dark">{{ $relacion->lastItem() ?? 0 }}</span> de <span class="fw-semibold text-dark">{{ $relacion->total() }}</span> registros
            </div>
            <div>
                {!! $relacion->links('pagination::bootstrap-5') !!}
            </div>
        </div>
    </div>
</div>

