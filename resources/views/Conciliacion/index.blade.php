@extends('layouts.app')

@section('title', 'Conciliación de Órdenes')

@push('css-datatable')
<link rel="stylesheet" type="text/css" href="https://datatables.net">
<link rel="stylesheet" type="text/css" href="https://datatables.net">
@endpush

@push('css')
  <style>
    .custom-file-input {
      display: none;
    }
    .custom-upload-btn {
      cursor: pointer;
    }
    .pagination-container .pagination {
        justify-content: center;
    }
    .form-control {
        display: block;
        width: 100%;
        padding: 0.375rem 0.75rem;
        font-size: 0.9rem;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .gap-2 {
        gap: 0.5rem !important;
    }
    .badge-conciliado {
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
    }
    .badge-advertencia {
        background-color: #fff3cd;
        color: #664d03;
        border: 1px solid #ffecb5;
    }
    .badge-error {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
    }
  </style>
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="container-fluid px-4">
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Conciliación de Materiales</li>
    </ol>
</div>

<ul class="nav nav-tabs" id="conciliacionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab">
            Mapeo y Conciliación Masiva
        </button>
    </li>
</ul>

<div class="tab-content mt-3" id="conciliacionTabsContent">
    <div class="tab-pane fade show active" id="datos" role="tabpanel" aria-labelledby="datos-tab">
        
        <!-- SECCIÓN 1: PANEL DE IMPORTACIÓN DE ARCHIVOS -->
        <div class="card shadow-sm border-0 bg-light p-3 mb-4">
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-8">
                        <h6 class="fw-bold text-secondary mb-2"><i class="fa fa-file-csv me-1"></i> Cargar Órdenes de Trabajo (Archivo CSV)</h6>
                        <form action="{{ route('conciliacion.importar') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap">
                            @csrf
                            <button type="submit" class="btn btn-success px-4">Subir y Mapear</button>
                            
                            <label for="archivo_csv" class="btn btn-primary mb-0 custom-upload-btn">
                                <i class="fa fa-upload"></i> Seleccionar CSV
                            </label>
                            <input type="file" id="archivo_csv" name="archivo_csv" accept=".csv" class="custom-file-input" onchange="mostrarNombreCSV(this)">
                            
                            <span id="nombre-archivo-csv" class="text-muted small ms-2">Ningún archivo seleccionado</span>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<div class="card shadow-sm border-0 bg-light p-3 mb-4">
    <button type="button" class="btn btn-dark btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConciliacionMasiva">
    <i class="fas fa-file-invoice me-1"></i> Extracción Cruzada por Archivo (.csv)
</button>
</div>


        <!-- SECCIÓN 2: BUSCADOR FILTRADO EN PANTALLA -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body border-bottom bg-white rounded-top">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-4">
                        <label for="globalSearch" class="form-label small fw-bold text-secondary">Buscar en pantalla:</label>
                        <input type="text" id="globalSearch" class="form-control" placeholder="Escribe orden, SKU, estado...">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: TABLA ÚNICA DE CONCILIACIÓN -->
            <div class="table-responsive p-3">
                <table id="tabla-conciliacion" class="table table-striped table-bordered align-middle fs-6" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Orden (8 Dígitos)</th>
                            <th>SKU / Servicio</th>
                            <th class="text-center">Cant. Importada</th>
                            <th class="text-center">Cant. PagoTécnico</th>
                            <th class="text-center">Dif. Cantidad</th>
                            <th class="text-right">Subtotal Importado</th>
                            <th class="text-right">Subtotal PagoTécnico</th>
                            <th class="text-right">Dif. Subtotal</th>
                            <th class="text-center">Estado Conciliación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reporte as $fila)
                            <tr>
                                <td class="fw-bold text-dark">{{ $fila->Orden }}</td>
                                <td>{{ $fila->SKU }}</td>
                                <td class="text-center">{{ number_format($fila->Cantidad_OT, 2) }}</td>
                                <td class="text-center">{{ number_format($fila->Cantidad_PT, 2) }}</td>
                                <td class="text-center fw-bold {{ $fila->Diferencia_Cantidad != 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $fila->Diferencia_Cantidad > 0 ? '+' : '' }}{{ number_format($fila->Diferencia_Cantidad, 2) }}
                                </td>
<td class="text-end">Q {{ number_format($fila->Subtotal_OT, 2) }}</td>
<td class="text-end">Q {{ number_format($fila->Subtotal_PT, 2) }}</td>
<td class="text-end fw-bold {{ $fila->Diferencia_Subtotal != 0 ? 'text-danger' : 'text-success' }}">
    {{ $fila->Diferencia_Subtotal < 0 ? '-' : '' }}Q {{ number_format(abs($fila->Diferencia_Subtotal), 2) }}
