@extends('layouts.app')

@section('title','Desgloce Pagos a Técnicos')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
  <style>
    .custom-file-input {
      display: none;
    }
    .custom-upload-btn {
      cursor: pointer;
    }
  </style>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')

@include('layouts.partials.alert')
@if(session('notificacion_extraccion'))
    <div class="alert alert-light border shadow-sm rounded-3 mb-4 p-3 animate__animated animate__fadeIn">
        <div class="d-flex align-items-center mb-2">
            <i class="fas fa-clipboard-check text-dark fs-16 me-2"></i>
            <h6 class="mb-0 fw-bold text-dark fs-14">Resultado de la Última Extracción Masiva</h6>
        </div>
        <ul class="list-group list-group-flush fs-12 ps-0 mb-0">
            <!-- Estado de Pago Técnico -->
            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1.5 border-0">
                <span><i class="fas fa-file-invoice-dollar me-2 text-secondary"></i>Resumen Órdenes:</span>
                <span class="badge {{ Str::contains(session('notificacion_extraccion.pago'), 'OK') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} rounded-pill px-2.5">
                    {{ session('notificacion_extraccion.pago') }}
                </span>
            </li>
            <!-- Estado de Movimientos -->
            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1.5 border-0">
                <span><i class="fas fa-boxes me-2 text-secondary"></i>Historial de Materiales:</span>
                <span class="badge {{ Str::contains(session('notificacion_extraccion.materiales'), 'OK') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} rounded-pill px-2.5">
                    {{ session('notificacion_extraccion.materiales') }}
                </span>
            </li>
            <!-- Estado de Fotografías -->
            <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1.5 border-0">
                <span><i class="fas fa-images me-2 text-secondary"></i>Evidencias Fotográficas:</span>
                <span class="badge {{ Str::contains(session('notificacion_extraccion.fotos'), 'OK') ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }} rounded-pill px-2.5">
                    {{ session('notificacion_extraccion.fotos') }}
                </span>
            </li>
        </ul>
    </div>
@endif

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Desgloce Pagos a Técnicos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Desgloce Pagos a Técnicos</li>
    </ol>

@can('crear-materialmanoobra')
    <!-- Contenedor alineado con el estilo de "Insertar Movimientos" -->
    <div class="card shadow-sm border rounded-3 mb-4">
        <!-- Encabezado con título integrado en el marco -->
        <div class="card-header bg-light py-2 px-3 border-bottom">
            <h6 class="card-title mb-0 text-primary fw-bold">
                <i class="fa fa-file-excel-o me-2"></i> Importar Formato de Pagos
            </h6>
        </div>
        
        <div class="card-body p-3">
            <!-- Bloque A: Acción de Subida (Únicamente procesa el archivo seleccionado) -->
            <form action="{{ route('pagotecnico.importar') }}" method="POST" enctype="multipart/form-data" class="row align-items-center g-3 mb-3">
                @csrf
                <div class="col-auto d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa fa-check me-1"></i> Subir
                    </button>
                    
                    <label for="archivo" class="btn btn-primary custom-upload-btn mb-0">
                        <i class="fa fa-upload"></i> Seleccionar Archivo
                    </label>
                    <input type="file" id="archivo" name="archivo" class="custom-file-input d-none" onchange="mostrarNombre(this)">
                </div>
                <div class="col">
                    <span id="nombre-archivo" class="text-muted small">Ningún archivo seleccionado</span>
                </div>
            </form>

            <!-- Línea divisoria estética -->
            <hr class="text-muted my-2">

            <!-- Bloque B: Descarga de Plantilla (Aislado del Formulario para evitar el submit accidental) -->
            <div class="row align-items-center">
                <div class="col-auto">
                    <a href="{{ route('pagotecnico.formato-pago') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-download me-1"></i> Descargar Formato de Pagos
                    </a>
                </div>
            </div>
        </div>
    </div>
@endcan


