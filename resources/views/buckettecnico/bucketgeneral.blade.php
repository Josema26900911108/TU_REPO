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
            <li class="breadcrumb-item active">Bucket Ordenes</li>
        </ol>
    </div>


<ul class="nav nav-tabs" id="tecnicoTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab">
            Bucket Ordenes Warehouse
        </button>
    </li>
</ul>

                                            <td>
                                                <form action="{{ route('tecnico.exportar') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <label for="fechaincio">Fecha Inicio:</label>
                                                    <input type="date" name="fechaincio" id="fechaincio" required value="{{ date('Y-m-d',strtotime('-7 day')) }}">

                                                    <label for="fechafin">Fecha Fin:</label>
                                                    <input type="date" name="fechafin" id="fechafin" required value="{{ date('Y-m-d',strtotime('+1 day')) }}">
                                                </form>

                                            </td>

<div class="tab-content mt-3" id="tecnicoTabsContent">
    <div class="tab-pane fade show active" id="datos" role="tabpanel" aria-labelledby="datos-tab">
        <div class="card">
            <div class="card-header">



<div class="card shadow-sm border-0 bg-light p-3">
    <div class="card-body">


        <div id="tabla_materiales_container">
            </div>
        </div>
    </div>
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
let searchTimer;

// 1. Inicializador Controlado de la Tabla
function initDataTable(tablasss, search) {
    console.log('initDataTable llamado de forma local controlada');
    
    
    if ($(tablasss).length && !$.fn.DataTable.isDataTable(tablasss)) {
        if (dataTableInstance) {
            try { dataTableInstance.destroy(); dataTableInstance = null; } catch(e) {}
        }

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
                        var idtecnico = "{{ $idtecnico ?? '' }}";
                        var fechain = $('#fechaincio').val() || '';
                        var fechafin = $('#fechafin').val() || '';
                        var searchVal = currentSearchValue || $('#globalSearchAsig').val() || '';

                        var exportUrl = "{{ route('bucketordenes.exportarExcel') }}?" + $.param({
                            id: idtecnico,
                            fechain: fechain,
                            fechafin: fechafin,
                            search: searchVal
                        });

                        window.location.href = exportUrl;
                    }
                },
                'pdfHtml5', 'print', 'copy'
            ],
            language: {
                "zeroRecords": "No se encontraron resultados en esta página"
            }
        });

        if (search) {
            $(search).off('keyup').on('keyup', function() {
                currentSearchValue = $(this).val();
                dataTableInstance.search(currentSearchValue).draw();
            });
        }
    }
}

// 2. Función de Petición AJAX (Conserva filtros, foco y paginación asíncrona)
function fillRelacionAsignada(page = 1) {
    console.log('fillRelacionAsignada buscando de forma global...');
    var fechain = $('#fechaincio').val();
    var fechafin = $('#fechafin').val();
    var search = $('#globalSearchAsig').val() || ''; 
    var idtecnico = "{{ $idtecnico ?? '' }}";

    $.ajax({
        url: "{{ route('fetchrelacionBucketOrdenes') }}",
        method: 'GET',
        data: { id: idtecnico, fechain : fechain, fechafin : fechafin, page: page, search: search },
        success: function(data) {
            $('#tabla_materiales_container').html(data);
            
            setTimeout(function() {
                initDataTable('#datatablesSimpleAsig', '#globalSearchAsig');
                
                if(search !== '') {
                    $('#globalSearchAsig').val(search).focus();
                }
            }, 300);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al filtrar: ' + xhr.responseText, 'error');
        }
    });
}

// Mantener alias globales mapeados
window.fillRelacion = fillRelacionAsignada;
window.fillRelacionAsig = fillRelacionAsignada;
window.fillRelacionAsignada = fillRelacionAsignada;
// 3. Conexión del Input de Búsqueda
function connectSearchInput(search) {
    if ($(search).length && dataTableInstance) {
        $(search).off('keyup.'+search);
        $(search).on('keyup.'+search, function() {
            const searchValue = $(this).val().trim();
            currentSearchValue = searchValue;
            dataTableInstance.search(searchValue).draw();
        });
    }
}

// 4. Exportador Manual Alternativo a CSV
function exportFilteredToCSV() {
    if (!dataTableInstance) {
        alert('La tabla no está inicializada');
        return;
    }
    const filteredData = dataTableInstance.rows({ search: 'applied' }).data();
    const columns = dataTableInstance.columns().header().toArray();

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
    const headers = columns.map(col => `"${$(col).text().trim()}"`).join(",");
    csvContent += headers + "\r\n";

    filteredData.each(function(value, index) {
        const rowData = [];
        dataTableInstance.columns().every(function() {
            if (this.visible()) {
                const cellData = dataTableInstance.cell(index, this.index()).data();
                const escapedData = typeof cellData === 'string' ? `"${cellData.replace(/"/g, '""')}"` : cellData;
                rowData.push(escapedData);
            }
        });
        csvContent += rowData.join(",") + "\r\n";
    });

    downloadCSV(csvContent, `inventario_filtrado_${new Date().toISOString().slice(0,10)}.csv`);
}

