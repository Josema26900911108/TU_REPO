@extends('layouts.app')

@section('title','Centros')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Centros de Organizacion</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Centros de Organizacion</li>
    </ol>

    @can('crear-centro')
    <div class="mb-4">
        <a href="{{route('centroorganizacion.create')}}">
            <button type="button" class="btn btn-primary">Añadir nuevo registro</button>
        </a>
    </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla centros
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table-striped fs-6">
                <thead>
                    <tr>
                        <th>Tienda Destino</th>
                        <th>Estatus Tienda</th>
                        <th>Codigo</th>
                        <th>Centro</th>
                        <th>Estatus Centro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($CentroOrganizacion as $centroorganizacion)
                    <tr>
                        <td>
                            {{$centroorganizacion->Tienda}}
                        </td>
                        <td>
                            {{$centroorganizacion->EstatusContable=='A' ? 'ACTIVO' : 'INACTIVO'}}
                        </td>
                        <td>
                            {{$centroorganizacion->codigo}}
                        </td>
                        <td>
                            {{$centroorganizacion->Centro}}
                        </td>
                                                <td>
                            {{$centroorganizacion->status=='A' ? 'ACTIVO' : 'INACTIVO'}}
                        </td>
                        <td>
                            <div class="d-flex justify-content-around">

                                <div>
                                    <button title="Opciones" class="btn btn-datatable btn-icon btn-transparent-dark me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg class="svg-inline--fa fa-ellipsis-vertical" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-vertical" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 512" data-fa-i2svg="">
                                            <path fill="currentColor" d="M56 472a56 56 0 1 1 0-112 56 56 0 1 1 0 112zm0-160a56 56 0 1 1 0-112 56 56 0 1 1 0 112zM0 96a56 56 0 1 1 112 0A56 56 0 1 1 0 96z"></path>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu text-bg-light" style="font-size: small;">
                                        <!-----Editar centro--->
                                        @can('editar-centroorganizacion')
                                        <li><a class="dropdown-item" href="{{ route('centroorganizacion.edit', ['centroorganizacion' => $centroorganizacion->id]) }}">Editar</a></li>
                                        @endcan
                                    </ul>
                                </div>

                            </div>
                        </td>
                    </tr>
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
@endpush
