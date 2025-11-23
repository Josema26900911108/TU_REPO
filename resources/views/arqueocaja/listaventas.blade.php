@extends('layouts.app')

@section('title', 'Ventas')

@push('css-datatable')
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .row-not-space {
            width: 110px;
        }
    </style>
@endpush

@section('content')
    @include('layouts.partials.alert')

    <div class="container-fluid px-4">
        <h1 class="mt-4 text-center">Ventas</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
            <li class="breadcrumb-item active">Ventas</li>


        </ol>

        @can('crear-venta')
            <div class="mb-4">
                <a href="{{ route('ventas.create') }}">
                    <button type="button" class="btn btn-primary">Añadir nuevo registro</button>
                </a>
            </div>
        @endcan

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Tabla ventas
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <select id="estadoFilter" class="form-control mb-3">
                    <option value="">Todos los estados</option>
                    <option value="Sin cobrar">Sin cobrar</option>
                    <option value="Cobrado">Cobrado</option>
                    <option value="En proceso">En proceso</option>
                    <option value="Anulado">Anulado</option>
                </select>

                <input type="date" id="fechaFilter" class="form-control mb-3">

                <!-- Tabla -->
                <table id="datatablesSimple" class="table table-striped">
                    <thead>
                        <tr>
                            <th>IdVenta/Estatus</th>
                            <th>Comprobante</th>
                            <th>Cliente</th>
                            <th>Fecha y hora</th>
                            <th>Vendedor</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ventas as $item)
                            <tr>
                                <td>
                                    @php
                                        $estados = [
                                            0 => 'Eliminado',
                                            1 => 'Sin cobrar',
                                            2 => 'Cobrado',
                                            3 => 'En proceso',
                                            4 => 'Anulado',
                                        ];
                                    @endphp
                                    <p class="fw-semibold mb-1">{{ $item->id }} / {{ $estados[$item->estado] ?? 'Estado desconocido' }}</p>
                                </td>
                                <td>
                                    <p class="fw-semibold mb-1">{{ $item->tipo_comprobante }}</p>
                                    <p class="text-muted mb-0">{{ $item->numero_comprobante }}</p>
                                </td>
                                <td>
                                    <p class="fw-semibold mb-1">{{ ucfirst($item->tipo_persona) }}</p>
                                    <p class="text-muted mb-0">{{ $item->razon_social }}</p>
                                    <p class="text-muted mb-0">{{ $item->numero_documento }}</p>
                                </td>
                                <td>
                                    <div class="row-not-space">
                                        <p class="fw-semibold mb-1">
                                            <span class="m-1"><i class="fa-solid fa-calendar-days"></i></span>
                                            {{ \Carbon\Carbon::parse($item->fecha_hora)->format('d-m-Y') }}
                                        </p>
                                        <p class="fw-semibold mb-0">
                                            <span class="m-1"><i class="fa-solid fa-clock"></i></span>
                                            {{ \Carbon\Carbon::parse($item->fecha_hora)->format('H:i') }}
                                        </p>
                                    </div>
                                </td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->total }}</td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Basic mixed styles example">
                                        @can('caja-cobrar-venta')
                                            @if ($item->estado == 1)
                                                <form action="{{ route('arqueocaja.cobrarventas', ['ventas' => $item->id]) }}" method="get">
                                                    <button type="submit" class="btn btn-info">Cobrar
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cash-stack" viewBox="0 0 16 16">
                                                        <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                                                        <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2z"/>
                                                      </svg>
                                                    </button>
                                                </form>
                                            @endif

                                        @endcan
                                        @can('caja-ver-venta')
                                        <form action="{{ route('ventas.show', ['venta' => $item->id]) }}" method="get">
                                            <button type="submit" class="btn btn-success">Ver
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
                                                  </svg>
                                            </button>
                                        </form>
                                    @endcan

                                    @can('caja-anular-venta')
                                    @if ($item->estado != 2 && $item->estado != 4)

                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmModal-{{ $item->id }}">Eliminar
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                                            <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/>
                                          </svg>
                                    </button>
                                    @endif
                                    <button type="button" class="btn btn-blue" data-bs-toggle="modal" data-bs-target="#confirmModalPDF-{{ $item->id }}">PDF
