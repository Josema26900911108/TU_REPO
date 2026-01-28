@extends('layouts.app')

@section('title','Ruta Tecnico')

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


  </style>

@endpush

@section('content')

@include('layouts.partials.alert')

    <div class="container-fluid px-4">

        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
                @if ($Estatus = "ER")
                    <li class="breadcrumb-item active">
                        <select name="tecnicoid" id="tecnicoid" class="form-control selectpicker" data-live-search="true" data-size="1" title="Elija un técnico">
        @if ($tecnicos==null)
        <option value="">No hay técnicos disponibles</option>
        @else

                @foreach ($tecnicos as $item)
                        <option value="{{ $item->id }}">{{ $item->codigo . ' - ' . $item->nombre }}</option>
                        @php
                        $idtecnico=$item->id;
                        @endphp
                @endforeach
        @endif
                        </select>
            </li>
                @else
                    <li class="breadcrumb-item active">
                        Bucket {{ $tecnico->nombre . ' - ' . $tecnico->codigo }}
                    </li>
                @endif
        </ol>
    </div>


<ul class="nav nav-tabs" id="tecnicoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab">
            Ordenes Asignadas
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="inventario-tab" data-bs-toggle="tab" data-bs-target="#inventario" type="button" role="tab">
            Inventario
        </button>
    </li>
        <li class="nav-item" role="presentation">
        <button class="nav-link" id="expediente-tab" data-bs-toggle="tab" data-bs-target="#expediente" type="button" role="tab">
            Expediente
        </button>
    </li>
        @can('ver-pagocobrotecnico')
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="pago-tab" data-bs-toggle="tab" data-bs-target="#pago" type="button" role="tab">
            Pago
        </button>
    </li>
    @endcan
    @can('ver-cobrotecnico')
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="cobro-tab" data-bs-toggle="tab" data-bs-target="#cobro" type="button" role="tab">
            Cobro
        </button>
    </li>
    @endcan

</ul>

<div class="tab-content mt-3" id="tecnicoTabsContent">
    <div class="tab-pane fade show active" id="datos" role="tabpanel" aria-labelledby="datos-tab">
        <div class="card">
            <div class="card-header">
                        <div>
                            <table><tr>

                                @can('crear-eta')
                            <td>
                                <form action="{{ route('tecnico.importar') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <button type="submit" class="btn btn-success">Subir</button>
                                    <label for="archivo" class="btn btn-primary custom-upload-btn">
                                    <i class="fa fa-upload"></i>
                                    </label>
                                        @if ($Estatus = "ER")
                                        <input type="hidden" name="id" id="id" value="{{ $idtecnico ?? '' }}">
                                        @else
                                        <input type="hidden" name="id" id="id" value="{{ $tecnico->id }}">
                                        @endif
                                    <input type="file" id="archivo" name="archivo" class="custom-file-input" onchange="mostrarNombre(this)">
                                    <span id="nombre-archivo" class="ml-2 text-muted">Ningún archivo seleccionado</span>
                                </form>
                            </td>
                            <td>
                                <a href="{{route('tecnico.formexpediente')}}">
                                    <button type="button" class="fa fa-download">descargar formato</button>
                                </a>
                            </td>
                            @endcan
</tr>
<tr>

                            <td>
                        <form action="{{ route('tecnico.exportar') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <label for="fechaincio">Fecha Inicio:</label>
                            <input type="date" name="fechaincio" id="fechaincio" required value="{{ date('Y-m-d',strtotime('-7 day')) }}">

                            <label for="fechafin">Fecha Fin:</label>
                            <input type="date" name="fechafin" id="fechafin" required value="{{ date('Y-m-d',strtotime('+1 day')) }}">



                        </form>

                            </td>
                        </tr></table>
                    </div>
            </div>
        </div>
        <div id="tabla_materiales_container">
        </div>
    </div>

    <div class="tab-pane fade" id="inventario" role="tabpanel" aria-labelledby="inventario-tab">
        @can('crear-etamaterial')
        <div class="card">
            <div class="card-header">
                <table>
                            <td>

                                <a href="{{route('tecnico.forminventario')}}">
                                    <button type="button" class="fa fa-download">descargar formato</button>
                                </a>


                            </td>
                            <td>
                            <form action="{{ route('tecnico.invimportar') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <button type="submit" class="btn btn-success">Subir</button>
                                <label for="archivoinv" class="btn btn-primary custom-upload-btn">
                                <i class="fa fa-upload"></i>
                                </label>
                                @if ($Estatus = "ER")
                                <input type="hidden" name="id" id="id" value="{{ $idtecnico ?? '' }}">
                                @else
                                <input type="hidden" name="id" id="id" value="{{ $tecnico->id }}">
                                @endif
                                <input type="file" id="archivoinv" name="archivoinv" class="custom-file-input" onchange="mostrarNombreINVENTARIO(this)">
                                <span id="nombre-archivoinv" class="ml-2 text-muted">Ningún archivo seleccionado</span>
                            </form>
                            </td>
</table>
                        <div id="tabla_materialesinv_container">
                        </div>

                </div>
                </div>
                </div>

        <div class="tab-pane fade" id="expediente" role="tabpanel" aria-labelledby="expediente-tab">
        <div class="card">
            <div class="card-header">
                        <div>
                            <table><tr>
