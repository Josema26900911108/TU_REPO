@extends('layouts.app')

@section('title','Caja Registradora')
@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
<style>
    /* Clases de forzado seguro para Bootstrap 4 */
    .modal-fuerza { z-index: 1060 !important; }
    .modal-backdrop { z-index: 1040 !important; }
</style>
@endpush
@push('css')
<script src="https://jsdelivr.net"></script>
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
                        <!-- 🚀 CORRECCIÓN: Respaldo para mapear tanto en mayúsculas como minúsculas según tu base de datos -->
                        <td><strong>Q. {{ number_format($item->CEF ?? $item->cef ?? 0, 2) }}</strong></td>
                        <td>Q. {{ number_format($item->VD ?? $item->vd ?? 0, 2) }}</td>
                        <td>Q. {{ number_format($item->VO ?? $item->vo ?? 0, 2) }}</td>
                        <td>
                            @php $valD = $item->D ?? $item->d ?? 0; @endphp
                            <span class="badge {{ $valD >= 0 ? 'bg-success' : 'bg-danger' }}">
                                Q. {{ number_format($valD, 2) }}
                            </span>
                        </td>
                        <td>Q. {{ number_format($item->CC ?? $item->cc ?? 0, 2) }}</td>
                        <td>Q. {{ number_format($item->OG ?? $item->og ?? 0, 2) }}</td>
                        <td>Q. {{ number_format($item->CEI ?? $item->cei ?? 0, 2) }}</td>
                        <td>{{ $item->created_at }}</td>
                        <td>{{ $item->updated_at }}</td>
                        <td>
                            @php $idArqueo = $item->idArqueoCaja ?? $item->id; @endphp
                            <div class="d-flex justify-content-around align-items-center">
                                <!-- DROPDOWN OPCIONES -->
                                <div>
                                    <button title="Opciones" class="btn btn-datatable btn-icon btn-transparent-dark me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu text-bg-light" style="font-size: small;">
                                        @can('editar-caja')
                                        <li><a class="dropdown-item" href="{{route('cash.edit',['cash'=>$item])}}">Editar</a></li>
                                        @endcan
                                        @can('eliminar-caja')
                                        <li><a class="dropdown-item" data-toggle="modal" data-target="#confirmModal-{{ $idArqueo }}">Eliminar</a></li>
                                        @endcan
                                    </ul>
                                </div>
                                <div class="vr"></div>
                                
                                <!-- BOTÓN HISTÓRICO (Lupa) -->
                                <div>
                                    <button title="Arqueo de Caja Histórico" data-toggle="modal" data-id="{{ $idArqueo }}" data-target="#aperturarModal-{{ $idArqueo }}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                        <i class="fas fa-search text-primary"></i>
                                    </button>
                                </div>
                                <div class="vr"></div>

                                <!-- BOTÓN ACCIÓN DE CIERRE (Caja Registradora) -->
                                <div>
                                    @can('ingresar-caja')
                                    @php $estatusActual = $item->Estatus ?? $item->estatus; @endphp
                                    @if ($estatusActual == "A")
                                    <button title="Ingresar Cierre de Caja" data-toggle="modal" data-id="{{ $idArqueo }}" data-target="#aperturarModal-{{ $idArqueo }}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                        <i class="fas fa-cash-register text-success"></i>
                                    </button>
                                    @else
                                    <button title="Turno Cerrado" class="btn btn-datatable btn-icon btn-transparent-dark" disabled>
                                        <i class="fas fa-lock text-muted"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>
                    <!-- Modal Eliminar -->
                    <div class="modal fade modal-fuerza" id="confirmModal-{{ $idArqueo }}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Mensaje de confirmación</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    ¿Seguro que desea eliminar este registro de Arqueo de Caja?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL COMPLETO DE ARQUEO INTERACTIVO -->
                    <div class="modal fade modal-fuerza" id="aperturarModal-{{ $idArqueo }}" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">📊 Control de Arqueo Contable de Caja</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body bg-light">
                                    <div class="row g-3">
                                        
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label font-weight-bold">Saldo Inicial (CEI):</label>
                                            <input type="text" id="CEIC-{{ $idArqueo }}" class="form-control font-weight-bold bg-white" readonly value="{{ number_format($item->CEI ?? $item->cei ?? 0, 2) }}">
                                        </div>
                                        
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label font-weight-bold">Ventas Totales (VD):</label>
                                            <input type="text" id="VDC-{{ $idArqueo }}" class="form-control bg-white" readonly value="0.00">
                                        </div>
                                        
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label font-weight-bold">Otros Medios Habilitados (VO):</label>
                                            <input type="text" id="VOC-{{ $idArqueo }}" class="form-control bg-white" readonly value="0.00">
                                        </div>
                                        
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label font-weight-bold">Descuentos (D):</label>
                                            <input type="text" id="DC-{{ $idArqueo }}" class="form-control bg-white" readonly value="0.00">
                                        </div>
                                        
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label font-weight-bold">Ventas a Crédito (CC):</label>
                                            <input type="text" id="CCC-{{ $idArqueo }}" class="form-control bg-white" readonly value="0.00">
                                        </div>
                                        
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label font-weight-bold">Salidas de Empresa (OG):</label>
                                            <input type="text" id="OGC-{{ $idArqueo }}" class="form-control bg-white" readonly value="0.00">
                                        </div>

                                        <div class="col-12 mt-3 pt-2 border-top">
                                            <div class="bg-white p-3 border border-2 border-success rounded">
                                                @php $estatusActual = $item->Estatus ?? $item->estatus; @endphp
                                                @if($estatusActual == 'A')
                                                    <label for="CEFC-{{ $idArqueo }}" class="form-label font-weight-bold text-success fs-5">
                                                        💰 Ingrese la Cantidad de Efectivo al Cierre (CEF) *
                                                    </label>
                                                    <input type="number" step="0.01" id="CEFC-{{ $idArqueo }}" class="form-control form-control-lg border-success font-weight-bold text-center" placeholder="0.00" required>
                                                    <small class="text-muted">Realice el conteo físico de la gaveta antes de procesar el cuadre contable.</small>
                                                @else
                                                    <label class="form-label font-weight-bold text-muted fs-5">Cantidad Efectivo al Cierre Registrado (CEF):</label>
                                                    <input type="text" id="CEFC-{{ $idArqueo }}" class="form-control form-control-lg text-center font-weight-bold bg-light" readonly value="{{ number_format($item->CEF ?? $item->cef ?? 0, 2) }}">
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="modal-footer bg-white">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                    @if($estatusActual == 'A')
                                        <button type="button" class="btn btn-success font-weight-bold btn-guardar-cierre" data-id="{{ $idArqueo }}">🔒 Procesar Cuadre y Cerrar</button>
                                    @endif
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
<script src="https://jsdelivr.net" type="text/javascript"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>
<script src="https://jsdelivr.net"></script>

