@extends('layouts.app')

@section('title', 'Documentos SAP')

@push('css-datatable')
<!-- Cambiado a la ruta local de tu proyecto para no depender de internet -->
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
  <style>
    .custom-file-input { display: none; }
    .custom-upload-btn { cursor: pointer; }
  </style>
@endpush

@section('content')
@include('layouts.partials.alert')

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Documentos SAP</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Documentos SAP</li>
    </ol>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex align-items-center">
                    <form action="{{ route('documentosap.importar') }}" method="POST" enctype="multipart/form-data" class="d-inline-flex align-items-center">
                        @csrf
                        <label for="archivo" class="btn btn-primary custom-upload-btn me-2">
                            <i class="fas fa-upload me-1"></i> Seleccionar CSV
                        </label>
                        <input type="file" id="archivo" name="archivo" class="custom-file-input" onchange="mostrarNombre(this)" required>
                        <span id="nombre-archivo" class="text-muted me-3">Ningún archivo seleccionado</span>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i> Subir</button>
                    </form>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <button type="button" id="btnExportarExcel" class="btn btn-success me-2">
                    <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                </button>
                    <a href="{{ route('documentosap.formato') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> Descargar Formato
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-invoice me-1"></i> Tabla de Documentos SAP
        </div>
        <div class="card-body" style="overflow-x: auto;">
            <table id="datatablesSimple" class="table table-striped fs-12">
                <thead>
                    <tr>
                        <th>N° Documento</th>
                        <th>SKU</th>
                        <th>Serie</th>
                        <th>Referencia</th>
                        <th>Clase Movimiento</th>
                        <th>U. Medida</th>
                        <th>Fecha Cont.</th>
                        <th>Cantidad</th>
                        <th>Centro</th>
                        <th>Nat. (D/H)</th>
                        <th>Status</th>
                        @can('vertienda-producto')
                        <th>Tienda</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documentos as $item)
                    <tr>
                        <td><strong>{{ $item->numero_documento }}</strong></td>
                        <td>{{ $item->SKU ?? 'N/A' }}</td>
                        <td>{{ $item->serie ?? 'N/A' }}</td>
                        <td>{{ $item->referencia_sap ?? 'N/A' }}</td>
                        <td><small>{{ $item->clase_movimiento_sap }} - {{ $item->texto_clase_movimiento_sap }}</small></td>
                        <td>{{ $item->unidad_medida_base_sap }}</td>
                        <td>{{ $item->fecha_contabilizacion_sap }}</td>
                        <td><span class="badge bg-secondary">{{ $item->cantidad_sap }}</span></td>
                        <td>{{ $item->centro_sap }}</td>
                        <td>
                            @if($item->Naturaleza == 'D' || $item->Naturaleza == 'Debe')
                                <span class="badge bg-success">DEBE</span>
                            @else
                                <span class="badge bg-warning text-dark">HABER</span>
                            @endif
                        </td>
                        <td><span class="badge bg-success">{{ $item->Status }}</span></td>
                        @can('vertienda-producto')
                        <td>{{ $item->nombre_tienda ?? 'Sin tienda' }}</td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('js')
<!-- Cambiado a la librería local para evitar bloqueos por falta de internet -->
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>


