@extends('layouts.app')

@section('title', 'Stock SAP')

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

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Stock SAP</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Stock SAP</li>
    </ol>

    @can('crear-materialmanoobra')
    <!-- Sección de Importación y Formato unificada -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex align-items-center">
                    <form action="{{ route('stocksap.importar') }}" method="POST" enctype="multipart/form-data" class="d-inline-flex align-items-center">
                        @csrf
                        <label for="archivo" class="btn btn-primary custom-upload-btn me-2" title="Seleccionar Archivo CSV">
                            <i class="fas fa-upload me-1"></i> Seleccionar CSV
                        </label>
                        <input type="file" id="archivo" name="archivo" class="custom-file-input" onchange="mostrarNombre(this)" required>
                        <span id="nombre-archivo" class="text-muted me-3">Ningún archivo seleccionado</span>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Subir
                        </button>
                    </form>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <a href="{{ route('stocksap.exportarformato') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> Descargar Formato
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <!-- Tabla de Contenido -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-boxes-stacked me-1"></i>
            Tabla de Existencias SAP y Movimientos de Material
        </div>
        <div class="card-body" style="overflow-x: auto;">
            <table id="datatablesSimple" class="table table-striped fs-12">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Serie</th>
                        <th>Almacén</th>
                        <th>Lote</th>
                        <th>MAC 1</th>
                        <th>Cantidad</th>
                        <th>Costo</th>
                        <th>Centro</th>
                        <th>Tipo</th>
                        <th>U. Medida</th>
                        <th>Movimiento</th>
                        @can('vertienda-producto')
                        <th>Tienda</th>
                        @endcan
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($materialmanoobra as $item) <!-- Reemplazar por $materialesSap si cambias la variable en el controlador -->
                    <tr>
                        <td><strong>{{ $item->SKU }}</strong></td>
                        <td>{{ $item->serie ?? 'N/A' }}</td>
                        <td>{{ $item->almacen }}</td>
                        <td>{{ $item->Lote ?? 'N/A' }}</td>
                        <td>{{ $item->MAC1 ?? 'N/A' }}</td>
                        <td><span class="badge bg-info text-dark">{{ number_format($item->cantidad, 2) }}</span></td>
                        <td>${{ number_format($item->COSTO, 2) }}</td>
                        <td>{{ $item->CENTRO }}</td>
                        <td>{{ $item->TIPO }}</td>
                        <td>{{ $item->unidadmedida }}</td>
                        <td><span class="small text-muted">{{ $item->TIPOMOVIMIENTO }}</span></td>
                        
                        @can('vertienda-producto')
                        <td>
                            @if($item->fkTienda && $item->tienda->idTienda)
                                {{ $item->tienda->Nombre }}
                            @else
                                <span class="text-danger small">Sin tienda</span>
                            @endif
                        </td>
                        @endcan

                        <td>
                            <div class="d-flex justify-content-around">
                                <div>
                                    <button title="Opciones" class="btn btn-datatable btn-icon btn-transparent-dark me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg class="svg-inline--fa fa-ellipsis-vertical" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-vertical" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 512" data-fa-i2svg="">
                                            <path fill="currentColor" d="M56 472a56 56 0 1 1 0-112 56 56 0 1 1 0 112zm0-160a56 56 0 1 1 0-112 56 56 0 1 1 0 112zM0 96a56 56 0 1 1 112 0A56 56 0 1 1 0 96z"></path>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu text-bg-light" style="font-size: small;">
                                        @can('ver-producto')
                                        <li>
                                            <a class="dropdown-item" role="button" data-bs-toggle="modal" data-bs-target="#verModal-{{$item->id}}">Ver Detalles</a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                                <div>
                                    <div class="vr"></div>
                                </div>
                                <div>
                                    @can('eliminar-materialmanoobra')
                                    <button type="button" title="Eliminar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark">
                                        <svg style="pointer-events: none;" class="svg-inline--fa fa-trash-can" aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-can" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg="">
                                            <path fill="currentColor" d="M170.5 51.6L151.5 80h145l-19-28.4c-1.5-2.2-4-3.6-6.7-3.6H177.1c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80H368h48 8c13.3 0 24 10.7 24 24s-10.7 24-24 24h-8V432c0 44.2-35.8 80-80 80H112c-44.2 0-80-35.8-80-80V128H24c-13.3 0-24-10.7-24-24S10.7 80 24 80h8H80 93.8l36.7-55.1C140.9 9.4 158.4 0 177.1 0h93.7c18.7 0 36.2 9.4 46.6 24.9zM80 128V432c0 17.7 14.3 32 32 32H336c17.7 0 32-14.3 32-32V128H80zm80 64V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16z"></path>
                                        </svg>
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="d-flex justify-content-center mt-3">
                {{ $materialmanoobra->links('pagination::bootstrap-5') }}
            </div>


        </div>
    </div>
</div>

<!-- ========================================================== -->
<!-- SECCIÓN AISLADA DE MODALES (FUERA DE LA TABLA)             -->
<!-- ========================================================== -->
@foreach ($materialmanoobra as $item)
    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="verModal-{{$item->id}}" tabindex="-1" aria-labelledby="verModalLabel-{{$item->id}}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="verModalLabel-{{$item->id}}">Detalles de Material SAP</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><p><span class="fw-bold">SKU:</span> {{ $item->SKU }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Serie:</span> {{ $item->serie ?? 'N/A' }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Almacén:</span> {{ $item->almacen }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Lote:</span> {{ $item->Lote ?? 'N/A' }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">MAC 1:</span> {{ $item->MAC1 ?? 'N/A' }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">MAC 2:</span> {{ $item->MAC2 ?? 'N/A' }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">MAC 3:</span> {{ $item->MAC3 ?? 'N/A' }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Cantidad:</span> {{ $item->cantidad }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Costo:</span> ${{ number_format($item->COSTO, 2) }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Centro:</span> {{ $item->CENTRO }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Tipo:</span> {{ $item->TIPO }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Unidad de Medida:</span> {{ $item->unidadmedida }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Estatus:</span> {{ $item->ESTATUS }}</p></div>
                        <div class="col-6"><p><span class="fw-bold">Creado por:</span> {{ $item->Creado_por }} ({{ $item->Creado_el }})</p></div>
                        <div class="col-6"><p><span class="fw-bold">Modificado por:</span> {{ $item->Modificado_por }} ({{ $item->Modificado_el }})</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
@push('js')
<!-- IMPORTANTE: Asegúrate de importar la librería si no está en tu layout principal -->
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>

<script>

    window.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('datatablesSimple');
        if (datatablesSimple) {
            new simpleDatatables.DataTable(datatablesSimple, {
                paging: false, // OBLIGATORIO: false. Laravel dibuja los números de página.
                labels: {
                    placeholder: "Buscar en esta página...",
                    noRows: "No se encontraron registros de material",
                    info: "Mostrando registros de esta página",
                }
            });
        }
    });

    // Función estética para mostrar el nombre del archivo CSV seleccionado
    function mostrarNombre(input) {
        const nombreSpan = document.getElementById('nombre-archivo');
        
        if (input.files && input.files[0]) {
            nombreSpan.textContent = input.files[0].name;
            nombreSpan.className = "ms-2 text-success fw-bold"; 
        } else {
            nombreSpan.textContent = "Ningún archivo seleccionado";
            nombreSpan.className = "ms-2 text-muted";
        }
    }
</script>
@endpush

