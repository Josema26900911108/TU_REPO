@extends('layouts.app')

@section('title','Eta Warehouse')

@push('css-datatable')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
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
    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}
.gap-2 {
    gap: 0.5rem !important;
}


  </style>
  

@endpush

@section('content')

@include('layouts.partials.alert')

    <div class="container-fluid px-4">

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Eta</li>
        </ol>
    </div>


<ul class="nav nav-tabs" id="tecnicoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab">
            Eta Warehouse
        </button>
    </li>


</ul>

<div class="tab-content mt-3" id="tecnicoTabsContent">
    <div class="tab-pane fade show active" id="datos" role="tabpanel" aria-labelledby="datos-tab">
        <div class="card">
            <div class="card-header">


         @can('crear-eta')
<div class="card shadow-sm border-0 bg-light p-3">
    <div class="card-body">
        
        <!-- SECCIÓN 1: ACCIONES DE ARCHIVOS (SUBIR / DESCARGAR) -->
        <div class="row align-items-center g-3 pb-3 mb-3 border-bottom">
            <div class="col-12 col-md-8">
                <form action="{{ route('etadirect.importar') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap">
                    @csrf
                    <button type="submit" class="btn btn-success px-4">Subir</button>
                    
                    <label for="archivo" class="btn btn-primary mb-0 custom-upload-btn">
                        <i class="fa fa-upload"></i> Seleccionar Archivo
                    </label>
                    <input type="file" id="archivo" name="archivo" class="custom-file-input" onchange="mostrarNombre(this)">
                    
                    <span id="nombre-archivo" class="text-muted small ms-2">Ningún archivo seleccionado</span>
                </form>
            </div>
            
            <div class="col-12 col-md-4 text-md-end">
                <a href="{{route('etadirect.formeta')}}" class="btn btn-outline-secondary w-100 w-md-auto">
                    <i class="fa fa-download me-1"></i> Descargar Formato
                </a>
            </div>
        </div>

        <!-- SECCIÓN 2: FORMULARIOS DE PROCESAMIENTO -->
        <div class="row g-4">
            
<!-- Formulario A: Trabajar Lotes -->
<div class="col-12 col-lg-8 border-end">
    <h6 class="fw-bold text-secondary mb-3"><i class="fa fa-tasks me-1"></i> Procesar por Lotes (Descargas en Tiempo Real cada 2 Órdenes)</h6>
    <form id="formLotesRealTime" action="{{ route('etadirect.AutomataValidarMamo') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">
            <div class="col-12 col-sm-6">
                <label for="fechaincio" class="form-label small fw-bold text-muted mb-1">Fecha Inicio:</label>
                <input type="date" name="fechaincio" id="fechaincio" class="form-control" required value="{{ date('Y-m-d',strtotime('-7 day')) }}">
            </div>
            <div class="col-12 col-sm-6">
                <label for="fechafin" class="form-label small fw-bold text-muted mb-1">Fecha Fin:</label>
                <input type="date" name="fechafin" id="fechafin" class="form-control" required value="{{ date('Y-m-d',strtotime('+1 day')) }}">
            </div>
            
            <!-- 🚫 CAMPO REINTEGRADO: CARGAR ÓRDENES A OMITIR (CSV) -->
            <div class="col-12">
                <label for="csv_excluidas" class="form-label small fw-bold text-danger mb-1">
                    <i class="fa fa-file-csv me-1"></i> Excluir Órdenes (Subir archivo .csv de órdenes trabajadas):
                </label>
                <input type="file" name="csv_excluidas" id="csv_excluidas" class="form-control" accept=".csv">
                <div class="form-text text-muted small">Opcional. Las órdenes dentro de este archivo serán saltadas automáticamente por el autómata.</div>
            </div>
            
            <!-- Barra de progreso visual -->
            <div class="col-12 d-none" id="contenedorProgreso">
                <label class="form-label small fw-bold text-primary" id="textoProgreso">Procesando...</label>
                <div class="progress" style="height: 20px;">
                    <div id="barraProgreso" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;">0%</div>
                </div>
            </div>

            <div class="col-12 text-end mt-2">
                <!-- Ejemplo de tus botones en el formulario -->
                <button type="submit" id="btnSubmit" class="btn btn-primary">Iniciar Validación</button>
                <button type="button" id="btnCancelar" class="btn btn-danger d-none">Detener Validación</button>

            </div>
        </div>
    </form>
</div>



            <!-- Formulario B: Reporte de Discrepancias de Materiales -->
<div class="col-12 col-lg-4">
    <h6 class="fw-bold text-secondary mb-3"><i class="fa fa-file-excel me-1"></i> Auditoría de Materiales</h6>
    <form action="{{ route('etadirect.reporteDiscrepancias') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-12 col-sm-6">
                <label for="rep_fechainicio" class="form-label small fw-bold text-muted mb-1">Fecha Inicio:</label>
                <input type="date" name="fechaincio" id="rep_fechainicio" class="form-control" required value="{{ date('Y-m-d', strtotime('-7 day')) }}">
            </div>
            <div class="col-12 col-sm-6">
                <label for="rep_fechafin" class="form-label small fw-bold text-muted mb-1">Fecha Fin:</label>
                <input type="date" name="fechafin" id="rep_fechafin" class="form-control" required value="{{ date('Y-m-d', strtotime('+1 day')) }}">
            </div>
            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-download me-1"></i> Generar Reporte
                </button>
            </div>
        </div>
    </form>
</div>