<svg width="16" height="16" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M2.5 6.5V6H2V6.5H2.5ZM6.5 6.5V6H6V6.5H6.5ZM6.5 10.5H6V11H6.5V10.5ZM13.5 3.5H14V3.29289L13.8536 3.14645L13.5 3.5ZM10.5 0.5L10.8536 0.146447L10.7071 0H10.5V0.5ZM2.5 7H3.5V6H2.5V7ZM3 11V8.5H2V11H3ZM3 8.5V6.5H2V8.5H3ZM3.5 8H2.5V9H3.5V8ZM4 7.5C4 7.77614 3.77614 8 3.5 8V9C4.32843 9 5 8.32843 5 7.5H4ZM3.5 7C3.77614 7 4 7.22386 4 7.5H5C5 6.67157 4.32843 6 3.5 6V7ZM6 6.5V10.5H7V6.5H6ZM6.5 11H7.5V10H6.5V11ZM9 9.5V7.5H8V9.5H9ZM7.5 6H6.5V7H7.5V6ZM9 7.5C9 6.67157 8.32843 6 7.5 6V7C7.77614 7 8 7.22386 8 7.5H9ZM7.5 11C8.32843 11 9 10.3284 9 9.5H8C8 9.77614 7.77614 10 7.5 10V11ZM10 6V11H11V6H10ZM10.5 7H13V6H10.5V7ZM10.5 9H12V8H10.5V9ZM2 5V1.5H1V5H2ZM13 3.5V5H14V3.5H13ZM2.5 1H10.5V0H2.5V1ZM10.1464 0.853553L13.1464 3.85355L13.8536 3.14645L10.8536 0.146447L10.1464 0.853553ZM2 1.5C2 1.22386 2.22386 1 2.5 1V0C1.67157 0 1 0.671573 1 1.5H2ZM1 12V13.5H2V12H1ZM2.5 15H12.5V14H2.5V15ZM14 13.5V12H13V13.5H14ZM12.5 15C13.3284 15 14 14.3284 14 13.5H13C13 13.7761 12.7761 14 12.5 14V15ZM1 13.5C1 14.3284 1.67157 15 2.5 15V14C2.22386 14 2 13.7761 2 13.5H1Z" fill="#000000"/>
</svg>
                                    </button>
                                @endcan
                                    </div>
                                </td>
                            </tr>

<!-- Modal de confirmación -->
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
                                    <form action="{{ route('arqueocaja.destroy',['arqueocaja'=>$item->id]) }}" method="post">
                                        @method('DELETE')
                                        <input type="hidden" name="idcaja" id="idcaja">

                                        <input type="hidden" name="idventa" value="{{ $item->id }}">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Confirmar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                                        <!-- Modal de confirmación PDF-->
                    <div class="modal fade" id="confirmModalPDF-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Mensaje de confirmación</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    ¿Desea ver la vista previa del documento?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <form action="{{ route('arqueocaja.vistapreviapdfventa',['arqueocaja'=>$item->id]) }}" method="post">

                                        <input type="hidden" name="idcaja" id="idcaja">

                                        <input type="hidden" name="idventa" value="{{ $item->id }}">
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
    <script>
            document.addEventListener("DOMContentLoaded", function () {
        const url = window.location.pathname; // "/arqueocaja/ventas/1"
        const partes = url.split("/"); // ["", "arqueocaja", "ventas", "1"]
        const idArqueoCaja = partes[3]; // posición 3 es el ID

        // Asignar el valor al input
        document.getElementById("idcaja").value = idArqueoCaja;
    });

        window.addEventListener('DOMContentLoaded', event => {
            // Inicializar DataTables
            const dataTable = new simpleDatatables.DataTable("#datatablesSimple", {
                searchable: true,  // Habilitar búsqueda
                perPage: 5,        // Número de filas por página
                sortable: true,    // Habilitar ordenación
            });

            // Filtrar por estado
            const estadoFilter = document.getElementById("estadoFilter");
            estadoFilter.addEventListener("change", function() {
                const estadoValue = estadoFilter.value;
                filterTable(estadoValue, document.getElementById("fechaFilter").value);
            });

            // Filtrar por fecha
            const fechaFilter = document.getElementById("fechaFilter");
            fechaFilter.addEventListener("input", function() {
                const fechaValue = fechaFilter.value;
                filterTable(document.getElementById("estadoFilter").value, fechaValue);
            });

            // Función para aplicar los filtros
            function filterTable(estado, fecha) {
                // Obtener todas las filas del DOM
                const filas = document.querySelectorAll("#datatablesSimple tbody tr");

                // Iterar sobre las filas
                filas.forEach((fila) => {
                    let showRow = true;

                    // Filtro por estado (columna 1)
                    const estadoCell = fila.querySelector("td:nth-child(1)").textContent.trim();  // Obtener el contenido de la celda de estado
                    if (estado && !estadoCell.includes(estado)) {
                        showRow = false;  // Ocultar fila si no coincide con el estado
                    }

                    // Filtro por fecha (columna 4)
                    const fechaCell = fila.querySelector("td:nth-child(4)").textContent.trim();  // Obtener el contenido de la celda de fecha
                    const fechaTabla2 = fechaCell.replace(/\n/g, '').split(' ')[0];  // Eliminar salto de línea y extraer la fecha
                    const fechaTabla = fechaTabla2.split(' ')[0];  // Extraer solo la fecha (sin la hora)

                    const partes = fechaTabla.split('-');  // Divide la fecha en partes
                    const fechaFormateada = `${partes[2]}-${partes[1]}-${partes[0]}`;  // Rearregla las partes


                    if (fecha && fechaFormateada !== fecha) {  // Comparar solo la fecha
                        showRow = false;  // Ocultar fila si no coincide con la fecha
                    }

                    // Mostrar u ocultar la fila
                    if (showRow) {
                        fila.style.display = '';  // Mostrar fila
                    } else {
                        fila.style.display = 'none';  // Ocultar fila
                    }
                });
            }
        });
    </script>
@endpush