@can('modificar-materialmanoobra')
    <!-- Contenedor alineado con el estilo de "Insertar Movimientos" -->
    <div class="card shadow-sm border rounded-3 mb-4">
        <!-- Encabezado con título integrado en el marco -->
        <div class="card-header bg-light py-2 px-3 border-bottom">
            <h6 class="card-title mb-0 text-primary fw-bold">
                <i class="fa fa-file-excel-o me-2"></i> Editar Formato de Pagos
            </h6>
        </div>
        
        <div class="card-body p-3">
            <!-- Bloque de Acción de Subida -->
            <form action="{{ route('pagotecnico.editar-masivo') }}" method="POST" enctype="multipart/form-data" class="row align-items-center g-3 mb-3">
                @csrf
                <div class="col-auto d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa fa-check me-1"></i> Subir
                    </button>
                    
                    <label for="archivo_edicion" class="btn btn-primary custom-upload-btn mb-0">
                        <i class="fa fa-upload"></i> Seleccionar Archivo
                    </label>
                    <input type="file" id="archivo_edicion" name="archivo" class="custom-file-input d-none" onchange="mostrarNombreEdicion(this)">
                </div>
                <div class="col">
                    <span id="nombre-archivo-edicion" class="text-muted small">Ningún archivo seleccionado</span>
                </div>
            </form>

            <!-- Separador visual -->
            <hr class="text-muted my-2">

            <!-- Bloque de Descarga con la nueva ruta unificada -->
            <div class="row align-items-center">
                <div class="col-auto">
                    <a href="{{ route('pagotecnico.nuevo_formato_descarga') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-download me-1"></i> Descargar Formato de Modificación
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Script rápido auxiliar por si tienes dos inputs de archivo en la misma vista y no choquen sus IDs -->
    <script>
    function mostrarNombreEdicion(input) {
        var nombre = input.files[0] ? input.files[0].name : "Ningún archivo seleccionado";
        document.getElementById('nombre-archivo-edicion').innerText = nombre;
    }
    </script>