<!-- Formulario B: Revisar Orden Individual -->
<div class="col-12 col-lg-4 d-flex flex-column justify-content-between">
    <div>
        <h6 class="fw-bold text-secondary mb-3"><i class="fa fa-search me-1"></i> Validación Express</h6>
        <!-- Interceptamos el submit para controlarlo con JavaScript de forma segura -->
        <form id="formOrdenIndividual" onsubmit="event.preventDefault(); descargarOrdenIndividual();">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted mb-1">Orden de Trabajo:</label>
                <!-- Conservamos el ID original que usa tu vista -->
                <input type="text" id="OrdenIndividual" name="Orden" class="form-control" placeholder="Número de orden" required>
            </div>
            <!-- Añadimos el ID btnIndividual para manejar los estados de carga -->
            <button type="button" id="btnIndividual" onclick="descargarOrdenIndividual()" class="btn btn-success w-100">Revisar orden</button>
        </form>
    </div>
</div>

<!-- Formulario B: Revisar Orden Individual -->
            <div class="col-12 col-lg-4 d-flex flex-column justify-content-between">
                <div>
                    <h6 class="fw-bold text-secondary mb-3"><i class="fa fa-search me-1"></i> Validación Express Json</h6>
                    <form action="{{ route('etadirect.AutomataValidarMamoOrden') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted mb-1">Orden de Trabajo:</label>
                            <input type="text" id="OrdenIndividual" name="Orden" class="form-control" placeholder="Número de orden">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Revisar orden</button>
                    </form>
                </div>
            </div>


        </div>

    </div>
</div>



        <div id="tabla_materiales_container">
            </div>
        </div>
    </div>
</div>

        @endcan
    

@endsection

@push('js')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>


<!-- DataTables JS (debe ir DESPUÉS de jQuery) -->
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
let dataTableInstance = null;
let currentSearchValue = '';


function initDataTable(tablasss, search) {
    console.log('initDataTable llamado de forma local controlada');
    
    if ($(tablasss).length && !$.fn.DataTable.isDataTable(tablasss)) {
        if (dataTableInstance) {
            try { dataTableInstance.destroy(); dataTableInstance = null; } catch(e) {}
        }

        // Inicializamos DataTables de forma normal pero sin paginar lo que ya viene paginado
dataTableInstance = $(tablasss).DataTable({
    paging: false,
    info: false,
    ordering: true,
    searching: true,
    responsive: false,
    dom: 'Bfrtip',
    buttons: [
        {
            text: '<i class="fas fa-file-excel"></i> Exportar todo a Excel',
            className: 'btn btn-success btn-sm custom-excel-btn',
            action: function (e, dt, node, config) {
                // Capturamos todos los filtros activos en pantalla
                var select = document.getElementById("tecnicoid");
                var id = select !== null ? select.options[select.selectedIndex].value : "{{ $tecnico->id ?? '' }}";
                var fechain = $('#fechaincio').val();
                var fechafin = $('#fechafin').val();
                var search = $('#globalSearch').val() || '';

                // Construimos la URL de descarga enviando los parámetros por GET
                var exportUrl = "{{ route('exportar.eta.excel') }}?" + $.param({
                    id: id,
                    fechain: fechain,
                    fechafin: fechafin,
                    search: search
                });

                // Abrimos la descarga en una pestaña nueva o ventana de descarga
                window.location.href = exportUrl;
            }
        },
        'pdfHtml5', 'print', 'copy' // Mantenemos los demás si los requieres
    ],
    // ... tu configuración de lenguaje sigue igual
});

        // Conectar tu input globalSearch si existe
        if (search) {
            $(search).off('keyup').on('keyup', function() {
                dataTableInstance.search($(this).val()).draw();
            });
        }
    }
}

// Tu función AJAX recuperada se encarga de inyectar el HTML limpio
function fillRelacionAsignada(page = 1) {
    console.log('fillRelacionAsignada buscando de forma global...');
    var select = document.getElementById("tecnicoid");
    var fechain = $('#fechaincio').val();
    var fechafin = $('#fechafin').val();
    
    // NUEVO: Capturar el texto que el usuario escribió en el buscador
    var search = $('#globalSearch').val() || ''; 

    let id = select !== null ? select.options[select.selectedIndex].value : "{{ $tecnico->id ?? '' }}";

    $.ajax({
        url: "{{ route('fetchrelacionEta') }}",
        method: 'GET',
        // NUEVO: Agregamos el parámetro 'search' a la petición AJAX
        data: { id : id, fechain : fechain, fechafin : fechafin, page: page, search: search },
        success: function(data) {
            $('#tabla_materiales_container').html(data);
            
            setTimeout(function() {
                initDataTable('#datatablesSimple', '#globalSearch');
                
                // NUEVO: Mantener el foco y el texto en el buscador después de recargar el HTML
                if(search !== '') {
                    $('#globalSearch').val(search).focus();
                }
            }, 300);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al filtrar: ' + xhr.responseText, 'error');
        }
    });
}


// Mantener alias globales
window.fillRelacion = fillRelacionAsignada;
window.fillRelacionAsig = fillRelacionAsignada;
window.fillRelacionAsignada = fillRelacionAsignada;

// Capturar los clics de la nueva paginación de Laravel para recargar mediante AJAX
$(document).on('click', '#laravel-pagination a', function (e) {
    e.preventDefault();
    // Extrae de forma automática el número de página del enlace (ej: page=2)
    let url = $(this).attr('href');
    let page = new URL(url, window.location.origin).searchParams.get('page') || 1;
    fillRelacionAsignada(page);
});


