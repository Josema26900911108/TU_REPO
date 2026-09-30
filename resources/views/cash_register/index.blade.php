@extends('layouts.app')

@section('title','Caja Registradora')
@push('css-datatable')
<link href="https://jsdelivr.net" rel="stylesheet" type="text/css">
<style>
    .modal-fuerza { z-index: 1060 !important; }
    .modal-backdrop { z-index: 1040 !important; }
</style>
@endpush
@push('css')
<script src="https://jsdelivr.net"></script>
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
    <!-- CORREGIDO: Extraer el nombre de la tienda desde el primer registro de la colección de forma segura -->
    @if($cashRegister->isNotEmpty() && isset($cashRegister->first()->tienda))
        <strong>Caja de Almacén:</strong> {{ $cashRegister->first()->tienda->nombre }}
    @else
        <strong>Caja de Almacén:</strong> General
    @endif
</div>

        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped fs-6">
                <thead>
                    <tr>
                        <th>Efectivo Cierre (CEF)</th>
                        <th>Suma Ventas (VD)</th>
                        <th>Otros Medios (VO)</th>
                        <th>Descuentos/Diferencia (D)</th>
                        <th>Ventas Crédito (CC)</th>
                        <th>Otros Gastos (OG)</th>
                        <th>Saldo Inicial (CEI)</th>
                        <th>Creado</th>
                        <th>Modificado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cashRegister as $item)
                    <tr>
                        <td><strong>Q. {{ number_format($item->CEF, 2) }}</strong></td>
                        <td>Q. {{ number_format($item->VD, 2) }}</td>
                        <td>Q. {{ number_format($item->VO, 2) }}</td>
                        <td>
                            <span class="badge {{ $item->D >= 0 ? 'bg-success' : 'bg-danger' }}">
                                Q. {{ number_format($item->D, 2) }}
                            </span>
                        </td>
                        <td>Q. {{ number_format($item->CC, 2) }}</td>
                        <td>Q. {{ number_format($item->OG, 2) }}</td>
                        <td>Q. {{ number_format($item->CEI, 2) }}</td>
                        <td>{{ $item->created_at }}</td>
                        <td>{{ $item->updated_at }}</td>
                        <td>
                            <div class="d-flex justify-content-around align-items-center">
                                <div>
                                    <button title="Opciones" class="btn btn-datatable btn-icon btn-transparent-dark me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg class="svg-inline--fa fa-ellipsis-vertical" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-vertical" role="img" xmlns="http://w3.org" viewBox="0 0 128 512">
                                            <path fill="currentColor" d="M56 472a56 56 0 1 1 0-112 56 56 0 1 1 0 112zm0-160a56 56 0 1 1 0-112 56 56 0 1 1 0 112zM0 96a56 56 0 1 1 112 0A56 56 0 1 1 0 96z"></path>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu text-bg-light" style="font-size: small;">
                                        @can('editar-caja')
                                        <li><a class="dropdown-item" href="{{route('cash.edit',['cash'=>$item])}}">Editar</a></li>
                                        @endcan
                                        @can('eliminar-caja')
                                        <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->idArqueoCaja}}">Eliminar</a></li>
                                        @endcan
                                    </ul>
                                </div>
                                <div class="vr"></div>
                                <div>
                                    <button title="Arqueo de Caja Histórico" data-id="{{ $item->idArqueoCaja }}" data-bs-toggle="modal" data-bs-target="#aperturarModal-{{$item->idArqueoCaja}}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://w3.org" transform="rotate(45)" style="width:20px;"><path d="M22 5V7C22 8.83 21.17 9.82 19.5 9.97C19.34 9.99 19.17 10 19 10H5C4.83 10 4.66 9.99 4.5 9.97C2.83 9.82 2 8.83 2 7V5C2 3 3 2 5 2H19C21 2 22 3 22 5Z" fill="#12498c"></path><path d="M5.5 11.25C4.95 11.25 4.5 11.7 4.5 12.25V19C4.5 21 5 22 7.5 22H16.5C19 22 19.5 21 19.5 19V12.25C19.5 11.7 19.05 11.25 18.5 11.25H5.5ZM13.82 15.75H10.18C9.77 15.75 9.43 15.41 9.43 15C9.43 14.59 9.77 14.25 10.18 14.25H13.82C14.23 14.25 14.57 14.59 14.57 15C14.57 15.41 14.23 15.75 13.82 15.75Z" fill="#12498c"></path></svg>
                                    </button>
                                </div>
                                <div class="vr"></div>
                                <div>
                                    @can('ingresar-caja')
                                    @if ($item->Estatus == "A")
                                    <button title="Ingresar Cierre de Caja" data-id="{{ $item->idArqueoCaja }}" data-bs-toggle="modal" data-bs-target="#aperturarModal-{{$item->idArqueoCaja}}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://w3.org" style="width:20px;"><path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </button>
                                    @else
                                    <button title="Historial Cerrado" class="btn btn-datatable btn-icon btn-transparent-dark" disabled>
                                        <i class="fa-solid fa-rotate text-muted"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>
                    <!-- (Aquí se pegará la parte 2) -->
                    <!-- Modal Eliminar -->
                    <div class="modal fade modal-fuerza" id="confirmModal-{{$item->idArqueoCaja}}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h1 class="modal-title fs-5">Mensaje de confirmación</h1>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    ¿Seguro que desea eliminar este registro de Arqueo de Caja?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL COMPLETO DE ARQUEO CONTABLE DINÁMICO -->
                    <div class="modal fade modal-fuerza" id="aperturarModal-{{$item->idArqueoCaja}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h1 class="modal-title fs-5">📊 Control de Arqueo Contable de Caja</h1>
                                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form class="formCierreCaja" data-id="{{ $item->idArqueoCaja }}">
                                    @csrf
                                    <div class="modal-body bg-light">
                                        <div class="row g-3">
                                            
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label font-weight-bold">Saldo Inicial (CEI):</label>
                                                <input type="text" id="CEIC-{{$item->idArqueoCaja}}" class="form-control font-weight-bold bg-white" readonly value="{{ number_format($item->CEI, 2) }}">
                                            </div>
                                            
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label font-weight-bold">Ventas Totales (VD):</label>
                                                <input type="text" id="VDC-{{$item->idArqueoCaja}}" class="form-control bg-white" readonly>
                                            </div>
                                            
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label font-weight-bold">Otros Medios Habilitados (VO):</label>
                                                <input type="text" id="VOC-{{$item->idArqueoCaja}}" class="form-control bg-white" readonly>
                                            </div>
                                            
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label font-weight-bold">Descuentos (D):</label>
                                                <input type="text" id="DC-{{$item->idArqueoCaja}}" class="form-control bg-white" readonly>
                                            </div>
                                            
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label font-weight-bold">Ventas a Crédito (CC):</label>
                                                <input type="text" id="CCC-{{$item->idArqueoCaja}}" class="form-control bg-white" readonly>
                                            </div>
                                            
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label font-weight-bold">Salidas de Empresa (OG):</label>
                                                <input type="text" id="OGC-{{$item->idArqueoCaja}}" class="form-control bg-white" readonly>
                                            </div>

                                            <div class="col-12 mt-3 pt-2 border-top">
                                                <div class="bg-white p-3 border border-2 border-success rounded">
                                                    @if($item->Estatus == 'A')
                                                        <label for="CEFC-{{$item->idArqueoCaja}}" class="form-label font-weight-bold text-success fs-5">
                                                            💰 Ingrese la Cantidad de Efectivo al Cierre (CEF) *
                                                        </label>
                                                        <input type="number" step="0.01" name="efectivo_fisico_contenedor" id="CEFC-{{$item->idArqueoCaja}}" class="form-control form-control-lg border-success font-weight-bold text-center" placeholder="0.00" required>
                                                        <small class="text-muted">Realice el conteo físico de billetes y monedas en la gaveta antes de guardar.</small>
                                                    @else
                                                        <label class="form-label font-weight-bold text-muted fs-5">Cantidad Efectivo al Cierre Registrado (CEF):</label>
                                                        <input type="text" id="CEFC-{{$item->idArqueoCaja}}" class="form-control form-control-lg text-center font-weight-bold bg-light" readonly value="{{ number_format($item->CEF, 2) }}">
                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="modal-footer bg-white">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        @if($item->Estatus == 'A')
                                            <button type="submit" class="btn btn-success font-weight-bold">🔒 Procesar Cuadre y Cerrar</button>
                                        @endif
                                    </div>
                                </form>
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
<script src="https://jsdelivr.net" type="text/javascript"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>
<script>
$(document).ready(function() {

    // =========================================================================
    // 🚀 1. RECOPILACIÓN ASÍNCRONA UNIFICADA AL ABRIR EL MODAL ($.each)
    // =========================================================================
    $(document).on('click', '.abrirModal', function() {
        var tiendaId = $(this).data('id'); 
        var modalSelector = '#aperturarModal-' + tiendaId;

        // Limpieza preventiva inicial de los campos del modal seleccionado
        $(modalSelector).find('#VDC-' + tiendaId + ', #VOC-' + tiendaId + ', #DC-' + tiendaId + ', #CCC-' + tiendaId + ', #OGC-' + tiendaId).val('0.00');

        Swal.fire({
            title: 'Analizando asientos contables...',
            text: 'Mapeando las marcas del comprobante de este turno',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '/metodopago/detalle/' + tiendaId, 
            method: 'GET',
            success: function(response) {
                Swal.close();
                console.log("Respuesta contable recibida:", response);

                // Forzar a que la respuesta sea tratada como un arreglo iterable
                let itemsContables = Array.isArray(response) ? response : [response];

                // === 🚀 CICLO PROCESADOR CON SWITCH (LLENA TODO SIMULTÁNEAMENTE) ===
                $.each(itemsContables, function(index, item) {
                    if (!item || !item.MetodoPago) return;

                    let valorFormateado = parseFloat(item.Monto || 0).toFixed(2);

                    switch (item.MetodoPago) {
                        case 'VD':
                            $(modalSelector).find('#VDC-' + tiendaId).val(valorFormateado);
                            break;
                        case 'VO':
                            $(modalSelector).find('#VOC-' + tiendaId).val(valorFormateado);
                            break;
                        case 'D':
                            $(modalSelector).find('#DC-' + tiendaId).val(valorFormateado);
                            break;
                        case 'CC':
                            $(modalSelector).find('#CCC-' + tiendaId).val(valorFormateado);
                            break;
                        case 'OG':
                            $(modalSelector).find('#OGC-' + tiendaId).val(valorFormateado);
                            break;
                        case 'CEI':
                            $(modalSelector).find('#CEIC-' + tiendaId).val(valorFormateado);
                            break;
                        case 'CEF':
                            if ($(modalSelector).find('#CEFC-' + tiendaId).prop('readonly')) {
                                $(modalSelector).find('#CEFC-' + tiendaId).val(valorFormateado);
                            }
                            break;
                    }
                });

                // Posicionar automáticamente el cursor en el input de entrada física
                setTimeout(function() {
                    let inputFisico = $(modalSelector).find('#CEFC-' + tiendaId);
                    if (inputFisico.length && !inputFisico.prop('readonly')) {
                        inputFisico.focus().select();
                    }
                }, 300);
            },
            error: function(xhr) {
                Swal.close();
                console.error('Error AJAX Arqueo:', xhr);
                Swal.fire('Error', 'No se pudieron recuperar los balances consolidados.', 'error');
            }
        });
    });

    // =========================================================================
    // 🔒 2. ENVÍO SEGURO DEL FORMULARIO DE CIERRE OPERATIVO POR AJAX
    // =========================================================================
    $(document).on('submit', '.formCierreCaja', function(e) {
        e.preventDefault();
        
        var arqueoId = $(this).data('id');
        var formElement = $(this);
        var botonSubmit = formElement.find('button[type="submit"]');

        // Desactivar el botón para evitar doble submit por clics repetidos
        botonSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        Swal.fire({
            title: 'Procesando cierre contable...',
            text: 'Liquidando saldos del arqueo operativo',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            url: '/cash/cerrar-turno/' + arqueoId, 
            method: 'POST',
            data: formElement.serialize(),
            success: function(res) {
                Swal.close();
                $('#aperturarModal-' + arqueoId).modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Caja Cerrada!',
                    text: 'El turno operativo se liquidó correctamente.',
                }).then(() => {
                    location.reload(); 
                });
            },
            error: function(xhr) {
                Swal.close();
                botonSubmit.prop('disabled', false).text('🔒 Procesar Cuadre y Cerrar');
                
                let msg = "No se pudo completar el arqueo de caja.";
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                Swal.fire('Error de Cuadre', msg, 'error');
            }
        });
    });

    // Control de remoción de backdrops de Bootstrap 4 al cancelar
    $(document).on('click', '[data-bs-dismiss="modal"]', function() {
        $('.modal').modal('hide').css({ 'display': 'none', 'opacity': '0' });
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', 'auto');
    });
});
</script>
@endpush
