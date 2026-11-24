@extends('layouts.app')

@section('title', 'MB51 - Importación SAP')

@push('css')
<style>
    .custom-file-input { display: none; }
    .custom-upload-btn { cursor: pointer; }
    .form-control {
        display: block; width: 100%; padding: 0.375rem 0.75rem;
        font-size: 0.9rem; line-height: 1.5; color: #212529;
        background-color: #fff; border: 1px solid #ced4da;
        border-radius: 0.25rem;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
</style>
@endpush

@section('content')
@include('layouts.partials.alert')

<div class="container-fluid px-4">

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">MB51 - Importación</li>
    </ol>

    @can('crear-mb51')
    <div class="card shadow-sm border-0 bg-light p-3 mb-4">
        <div class="card-body">

            {{-- SECCIÓN 1: Subir archivo + descargar formato --}}
            <div class="row align-items-center g-3 pb-3 mb-3 border-bottom">
                <div class="col-12 col-md-8">
                    <form id="formImportMB51"
                          action="{{ route('mb51.importar') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="d-flex align-items-center gap-2 flex-wrap">
                        @csrf

                        <button type="submit" id="btnSubmitMB51" class="btn btn-success px-4">
                            <i class="fa fa-upload me-1"></i> Subir
                        </button>

                        <label for="archivo" class="btn btn-primary mb-0 custom-upload-btn">
                            <i class="fa fa-file"></i> Seleccionar Archivo
                        </label>
                        <input type="file" id="archivo" name="archivo"
                               class="custom-file-input"
                               accept=".csv,.txt"
                               onchange="mostrarNombreMB51(this)">

                        <span id="nombre-archivo" class="text-muted small ms-2">
                            Ningún archivo seleccionado
                        </span>
                    </form>

                    @error('archivo')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-md-4 text-md-end">
                    <a href="{{ route('mb51.formato') }}"
                       class="btn btn-outline-secondary w-100">
                        <i class="fa fa-download me-1"></i> Descargar Formato SAP
                    </a>
                </div>
            </div>

            {{-- ======================================================= --}}
{{-- SECCIÓN DE CONCILIACIÓN Y JUSTIFICACIÓN (NUEVA)         --}}
{{-- ======================================================= --}}
<div class="card shadow-sm border-0 bg-light p-3 mt-4">
    <div class="card-body">
        <h6 class="fw-bold text-secondary mb-3">
            <i class="fa fa-file-excel me-1"></i> Conciliación de Pendientes de Justificar
        </h6>
        
        <div class="row align-items-center g-3">
            {{-- Botón para descargar el formato en CSV --}}
            <div class="col-12 col-md-6">
                <a href="{{ route('mb51.descargarPendientes') }}" class="btn btn-outline-primary w-100">
                    <i class="fa fa-download me-1"></i> Descargar Formato Pendientes (.csv)
                </a>
                <div class="form-text text-muted small mt-1">
                    Plantilla con las columnas: Material, Denominación, N° Serie, Centro, Cantidad.
                </div>
            </div>

            {{-- Botón para abrir el modal de subida --}}
            <div class="col-12 col-md-6">
                <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalJustificacion">
                    <i class="fa fa-search me-1"></i> Subir y Generar Reporte (3 Hojas)
                </button>
                <div class="form-text text-muted small mt-1">
                    Cruza los SKUs y Series contra ETA, Movimientos y MB51.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL PARA SUBIR EL ARCHIVO --}}
