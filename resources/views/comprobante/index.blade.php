@extends('layouts.app')

@section('content')

@include('layouts.partials.alert')

@section('title', 'Comprobantes')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }
    table, th, td {
        border: 1px solid black;
    }
    th, td {
        padding: 8px;
        text-align: left;
    }
    .expandable {
        cursor: pointer;
        background-color: #f2f2f2;
    }
    .hidden-row {
        display: none;
    }
</style>
@endpush

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

    <div class="container-fluid px-4">
        <h1 class="mt-4 text-center">Comprobantes</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Comprobantes</li>
        </ol>

        @can('crear-comprobante')
        <div class="mb-4">
            <a href="{{ route('comprobante.create') }}">
                <button type="button" class="btn btn-primary">Añadir nuevo comprobante</button>
            </a>
        </div>
        @endcan

        <div class="card">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Tabla Comprobante
            </div>
            <div class="card-body">
                <table id="comprobantesTable" class="table table-striped fs-6">
                    <thead>
                        <tr>
                            <th>Expandir</th>
                            <th>Comprobante</th>
                            <th>Formula</th>
                            <th>Tienda</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comprobante as $item)
                            <tr>
                                <!-- Botón para expandir -->
                                <td>
                                    <button class="btn btn-sm btn-info toggle-details" data-id="{{ $item->id }}">+</button>
                                </td>
                                <td>{{ $item->tipo_comprobante }}</td>
                                <td>{{ $item->formula }}</td>
                                <td>
                                    @if($item->tienda_nombre!="")
                                        {{ $item->tienda_nombre }}
                                    @else
                                        Sin tienda asignada
                                    @endif
                                </td>
                                <td>

                                    @can('ver-comprobante')
                                    <div>
                                        <button title="Opciones" class="btn btn-datatable btn-icon btn-transparent-dark me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                            <svg class="svg-inline--fa fa-ellipsis-vertical" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-vertical" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 512" data-fa-i2svg="">
                                                <path fill="currentColor" d="M56 472a56 56 0 1 1 0-112 56 56 0 1 1 0 112zm0-160a56 56 0 1 1 0-112 56 56 0 1 1 0 112zM0 96a56 56 0 1 1 112 0A56 56 0 1 1 0 96z"></path>
                                            </svg>
                                        </button>
                                        <ul class="dropdown-menu text-bg-light" style="font-size: small;">
                                            <li><a class="dropdown-item" href="{{ route('detallecomprobante.create', ['comprobante' => $item->id]) }}">Insertar Detalles</a></li>
                                            <li><a class="dropdown-item" href="{{ route('detallecomprobante.edit', ['comprobante' => $item->id]) }}">Ver Detalles</a></li>
                                        </ul>
                                    </div>

                                    @endcan
                                    @can('editar-comprobante')
                                    <div>
                                        <button title="Opciones" class="btn btn-datatable btn-icon btn-transparent-dark me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                            <svg class="svg-inline--fa fa-ellipsis-vertical" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-vertical" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 512" data-fa-i2svg="">
                                                <path fill="currentColor" d="M56 472a56 56 0 1 1 0-112 56 56 0 1 1 0 112zm0-160a56 56 0 1 1 0-112 56 56 0 1 1 0 112zM0 96a56 56 0 1 1 112 0A56 56 0 1 1 0 96z"></path>
                                            </svg>
                                        </button>
                                        <ul class="dropdown-menu text-bg-light" style="font-size: small;">
                                            <li><a class="dropdown-item" href="{{ route('comprobante.edit', ['comprobante' => $item->id]) }}">Editar</a></li>
                                            </ul>
                                    </div>

                                    @endcan

                                    @can('eliminar-comprobante')
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}">Eliminar</button>
                                    @endcan

                                </td>
                            </tr>
                            <!-- Fila oculta para detalles -->
                            <tr class="details-row details-row-{{ $item->id }}" style="display: none;">
                                <td colspan="5">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Cuenta</th>
                                                <th>Numero Cuenta Contable</th>
                                                <th>Valor Mínimo</th>
                                                <th>Naturaleza</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($detallecomprobante->where('fkComprobante', $item->id) as $detalle)
                                                <tr>
                                                    <td>{{ $detalle->cuenta_contable_nombre }}</td>
                                                    <td>{{ $detalle->cuenta_contable_numero }}</td>
                                                    <td>{{ $detalle->valorminimo }}</td>
                                                    <td>{{ $detalle->Naturaleza }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr colspan="5">
                                                <td><strong>DEBE:</strong></td>
                                                <td><strong>{{ number_format($detalle->Debe ?? 0, 2) }}</strong></td>
                                                <td ><strong>HABER:</strong></td>
                                                <td><strong>{{ number_format($detalle->Haber ?? 0, 2) }}</strong></td>

                                            </tr>
                                        </tfoot>

                                    </table>
                                </td>
                            </tr>

                                                <!-- Modal de confirmación-->
                    <div class="modal fade" id="confirmModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Mensaje de confirmación</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    ¿Seguro que quieres eliminar el registro?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        <form action="{{ route('comprobante.destroy',['comprobante'=>$item->id]) }}" method="post">
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

                <!-- Paginación de comprobantes -->
                {{ $comprobante->links() }}
            </div>
        </div>
    </div>



@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Escuchar cuando se hace clic en el botón de expandir/contraer
        document.querySelectorAll('.toggle-details').forEach(button => {
            button.addEventListener('click', function() {
                var id = this.dataset.id;
                var detailsRow = document.querySelector('.details-row-' + id);

                // Alternar la visibilidad de la fila de detalles
                if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
                    detailsRow.style.display = 'table-row';
                    this.textContent = '-'; // Cambiar el botón a "-"
                } else {
                    detailsRow.style.display = 'none';
                    this.textContent = '+'; // Cambiar el botón a "+"
                }
            });
        });
    });
</script>
@endpush