function fillRelacion(page = 1) {
    console.log('fillRelacionAsignada refrescando DataTable...');
    
    if (dataTableInstance) {
        // En modo Server-Side no reescribimos el HTML, solo refrescamos los datos
        dataTableInstance.ajax.reload(); 
    } else {
        // Si no se ha creado la tabla, la inicializamos por primera vez
        initDataTable('#datatablesSimple', '#globalSearch');
    }
}



function connectSearchInput(search) {
    if ($().length && dataTableInstance) {
        console.log('Conectando globalSearch con DataTable');

        // Limpiar eventos previos
        $(search).off('keyup.'+search);

        // Conectar evento keyup - VERSIÓN MEJORADA
        $(search).on('keyup.'+search, function() {
            const searchValue = $(this).val().trim();
            console.log('Búsqueda realizada:', searchValue);

            // VERIFICACIÓN: Ver el estado actual
            console.log('Filas totales antes:', dataTableInstance.rows().count());
            console.log('Filas visibles antes:', dataTableInstance.rows({ search: 'applied' }).count());

            // Aplicar búsqueda en DataTable - FORMA CORRECTA
            dataTableInstance
                .search(searchValue)
                .draw();

            // VERIFICACIÓN: Ver el estado después
            setTimeout(() => {
                console.log('Filas totales después:', dataTableInstance.rows().count());
                console.log('Filas visibles después:', dataTableInstance.rows({ search: 'applied' }).count());
                console.log('Info de página:', dataTableInstance.page.info());

                // Si no hay resultados, forzar mensaje
                if (dataTableInstance.rows({ search: 'applied' }).count() === 0 && searchValue !== '') {
                    console.log('NO HAY RESULTADOS para:', searchValue);
                    // Forzar mostrar mensaje de "no results"
                    $('.dataTables_empty').show();
                }
            }, 100);
        });

        // Si hay valor guardado, aplicarlo
        if (currentSearchValue) {
            $(search).val(currentSearchValue);
            dataTableInstance.search(currentSearchValue).draw();
            console.log('Valor de búsqueda restaurado:', currentSearchValue);
        }
    } else {
        console.log('No se puede conectar: globalSearch:', $(search).length, 'dataTableInstance:', !!dataTableInstance);
    }
}

// También hacer fillRelacion global para que sea accesible
window.fillRelacionInv = function(page = 1) {
    console.log('fillRelacion llamado con página:', page);
    var select = document.getElementById("tecnicoid");
    var fechain = $('#fechaincio').val();
    var fechafin = $('#fechafin').val();

    let id = null;
    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        id = "{{ $tecnico->id ?? '' }}";
    }

    // Cargar inventario
    $.ajax({
        url: "{{ route('fetchinvtabla') }}",
        method: 'GET',
        data: { id: id, page : page },
        success: function(data) {
            $('#tabla_materialesinv_container').html(data);
            console.log('Tabla inventario cargada, inicializando DataTable...');

            // Esperar a que se renderice el DOM
            setTimeout(function() {
                initDataTable('#datatablesSimpleInv', '#globalSearch');
            }, 300);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });
};


// También hacer fillRelacion global para que sea accesible
window.fillRelacionAsig = function(page = 1) {
    console.log('fillRelacion llamado con página:', page);
    var select = document.getElementById("tecnicoid");
    var fechain = $('#fechaincio').val();
    var fechafin = $('#fechafin').val();

    let id = null;
    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        id = "{{ $tecnico->id ?? '' }}";
    }

    $.ajax({
        url: "{{ route('fetchrelacionEta') }}",
        method: 'GET',
        data: { id : id, fechain : fechain, fechafin : fechafin, page: page },
        success: function(data) {
            $('#tabla_materiales_container').html(data);
            setTimeout(function() {
                initDataTable('#datatablesSimple', '#globalSearch');
            }, 300);
            console.log('Tabla principal cargada');
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });
};
// ==========================================
// FUNCIÓN PRINCIPAL DE ETA RECUPERADA Y PROTEGIDA
// ==========================================




