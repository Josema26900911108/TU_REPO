@extends('layouts.app')

@section('title','Realizar compra')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="{{ asset('js/math.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Detalle Comprobante</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('comprobante.index')}}">Comprobantes</a></li>
        <li class="breadcrumb-item active">Editar Detalle Comprobante</li>
    </ol>
</div>

<form action="{{ route('detallecomprobante.store') }}" method="post">
    @csrf

    <div class="container-lg mt-4">
        <div class="row gy-4">
            <!------Detalle Comprobante producto---->
            <div class="col-xl-12">
                <div class="text-white bg-primary p-1 text-center">
                    Detalles de Comprobante
                </div>
                <div class="p-3 border border-3 border-primary">

                    <div class="col-sm-12 mb-4">
                            <label for="nombre" class="form-label">Nombre Comprobante:</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" maxlength="225" value="{{old('nombre',$comprobante->tipo_comprobante)}}">
                        </div>


                        <!-----Tabla para el detalle de la compra--->
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="tabla_detalle" class="table table-hover">
                                    <thead class="bg-primary">
                                        <tr>
                                            <th class="text-white">#</th>
                                            <th class="text-white">Cuenta Contable</th>
                                            <th class="text-white">Naturaleza</th>
                                            <th class="text-white">Formula</th>
                                            <th class="text-white">Minimo</th>
                                            <th class="text-white">Descripcion</th>
                                            <th class="text-white">Tipo Arqueo</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($detallecomprobante as $item)
                                        <tr>
                                            <td>
                                                {{$item->id}}
                                            </td>
                                            <td>
                                                <!-- Mostramos el nombre de la cuenta de manera segura -->
                                                {{ $item->cuentaContable->nombre ?? 'Cuenta no asignada o eliminada' }}
                                            </td>                                      
                                            <td>
                                                {{$item->Naturaleza}}
                                            </td>
                                            <td>
                                                {{$item->formula}}
                                            </td>
                                            <td>
                                                {{$item->valorminimo}}
                                            </td>
                                            <td>
                                                {{($item->nombre)}}
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm select-tipo-arqueo" data-id="{{ $item->id }}">
                                                    <option value="NO_APLICA" {{ is_null($item->tipo_arqueo) ? 'selected' : '' }}>No aplica</option>
                                                    <option value="CE" {{ $item->tipo_arqueo == 'CE' ? 'selected' : '' }}>CE - Cuenta de Efectivo</option>
                                                    <option value="VO" {{ $item->tipo_arqueo == 'VO' ? 'selected' : '' }}>VO - Cuenta de Otros Medios</option>
                                                    <option value="CC" {{ $item->tipo_arqueo == 'CC' ? 'selected' : '' }}>CC - Cuenta de Ventas a Crédito</option>
                                                    <option value="D"  {{ $item->tipo_arqueo == 'D' ? 'selected' : '' }}>D - Cuenta de Descuentos</option>
                                                    <option value="OG" {{ $item->tipo_arqueo == 'OG' ? 'selected' : '' }}>OG - Cuenta de Otros Gastos</option>
                                                    <option value="CH" {{ $item->tipo_arqueo == 'CH' ? 'selected' : '' }}>CH - Cuenta de Cheques</option>
                                                    <option value="VA" {{ $item->tipo_arqueo == 'VA' ? 'selected' : '' }}>VA - Cuenta de Vales</option>
                                                </select>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal para cancelar la compra -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Advertencia</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Seguro que quieres cancelar la compra?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button id="btnCancelarCompra" type="button" class="btn btn-danger" data-bs-dismiss="modal">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

</form>
@endsection

@push('js')
<!-- Bootstrap Select -->
<script src="https://cloudflare.com"></script>
<!-- SweetAlert2 -->
<script src="https://cloudflare.com"></script>

<script>
$(document).ready(function() {
    function mostrarAlerta(tipo, titulo, mensaje) {
        if (typeof Swal !== 'undefined') {
            if (tipo === 'toast') {
                Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                }).fire({ icon: 'success', title: titulo });
            } else {
                // Usamos un diseño más amplio si el mensaje de error es muy largo (como los de SQL)
                Swal.fire({ 
                    icon: tipo, 
                    title: titulo, 
                    text: mensaje,
                    customClass: {
                        htmlContainer: 'text-start text-monospace text-danger bg-light p-2 border'
                    }
                });
            }
        } else {
            alert(titulo + "\n\n" + mensaje);
        }
    }

    $('.select-tipo-arqueo').on('change', function() {
        let detalleId = $(this).data('id');
        let nuevoTipo = $(this).val();
        let selectElement = $(this);

        selectElement.prop('disabled', true);

        $.ajax({
            url: "{{ route('detallecomprobante.update-arqueo') }}", 
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: detalleId,
                tipo_arqueo: nuevoTipo
            },
            success: function(response) {
                selectElement.prop('disabled', false);
                mostrarAlerta('toast', 'Tipo de arqueo actualizado con éxito', '');
            },
            error: function(xhr) {
                selectElement.prop('disabled', false);
                
                // Inicializamos la variable con un texto genérico por si no viene nada en la respuesta
                let mensajeDetallado = "No se obtuvo más información del servidor.";

                // 1. Intentar obtener el mensaje detallado que envía Laravel en modo debug
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.error) {
                        // Captura el mensaje personalizado de nuestro try-catch del controlador
                        mensajeDetallado = xhr.responseJSON.error;
                    } else if (xhr.responseJSON.message) {
                        // Captura la excepción nativa de Laravel (como el SQLSTATE completo)
                        mensajeDetallado = xhr.responseJSON.message;
                    }
                } else if (xhr.responseText) {
                    // Si Laravel devolvió código HTML (como la pantalla de error clásica de Ignition)
                    // intentamos limpiar un poco el texto plano
                    mensajeDetallado = xhr.responseText.substring(0, 300) + "...";
                }
                
                // 2. Clasificar la alerta según el código de estado HTTP
                if(xhr.status === 422) {
                    let erroresValidacion = xhr.responseJSON && xhr.responseJSON.errors 
                        ? JSON.stringify(xhr.responseJSON.errors, null, 2) 
                        : '';
                    mostrarAlerta('error', 'Error de Validación (422)', 'Los datos enviados no pasaron las reglas: \n' + erroresValidacion);
                } else if(xhr.status === 500) {
                    // Muestra "Error Interno (500)" y abajo el texto exacto de MySQL/PHP
                    mostrarAlerta('error', 'Error Interno (500)', mensajeDetallado);
                } else {
                    mostrarAlerta('error', 'Error Técnico (' + xhr.status + ')', mensajeDetallado);
                }
            }
        });
    });
});
</script>
@endpush