@endcan


        <!-- ========================================== -->
    <!-- COMPONENTE DE FILTROS DINÁMICOS -->
    <!-- ========================================== -->
    <div class="card mb-4 bg-light">
        <div class="card-body">
            <form method="GET" action="{{ url()->current() }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Orden / Expediente</label>
                    <input type="text" name="orden" class="form-control" placeholder="Ej: ORD-1234" value="{{ request('orden') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Técnico</label>
                    <select name="tecnico_id" class="form-select">
                        <option value="">-- Todos los Técnicos --</option>
                        @foreach($tecnicos as $tec)
                            <option value="{{ $tec->id }}" {{ request('tecnico_id') == $tec->id ? 'selected' : '' }}>
                                {{ $tec->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Estatus de Pago</label>
                    <select name="Status" class="form-select">
                            <option value="">-- Todos los Estatus de Pago --</option>
                            <option value="C" {{ request('Status') == 'C' ? 'selected' : '' }}>
                                Pagado (C)
                            </option>
                            <option value="S" {{ request('Status') == 'S' ? 'selected' : '' }}>
                                Pendiente (S)
                            </option>
                            <option value="B" {{ request('Status') == 'B' ? 'selected' : '' }}>
                                Pago Anulado/No pagado (B)
                            </option>                            
                        
                    </select>
                </div>                
                <div class="col-md-2">
                    <label class="form-label fw-bold">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="{{ url()->current() }}" class="btn btn-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>
    </div>


    <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
        <button type="button" class="btn btn-success btn-sm px-3 shadow-sm" onclick="exportarExcel()">
            <i class="fas fa-file-excel me-1.5"></i> Exportar a Excel
        </button>
        <button type="button" class="btn btn-info btn-sm px-3 text-white shadow-sm" onclick="exportarFotos()">
            <i class="fas fa-file-archive me-1.5"></i> Exportar Fotos (ZIP)
        </button>
        <button type="button" class="btn btn-dark btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalExtraccionMasiva">
            <i class="fas fa-file-archive me-1.5"></i> Exportar Expedientes Masivos (ZIP)
        </button>
        <button type="button" class="btn btn-dark btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMemoriaFotografica">
            <i class="fas fa-file-archive me-1.5"></i> Memoria Fotográfica (ZIP)
        </button>
        <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLiquidacionFirmas">
            <i class="fas fa-file-signature me-1.5"></i> Exportar Liquidaciones Firmadas (ZIP)
        </button>
    </div>

<!-- Ventana Modal para el Reporte de Firmas Digitales -->
<div class="modal fade" id="modalLiquidacionFirmas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark fs-14">
                    <i class="fas fa-pen-fancy text-primary me-2"></i> Reporte de Liquidaciones con Firmas
                </h5>
                <!-- Botón de cerrar oculto mientras procesa -->
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="btn-cerrar-modal"></button>
            </div>
            
            <form action="{{ route('reportes.exportarFirmas') }}" method="POST" enctype="multipart/form-data" id="form-liquidacion-firmas">
                @csrf
                <div class="modal-body p-4 fs-13">
                    <!-- Alerta informativa (se oculta al procesar) -->
                    <div id="alerta-informativa" class="alert alert-primary border-0 shadow-sm mb-3 fs-12" style="background-color: #e3f2fd; color: #0d47a1;">
                        <i class="fas fa-info-circle me-1"></i> Suba el archivo Excel con el listado de órdenes en la <b>primera columna (Columna A)</b>. El sistema procesará las firmas en segundo plano.
                    </div>
                    
                    <!-- Selector de archivo (se bloquea al procesar) -->
                    <div class="mb-3" id="contenedor-input">
                        <label class="form-label fw-semibold text-dark">Seleccionar Excel de Órdenes (.xlsx, .xls, .csv)</label>
                        <input type="file" name="excel_ordenes" id="input-excel-ordenes" class="form-control form-control-sm" accept=".xlsx, .xls, .csv" required>
                    </div>

                    <!-- BARRA DE PROGRESO (Oculta por defecto) -->
                    <div id="contenedor-progreso" class="mt-4 p-3 bg-light rounded border d-none">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="fs-12 fw-bold text-primary" id="texto-progreso">
                                <i class="fas fa-cogs fa-spin me-1"></i> Iniciando proceso...
                            </span>
                            <span class="fs-12 fw-bold text-primary" id="porcentaje-progreso">0%</span>
                        </div>
                        <div class="progress" style="height: 12px; border-radius: 10px;">
                            <div id="barra-progreso" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%; border-radius: 10px;"></div>
                        </div>
                        <div class="text-muted fs-11 mt-2 text-center">
                            No cierre esta ventana. La descarga comenzará automáticamente.
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal" id="btn-cancelar-firmas">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3" id="btn-submit-firmas">
                        <i class="fas fa-spinner fa-spin me-1 d-none" id="loader-firmas"></i> Generar Hojas Firmadas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="modalExtraccionMasiva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark fs-14">
                    <i class="fas fa-boxes text-dark me-2"></i> Extracción Cruzada de Información
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pagotecnico.extraccion-masiva') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 fs-13">
                    <div class="alert alert-info border-0 shadow-sm mb-3 fs-12">
                        <i class="fas fa-info-circle me-1"></i> Suba un archivo Excel que contenga una columna llamada <b>"orden"</b> o <b>"Orden"</b> con el listado que desea procesar.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Seleccionar Excel de Órdenes (.csv)</label>
                        <input type="file" name="excel_ordenes" class="form-control form-control-sm" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark btn-sm px-3">
                        <i class="fas fa-cog fa-spin me-1 d-none" id="loader-zip"></i> Procesar y Descargar ZIP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMemoriaFotografica" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark fs-14">
                    <i class="fas fa-boxes text-dark me-2"></i> Memoria Fotográfica
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('pagotecnico.memoria-fotografica') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4 fs-13">
                    <div class="alert alert-info border-0 shadow-sm mb-3 fs-12">
                        <i class="fas fa-info-circle me-1"></i> Suba un archivo Excel que contenga una columna llamada <b>"orden"</b> o <b>"Orden"</b> con el listado que desea procesar.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Seleccionar Excel de Órdenes (.csv)</label>
                        <input type="file" name="excel_ordenes" class="form-control form-control-sm" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark btn-sm px-3">
                        <i class="fas fa-cog fa-spin me-1 d-none" id="loader-zip"></i> Procesar y Descargar ZIP
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
    <!-- ========================================== -->
    <!-- TARJETA DE BALANCE ALGEBRAICO TOTAL -->
    <!-- ========================================== -->
<div class="row g-3 mb-4">
    <!-- Tarjeta Balance General -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #212529 0%, #343a40 100%);">
            <div class="card-body">
                <h6 class="text-white-50 text-uppercase fw-bold fs-11 mb-1">Balance General</h6>
                <h4 class="fw-bold mb-0">Q{{ number_format($totalBalance, 2) }}</h4>
                <small class="text-white-50 fs-11">Total neto filtrado</small>
            </div>
        </div>
    </div>

    <!-- Tarjeta Estatus S -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-white bg-success" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%);">
            <div class="card-body">
                <h6 class="text-white-50 text-uppercase fw-bold fs-11 mb-1">Estatus S (Pendiente)</h6>
                <h4 class="fw-bold mb-0">Q{{ number_format($balanceS, 2) }}</h4>
                <small class="text-white-50 fs-11">Monto acumulado</small>
            </div>
        </div>
    </div>

    <!-- Tarjeta Estatus C -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-white bg-warning" style="background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%);">
            <div class="card-body">
                <h6 class="text-dark-50 text-uppercase fw-bold fs-11 mb-1 text-dark">Estatus C (Pagado)</h6>
                <h4 class="fw-bold mb-0 text-dark">Q{{ number_format($balanceC, 2) }}</h4>
                <small class="text-dark-50 fs-11 text-dark">Monto acumulado</small>
            </div>
        </div>
    </div>

    <!-- Tarjeta Estatus B -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-white bg-danger" style="background: linear-gradient(135deg, #dc3545 0%, #b21f2d 100%);">
            <div class="card-body">
                <h6 class="text-white-50 text-uppercase fw-bold fs-11 mb-1">Estatus B (No Pagado)</h6>
                <h4 class="fw-bold mb-0">Q{{ number_format($balanceB, 2) }}</h4>
                <small class="text-white-50 fs-11">Monto acumulado</small>
            </div>
        </div>
    </div>
</div>

    <!-- ========================================== -->
    <!-- TABLA PRINCIPAL DE REGISTROS -->
    <!-- ========================================== -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla Desglose Pagos a Técnicos
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped fs-6">
                <thead>
                    <tr>
                        <th>ID Pago</th>
                        <th>Orden / Expediente</th>
                        <th>SKU</th>
                        <th>Descripción Item</th>
                        <th>Cantidad</th>
                        <th>Costo Pago (Q)</th>
                        <th>Estatus Pago</th>
                        <th>Naturaleza</th>                        
                        <th>Observaciones</th>
                        @can('vertienda-producto')
                        <th>Tienda</th>
                        @endcan
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pagostecnico as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->Orden }}</td>
                        <td>{{ $item->SKU }}</td>
                        <td>{{ $item->Descripcion }}</td>
                        <td>{{ $item->Cantidad }}</td>
                        <td>{{ number_format($item->COSTOPAGO, 2) }}</td>
                        <td>
                            <span class="badge {{ $item->Status === 'S' ? 'bg-success' : 'bg-warning' }}">
                                {{ $item->Status }}
                            </span>
                        </td>
                        <td>
                            @if(strtoupper(trim($item->Naturaleza)) === 'D')
                                <span class="text-danger fw-bold"><i class="fas fa-arrow-down"></i> D (Resta)</span>
                            @elseif(strtoupper(trim($item->Naturaleza)) === 'H')
                                <span class="text-success fw-bold"><i class="fas fa-arrow-up"></i> H (Suma)</span>
                            @else
                                {{ $item->Naturaleza }}
                            @endif
                        </td>
                        
                        <td>{{ $item->OBS }}</td>
                        @can('vertienda-producto')
                        <td>
                            @if($item->fkTienda && $item->tienda)
                                {{ $item->tienda->Nombre }}
                            @else
                                Sin tienda asignada
                            @endif
                        </td>
                        @endcan

                        <td>
                            <div class="d-flex justify-content-around">
                                <div>
                                    <button title="Opciones" class="btn btn-datatable btn-icon btn-transparent-dark me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg class="svg-inline--fa fa-ellipsis-vertical" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-vertical" role="img" xmlns="http://w3.org" viewBox="0 0 128 512">
                                            <path fill="currentColor" d="M56 472a56 56 0 1 1 0-112 56 56 0 1 1 0 112zm0-160a56 56 0 1 1 0-112 56 56 0 1 1 0 112zM0 96a56 56 0 1 1 112 0A56 56 0 1 1 0 96z"></path>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu text-bg-light" style="font-size: small;">
                                        @can('ver-producto')
                                        <li>
                                            <a class="dropdown-item" role="button" data-bs-toggle="modal" data-bs-target="#verModal-{{ $item->id }}">Ver Detalles</a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                                <div><div class="vr"></div></div>
                                <div>
                                    @can('eliminar-materialmanoobra')
                                    <button title="Eliminar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{ $item->id }}" class="btn btn-datatable btn-icon btn-transparent-dark">
                                        <svg class="svg-inline--fa fa-trash-can" aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-can" role="img" xmlns="http://w3.org" viewBox="0 0 448 512">
                                            <path fill="currentColor" d="M170.5 51.6L151.5 80h145l-19-28.4c-1.5-2.2-4-3.6-6.7-3.6H177.1c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80H368h48 8c13.3 0 24 10.7 24 24s-10.7 24-24 24h-8V432c0 44.2-35.8 80-80 80H112c-44.2 0-80-35.8-80-80V128H24c-13.3 0-24-10.7-24-24S10.7 80 24 80h8H80 93.8l36.7-55.1C140.9 9.4 158.4 0 177.1 0h93.7c18.7 0 36.2 9.4 46.6 24.9zM80 128V432c0 17.7 14.3 32 32 32H336c17.7 0 32-14.3 32-32V128H80zm80 64V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16z"></path>
                                        </svg>
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal Detalles -->
                    <div class="modal fade" id="verModal-{{ $item->id }}" tabindex="-1" aria-labelledby="verModalLabel-{{ $item->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="verModalLabel-{{ $item->id }}">Detalles del Registro de Pago</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-12 mb-2"><p><span class="fw-bolder">ID Transacción: </span>{{ $item->id }}</p></div>
                                        <div class="col-12 mb-2"><p><span class="fw-bolder">Orden / Expediente: </span>{{ $item->Orden }}</p></div>
                                        <div class="col-12 mb-2"><p><span class="fw-bolder">SKU del Item: </span>{{ $item->SKU }}</p></div>
                                        <div class="col-12 mb-2"><p><span class="fw-bolder">Descripción: </span>{{ $item->Descripcion }}</p></div>
                                        <div class="col-12 mb-2"><p><span class="fw-bolder">Cantidad Otorgada: </span>{{ $item->Cantidad }}</p></div>
                                        <div class="col-12 mb-2"><p><span class="fw-bolder">Monto Total Liquidado: </span>{{ number_format($item->COSTOPAGO, 2) }}</p></div>
                                        <div class="col-12 mb-2"><p><span class="fw-bolder">Estado de Nómina: </span>{{ $item->Status }}</p></div>
                                        <div class="col-12 mb-2"><p><span class="fw-bolder">Naturaleza: </span>{{ $item->Naturaleza }}</p></div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>
