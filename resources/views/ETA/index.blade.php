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
    <h1 class="mt-4 text-center">ETA</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">ETA</li>
    </ol>

    @can('crear-eta')

    <div class="container mt-4">
  <form action="{{ route('etadirect.importar') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <button type="submit" class="btn btn-success">Subir</button>
    <label for="archivo" class="btn btn-primary custom-upload-btn">
      <i class="fa fa-upload"></i>
    </label>

    <input type="file" id="archivo" name="archivo" class="custom-file-input" onchange="mostrarNombre(this)">
    <span id="nombre-archivo" class="ml-2 text-muted">Ningún archivo seleccionado</span>


  </form>
</div>

    <div class="card">
        <div class="card-header">
        <div class="mb-4">
    <table><tr>
    <td>
        <a href="{{route('etadirect.formeta')}}">
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

    <label for="fkTienda">Tienda:</label>
    <select name="fkTienda" id="fkTienda" required>
        @foreach ($tienda as $item)
            <option value="{{ $item->idTienda }}">{{ $item->Nombre }}</option>
        @endforeach
    </select>

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
            <i class="fas fa-table me-1"></i>
            Tabla ETA
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped fs-6">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>SKU</th>
                        <th>Descripcion</th>
                        <th>Cantidad</th>
                        <th>Serie</th>
                        <th>MAC1</th>
                        <th>MAC2</th>
                        <th>MAC3</th>
                        <th>CENTRO</th>
                        <th>EMPLEADO</th>
                        <th>FECHA</th>

                        <!------Eliminar producto---->
                        @can('vertienda-producto')
                        <th>Tienda</th>
                        @endcan
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($eta as $item)
                    <tr>
                        <td>{{$item->Orden}}</td>
                        <td>{{$item->SKU}}</td>
                        <td>{{$item->Descripcion}}</td>
                        <td>{{$item->Cantidad}}</td>
                        <td>{{$item->Serie}}</td>
                        <td>{{$item->MAC1}}</td>
                        <td>{{$item->MAC2}}</td>
                        <td>{{$item->MAC3}}</td>
                        <td>{{$item->CENTRO}}</td>
                        <td>{{$item->EMPLEADO}}</td>
                        <td>{{$item->created_at->format('d-m-Y')}}</td>
                        @can('vertienda-producto')
                        <td>
                            @if($item->fkTienda)
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
                                        <svg class="svg-inline--fa fa-ellipsis-vertical" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-vertical" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 512" data-fa-i2svg="">
                                            <path fill="currentColor" d="M56 472a56 56 0 1 1 0-112 56 56 0 1 1 0 112zm0-160a56 56 0 1 1 0-112 56 56 0 1 1 0 112zM0 96a56 56 0 1 1 112 0A56 56 0 1 1 0 96z"></path>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu text-bg-light" style="font-size: small;">

                                        <!----Ver-producto--->
                                        @can('ver-producto')
                                        <li>
                                            <a class="dropdown-item" role="button" data-bs-toggle="modal" data-bs-target="#verModal-{{$item->id}}">Ver</a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                                <div>
                                    <!----Separador----->
                                    <div class="vr"></div>
                                </div>
                                <div>
                                    <!------Eliminar producto---->
                                    @can('eliminar-eta')

                                    <button title="Eliminar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark">
                                        <svg class="svg-inline--fa fa-trash-can" aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-can" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg="">
                                            <path fill="currentColor" d="M170.5 51.6L151.5 80h145l-19-28.4c-1.5-2.2-4-3.6-6.7-3.6H177.1c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80H368h48 8c13.3 0 24 10.7 24 24s-10.7 24-24 24h-8V432c0 44.2-35.8 80-80 80H112c-44.2 0-80-35.8-80-80V128H24c-13.3 0-24-10.7-24-24S10.7 80 24 80h8H80 93.8l36.7-55.1C140.9 9.4 158.4 0 177.1 0h93.7c18.7 0 36.2 9.4 46.6 24.9zM80 128V432c0 17.7 14.3 32 32 32H336c17.7 0 32-14.3 32-32V128H80zm80 64V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16z"></path>
                                        </svg>
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal -->
                    <div class="modal fade" id="verModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Detalles del Mano de Obra o Material</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <p><span class="fw-bolder">Descripción: </span>{{$item->descripcion}}</p>
                                        </div>
                                        <div class="col-12">
                                            <p><span class="fw-bolder">Stock: </span>{{$item->id}}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                                        <!-- Modal de confirmación-->
                    <div class="modal fade" id="confirmModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Mensaje de confirmación</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                     '¿Seguro que quieres eliminar la mano de obra o material?'
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <form action="{{ route('etadirect.destroy',['etadirect'=>$item->id]) }}" method="post">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Confirmar</button>
                                    </form>
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
</script>
@endpush
