@extends('layouts.app')

@section('title','Productos')

@push('css-datatable')

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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



                            <button type="submit">
                                <i class="fa fa-cloud-download"></i> Descargar
                            </button>
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
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables -->
<link rel="stylesheet"
      href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<script>
    let tablaInventario = null;

$(document).ready(function(){

          const table = $('#datatablesSimpleInv').DataTable({
        paging: true,
        info: true,
        ordering: true,
        responsive: false,
        pageLength: 10,
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

    // 🔍 BÚSQUEDA GENERAL
    $('#globalSearch').on('keyup', function () {
        table.search(this.value).draw();
    });

    // 🔎 FILTROS POR COLUMNA
    // Ajusta los índices si cambias columnas

    $('#searchSKU').on('keyup', function () {
        table.column(2).search(this.value).draw(); // SKU
    });

    $('#searchAlmacen').on('keyup', function () {
        table.column(3).search(this.value).draw(); // Almacén
    });

    $('#searchEstatus').on('change', function () {
        table.column(8).search(this.value).draw(); // Estatus
    });

    $('#searchCentro').on('keyup', function () {
        table.column(10).search(this.value).draw(); // Centro
    });


$(document).on('click', '#tabla_pago_container a[href*="page="]', function (e) {
    e.preventDefault();
    let url = $(this).attr('href');
    let page = new URL(url, window.location.origin).searchParams.get('page') || 1;
    fillRelacionP(page);
});

    $('#fechaincio').change(function(){

        fechain=$('#fechaincio').val();
        fechafin=$('#fechafin').val();

if (typeof fechain === 'undefined' || fechain === null || fechain === '') {
    Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
}
        if(fechain>fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal  mayor o igual a fecha inicial', 'error');
        }

        fillRelacionAsignada();


    });

    $('#fechafin').change(function(){

        fechain=$('#fechaincio').val();
        fechafin=$('#fechafin').val();

if (typeof fechain === 'undefined' || fechain === null || fechain === '') {
    Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
}
        if(fechain>fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal  mayor o igual a fecha inicial', 'error');
        }
        fillRelacionAsignada();

    });

    $('#fechaincioS').change(function(){

        fechain=$('#fechaincioS').val();
        fechafin=$('#fechafinS').val();

if (typeof fechain === 'undefined' || fechain === null || fechain === '') {
    Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
}
        if(fechain>fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal  mayor o igual a fecha inicial', 'error');
        }

        fillRelacionS();


    });

    $('#fechafinS').change(function(){

        fechain=$('#fechaincioS').val();
        fechafin=$('#fechafinS').val();

if (typeof fechain === 'undefined' || fechain === null || fechain === '') {
    Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
}
        if(fechain>fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal  mayor o igual a fecha inicial', 'error');
        }
        fillRelacionS();

    });

     $('#fechaincioP').change(function(){

        fechain=$('#fechaincioP').val();
        fechafin=$('#fechafinP').val();

if (typeof fechain === 'undefined' || fechain === null || fechain === '') {
    Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
}
        if(fechain>fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal  mayor o igual a fecha inicial', 'error');
        }

        fillRelacionP(1);


    });

    $('#fechafinP').change(function(){

        fechain=$('#fechaincioP').val();
        fechafin=$('#fechafinP').val();

if (typeof fechain === 'undefined' || fechain === null || fechain === '') {
    Swal.fire('Error:', 'Favor seleccionar una fecha válida', 'error');
}
        if(fechain>fechafin){
            Swal.fire('Error: ', 'Favor de seleccionar fechafinal  mayor o igual a fecha inicial', 'error');
        }
        fillRelacionP(1);

    });

    $('#tecnicoid').change(function(){
            var select = document.getElementById("tecnicoid");

    let id = null;

    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        // Si quieres usar un valor de Laravel en JS, pásalo desde Blade:
        id = "{{ $tecnico->id ?? '' }}"; // Asegúrate de que $tecnico exista
    }
fillRelacion();
fillRelacionS();
fillRelacionP(1);

    });


fillRelacionS();
fillRelacionP(1);
fillRelacion(1);
});

$(document).on('click', '#tabla_materialesinv_container a[href*="page="]', function (e) {
    e.preventDefault();
    let url = $(this).attr('href');
    let page = new URL(url, window.location.origin).searchParams.get('page') || 1;
    fillRelacion(page);
});
$(document).on('click', '#tabla_materiales_container a[href*="page="]', function (e) {
    e.preventDefault();
    let url = $(this).attr('href');
    let page = new URL(url, window.location.origin).searchParams.get('page') || 1;
    fillRelacion(page);
});

  function mostrarNombre(input) {
    const nombre = input.files.length > 0 ? input.files[0].name : "Ningún archivo seleccionado";
    document.getElementById('nombre-archivo').textContent = nombre;
  }
  function mostrarNombreINVENTARIO(input) {
    const nombre = input.files.length > 0 ? input.files[0].name : "Ningún archivo seleccionado";
    document.getElementById('nombre-archivoinv').textContent = nombre;
  }
function fillRelacionS() {
    var select = document.getElementById("tecnicoid");
    var fechainS=$('#fechaincioS').val();
    var fechafinS=$('#fechafinS').val();


    let id = null;

    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        // Si quieres usar un valor de Laravel en JS, pásalo desde Blade:
        id = "{{ $tecnico->id ?? '' }}"; // Asegúrate de que $tecnico exista
    }


    $.ajax({
        url: "{{ route('fetchtablaS') }}",
        method: 'GET',
        data: { id: id, fechainS:fechainS, fechafinS:fechafinS },
        success: function(data) {
            $('#tabla_expediente_container').html(data);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });


}

function fillRelacionP(page) {
    var select = document.getElementById("tecnicoid");
    var fechainP=$('#fechaincioP').val();
    var fechafinP=$('#fechafinP').val();

    let id = null;
    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        id = "{{ $tecnico->id ?? '' }}";
    }

    $.ajax({
        url: "{{ route('fetchtablaP') }}",  // 👈 siempre apunta al mismo route
        method: 'GET',
        data: { id: id, fechainP: fechainP, fechafinP: fechafinP, page: page }, // 👈 aquí pasamos el page
        success: function(data) {
            $('#tabla_pago_container').html(data);
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });
}

    $(document).on('click', 'a[href*="tecnicotabla?page="]', function(e) {
        e.preventDefault();

        let url = $(this).attr('href');

        $.ajax({
            url: url,
            type: 'GET',
            success: function(data) {
                $('#tabla_materiales_container').html(data);
                setTimeout(function() {
                initDataTable('#datatablesSimpleAsig', '#globalSearchAsig');
            }, 300);
            },
            error: function(xhr) {
                Swal.fire('Error', 'No se pudo cargar la página: ' + xhr.statusText, 'error');
            }
        });
    });

    function fillRelacionAsignada(page) {
    var select = document.getElementById("tecnicoid");
    var fechain=$('#fechaincio').val();
    var fechafin=$('#fechafin').val();

    let id = null;
    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        id = "{{ $tecnico->id ?? '' }}";
    }

    $.ajax({
        url: "{{ route('fetchtablaT') }}",
        method: 'GET',
        data: { id : id, fechain : fechain, fechafin : fechafin, page: page }, // 👈 aquí pasamos el page
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


function fillRelacion(page = 1) {
    var select = document.getElementById("tecnicoid");
    var fechain=$('#fechaincio').val();
    var fechafin=$('#fechafin').val();

    let id = null;
    if (select !== null) {
        id = select.options[select.selectedIndex].value;
    } else {
        id = "{{ $tecnico->id ?? '' }}";
    }

    $.ajax({
        url: "{{ route('fetchtabla') }}",
        method: 'GET',
        data: { id : id, fechain : fechain, fechafin : fechafin, page: page }, // 👈 aquí pasamos el page
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

            $.ajax({
        url: "{{ route('fetchinvtabla') }}",
        method: 'GET',
        data: { id: id, page : page},
        success: function(data) {
            $('#tabla_materialesinv_container').html(data);
                        setTimeout(function() {
                initDataTable('#datatablesSimpleInv', '#globalSearchInv');
            }, 300);
        },
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar: ' + xhr.responseText, 'error');
        }
    });
}





</script>
@endpush