// Función para exportar datos filtrados a CSV
function exportFilteredToCSV() {
    if (!dataTableInstance) {
        alert('La tabla no está inicializada');
        return;
    }

    // Obtener datos filtrados
    const filteredData = dataTableInstance.rows({ search: 'applied' }).data();
    const columns = dataTableInstance.columns().header().toArray();

    // Crear cabeceras CSV
    let csvContent = "data:text/csv;charset=utf-8,";
    const headers = columns.map(col => `"${$(col).text().trim()}"`).join(",");
    csvContent += headers + "\r\n";

    // Agregar datos
    filteredData.each(function(value, index) {
        const row = value;
        const rowData = [];

        // Para cada columna visible
        dataTableInstance.columns().every(function() {
            if (this.visible()) {
                const cellData = dataTableInstance.cell(index, this.index()).data();
                // Escapar comillas para CSV
                const escapedData = typeof cellData === 'string'
                    ? `"${cellData.replace(/"/g, '""')}"`
                    : cellData;
                rowData.push(escapedData);
            }
        });

        csvContent += rowData.join(",") + "\r\n";
    });

    // Descargar archivo
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `inventario_filtrado_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Función para exportar a Excel (usando SheetJS)
function exportFilteredToExcel() {
    if (!dataTableInstance) return;

    // Obtener datos filtrados
    const filteredData = dataTableInstance.rows({ search: 'applied' }).data();
    const columns = dataTableInstance.columns().visible().header().toArray();

    // Crear array de datos
    const data = [];

    // Cabeceras
    const headers = columns.map(col => $(col).text().trim());
    data.push(headers);

    // Datos
    filteredData.each(function(value, index) {
        const row = [];
        dataTableInstance.columns().every(function() {
            if (this.visible()) {
                const cellData = dataTableInstance.cell(index, this.index()).data();
                row.push(cellData);
            }
        });
        data.push(row);
    });

    // Crear libro de Excel
    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Inventario");

    // Descargar
    XLSX.writeFile(wb, `inventario_filtrado_${new Date().toISOString().slice(0,10)}.xlsx`);
}


$(document).ready(function(){
    console.log('Documento listo');

      $('#exportFilteredBtn').click(function() {
        if (!dataTableInstance) {
            Swal.fire('Error', 'La tabla no está inicializada', 'error');
            return;
        }

        const filteredCount = dataTableInstance.rows({ search: 'applied' }).count();
        const totalCount = dataTableInstance.rows().count();

        if (filteredCount === 0) {
            Swal.fire('Info', 'No hay datos filtrados para exportar', 'info');
            return;
        }

        Swal.fire({
            title: 'Exportar datos filtrados',
            text: `¿Exportar ${filteredCount} de ${totalCount} registros?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Exportar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                exportFilteredData('all');
            }
        });
    });

    // Exportar solo la página actual
    $('#exportCurrentPageBtn').click(function() {
        if (!dataTableInstance) return;
        exportFilteredData('current');
    });

    function exportFilteredData(type) {
        // Obtener datos según el tipo
        let dataRows;
        let filename;

        if (type === 'all') {
            dataRows = dataTableInstance.rows({ search: 'applied' });
            filename = `inventario_filtrado_completo_${new Date().toISOString().slice(0,10)}`;
        } else {
            dataRows = dataTableInstance.rows({ page: 'current', search: 'applied' });
            const pageInfo = dataTableInstance.page.info();
            filename = `inventario_pagina_${pageInfo.page + 1}_${new Date().toISOString().slice(0,10)}`;
        }

        // Crear CSV
        const csvData = [];
        const headers = [];

        // Obtener cabeceras visibles
        dataTableInstance.columns().every(function() {
            if (this.visible()) {
                const header = $(this.header()).text().trim();
                headers.push(header);
            }
        });

        csvData.push(headers);

        // Obtener datos
        dataRows.every(function() {
            const row = this;
            const rowData = [];

            dataTableInstance.columns().every(function() {
                if (this.visible()) {
                    const cellData = dataTableInstance.cell(row.index(), this.index()).data();
                    rowData.push(formatCellData(cellData));
                }
            });

            csvData.push(rowData);
        });

        // Convertir a CSV
        const csvContent = csvData.map(row =>
            row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')
        ).join('\n');

        // Descargar
        downloadCSV(csvContent, filename + '.csv');
    }

    function formatCellData(data) {
        if (data === null || data === undefined) return '';
        if (typeof data === 'object') {
            // Si es un objeto, extraer texto
            const $data = $(data);
            const text = $data.text().trim();
            return text || String(data);
        }
        return String(data).trim();
    }

    function downloadCSV(content, filename) {
        const blob = new Blob(["\uFEFF" + content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');

        if (navigator.msSaveBlob) {
            // Para IE
            navigator.msSaveBlob(blob, filename);
        } else {
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        Swal.fire('Éxito', 'Archivo exportado correctamente', 'success');
    }

    $(document).on('click', '[data-bs-toggle="dropdown"]', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var dropdownElement = this;
        var dropdown = bootstrap.Dropdown.getInstance(dropdownElement);

        if (!dropdown) {
            dropdown = new bootstrap.Dropdown(dropdownElement);
        }

        dropdown.toggle();
    });

let searchTimer;


    // Event delegation para el input de búsqueda (por si se carga dinámicamente)
$(document).on('keyup', '#globalSearch', function() {
    clearTimeout(searchTimer);
    // Espera 500 milisegundos después de que el usuario deja de escribir para lanzar la búsqueda
    searchTimer = setTimeout(function() {
        fillRelacionAsignada(1); 
    }, 500);
});

        // Event delegation para el input de búsqueda (por si se carga dinámicamente)
    $(document).on('keyup', '#globalSearchExp', function() {
        console.log('Evento keyup en globalSearchExp');
        currentSearchValue = $(this).val();
        if (dataTableInstance && $.fn.DataTable.isDataTable('#datatablesSimpleExp')) {
            dataTableInstance.search(currentSearchValue).draw();
        }
    });

            // Event delegation para el input de búsqueda (por si se carga dinámicamente)
    $(document).on('keyup', '#globalSearchAsig', function() {
        console.log('Evento keyup en globalSearchAsig');
        currentSearchValue = $(this).val();
        if (dataTableInstance && $.fn.DataTable.isDataTable('#datatablesSimpleAsig')) {
            dataTableInstance.search(currentSearchValue).draw();
        }
    });

       // Mapeo de pestañas a funciones
    const tabActions = {
        'datos': function() {
            console.log('🔄 Ejecutando fillRelacion para Ordenes');
            fillRelacionAsig(1);
            // Configurar eventos de fecha
            setupFechaEvents();
        },
        'inventario': function() {
            console.log('📦 Ejecutando fillRelacionInv para Inventario');
            fillRelacionInv(1);
        },
        'expediente': function() {
            console.log('📁 Ejecutando fillRelacionS para Expediente');
            fillRelacionS();
        },
        'pago': function() {
            console.log('💰 Ejecutando fillRelacionP para Pago');
            fillRelacionP(1);
        },
        'cobro': function() {
            console.log('💵 Ejecutando para Cobro');
            // Si tienes función específica
            if (typeof fillRelacionCobro === 'function') {
                fillRelacionCobro();
            }
        }
    };

    // Detectar cambio de pestañas
    $('#tecnicoTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const tabId = $(this).attr('id').replace('-tab', '');
        console.log(`📌 Pestaña seleccionada: ${tabId}`);

        // Ejecutar función correspondiente
        if (tabActions[tabId]) {
            tabActions[tabId]();
        }
    });

    // Ejecutar para pestaña activa inicial
    const activeTabId = $('#tecnicoTabs .nav-link.active').attr('id').replace('-tab', '');
    if (activeTabId && tabActions[activeTabId]) {
        console.log(`🎯 Pestaña activa inicial: ${activeTabId}`);
        // Esperar un poco para que todo cargue
        setTimeout(() => tabActions[activeTabId](), 400);
    }

    // Observer para detectar cuando se carga el input #globalSearch
    const searchObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        if (node.matches && node.matches('#globalSearch')) {
                            console.log('globalSearch detectado por MutationObserver');
                            setupSearchInput(node);
                        }
                        if (node.querySelectorAll) {
                            node.querySelectorAll('#globalSearch').forEach(function(input) {
                                console.log('globalSearch encontrado en hijos');
                                setupSearchInput(input);
                            });
                        }
                    }
                });
            }
        });
    });

    // Observar el contenedor de inventario
    const inventarioContainer = document.getElementById('tabla_materialesinv_container');
    if (inventarioContainer) {
        searchObserver.observe(inventarioContainer, {
            childList: true,
            subtree: true
        });
        console.log('Observer activado para inventario');
    }

    // Observar el contenedor de datos
    const datosContainer = document.getElementById('tabla_materiales_container');
    if (datosContainer) {
        searchObserver.observe(datosContainer, {
            childList: true,
            subtree: true
        });
        console.log('Observer activado para datos');
    }

    // Si la pestaña de inventario ya está activa al cargar
    if ($('#inventario').hasClass('show active')) {
        console.log('Pestaña inventario activa al cargar');
        setTimeout(function() {
            if ($('#datatablesSimpleInv').length) {
                initDataTable('#datatablesSimpleInv', '#globalSearch');
            } else {
                // Cargar datos si no hay tabla
                fillRelacion(1);
            }
        }, 500);
    }

    // Definición de la función que faltaba