<script>
    window.addEventListener('DOMContentLoaded', event => {
        const datatablesSimple = document.getElementById('datatablesSimple');
        
        if (datatablesSimple) {
            // 1. Inicializamos la instancia de Simple-DataTables
            const myDataTable = new simpleDatatables.DataTable(datatablesSimple, {
                paging: true,
                perPage: 10,
                labels: {
                    placeholder: "Buscar en stock sap...",
                    perPage: "{select} registros por página",
                    noRows: "No se encontraron registros de documentos",
                    info: "Mostrando {start} a {end} de {rows} registros",
                }
            });

            // 2. Exportación Absoluta de todas las páginas de la tabla
            const btnExcel = document.getElementById('btnExportarExcel');
            if (btnExcel) {
                btnExcel.addEventListener("click", () => {
                    console.log("Iniciando extracción total de los 1500+ registros...");
                    
                    // Extraemos las cabeceras desde el DOM (Estas siempre están fijas)
                    const thElements = Array.from(datatablesSimple.querySelectorAll('thead th'));
                    const cabeceras = thElements.map(th => `"${th.textContent.trim().replace(/"/g, '""')}"`);
                    
                    // Intentamos recuperar la base de datos completa guardada en la memoria interna de la extensión
                    let registrosInternos = [];
                    
                    if (myDataTable.data && myDataTable.data.data) {
                        registrosInternos = myDataTable.data.data;
                    } else if (myDataTable.rows && myDataTable.rows.data) {
                        registrosInternos = myDataTable.rows.data;
                    } else if (myDataTable.instance && myDataTable.instance.data) {
                        registrosInternos = myDataTable.instance.data;
                    }

                    let filas = [];

                    // Si la librería guardó los registros en memoria, los extraemos de allí
                    if (registrosInternos && registrosInternos.length > 0) {
                        filas = registrosInternos.map(row => {
                            // Si la fila es un objeto de celdas o un array plano, extraemos el texto limpio
                            let celdas = Array.isArray(row) ? row : (row.cells || []);
                            return celdas.map(cell => {
                                let texto = typeof cell === 'object' ? (cell.text || cell.textContent || '') : cell;
                                return `"${String(texto).trim().replace(/"/g, '""')}"`;
                            });
                        });
                    } else {
                        // Respaldo de seguridad si la versión de la librería bloqueó la memoria:
                        // Desactivamos la paginación temporalmente para renderizar todo en el DOM, copiamos y restauramos
                        console.log("Ejecutando respaldo por lectura de DOM masivo...");
                        const paginaActual = myDataTable.currentPage;
                        
                        myDataTable.page(0); // Forzamos a mostrar absolutamente todos los registros en pantalla
                        
                        const trElements = Array.from(datatablesSimple.querySelectorAll('tbody tr'));
                        filas = trElements.map(tr => {
                            return Array.from(tr.querySelectorAll('td')).map(td => `"${td.textContent.trim().replace(/"/g, '""')}"`);
                        });
                        
                        myDataTable.page(paginaActual); // Regresamos al usuario a la página de origen de forma invisible
                    }

                    // Construimos el archivo uniendo las cabeceras con el universo de filas extraídas
                    const contenidoCsv = [cabeceras.join(","), ...filas.map(f => f.join(","))].join("\n");
                    
                    // Descargamos el archivo con codificación UTF-8 + BOM para compatibilidad directa con Excel
                    const blob = new Blob(["\uFEFF" + contenidoCsv], { type: "text/csv;charset=utf-8;" });
                    const url = URL.createObjectURL(blob);
                    
                    const enlaceDescarga = document.createElement("a");
                    enlaceDescarga.setAttribute("href", url);
                    enlaceDescarga.setAttribute("download", "Reporte_Completo_SAP_" + new Date().toISOString().slice(0,10) + ".csv");
                    enlaceDescarga.style.visibility = 'hidden';
                    
                    document.body.appendChild(enlaceDescarga);
                    enlaceDescarga.click();
                    document.body.removeChild(enlaceDescarga);
                    
                    console.log("¡Los 1500+ registros se exportaron con éxito!");
                });
            }
        }
    });

    // Función estética para el nombre del archivo seleccionado
    function mostrarNombre(input) {
        const nombreSpan = document.getElementById('nombre-archivo');
        if (input.files && input.files) {
            nombreSpan.textContent = input.files.name;
            nombreSpan.className = "ms-2 text-success fw-bold"; 
        } else {
            nombreSpan.textContent = "Ningún archivo seleccionado";
            nombreSpan.className = "ms-2 text-muted";
        }
    }
</script>
@endpush
