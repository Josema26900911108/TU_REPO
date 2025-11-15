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
                                            @if ($item->estado == 2)
                                                <form action="{{ route('ventas.show', ['venta' => $item->id]) }}" method="get">
                                                    <button type="submit" class="btn btn-success">Ver
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/>
                                                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/>
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
                                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmModalPDF-{{ $item->id }}">PDF
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                                            <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/>
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