function setupSearchInput(inputElement) {
    console.log('Configurando buscador global...');
    
    // Escucha cada vez que el usuario escribe en el buscador
    inputElement.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        
        // 1. Si usas DataTables nativo (Simple-DataTables / Vanilla DataTables)
        if (window.simpleDatatables || $.fn.DataTable) {
            // El MutationObserver ya maneja la inicialización, DataTables suele vincularse solo
            return; 
        }

        // 2. Solución universal por software: Filtrado manual de filas por si falla lo anterior
        const activeTable = document.querySelector('.tab-pane.show.active table');
        if (activeTable) {
            const rows = activeTable.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }
    });
}


    // Event handlers para fechas
    $('#fechaincio').change(function(){
        var fechain = $(this).val();
        var fechafin = $('#fechafin').val();

        if (!fechain) {
            Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
            return;
        }
        if(fechain > fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal mayor o igual a fecha inicial', 'error');
            return;
        }

        fillRelacionAsignada(1);
    });

    $('#fechafin').change(function(){
        var fechain = $('#fechaincio').val();
        var fechafin = $(this).val();

        if (!fechain) {
            Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
            return;
        }
        if(fechain > fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal mayor o igual a fecha inicial', 'error');
            return;
        }
        fillRelacionAsignada(1);
    });

    $('#fechaincioS').change(function(){
        var fechain = $(this).val();
        var fechafin = $('#fechafinS').val();

        if (!fechain) {
            Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
            return;
        }
        if(fechain > fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal mayor o igual a fecha inicial', 'error');
            return;
        }

        fillRelacionS();
    });

    $('#fechafinS').change(function(){
        var fechain = $('#fechaincioS').val();
        var fechafin = $(this).val();

        if (!fechain) {
            Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
            return;
        }
        if(fechain > fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal mayor o igual a fecha inicial', 'error');
            return;
        }
        fillRelacionS();
    });

    $('#fechaincioP').change(function(){
        var fechain = $(this).val();
        var fechafin = $('#fechafinP').val();

        if (!fechain) {
            Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
            return;
        }
        if(fechain > fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal mayor o igual a fecha inicial', 'error');
            return;
        }

        fillRelacionP(1);
    });

    $('#fechafinP').change(function(){
        var fechain = $('#fechaincioP').val();
        var fechafin = $(this).val();

        if (!fechain) {
            Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
            return;
        }
        if(fechain > fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal mayor o igual a fecha inicial', 'error');
            return;
        }
        fillRelacionP(1);
    });

    $('#tecnicoid').change(function(){
        var select = document.getElementById("tecnicoid");
        let id = null;

        if (select !== null) {
            id = select.options[select.selectedIndex].value;
        } else {
            id = "{{ $tecnico->id ?? '' }}";
        }

        console.log('Técnico cambiado a:', id);
        fillRelacion(1);
        fillRelacionS();
        fillRelacionP(1);
    });

    // Cargar datos iniciales
    console.log('Cargando datos iniciales...');
    fillRelacionS();
    fillRelacionP(1);
    fillRelacion(1);
});