<script>
  function mostrarNombre(input) {
    const nombre = input.files.length > 0 ? input.files[0].name : "Ningún archivo seleccionado";
    document.getElementById('nombre-archivo').textContent = nombre;
  }

function exportarExcel() {
    Swal.fire({
        title: '¿Deseas exportar el desglose de pagos a Excel?',
        text: "Se generará un archivo CSV/Excel con los filtros aplicados actualmente en pantalla.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, exportar',
        cancelButtonText: 'No, cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // 1. CAPTURA DINÁMICA CON PREVENCIÓN DE NULOS
            let orden       = document.querySelector("input[name='orden']")?.value || '';
            let tecnicoId   = document.querySelector("select[name='tecnico_id']")?.value || '';
            let fechaInicio = document.querySelector("input[name='fecha_inicio']")?.value || '';
            let fechaFin    = document.querySelector("input[name='fecha_fin']")?.value || '';
            let status      = document.querySelector("select[name='Status']")?.value || '';

            // 2. CONSTRUIR PARÁMETROS FILTRANDO CAMPOS VACÍOS
            let params = {};
            if (orden.trim() !== '') params.orden = orden.trim();
            if (tecnicoId.trim() !== '') params.tecnico_id = tecnicoId;
            if (fechaInicio.trim() !== '') params.fecha_inicio = fechaInicio;
            if (fechaFin.trim() !== '') params.fecha_fin = fechaFin;
            if (status.trim() !== '') params.Status = status;

            let queryParams = new URLSearchParams(params);
            let urlBase = "{{ route('pagotecnico.exportar') }}";

            Swal.fire({
                title: 'Generando archivo...',
                text: 'Tu descarga iniciará en unos momentos.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // 3. ACTIVAR LA DESCARGA NATIVA
            window.location.href = urlBase + '?' + queryParams.toString();
        }
    });
}


function exportarFotos() {
    Swal.fire({
        title: '¿Deseas exportar las fotografías?',
        text: "Se descargará un archivo comprimido (.ZIP) con las evidencias fotográficas de los expedientes filtrados actualmente.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd', // Azul Bootstrap primary
        cancelButtonColor: '#6c757d',  // Gris Bootstrap secondary
        confirmButtonText: 'Sí, descargar ZIP',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // 1. CAPTURA DINÁMICA DE FILTROS EN TIEMPO REAL
            let orden       = document.querySelector("input[name='orden']").value;
            let tecnicoId   = document.querySelector("select[name='tecnico_id']").value;
            let fechaInicio = document.querySelector("input[name='fecha_inicio']").value;
            let fechaFin    = document.querySelector("input[name='fecha_fin']").value;

            // 2. CONSTRUIR LOS PARÁMETROS URL
            let queryParams = new URLSearchParams({
                orden: orden,
                tecnico_id: tecnicoId,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin
            });

            // Reemplaza 'pagostecnico.exportarfotos' por el nombre exacto de tu ruta en web.php
            let urlBase = "{{ route('pagostecnico.exportarfotos') }}";

            // Mostrar notificación informativa de empaquetado
            Swal.fire({
                title: 'Comprimiendo imágenes...',
                text: 'Esto puede demorar unos segundos dependiendo del volumen de fotos. No cierres la ventana.',
                icon: 'info',
                timer: 4000,
                showConfirmButton: false,
                allowOutsideClick: false
            });

            // 3. ACTIVAR LA DESCARGA NATIVA DEL COMPRIMIDO
            window.location.href = urlBase + '?' + queryParams.toString();
        }
    });
}

