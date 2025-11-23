@extends('layouts.app')

@section('title','Ver venta')

@push('css')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<style>
    @media (max-width:575px) {
        #hide-group {
            display: none;
        }
    }

    @media (min-width:576px) {
        #icon-form {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Ver Venta</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('ventas.index')}}">Ventas</a></li>
        <li class="breadcrumb-item active">Ver Venta</li>
    </ol>
</div>

<div class="container-fluid">

    <div class="card mb-4">

        <div class="card-header">
            Datos generales de la venta
        </div>

        <div class="card-body">

            <!---Tipo comprobante--->
            <div class="row mb-4">
                <div class="col-sm-6">
                    <div class="input-group" id="hide-group">
                        <span class="input-group-text"><i class="fa-solid fa-file"></i></span>
                        <input disabled type="text" class="form-control" value="Tipo de comprobante: ">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span title="Tipo de comprobante" id="icon-form" class="input-group-text"><i class="fa-solid fa-file"></i></span>
                        <input disabled type="text" class="form-control" value="{{$venta->comprobante->tipo_comprobante}}">
                    </div>
                </div>
            </div>

            <!---Numero comprobante--->
            <div class="row mb-4">
                <div class="col-sm-6">
                    <div class="input-group" id="hide-group">
                        <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                        <input disabled type="text" class="form-control" value="Número de comprobante: ">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span title="Número de comprobante" id="icon-form" class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                        <input disabled type="text" class="form-control" value="{{$venta->numero_comprobante}}">
                    </div>
                </div>
            </div>

            <!---Cliente--->
            <div class="row mb-4">
                <div class="col-sm-6">
                    <div class="input-group" id="hide-group">
                        <span class="input-group-text"><i class="fa-solid fa-user-tie"></i></span>
                        <input disabled type="text" class="form-control" value="Cliente: ">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span title="Cliente" class="input-group-text" id="icon-form"><i class="fa-solid fa-user-tie"></i></span>
                        <input disabled type="text" class="form-control" value="{{$venta->cliente->persona->razon_social}}">
                    </div>
                </div>
            </div>

            <!---Usuario-->
            <div class="row mb-4">
                <div class="col-sm-6">
                    <div class="input-group" id="hide-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input disabled type="text" class="form-control" value="Vendedor: ">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span title="Vendedor" class="input-group-text" id="icon-form"><i class="fa-solid fa-user"></i></span>
                        <input disabled type="text" class="form-control" value="{{$venta->user->name}}">
                    </div>
                </div>
            </div>

            <!---Fecha--->
            <div class="row mb-4">
                <div class="col-sm-6">
                    <div class="input-group" id="hide-group">
                        <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                        <input disabled type="text" class="form-control" value="Fecha: ">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span title="Fecha" class="input-group-text" id="icon-form"><i class="fa-solid fa-calendar-days"></i></span>
                        <input disabled type="text" class="form-control" value="{{ \Carbon\Carbon::parse($venta->fecha_hora)->format('d-m-Y') }}">
                    </div>
                </div>
            </div>

            <!---Hora-->
            <div class="row mb-4">
                <div class="col-sm-6">
                    <div class="input-group" id="hide-group">
                        <span class="input-group-text"><i class="fa-solid fa-clock"></i></span>
                        <input disabled type="text" class="form-control" value="Hora: ">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span title="Hora" class="input-group-text" id="icon-form"><i class="fa-solid fa-clock"></i></span>
                        <input disabled type="text" class="form-control" value="{{ \Carbon\Carbon::parse($venta->fecha_hora)->format('H:i') }}">
                    </div>

                </div>
            </div>

            <!---Impuesto--->
            <div class="row">
                <div class="col-sm-6">
                    <div class="input-group" id="hide-group">
                        <span class="input-group-text"><i class="fa-solid fa-percent"></i></span>
                        <input disabled type="text" class="form-control" value="Impuesto: ">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="input-group">
                        <span title="Impuesto" class="input-group-text" id="icon-form"><i class="fa-solid fa-percent"></i></span>
                        <input id="input-impuesto" disabled type="text" class="form-control" value="{{ $venta->impuesto }}">
                    </div>

                </div>
            </div>
        </div>


    </div>


    <!---Tabla--->
    <div class="card mb-2">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla de detalle de la venta
        </div>
                            <tr class="col-sm-12" style="text-align: center;">
                        @if ($venta->estado == 2)
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#confirmModalPDF-{{ $venta->id }}">Imprimir PDF
                            <svg width="16" height="16" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.5 6.5V6H2V6.5H2.5ZM6.5 6.5V6H6V6.5H6.5ZM6.5 10.5H6V11H6.5V10.5ZM13.5 3.5H14V3.29289L13.8536 3.14645L13.5 3.5ZM10.5 0.5L10.8536 0.146447L10.7071 0H10.5V0.5ZM2.5 7H3.5V6H2.5V7ZM3 11V8.5H2V11H3ZM3 8.5V6.5H2V8.5H3ZM3.5 8H2.5V9H3.5V8ZM4 7.5C4 7.77614 3.77614 8 3.5 8V9C4.32843 9 5 8.32843 5 7.5H4ZM3.5 7C3.77614 7 4 7.22386 4 7.5H5C5 6.67157 4.32843 6 3.5 6V7ZM6 6.5V10.5H7V6.5H6ZM6.5 11H7.5V10H6.5V11ZM9 9.5V7.5H8V9.5H9ZM7.5 6H6.5V7H7.5V6ZM9 7.5C9 6.67157 8.32843 6 7.5 6V7C7.77614 7 8 7.22386 8 7.5H9ZM7.5 11C8.32843 11 9 10.3284 9 9.5H8C8 9.77614 7.77614 10 7.5 10V11ZM10 6V11H11V6H10ZM10.5 7H13V6H10.5V7ZM10.5 9H12V8H10.5V9ZM2 5V1.5H1V5H2ZM13 3.5V5H14V3.5H13ZM2.5 1H10.5V0H2.5V1ZM10.1464 0.853553L13.1464 3.85355L13.8536 3.14645L10.8536 0.146447L10.1464 0.853553ZM2 1.5C2 1.22386 2.22386 1 2.5 1V0C1.67157 0 1 0.671573 1 1.5H2ZM1 12V13.5H2V12H1ZM2.5 15H12.5V14H2.5V15ZM14 13.5V12H13V13.5H14ZM12.5 15C13.3284 15 14 14.3284 14 13.5H13C13 13.7761 12.7761 14 12.5 14V15ZM1 13.5C1 14.3284 1.67157 15 2.5 15V14C2.22386 14 2 13.7761 2 13.5H1Z" fill="#000000"/>
                            </svg>
                        </button>
                        <br><br>
                        @endif
                    </tr>
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead class="bg-primary text-white">
                    <tr class="align-top">
                        <th class="text-white">Producto</th>
                        <th class="text-white">Cantidad</th>
                        <th class="text-white">Precio de venta</th>
                        <th class="text-white">Descuento</th>
                        <th class="text-white">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($venta->productos as $item)
                    <tr>
                        <td>
                            {{$item->nombre}}
                        </td>
                        <td>
                            {{$item->pivot->cantidad}}
                        </td>
                        <td>
                            {{$item->pivot->precio_venta}}
                        </td>
                        <td>
                            {{$item->pivot->descuento}}
                        </td>
                        <td class="td-subtotal">
                            {{($item->pivot->cantidad) * ($item->pivot->precio_venta) - ($item->pivot->descuento)}}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5"></th>
                    </tr>
                    <tr>
                        <th colspan="4">Sumas:</th>
                        <th id="th-suma"></th>
                    </tr>
                    <tr>
                        <th colspan="4">IVA:</th>
                        <th id="th-IVA"></th>
                    </tr>
                    <tr>
                        <th colspan="4">Total:</th>
                        <th id="th-total"></th>
                    </tr>

                </tfoot>
            </table>
        </div>
    </div>

</div>

                                        <!-- Modal de confirmación PDF-->
                    <div class="modal fade" id="confirmModalPDF-{{$venta->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                                    <form action="{{ route('arqueocaja.vistapreviapdfventa',['arqueocaja'=>$venta->id]) }}" method="post">

                                        <input type="hidden" name="idcaja" id="idcaja">

                                        <input type="hidden" name="idventa" value="{{ $venta->id }}">
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Confirmar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

@endsection

@push('js')
<script>
    //Variables
    let filasSubtotal = document.getElementsByClassName('td-subtotal');
    let cont = 0;
    let impuesto = $('#input-impuesto').val();

    $(document).ready(function() {
        calcularValores();
    });

    function calcularValores() {
        for (let i = 0; i < filasSubtotal.length; i++) {
            cont += parseFloat(filasSubtotal[i].innerHTML);
        }

        $('#th-suma').html(cont-impuesto);
        $('#th-IVA').html(impuesto);
        $('#th-total').html(round(cont));
    }

    function round(num, decimales = 2) {
        var signo = (num >= 0 ? 1 : -1);
        num = num * signo;
        if (decimales === 0) //con 0 decimales
            return signo * Math.round(num);
        // round(x * 10 ^ decimales)
        num = num.toString().split('e');
        num = Math.round(+(num[0] + 'e' + (num[1] ? (+num[1] + decimales) : decimales)));
        // x * 10 ^ (-decimales)
        num = num.toString().split('e');
        return signo * (num[0] + 'e' + (num[1] ? (+num[1] - decimales) : -decimales));
    }
    //Fuente: https://es.stackoverflow.com/questions/48958/redondear-a-dos-decimales-cuando-sea-necesario
</script>
@endpush