$(document).on('click', '#tabla_materiales_container a[href*="page="]', function (e) {
    e.preventDefault();
    let url = $(this).attr('href');
    let page = new URL(url, window.location.origin).searchParams.get('page') || 1;
    console.log('Paginación datos, página:', page);
    fillRelacion(page);
});

$(document).on('click', '#tabla_materialesinv_container a[href*="page="]', function (e) {
    e.preventDefault();
    let url = $(this).attr('href');
    let page = new URL(url, window.location.origin).searchParams.get('page') || 1;
    console.log('Paginación datos, página:', page);
    fillRelacion(page);
});


$(document).on('click', '#tabla_pago_container a[href*="page="]', function (e) {
    e.preventDefault();
    let url = $(this).attr('href');
    let page = new URL(url, window.location.origin).searchParams.get('page') || 1;
    console.log('Paginación pago, página:', page);
    fillRelacionP(page);
});

// Funciones auxiliares
function mostrarNombre(input) {
    const nombre = input.files.length > 0 ? input.files[0].name : "Ningún archivo seleccionado";
    document.getElementById('nombre-archivo').textContent = nombre;
}

function mostrarNombreINVENTARIO(input) {
    const nombre = input.files.length > 0 ? input.files[0].name : "Ningún archivo seleccionado";
    document.getElementById('nombre-archivoinv').textContent = nombre;
}



function fillRelacionP(page) {
    var select = document.getElementById("tecnicoid");
    var fechainP = $('#fechaincioP').val();
    var fechafinP = $('#fechafinP').val();

    let id = null;
    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        id = "{{ $tecnico->id ?? '' }}";
    }

    $.ajax({
        url: "{{ route('fetchtablaP') }}",
        method: 'GET',
        data: { id: id, fechainP: fechainP, fechafinP: fechafinP, page: page },
        success: function(data) {
            $('#tabla_pago_container').html(data);
            setTimeout(function() {
                initDataTable('#datatablesSimplePago', '#globalSearchPago');
            }, 300);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });
}

function fillRelacionS() {
    var select = document.getElementById("tecnicoid");
    var fechainS = $('#fechaincioS').val();
    var fechafinS = $('#fechafinS').val();

    let id = null;
    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        id = "{{ $tecnico->id ?? '' }}";
    }

    $.ajax({
        url: "{{ route('fetchtablaS') }}",
        method: 'GET',
        data: { id: id, fechainS: fechainS, fechafinS: fechafinS },
        success: function(data) {
            $('#tabla_expediente_container').html(data);
                        setTimeout(function() {
                initDataTable('#datatablesSimpleExp', '#globalSearchExp');
            }, 300);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });
}



// REMOVER LA FUNCIÓN DUPLICADA fillRelacionS que está al final
</script>
<script>
async function descargarPorLotes() {
    const btn = document.getElementById('btnProcesar');
    
    // Deshabilitamos el botón e indicamos estado de carga
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Procesando...';

    let lote = 1;
    let procesando = true;
    
    const orden = document.getElementById('Orden').value || 10;
    const fechaincio = document.getElementById('fechaincio').value;
    const fechafin = document.getElementById('fechafin').value;

    const urlBase = "{{ route('etadirect.AutomataValidarMamo') }}";

    while (procesando) {
        console.log(`Solicitando lote número: ${lote}`);
        const url = `${urlBase}?fechaincio=${fechaincio}&fechafin=${fechafin}&Orden=${orden}&lote=${lote}`;
        
        try {
            // 🚀 ÚNICA PETICIÓN AL SERVIDOR: Obtenemos la respuesta directa
            const respuesta = await fetch(url, { method: 'GET' });
            
            if (!respuesta.ok) {
                throw new Error(`Error en el servidor: Código HTTP ${respuesta.status}`);
            }

            const tipoContenido = respuesta.headers.get('content-type') || '';

            // Caso A: Si el servidor nos responde con JSON, significa que terminamos
            if (tipoContenido.includes('application/json')) {
                const data = await respuesta.json();
                if (data.status === 'completado' || data.mensaje) {
                    alert("¡Proceso finalizado! Se han descargado todos los lotes disponibles.");
                    procesando = false;
                    break;
                }
            }

            // Caso B: Si es el archivo CSV, lo descargamos directamente desde la memoria
            // Esto evita llamar a la URL una segunda vez y protege tu XAMPP local
            const datosArchivo = await respuesta.blob();
            const urlDescargaDescarga = window.URL.createObjectURL(datosArchivo);
            
            const nombreArchivo = `validaciones_lote_${lote}_ordenes.csv`;
            const link = document.createElement('a');
            link.href = urlDescargaDescarga;
            link.setAttribute('download', nombreArchivo);
            document.body.appendChild(link);
            link.click();
            
            // Limpieza de residuos en el navegador
            document.body.removeChild(link);
            window.URL.revokeObjectURL(urlDescargaDescarga);

            // Pausa estratégica de 3 segundos para que el servidor respire antes del siguiente lote
            await new Promise(resolve => setTimeout(resolve, 3000));
            lote++;

        } catch (error) {
            console.error("Error crítico al procesar el lote:", error);
            alert("El lote actual tardó demasiado o el servidor local se desconectó. Revisa la consola (F12) o los logs de Laravel.");
            procesando = false;
        }
    }

    // Restauramos el botón a su estado original al finalizar todo el ciclo
    btn.disabled = false;
    btn.innerHTML = 'Trabajar Lotes';
}