<div class="modal fade" id="modalJustificacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-upload me-1"></i> Reporte de Justificación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formJustificacion">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label fw-bold">Archivo CSV</label>
              <input type="file" name="archivo_csv" id="archivo_csv" class="form-control" accept=".csv,.txt" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-bold">Fecha inicio</label>
              <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required
                     value="{{ date('Y-m-d', strtotime('-30 days')) }}">
            </div>
            <div class="col-6">
              <label class="form-label fw-bold">Fecha fin</label>
              <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required
                     value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-12 d-none" id="contenedorProgresoJust">
              <label class="form-label small fw-bold text-primary" id="textoProgresoJust">Procesando...</label>
              <div class="progress" style="height: 20px;">
                <div id="barraProgresoJust" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%">0%</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" id="btnGenerarReporte" class="btn btn-success">
            <i class="fa fa-file-excel me-1"></i> Generar Reporte
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

            {{-- SECCIÓN 2: Ayuda --}}
            <div class="alert alert-info mb-0">
                <i class="fa fa-info-circle me-1"></i>
                <strong>Separador</strong> autodetectado (<code>,</code> <code>;</code> <code>Tab</code> <code>|</code>)
                y codificación (UTF-8 / ISO-8859-1 / con BOM).<br>
                <strong>Anti-duplicados:</strong>
                <em>Material + Referencia + Cantidad + Clase de movimiento + Texto de clase de movimiento + Centro</em>.
            </div>
        </div>
    </div>
    @endcan

    {{-- LISTADO DE IMPORTACIONES --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fa fa-table me-1"></i> Registros importados MB51</span>
            <div class="d-flex gap-2">
                <input type="text" id="mb51Search"
                       class="form-control form-control-sm"
                       placeholder="Buscar material, referencia, centro…"
                       style="width: 260px;">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="recargarMb51Tabla()">
                    <i class="fa fa-refresh"></i> Refrescar
                </button>
            </div>
        </div>
        <div class="card-body" id="mb51TablaContainer">
            {{-- Aquí se inyecta la tabla vía AJAX --}}
        </div>
    </div>
</div>
@endsection

@push('js')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function mostrarNombreMB51(input) {
    const nombre = input.files.length > 0
        ? input.files[0].name
        : 'Ningún archivo seleccionado';
    document.getElementById('nombre-archivo').textContent = nombre;
}

document.getElementById('formImportMB51')?.addEventListener('submit', function () {
    const btn = document.getElementById('btnSubmitMB51');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importando…';
});

/* ------- Carga y paginación AJAX del listado ------- */
let mb51Timer = null;

function cargarMb51Tabla(page = 1) {
    const search = $('#mb51Search').val() || '';
    $.ajax({
        url: "{{ route('mb51.tabla') }}",
        method: 'GET',
        data: { page, search },
        success: function (html) {
            $('#mb51TablaContainer').html(html);
        },
        error: function (xhr) {
            Swal.fire('Error', 'No se pudo cargar la tabla: ' + xhr.status, 'error');
        }
    });
}

function recargarMb51Tabla() {
    cargarMb51Tabla(1);
}

$(document).on('keyup', '#mb51Search', function () {
    clearTimeout(mb51Timer);
    mb51Timer = setTimeout(() => cargarMb51Tabla(1), 400);
});

$(document).on('click', '#mb51Pagination a', function (e) {
    e.preventDefault();
    const url = $(this).attr('href');
    const page = new URL(url, window.location.origin).searchParams.get('page') || 1;
    cargarMb51Tabla(page);
});

$(document).ready(function () {
    cargarMb51Tabla(1);
});

document.getElementById('formJustificacion').addEventListener('submit', async function (e) {
    e.preventDefault();

    const btn        = document.getElementById('btnGenerarReporte');
    const contProg   = document.getElementById('contenedorProgresoJust');
    const barra      = document.getElementById('barraProgresoJust');
    const texto      = document.getElementById('textoProgresoJust');

    btn.disabled   = true;
    btn.innerHTML  = '<i class="fa fa-spinner fa-spin me-1"></i> Enviando...';
    contProg.classList.remove('d-none');
    barra.style.width = '0%';
    barra.innerText   = '0%';
    texto.innerText   = 'Subiendo archivo al servidor...';

    try {
        const fd  = new FormData(this);
        const res = await fetch("{{ route('mb51.reporteJustificacion') }}", {
            method: 'POST',
            body:   fd,
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest' 
            }
        });
        
        const data = await res.json();

        if (!res.ok || !data.job_id) {
            // Si el servidor responde con un error, mostramos el mensaje real
            const errorText = data.message || data.error || JSON.stringify(data);
            throw new Error(errorText);
        }

        const jobId = data.job_id;
        let polling;

        const consultarProgreso = async () => {
            // Hacemos la petición a la ruta de progreso
            const r = await fetch("{{ url('/mb51/reporte/progreso') }}/" + jobId);
            const p = await r.json();

            // Si el total ya se calculó, actualizamos la barra
            if (p.total > 0) {
                const pct = Math.min(Math.round((p.procesadas / p.total) * 100), 100);
                barra.style.width = pct + '%';
                barra.innerText   = pct + '%';
            }
            // Mostramos el mensaje de estado que manda el Job
            texto.innerText = p.mensaje || 'Procesando en segundo plano...';

            if (p.estado === 'completado') {
                clearInterval(polling);
                texto.innerText = '¡Listo! Descargando archivo...';
                window.location.href = "{{ url('/mb51/reporte/descargar') }}/" + jobId;
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-file-excel me-1"></i> Generar Reporte';
                    contProg.classList.add('d-none');
                }, 3000);
            } else if (p.estado === 'error') {
                clearInterval(polling);
                Swal.fire('Error', p.mensaje || 'Falló el procesamiento en el servidor', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-file-excel me-1"></i> Generar Reporte';
            }
        };

        // Iniciar el ciclo de consultas
        polling = setInterval(consultarProgreso, 1500);
        consultarProgreso();

    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo generar el reporte: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-file-excel me-1"></i> Generar Reporte';
        contProg.classList.add('d-none');
    }
});
</script>


@endpush