<script>
$(document).ready(function() {

    // =========================================================================
    // 🚀 1. RECOPILACIÓN CONTABLE AL ABRIR EL MODAL (USANDO TU NUEVA RUTA POST)
    // =========================================================================
    $(document).on('click', '.abrirModal', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var cajaId = $(this).data('id') || $(this).attr('data-id'); 
        var modalSelector = '#aperturarModal-' + cajaId;

        if (!cajaId) {
            Swal.fire('Error', 'No se pudo capturar el ID del Arqueo.', 'error');
            return false;
        }

        // Limpieza preventiva inicial de los inputs de ese modal específico
        $(modalSelector).find('#VDC-' + cajaId + ', #VOC-' + cajaId + ', #DC-' + cajaId + ', #CCC-' + cajaId + ', #OGC-' + cajaId).val('0.00');

        Swal.fire({
            title: 'Sincronizando balances contables...',
            text: 'Mapeando asientos del turno activo',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        $.ajax({
            // 🚀 CORRECCIÓN: Apuntamos exactamente a la ruta POST que me compartiste
            url: "{{ route('detallecomprobante.update-arqueo') }}", 
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id: cajaId // Enviamos el ID de la caja para que lo procese tu controlador
            },
            success: function(data) {
                Swal.close();
                console.log("Respuesta contable cargada con éxito:", data);

                let items = Array.isArray(data) ? data : [data];

                // Ciclo procesador interactivo
                $.each(items, function(index, item) {
                    if (!item || !item.MetodoPago) return;

                    let montoFormateado = parseFloat(item.Monto || 0).toFixed(2);

                    switch (item.MetodoPago) {
                        case 'VD':
                            $(modalSelector).find('#VDC-' + cajaId).val(montoFormateado);
                            break;
                        case 'VO':
                            $(modalSelector).find('#VOC-' + cajaId).val(montoFormateado);
                            break;
                        case 'D':
                            $(modalSelector).find('#DC-' + cajaId).val(montoFormateado);
                            break;
                        case 'CC':
                            $(modalSelector).find('#CCC-' + cajaId).val(montoFormateado);
                            break;
                        case 'OG':
                            $(modalSelector).find('#OGC-' + cajaId).val(montoFormateado);
                            break;
                        case 'CEI':
                            $(modalSelector).find('#CEIC-' + cajaId).val(montoFormateado);
                            break;
                        case 'CEF':
                            if ($(modalSelector).find('#CEFC-' + cajaId).prop('readonly')) {
                                $(modalSelector).find('#CEFC-' + cajaId).val(montoFormateado);
                            }
                            break;
                    }
                });

                // Autofoco en el input verde de efectivo físico
                setTimeout(function() {
                    let inputFisico = $(modalSelector).find('#CEFC-' + cajaId);
                    if (inputFisico.length && !inputFisico.prop('readonly')) {
                        inputFisico.focus().select();
                    }
                }, 300);
            },
            error: function(xhr) {
                Swal.close();
                console.error("Error en ArqueoCajaController:", xhr.responseText);
                Swal.fire('Error Contable', 'No se pudieron recuperar los balances del turno.', 'error');
            }
        });
    });

    // =========================================================================
    // 🔒 2. PROCESAR EL CIERRE FINAL POR AJAX AL DAR CLIC AL BOTÓN VERDE
    // =========================================================================
    $(document).on('click', '.btn-guardar-cierre', function(e) {
        e.preventDefault();
        
        var arqueoId = $(this).data('id');
        var modalSelector = '#aperturarModal-' + arqueoId;
        var efectivoContado = $(modalSelector).find('#CEFC-' + arqueoId).val();

        if(!efectivoContado || efectivoContado === "" || isNaN(efectivoContado)) {
            Swal.fire('Atención', 'Por favor ingrese una cantidad válida de efectivo.', 'warning');
            return false;
        }

        Swal.fire({
            title: '¿Desea cerrar el turno de caja?',
            text: "Esta acción congelará los balances del arqueo.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, Cerrar Caja',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                
                Swal.fire({
                    title: 'Procesando cuadre...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: '/cash/cerrar-turno/' + arqueoId,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        efectivo_fisico_contenedor: efectivoContado
                    },
                    success: function(res) {
                        Swal.close();
                        $(modalSelector).modal('hide');
                        Swal.fire('¡Éxito!', 'Turno cerrado correctamente.', 'success')
                            .then(() => location.reload());
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire('Error', xhr.responseJSON.error || 'No se pudo liquidar el turno de caja.', 'error');
                    }
                });
            }
        });
    });

    // Remoción de backdrops de Bootstrap 4
    $(document).on('click', '[data-dismiss="modal"]', function() {
        $('.modal').modal('hide').css({ 'display': 'none', 'opacity': '0' });
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', 'auto');
    });
});
</script>
@endpush