async function descargarOrdenIndividual() {
    const btn = document.getElementById('btnIndividual');
    if (!btn) return;

    // 1. Estado visual de carga inmediato
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Procesando Orden...';

    // 2. Extraemos el valor del input del formulario
    const ordenInput = document.getElementById('OrdenIndividual');
    const orden = ordenInput ? ordenInput.value : '';
    
    if (!orden || !orden.trim()) {
        alert("Por favor, ingresa un número de orden válido.");
        btn.disabled = false;
        btn.innerHTML = 'Revisar orden';
        return;
    }

    const urlBase = "{{ route('etadirect.AutomataValidarMamoOrdenExcel') }}";
    const url = `${urlBase}?Orden=${encodeURIComponent(orden.trim())}`;
    
    try {
        console.log("Enviando petición a la URL:", url);
        
        // 🚀 PETICIÓN SEGURA: Añadimos cabeceras para que Laravel no pida token CSRF en solicitudes GET
        const respuesta = await fetch(url, { 
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json, text/csv'
            }
        });
        
        if (!respuesta.ok) {
            throw new Error(`Error en el servidor local: Código HTTP ${respuesta.status}`);
        }

        const tipoContenido = respuesta.headers.get('content-type') || '';

        // Caso A: Si el servidor falló por credenciales u otra excepción y devuelve JSON
        if (tipoContenido.includes('application/json')) {
            const data = await respuesta.json();
            if (data.error || data.mensaje) {
                alert(`Atención: ${data.error || data.mensaje}`);
                btn.disabled = false;
                btn.innerHTML = 'Revisar orden';
                return;
            }
        }

        // Caso B: El autómata recursivo entró con éxito y procesó la orden única
        const datosArchivo = await respuesta.blob();
        const urlDescarga = window.URL.createObjectURL(datosArchivo);
        
        // Forzamos la descarga instantánea en el navegador desde el Blob
        const link = document.createElement('a');
        link.href = urlDescarga;
        link.setAttribute('download', `validaciones_orden_${orden.trim()}.csv`);
        document.body.appendChild(link);
        link.click();
        
        // Purgamos hilos de memoria
        document.body.removeChild(link);
        window.URL.revokeObjectURL(urlDescarga);
        console.log(`¡Descarga completada con éxito para la orden ${orden}!`);

    } catch (error) {
        console.error("Error crítico de comunicación:", error);
        alert("No se pudo conectar con el controlador. Verifica en tu terminal de comandos si el proceso de 'php artisan serve' sigue encendido.");
    }

    // Restauramos el botón a su estado original
    btn.disabled = false;
    btn.innerHTML = 'Revisar orden';
}

</script>
<script>
// Variable de control global para manejar la detención
let detenerProceso = false;

