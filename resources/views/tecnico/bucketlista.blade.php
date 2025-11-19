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

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
            @if ($Estatus = "ER")
                <li class="breadcrumb-item active">
                    <select name="tecnicoid" id="tecnicoid" class="form-control selectpicker" data-live-search="true" data-size="1" title="Elija un técnico">
            @foreach ($tecnicos as $item)
                    <option value="{{ $item->id }}">{{ $item->codigo . ' - ' . $item->nombre }}</option>
            @endforeach
                    </select>
        </li>
        
            @else
                <li class="breadcrumb-item active">
                    Bucket {{ $tecnico->nombre . ' - ' . $tecnico->codigo }}
                </li>
            @endif
    </ol>

    @can('crear-eta')

    <div class="container mt-4">
  <form action="{{ route('tecnico.importar') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <button type="submit" class="btn btn-success">Subir</button>
    <label for="archivo" class="btn btn-primary custom-upload-btn">
      <i class="fa fa-upload"></i>
    </label>

@if ($Estatus = "ER")
<input type="hidden" name="id" id="id">
@else
<input type="hidden" name="id" id="id" value="{{ $tecnico->id }}">
@endif
    <input type="file" id="archivo" name="archivo" class="custom-file-input" onchange="mostrarNombre(this)">
    <span id="nombre-archivo" class="ml-2 text-muted">Ningún archivo seleccionado</span>


  </form>
</div>
    <div class="card">
        <div class="card-header">
        <div class="mb-4">
    <table><tr>
    <td>
        <a href="{{route('tecnico.formexpediente')}}">
            <button type="button" class="fa fa-download">descargar formato</button>
        </a>
    </td>

    <td>
<form action="{{ route('etadirect.exportar') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <label for="fechaincio">Fecha Inicio:</label>
    <input type="date" name="fechaincio" id="fechaincio" required>

    <label for="fechafin">Fecha Fin:</label>
    <input type="date" name="fechafin" id="fechafin" required>



    <button type="submit">
        <i class="fa fa-cloud-download"></i> Descargar
    </button>
</form>

    </td>
</tr></table>
    </div>
    </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <i class="fas fa-tab    le me-1"></i>
            Tabla ETA
        </div>
        <div id="tabla_materiales_container">
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
      function fillRelacion(){
        let formElement = document.getElementById('treeview_form_relacion');
        let formData = new FormData(formElement);
        $.ajax({
            url: "{{ route('fetchtabla') }}",
            method:'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data){

            $('#tabla_materiales_container').empty();

            $('#tabla_materiales_container').html(data);
            },error: function(xhr,status,error){
                Swal.fire('Error', 'Hubo un problema al actualizar.'+xhr.responseText, 'error');
                console.error('Error al obtener las cuentas padre:', xhr.responseText);
        }
        });
    }
    $(document).on('click', '.pagination a', function(e) {
    e.preventDefault();

    let url = $(this).attr('href');

    $.ajax({
        url: url,
        type: 'GET',
        success: function(data) {
            $('#tabla_materiales_container').html(data);
        },
        error: function(xhr) {
            Swal.fire('Error', 'No se pudo cargar la página: ' + xhr.statusText, 'error');
        }
    });
});

</script>
@endpush
