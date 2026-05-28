@extends('layouts.app')

@section('title','Productos')

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
    <h1 class="mt-4 text-center">Desgloce Pagos a Técnicos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Desgloce Pagos a Técnicos</li>
    </ol>

    @can('crear-materialmanoobra')

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body p-2">
                <div class="row align-items-center g-3">
                    <form action="{{ route('pagotecnico.importar') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <button type="submit" class="btn btn-success">Subir</button>
                            <label for="archivo" class="btn btn-primary custom-upload-btn">
                            <i class="fa fa-upload"></i>
                            </label>

                            <input type="file" id="archivo" name="archivo" class="custom-file-input" onchange="mostrarNombre(this)">
                            <span id="nombre-archivo" class="ml-2 text-muted">Ningún archivo seleccionado</span>
                    </form>
                </div>
                <div class="row align-items-center g-1">
                    <a href="{{route('pagotecnico.formato-pago')}}">
                        <button type="button" class="fa fa-download">descargar formato</button>
                    </a>
                </div>
            </div>
        </div>
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
                            <option value="I" {{ request('Status') == 'I' ? 'selected' : '' }}>
                                Pendiente (I)
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
                        <th>Costo Pago ($)</th>
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
        confirmButtonColor: '#198754', // Verde Bootstrap success
        cancelButtonColor: '#6c757d',  // Gris Bootstrap secondary
        confirmButtonText: 'Sí, exportar',
        cancelButtonText: 'No, cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            
            // 1. CAPTURA DINÁMICA EN TIEMPO REAL (Lee el valor actual de los inputs de tu formulario)
            // Asegúrate de que los inputs de tu filtro tengan estos mismos nombres de atributo 'name'
            let orden       = document.querySelector("input[name='orden']").value;
            let tecnicoId   = document.querySelector("select[name='tecnico_id']").value;
            let fechaInicio = document.querySelector("input[name='fecha_inicio']").value;
            let fechaFin    = document.querySelector("input[name='fecha_fin']").value;

            // 2. CONSTRUIR PARÁMETROS DE LA URL
            let queryParams = new URLSearchParams({
                orden: orden,
                tecnico_id: tecnicoId,
                fecha_inicio: fechaInicio,
                fecha_fin: fechaFin
            });

            // Base de la URL generada limpiamente por Blade
            let urlBase = "{{ route('pagotecnico.exportar') }}";

            // Mostrar una alerta rápida de éxito
            Swal.fire({
                title: 'Generando archivo...',
                text: 'Tu descarga iniciará en unos momentos.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // 3. ACTIVAR LA DESCARGA NATIVA DEL NAVEGADOR
            // Redirige al stream del controlador concatenando los filtros en tiempo real
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
@endpush