// Evento para el botón de cancelar
document.getElementById('btnCancelar').addEventListener('click', function() {
    detenerProceso = true;
    this.disabled = true; // Evitar múltiples clics al cancelar
    document.getElementById('textoProgreso').innerText = "Cancelando proceso... Esperando a que termine el bloque actual.";
});
document.getElementById('formLotesRealTime').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    detenerProceso = false; // Inicializar estado de detención

    const btn = document.getElementById('btnSubmit');
    const btnCancelar = document.getElementById('btnCancelar'); 
    const contenedorProgreso = document.getElementById('contenedorProgreso');
    const barraProgreso = document.getElementById('barraProgreso');
    const textoProgreso = document.getElementById('textoProgreso');
    
    btn.disabled = true;
    if (btnCancelar) btnCancelar.classList.remove('d-none'); 
    contenedorProgreso.classList.remove('d-none');
    
    let offset = 0;
    const tamanoLote = 2;
    let totalOrdenes = 0;
    
    let todasLasValidaciones = [];
    let todasLasOrdenesProcesadas = [];
    let ordenesAnalizadasTotales = []; // Acumulador del total grande

    try {
        // 1. OBTENER EL TOTAL
        const baseFormData = new FormData(this);
        baseFormData.append('obtener_total', '1');
        
        const resInicio = await fetch(this.action, { 
            method: 'POST', 
            body: baseFormData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        
        if (!resInicio.ok) throw new Error('Error al inicializar el autómata.');
        const dataInicio = await resInicio.json();
        totalOrdenes = dataInicio.total_ordenes;

        if (totalOrdenes === 0) {
            alert('No se encontraron órdenes para procesar (o todas fueron excluidas).');
            btn.disabled = false;
            if (btnCancelar) btnCancelar.classList.add('d-none');
            contenedorProgreso.classList.add('d-none');
            return;
        }

        // 2. BUCLE ITERATIVO (De 2 en 2)
        while (offset < totalOrdenes) {
            
            // Si el usuario presionó detener, rompemos el bucle pero NO matamos la función
            if (detenerProceso) {
                console.log(`Procesamiento detenido por el usuario en el índice ${offset}.`);
                break; 
            }

            const loteFormData = new FormData();
            loteFormData.append('_token', document.querySelector('input[name="_token"]').value);
            loteFormData.append('fechaincio', document.getElementById('fechaincio').value);
            loteFormData.append('fechafin', document.getElementById('fechafin').value);
            loteFormData.append('offset', offset.toString());

            textoProgreso.innerText = `Procesando órdenes del índice ${offset} al ${offset + tamanoLote} de un total de ${totalOrdenes}...`;

            const response = await fetch(this.action, { 
                method: 'POST', 
                body: loteFormData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!response.ok) throw new Error(`Fallo en el bloque del índice ${offset}`);
            const resultado = await response.json();

            if (resultado.status === 'completado') break;

            // EXPORTACIÓN INMEDIATA EN TIEMPO REAL (Parciales)
            if (resultado.datos_lote && resultado.datos_lote.length > 0) {
                const numLoteActual = offset + resultado.ordenes_lote.length;
                descargarDesdeCliente(resultado.datos_lote, `parcial_lote_${numLoteActual}_ordenes.csv`);
                todasLasValidaciones = todasLasValidaciones.concat(resultado.datos_lote);
            }
            
            // ACUMULAR Y LIMPIAR ÓRDENES PROCESADAS
            if (resultado.ordenes_lote && resultado.ordenes_lote.length > 0) {
                let loteLimpio = [];
                resultado.ordenes_lote.forEach(elemento => {
                    let numeroOrden = elemento;
                    
                    // Extraer solo el número limpio si viene envuelto como JSON string
                    if (typeof elemento === 'string' && elemento.startsWith('{')) {
                        try { numeroOrden = JSON.parse(elemento).Orden || elemento; } catch (e) {}
                    } else if (typeof elemento === 'object' && elemento !== null) {
                        numeroOrden = elemento.Orden || Object.values(elemento);
                    }
                    
                    loteLimpio.push({ "Orden": numeroOrden });
                });

                todasLasOrdenesProcesadas = todasLasOrdenesProcesadas.concat(loteLimpio);
                ordenesAnalizadasTotales = ordenesAnalizadasTotales.concat(loteLimpio);
            }

            offset += tamanoLote;
            const porcentaje = Math.min(Math.round((offset / totalOrdenes) * 100), 100);
            barraProgreso.style.width = `${porcentaje}%`;
            barraProgreso.innerText = `${porcentaje}%`;
        }
        // 3. LLAMADA DE CIERRE (Solo si NO se presionó detener)
        let resultadoFinal = null;
        if (!detenerProceso) {
            textoProgreso.innerText = "Finalizando procesamiento y recolectando auditoría global...";
            
            const finalFormData = new FormData();
            finalFormData.append('_token', document.querySelector('input[name="_token"]').value);
            finalFormData.append('fechaincio', document.getElementById('fechaincio').value);
            finalFormData.append('fechafin', document.getElementById('fechafin').value);
            finalFormData.append('offset', offset.toString());

            const resFinal = await fetch(this.action, { 
                method: 'POST', 
                body: finalFormData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            resultadoFinal = await resFinal.json();
            
            if (resultadoFinal.status === 'completado' && resultadoFinal.ordenes_analizadas_totales && resultadoFinal.ordenes_analizadas_totales.length > 0) {
                ordenesAnalizadasTotales = resultadoFinal.ordenes_analizadas_totales.map(el => {
                    if (typeof el === 'string' && el.startsWith('{')) {
                        try { return { "Orden": JSON.parse(el).Orden }; } catch(e){}
                    }
                    return typeof el === 'object' ? { "Orden": el.Orden } : { "Orden": el };
                });
            }
        }

        // 4. DESCARGAS MAESTRAS FINALES (Ocurre al terminar o al dar Detener)
        textoProgreso.innerText = detenerProceso 
            ? "Descargando reportes acumulados hasta la detención..." 
            : "¡Universo completado! Descargando reportes maestros finales...";
        
        const sufijo = detenerProceso ? '_parcial.csv' : '_universo_completo.csv';

        if (todasLasValidaciones.length > 0) {
            descargarDesdeCliente(todasLasValidaciones, `validaciones${sufijo}`);
        }
        
        if (todasLasOrdenesProcesadas.length > 0) {
            descargarDesdeCliente(todasLasOrdenesProcesadas, `ordenes_procesadas_totales${sufijo}`);
        }
        
        // ¡Aquí se descarga el total grande acumulado!
        if (ordenesAnalizadasTotales.length > 0) {
            descargarDesdeCliente(ordenesAnalizadasTotales, `ordenes_analizadas_totales${sufijo}`);
        }
        
        if (detenerProceso) {
            alert('El proceso ha sido detenido por el usuario. Se exportó el total de órdenes analizadas hasta el momento.');
        } else {
            alert('El proceso ha finalizado correctamente.');
        }

    } catch (error) {
        console.error(error);
        textoProgreso.innerText = "Se detectó un error en el procesamiento.";
        alert('Ocurrió un error en la comunicación con el servidor.');
    } finally {
        btn.disabled = false;
        if (btnCancelar) {
            btnCancelar.classList.add('d-none');
            btnCancelar.disabled = false; 
        }
        contenedorProgreso.classList.add('d-none');
    }
});
function descargarDesdeCliente(dataJson, nombreArchivo) {
    if (!dataJson || dataJson.length === 0) return;

    // CORRECCIÓN CLAVE: Leer el índice 0 para construir las columnas reales en base a la propiedad ("Orden")
    const cabeceras = Object.keys(dataJson[0]);
    const filasCsv = [cabeceras.join(',')];

    for (const fila of dataJson) {
        const valores = cabeceras.map(cabecera => {
            let valor = fila[cabecera];
            if (valor === null || valor === undefined) return '""';
            if (typeof valor === 'object') valor = JSON.stringify(valor);
            const valorLimpio = typeof valor === 'string' ? valor.replace(/"/g, '""') : valor;
            return `"${valorLimpio}"`;
        });
        filasCsv.push(valores.join(','));
    }

    const contenidoCsv = '\uFEFF' + filasCsv.join('\n');
    const blob = new Blob([contenidoCsv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    
    if (link.download !== undefined) {
        link.setAttribute("href", URL.createObjectURL(blob));
        link.setAttribute("download", nombreArchivo);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

</script>

@endpush