</script> 

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formFirmas = document.getElementById('form-liquidacion-firmas');
    
    if (formFirmas) {
        formFirmas.addEventListener('submit', function(e) {
            e.preventDefault();

            let btnSubmit = document.getElementById('btn-submit-firmas');
            let btnCancelar = document.getElementById('btn-cancelar-firmas');
            let btnCerrarModal = document.getElementById('btn-cerrar-modal');
            let loader = document.getElementById('loader-firmas');
            let inputExcel = document.getElementById('input-excel-ordenes');
            let alertaInformativa = document.getElementById('alerta-informativa');
            let contenedorInput = document.getElementById('contenedor-input');
            
            let contenedorProgreso = document.getElementById('contenedor-progreso');
            let barraProgreso = document.getElementById('barra-progreso');
            let textoProgreso = document.getElementById('texto-progreso');
            let porcentajeProgreso = document.getElementById('porcentaje-progreso');

            if (inputExcel.files.length === 0) {
                Swal.fire('Atencion', 'Por favor, selecciona un archivo Excel.', 'warning');
                return;
            }

            let formData = new FormData(this);
            let token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

            btnSubmit.disabled = true;
            btnCancelar.disabled = true;
            btnCerrarModal.style.display = 'none';
            inputExcel.disabled = true;
            loader.classList.remove('d-none');
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Subiendo...';
            
            alertaInformativa.classList.add('d-none');
            contenedorInput.classList.add('d-none');
            contenedorProgreso.classList.remove('d-none');
            textoProgreso.innerHTML = '<i class="fas fa-cloud-upload-alt me-1"></i> Subiendo archivo al servidor...';

fetch(this.action, {
    method: 'POST',
    body: formData,
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token }
})
.then(response => {
    if (!response.ok) {
        // Si es un error de Laravel (500, 419, 413), lo mostramos
        return response.text().then(text => {
            throw new Error('Error ' + response.status + ': ' + text);
        });
    }
    return response.json();
})
.then(data => {
    if (!data.ok) {
        throw new Error(data.mensaje || 'Error al iniciar el proceso');
    }

    // El servidor ya recibió el archivo y respondió con éxito.
    // Ahora empezamos el polling (la barra de progreso).
    btnSubmit.innerHTML = 'Procesando...';
    textoProgreso.innerHTML = '<i class="fas fa-cogs fa-spin me-1"></i> Procesando firmas y PDF...';
    
    let progresoActual = 0;
    let intervaloBarra = setInterval(() => {
        if (progresoActual < 50) progresoActual += 1;
        else if (progresoActual < 95) progresoActual += 0.2; 
        if (progresoActual > 95) progresoActual = 95; 
        
        barraProgreso.style.width = progresoActual + '%';
        porcentajeProgreso.innerText = Math.floor(progresoActual) + '%';

        if (progresoActual < 30) textoProgreso.innerHTML = '<i class="fas fa-database fa-spin me-1"></i> Consultando materiales...';
        else if (progresoActual < 60) textoProgreso.innerHTML = '<i class="fas fa-image fa-spin me-1"></i> Descargando firmas...';
        else textoProgreso.innerHTML = '<i class="fas fa-file-pdf fa-spin me-1"></i> Ensamblando el PDF...';
    }, 1000);

    let intentos = 0; 
    let intervaloServer = setInterval(() => {
        intentos++; 
        
        if (intentos > 100) { 
            clearInterval(intervaloServer);
            clearInterval(intervaloBarra); 
            restaurarBoton();
            Swal.fire('Tiempo agotado', 'El servidor tardó más de 5 minutos.', 'warning');
            return;
        }

        fetch('{{ route("reportes.verificar-estado") }}')
            .then(res => res.json())
            .then(status => {
                if (status.estado === 'completado') {
                    clearInterval(intervaloServer);
                    clearInterval(intervaloBarra); 
                    
                    // Lógica de descarga...
                    let modal = bootstrap.Modal.getInstance(document.getElementById('modalLiquidacionFirmas'));
                    if(modal) modal.hide();

                    Swal.fire({ 
                        icon: 'success', 
                        title: '¡Reporte Listo!', 
                        text: 'La descarga ha comenzado.', 
                        timer: 4000, 
                        showConfirmButton: true 
                    });

                    let urlDescarga = '/reportes/descargar/' + encodeURIComponent(status.archivo);
                    let iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    iframe.src = urlDescarga;
                    document.body.appendChild(iframe);

                    restaurarBoton();

                    setTimeout(() => {
                        if (iframe.parentNode) document.body.removeChild(iframe);
                    }, 10000);

                } else if (status.estado === 'error') {
                    clearInterval(intervaloServer);
                    clearInterval(intervaloBarra);
                    restaurarBoton();
                    Swal.fire('No se pudo generar', status.mensaje, 'error');
                }
            })
            .catch(err => console.log("Reintentando conexión...")); // Si falla una vez, no pasa nada, sigue intentando
    }, 3000); 
})
.catch(error => {
    // Si el fetch inicial falla (Failed to fetch), mostramos el error real.
    restaurarBoton();
    Swal.fire('Error de conexión', 'No se pudo comunicar con el servidor. Intenta de nuevo.', 'error');
    console.error('Error:', error);
});

            function restaurarBoton() {
                btnSubmit.disabled = false;
                btnCancelar.disabled = false;
                btnCerrarModal.style.display = 'block';
                inputExcel.disabled = false;
                inputExcel.value = ''; 
                loader.classList.add('d-none');
                btnSubmit.innerHTML = 'Generar Hojas Firmadas';
                
                alertaInformativa.classList.remove('d-none');
                contenedorInput.classList.remove('d-none');
                contenedorProgreso.classList.add('d-none');
                
                setTimeout(() => {
                    barraProgreso.style.width = '0%';
                    barraProgreso.classList.add('bg-primary', 'progress-bar-animated');
                    barraProgreso.classList.remove('bg-success');
                    porcentajeProgreso.innerText = '0%';
                    porcentajeProgreso.classList.replace('text-success', 'text-primary');
                    textoProgreso.innerHTML = '<i class="fas fa-cogs fa-spin me-1"></i> Iniciando proceso...';
                    textoProgreso.classList.replace('text-success', 'text-primary');
                }, 500);
            }
        });
    }
});
</script>
@endpush