</tr>
<tr>

                            <td>
                        <form action="{{ route('tecnico.exportar') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <label for="fechaincio">Fecha Inicio:</label>
                            <input type="date" name="fechaincio" id="fechaincioS" required value="{{ date('Y-m-d',strtotime('-1 day')) }}">

                            <label for="fechafin">Fecha Fin:</label>
                            <input type="date" name="fechafin" id="fechafinS" required value="{{ date('Y-m-d',strtotime('+1 day')) }}">



                            <button type="submit">
                                <i class="fa fa-cloud-download"></i> Descargar
                            </button>
                        </form>

                            </td>
                        </tr></table>
                    </div>
            </div>
        </div>
        <div id="tabla_expediente_container">
        </div>
    </div>

            <div class="tab-pane fade" id="pago" role="tabpanel" aria-labelledby="pago-tab">
        <div class="card">
            <div class="card-header">
                        <div>
                            <table><tr>
</tr>
<tr>

                            <td>
                        <form action="{{ route('tecnico.exportar') }}" method="POST" enctype="multipart/form-data">
                            @csrf

                            <label for="fechaincio">Fecha Inicio:</label>
                            <input type="date" name="fechaincioP" id="fechaincioP" required value="{{ date('Y-m-d',strtotime('-1 day')) }}">

                            <label for="fechafin">Fecha Fin:</label>
                            <input type="date" name="fechafinP" id="fechafinP" required value="{{ date('Y-m-d',strtotime('+1 day')) }}">



                            <button type="submit">
                                <i class="fa fa-cloud-download"></i> Descargar
                            </button>
                        </form>

                            </td>
                        </tr></table>
                    </div>
            </div>
        </div>
        <div id="tabla_pago_container">
        </div>
    </div>


        @endcan
    </div>

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

// Función global
function initDataTable(tablasss,search) {
    console.log('initDataTable llamado');
    if ($(tablasss).length && !$.fn.DataTable.isDataTable(tablasss)) {
        // Destruir instancia anterior
        if (dataTableInstance) {
            try {
                dataTableInstance.destroy();
                dataTableInstance = null;
                console.log('DataTable anterior destruida');
            } catch(e) {
                console.log('Error al destruir DataTable:', e);
            }
        }

        // Inicializar DataTable CON configuración específica para búsqueda
        console.log('Inicializando nueva DataTable');
        dataTableInstance = $(tablasss).DataTable({
  paging: true,
            info: true,
            ordering: true,
            responsive: false,
            pageLength: 10,
            searching: true,
            dom: 'Bfrtip', // Agregar B para botones
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    title: 'Inventario Filtrado',
                    exportOptions: {
                        // Exportar solo datos filtrados
                        modifier: {
                            search: 'applied',
                            order: 'applied',
                            page: 'all' // Exportar TODAS las páginas filtradas
                        },
                        columns: ':visible' // Solo columnas visibles
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    title: 'Inventario Filtrado',
                    exportOptions: {
                        modifier: {
                            search: 'applied',
                            order: 'applied',
                            page: 'all'
                        },
                        columns: ':visible'
                    },
                    customize: function(doc) {
                        doc.defaultStyle.fontSize = 10;
                        doc.styles.tableHeader.fontSize = 11;
                        doc.pageMargins = [10, 10, 10, 10];
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Imprimir',
                    title: 'Inventario Filtrado',
                    exportOptions: {
                        modifier: {
                            search: 'applied',
                            order: 'applied',
                            page: 'all'
                        },
                        columns: ':visible'
                    },
                    customize: function(win) {
                        $(win.document.body).find('table').addClass('display').css('font-size', '10px');
                        $(win.document.body).find('h1').css('text-align','center');
                    }
                },
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copiar',
                    title: 'Inventario Filtrado',
                    exportOptions: {
                        modifier: {
                            search: 'applied',
                            order: 'applied'
                        }
                    }
                }
            ],
            language: {
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                paginate: {
                    next: "›",
                    previous: "‹"
                },
                zeroRecords: "No se encontraron resultados"
            }
        });

        console.log('DataTable inicializada con ID:', dataTableInstance.table().node().id);

        // Conectar tu input personalizado con DataTable - VERSIÓN MEJORADA
        connectSearchInput(search);
    } else {
        console.log('Tabla no encontrada o ya inicializada');
        if ($.fn.DataTable.isDataTable(tablasss)) {
            console.log('DataTable ya existe, reconectando buscador...');
            dataTableInstance = $(tablasss).DataTable();
            connectSearchInput(search);
        }
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
        data: { id: id, page : page, count: 1000000 },
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
        url: "{{ route('fetchtabla') }}",
        method: 'GET',
        data: { id : id, fechain : fechain, fechafin : fechafin, page: page },
        success: function(data) {
            $('#tabla_materiales_container').html(data);
            setTimeout(function() {
                initDataTable('#datatablesSimpleAsig', '#globalSearchAsig');
            }, 300);
            console.log('Tabla principal cargada');
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });
};

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

    // Event delegation para el input de búsqueda (por si se carga dinámicamente)
    $(document).on('keyup', '#globalSearch', function() {
        console.log('Evento keyup en globalSearch');
        currentSearchValue = $(this).val();
        if (dataTableInstance && $.fn.DataTable.isDataTable('#datatablesSimpleInv')) {
            dataTableInstance.search(currentSearchValue).draw();
        }
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

// Otras funciones AJAX (NO duplicadas)
function fillRelacionAsignada(page) {
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
        url: "{{ route('fetchtabla') }}",
        method: 'GET',
        data: { id : id, fechain : fechain, fechafin : fechafin, page: page },
        success: function(data) {
            $('#tabla_materiales_container').html(data);
setTimeout(function() {
                initDataTable('#datatablesSimpleAsig', '#globalSearchAsig');
            }, 300);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });
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
@endpush
