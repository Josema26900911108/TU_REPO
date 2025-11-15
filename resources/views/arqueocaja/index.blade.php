@extends('layouts.app')

@section('title','Caja Registradora')
@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush
@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Historial Arqueo Caja</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('cash.index') }}">Caja</a></li>
        <li class="breadcrumb-item active">Arqueo Caja</li>
    </ol>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            @foreach ($caja as $itemm)
            {{$itemm->Nombre}}
            @endforeach
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped fs-6">
                <thead>
                    <tr>
                        <th>Efectivo al momento del cierre</th>
                        <th>Suma de ventas diarias</th>
                        <th>Pagos mediante el resto de medios habilitados</th>
                        <th>Descuentos</th>
                        <th>Ventas a crédito</th>
                        <th>Salida de dinero para otras partidas de la empresa</th>
                        <th>Saldo inicial de la caja </th>
                        <th>Creado</th>
                        <th>Modificado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($arqueocaja as $item)
                    <tr>
                        <td>
                            {{$item->CEF}}
                        </td>
                        <td>
                            {{$item->VD;}}
                        </td>
                        <td>
                            {{$item->VO}}
                        </td>
                        <td>
                            {{$item->D}}
                        </td>
                        <td>
                            {{$item->CC}}
                        </td>
                        <td>
                            {{$item->OG}}
                        </td>
                        <td>
                            {{$item->CEI}}
                        </td>
                        <td>
                            {{$item->created_at}}
                        </td>
                        <td>
                            {{$item->updated_at}}
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
                                        <!-----Editar presentacione--->
                                        @can('editar-caja')
                                        <li><a class="dropdown-item" href="{{route('cash.edit',['cash'=>$item])}}">Editar</a></li>
                                        @endcan
                                        @can('eliminar-caja')
                                        <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}"  href="{{route('cash.edit',['cash'=>$item])}}">Eliminar</a></li>
                                        @endcan
                                    </ul>
                                </div>
                                <div>
                                    <!----Separador----->
                                    <div class="vr"></div>
                                </div>
                                <div>
                                    <button title="Arqueo de Caja Historico" data-bs-toggle="modal" data-id="{{ $item->id }}" data-bs-target="#aperturarModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="rotate(45)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M22 5V7C22 8.83 21.17 9.82 19.5 9.97C19.34 9.99 19.17 10 19 10H5C4.83 10 4.66 9.99 4.5 9.97C2.83 9.82 2 8.83 2 7V5C2 3 3 2 5 2H19C21 2 22 3 22 5Z" fill="#12498c"></path> <path d="M5.5 11.25C4.95 11.25 4.5 11.7 4.5 12.25V19C4.5 21 5 22 7.5 22H16.5C19 22 19.5 21 19.5 19V12.25C19.5 11.7 19.05 11.25 18.5 11.25H5.5ZM13.82 15.75H10.18C9.77 15.75 9.43 15.41 9.43 15C9.43 14.59 9.77 14.25 10.18 14.25H13.82C14.23 14.25 14.57 14.59 14.57 15C14.57 15.41 14.23 15.75 13.82 15.75Z" fill="#12498c"></path> </g></svg>
                                    </button>
                                </div>
                                <div>
                                    <!----Separador----->
                                    <div class="vr"></div>
                                </div>
                                <div>
                                    <!------Eliminar Presentacione---->
                                    @can('ingresar-caja')
                                    @if ($item->closed_at == "")
                                    <button title="Ingresar" data-bs-toggle="modal" data-id="{{ $item->id }}" data-bs-target="#aperturarModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="rotate(0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="2.496">
                                                <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                </path>
                                            </g>
                                        </svg>
                                    </button>
                                    @else
                                    <button title="Restaurar" data-bs-toggle="modal" data-bs-target="#aperturarModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark">
                                        <i class="fa-solid fa-rotate"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal -->
                    <div class="modal fade" id="confirmModal-{{$item->id}}" tabindex="-2" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Mensaje de confirmación</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    {{ $item->closed_at == "" ? '¿Seguro que eliminara caja?' : '¿Desea eliminar caja?' }}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="aperturarModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Se va a aperturar Caja</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    {{ $item->closed_at == "" ? '¿Seguro que aperturara caja? Favor verificar que datos coinsidan con caja' : '¿Desea aperturar caja?' }}

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>

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
        $(document).on('click', '.abrirModal', function() {
        var tiendaId = $(this).data('id'); // Obtener el ID de la tienda

        // Realizar una solicitud AJAX para obtener los datos de la tienda
        $.ajax({
            url: '/metodopago/detalle/' + tiendaId, // Cambia a la ruta correspondiente en Laravel
            method: 'GET',
            success: function(data) {
                // Llenar los campos de la ventana modal con los datos obtenidos
                if(data.MetodoPago=='CEF'){
                    $('#CEF').val(data.Monto);
                }else if (data.MetodoPago=='VD'){
                    $('#VD').val(data.Monto);
                }else if(data.MetodoPago=='VO'){
                    $('#VO').val(data.Monto);
                }else if (data.MetodoPago=='D'){
                    $('#D').val(data.Monto);
                }else if (data.MetodoPago=='CC'){
                    $('#CC').val(data.Monto);
                }else if (data.MetodoPago=='OG'){
                    $('#OG').val(data.Monto);
                }else if (data.MetodoPago=='CEI'){
                    $('#CEI').val(data.Monto);
                }else{
                    $('#CEF').val(0);
                    $('#VD').val(0);
                    $('#VO').val(0);
                    $('#D').val(0);
                    $('#CC').val(0);
                    $('#OG').val(0);
                    $('#CEI').val(0);
                }
            },
            error: function(xhr) {
                console.error('Error al obtener los datos:', xhr);
            }
        });
    });
</script>
@endpush