// 5. Exportador Manual Completo a Excel
function exportFilteredToExcel() {
    if (!dataTableInstance) return;
    const filteredData = dataTableInstance.rows({ search: 'applied' }).data();
    const columns = dataTableInstance.columns().visible().header().toArray();
    const data = [];

    const headers = columns.map(col => $(col).text().trim());
    data.push(headers);

    filteredData.each(function(value, index) {
        const row = [];
        dataTableInstance.columns().every(function() {
            if (this.visible()) {
                row.push(dataTableInstance.cell(index, this.index()).data());
            }
        });
        data.push(row);
    });

    if (typeof XLSX !== 'undefined') {
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Inventario");
        XLSX.writeFile(wb, `inventario_filtrado_${new Date().toISOString().slice(0,10)}.xlsx`);
    } else {
        dataTableInstance.button('.buttons-excel').trigger();
    }
}

// 6. Procesadores de flujos y formatos de descarga
function formatCellData(data) {
    if (data === null || data === undefined) return '';
    if (typeof data === 'object') {
        return $(data).text().trim() || String(data);
    }
    return String(data).trim();
}

function downloadCSV(content, filename) {
    const blob = new Blob(["\uFEFF" + content], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    if (navigator.msSaveBlob) {
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
function exportFilteredData(type) {
    let dataRows = (type === 'all') ? dataTableInstance.rows({ search: 'applied' }) : dataTableInstance.rows({ page: 'current', search: 'applied' });
    let filename = (type === 'all') ? `inventario_completo_${new Date().toISOString().slice(0,10)}` : `inventario_pag_${new Date().toISOString().slice(0,10)}`;

    const csvData = [];
    const headers = [];
    dataTableInstance.columns().every(function() { if (this.visible()) headers.push($(this.header()).text().trim()); });
    csvData.push(headers);

    dataRows.every(function() {
        const row = this; const rowData = [];
        dataTableInstance.columns().every(function() { if (this.visible()) rowData.push(formatCellData(dataTableInstance.cell(row.index(), this.index()).data())); });
        csvData.push(rowData);
    });

    const csvContent = csvData.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
    downloadCSV(csvContent, filename + '.csv');
}

// 7. Inicialización de Escuchas al Cargar el DOM
$(document).ready(function() {
    console.log('Documento listo');
    fillRelacionAsignada(1);

    $(document).on('click', '#laravel-pagination a', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        let page = new URL(url, window.location.origin).searchParams.get('page') || 1;
        fillRelacionAsignada(page);
    });

    $(document).on('click', '#exportFilteredBtn', function() { exportFilteredData('all'); });
    $(document).on('click', '#exportCurrentPageBtn', function() { exportFilteredData('current'); });

    $(document).on('click', '[data-bs-toggle="dropdown"]', function(e) {
        e.preventDefault(); e.stopPropagation();
        var dropdown = bootstrap.Dropdown.getInstance(this) || new bootstrap.Dropdown(this);
        dropdown.toggle();
    });

    // Filtro asíncrono con retraso de tecleo (Debounce)
    $(document).on('keyup', '#globalSearchAsig', function() {
        currentSearchValue = $(this).val();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { fillRelacionAsignada(1); }, 600);
    });

    // MutationObserver para persistencia del DOM
    const searchObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && (node.matches('#globalSearchAsig') || node.querySelector('#globalSearchAsig'))) {
                        console.log('Buscador re-detectado');
                    }
                });
            }
        });
    });

    const datosContainer = document.getElementById('tabla_materiales_container');
    if (datosContainer) searchObserver.observe(datosContainer, { childList: true, subtree: true });

    // Filtros de cambio de Fecha Superior
    $('#fechaincio, #fechafin').change(function(){
        var fechain = $('#fechaincio').val(); var fechafin = $('#fechafin').val();
        if (!fechain || !fechafin) return;
        if(fechain > fechafin){ Swal.fire('Error', 'La fecha final debe ser mayor o igual a la inicial', 'error'); return; }
        fillRelacionAsignada(1);
    });
});

function mostrarNombre(input) {
    const nombre = input.files.length > 0 ? input.files[0].name : "Ningún archivo seleccionado";
    if(document.getElementById('nombre-archivo')) document.getElementById('nombre-archivo').textContent = nombre;
}

</script>
@endpush