</td>

                                <td class="text-center">
                                    @if(str_contains($fila->Estado_Conciliacion, '✅'))
                                        <span class="badge badge-conciliado px-2.5 py-1.5 rounded-pill font-medium small">
                                            {{ $fila->Estado_Conciliacion }}
                                        </span>
                                    @elseif(str_contains($fila->Estado_Conciliacion, '⚠️'))
                                        <span class="badge badge-advertencia px-2.5 py-1.5 rounded-pill font-medium small">
                                            {{ $fila->Estado_Conciliacion }}
                                        </span>
                                    @else
                                        <span class="badge badge-error px-2.5 py-1.5 rounded-pill font-medium small">
                                            {{ $fila->Estado_Conciliacion }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No se encontraron datos cruzados. Sube un archivo de órdenes de trabajo para calcular discrepancias.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
<!-- 🚀 RENDERIZADO DE LOS BOTONES DE PÁGINA (Siguiente, Anterior, Números) -->
<div class="card-footer bg-white border-top-0 pb-4">
    <div class="d-flex justify-content-center mt-3">
        {!! $reporte->appends(request()->query())->links('pagination::bootstrap-5') !!}
    </div>
</div>
    </div>
</div>

<!-- Modal de Extracción Personalizada -->
<div class="modal fade" id="modalConciliacionMasiva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark fs-14">
                    <i class="fas fa-scale-balanced text-dark me-2"></i> Conciliación Cruzada por Órdenes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Apuntamos a la nueva ruta dedicada para la extracción masiva bajo demanda -->
            <form action="{{ route('conciliacion.extraccion-masiva') }}" method="POST" enctype="multipart/form-data" onsubmit="activarLoaderConciliacion()">
                @csrf
                <div class="modal-body p-4 fs-13">
                    <div class="alert alert-info border-0 shadow-sm mb-3 fs-12">
                        <i class="fas fa-info-circle me-1"></i> Suba un archivo CSV que contenga una columna de órdenes. El sistema buscará sus equivalencias en <b>Pago Técnico</b> y <b>Órdenes de Trabajo</b> para calcular diferencias financieras en Quetzales.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Seleccionar CSV de Órdenes (.csv)</label>
                        <input type="file" name="csv_ordenes" class="form-control form-control-sm" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">
                        <i class="fas fa-cog fa-spin me-1 d-none" id="loader-conciliacion"></i> Procesar y Descargar CSV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://jquery.com"></script>
<script src="https://jsdelivr.net"></script>
<script src="https://jsdelivr.net"></script>

<!-- DataTables JS nativos -->
<script type="text/javascript" src="https://datatables.net"></script>
<script src="https://datatables.net"></script>
<script src="https://datatables.net"></script>
<script src="https://datatables.net"></script>
<script src="https://cloudflare.com"></script>

<script>
    function activarLoaderConciliacion() {
    let loader = document.getElementById('loader-conciliacion');
    if (loader) {
        loader.classList.remove('d-none');
    }
    // Cerrar el modal automáticamente después de unos segundos tras enviar la petición de descarga
    setTimeout(() => {
        var modalEl = document.getElementById('modalConciliacionMasiva');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        if (loader) loader.classList.add('d-none');
    }, 3000);
}


    $(document).ready(function() {
        // Inicializar el motor de DataTables con botones automatizados de Excel e Impresión
        let dataTableInstance = $('#tabla-conciliacion').DataTable({
            responsive: true,
            order: [[8, 'asc']], // Prioriza mostrar las discrepancias arriba
            pageLength: 25,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fa fa-file-excel"></i> Generar Reporte de Conciliación',
                    className: 'btn btn-success btn-sm mb-2 shadow-sm font-semibold',
                    title: 'Reporte_Conciliacion_Diferencias_' + new Date().toISOString().slice(0,10)
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Imprimir Vista',
                    className: 'btn btn-secondary btn-sm mb-2'
                }
            ]
        });

        // Conexión en tiempo real del input de búsqueda con DataTables
$('#globalSearch').on('keyup', function() {
    dataTableInstance.search(this.value).draw();
    });
    });

    // Muestra dinámicamente el nombre del archivo seleccionado en el botón personalizado
    function mostrarNombreCSV(input) {
        const nombreArchivo = input.files[0] ? input.files[0].name : 'Ningún archivo seleccionado';
        document.getElementById('nombre-archivo-csv').textContent = nombreArchivo;
    }
    function initConciliacionDataTable() {
    let tablaConciliacion = '#tabla-conciliacion';
    if ($(tablaConciliacion).length && !$.fn.DataTable.isDataTable(tablaConciliacion)) {
        $(tablaConciliacion).DataTable({
            responsive: true,
            paging: false, // 🚀 APAGADO: La paginación la maneja Laravel abajo de la tabla
            info: true,
            ordering: true,
            searching: true, // Mantiene el buscador en pantalla para las 25 filas visibles
            dom: 'Bfrtip',
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Exportar Vista Actual',
                    className: 'btn btn-success btn-sm mb-3',
                    title: 'Reporte_Pagina_Actual_' + new Date().toISOString().slice(0,10)
                }
            ]
        });
    }
}

   </script>
    @endpush