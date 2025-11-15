@extends('layouts.app')

@section('title','Realizar venta')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/math.js') }}"></script>
<style>
    .form-control {
        width: 100%;
        border: none;
        background: transparent;
        padding: 0.375rem 0.75rem;
    }

    .form-control:focus {
        outline: none;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 4px;
    }

    .small-text {
        font-size: 0.875rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Realizar Venta</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('ventas.index')}}">Ventas</a></li>
        <li class="breadcrumb-item active">Realizar Venta</li>
    </ol>
</div>

<form action="{{ route('ventas.storeCC') }}" method="post">
    @csrf
    <div class="container-lg mt-4">
        <div class="row gy-4">

            <!------venta producto---->
            <div class="col-xl-8">
                <div class="text-white bg-primary p-1 text-center">
                    Detalles de la venta
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cart4" viewBox="0 0 16 16">
                        <path d="M0 2.5A.5.5 0 0 1 .5 2H2a.5.5 0 0 1 .485.379L2.89 4H14.5a.5.5 0 0 1 .485.621l-1.5 6A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.485-.379L1.61 3H.5a.5.5 0 0 1-.5-.5M3.14 5l.5 2H5V5zM6 5v2h2V5zm3 0v2h2V5zm3 0v2h1.36l.5-2zm1.11 3H12v2h.61zM11 8H9v2h2zM8 8H6v2h2zM5 8H3.89l.5 2H5zm0 5a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0m9-1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m-2 1a2 2 0 1 1 4 0 2 2 0 0 1-4 0"/>
                      </svg>
                </div>
                <div class="p-3 border border-3 border-primary">
                    <div class="row gy-4">

                        <!-----Producto---->
                        <div class="col-12">
                            <select name="producto_id" id="producto_id" class="form-control selectpicker" data-live-search="true" data-size="1" title="Busque un producto aquí">
                                @foreach ($productos as $item)
                                <option value="{{$item->id}}-{{$item->stock}}-{{$item->precio_venta}}">{{$item->codigo.' '.$item->nombre}}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-----Stock--->
                        <div class="d-flex justify-content-end">
                            <div class="col-12 col-sm-6">
                                <div class="row">
                                    <label for="stock" class="col-form-label col-4">Stock:</label>
                                    <div class="col-8">
                                        <input disabled id="stock" type="text" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-----Precio de venta---->
                        <div class="col-sm-4">
                            <label for="precio_venta" class="form-label">Precio de venta:</label>
                            <input disabled type="number" name="precio_venta" id="precio_venta" class="form-control" step="0.1">
                        </div>

                        <!-----Cantidad---->
                        <div class="col-sm-4">
                            <label for="cantidad" class="form-label">Cantidad:</label>
                            <input type="number" name="cantidad" id="cantidad" class="form-control">
                        </div>

                        <!----Descuento---->
                        <div class="col-sm-4">
                            <label for="descuento" class="form-label">Descuento:</label>
                            <input type="number" name="descuento" id="descuento" class="form-control">
                        </div>

                        <!----idventa---->

                            <input type="hidden" name="idventa" id="idventa" class="form-control" value="{{ $idventa }}">

                        <!-----botón para agregar--->
                        <div class="col-12 text-end">
                            <button id="btn_agregar" class="btn btn-primary" type="button">Agregar</button>
                        </div>

                        <!-----Tabla para el detalle de la venta--->
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="tabla_detalle" class="table table-hover">
                                    <thead class="bg-primary">
                                        <tr>
                                            <th class="text-white">#</th>
                                            <th class="text-white">Producto</th>
                                            <th class="text-white">Cantidad</th>
                                            <th class="text-white">Precio venta</th>
                                            <th class="text-white">IVA</th>
                                            <th class="text-white">Descuento</th>
                                            <th class="text-white">Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_detalle_tbody">
                                        <tr>
                                            <th></th>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2">Cantidad Articulos</th>
                                            <th colspan="2"><span id="sumas">0</span></th>

                                            <th colspan="2">Total</th>
                                            <th colspan="2"> <input type="hidden" name="total" value="0" id="inputTotal"> <span id="total">0</span></th>
                                        </tr>
                                    </tfoot>
                                </table>
                                <table class="table table-hover">
                                    <div id="contenedor-dinamico"></div>

                                    <div class="text-white bg-secondary p-1 text-center">
                                        FOLIO
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-book-half" viewBox="0 0 16 16">
                                            <path d="M8.5 2.687c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/>
                                          </svg>
                                          CONTABLE (Comprobante)
                                    </div>
                                    <thead class="bg-info">
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            <th class="text-white">Nombre</th>
                                            <th class="text-white">Fórmula</th>
                                            <th class="text-white">Valor Mínimo</th>
                                            <th class="text-white">Naturaleza</th>
                                        </tr>
                                    </thead>
                                    <label id="msj" for="detalle_tbody" class="form-label"></label>

                                    <tbody id="detalle_tbody">
                                        <!-- Los detalles del comprobante se cargarán aquí -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!--Boton para cancelar venta--->
                        <div class="col-12">
                            <button id="cancelar" type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                Cancelar venta
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-----Venta---->
            <div class="col-xl-4">
                <div class="text-white bg-success p-1 text-center">
                    Datos generales
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                      </svg>
                </div>
                <div class="p-3 border border-3 border-success">
                    <div class="row gy-4">
                        <!--Cliente-->
                        <div class="col-12">
                            <label for="cliente_id" class="form-label">Cliente:</label>
                            <select name="cliente_id" id="cliente_id" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona" data-size='2'>
                                @foreach ($clientes as $item)
                                    <option value="{{ $item->id }}" {{ $item->id == $selectedItemId ? 'selected' : '' }}>
                                        {{ $item->persona->razon_social ?? 'Sin Razón Social' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cliente_id')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Tipo de comprobante-->
                        <div class="col-12">
                            <label for="comprobante_id" class="form-label">Comprobante:</label>
                            <select name="comprobante_id" id="comprobante_id" class="form-control selectpicker" title="Selecciona">

                            @foreach ($comprobantes as $item)
                            <option value="{{ $item->id }}" {{ $item->id == $selectedItemIdcomp ? 'selected' : '' }}>{{$item->tipo_comprobante}}</option>
                                @endforeach
                            </select>
                            @error('comprobante_id')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>
                                                <!--Tipo Folio-->
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label for="TipoFolio">Tipo de Folio:</label>
                                                        <div id="TipoFolio">
                                                            <label class="radio-inline">
                                                                <input type="radio" id="TipoFolio" name="TipoFolio" value="A" checked> Automatico
                                                            </label>
                                                            <label class="radio-inline">
                                                                <input type="radio" id="TipoFolio" name="TipoFolio" value="M"> Manual
                                                            </label>
                                                            <label class="radio-inline">
                                                                <input type="radio" id="TipoFolio" name="TipoFolio" value="F"> Folio Manual
                                                            </label>
                                                        </div>
                                                    @error('TipoFolio')
                                                    <small class="text-danger">{{ '*'.$message }}</small>
                                                    @enderror
                                                </div>

                        <!--MontoFolio---->
                        <div class="col-sm-6">
                            <label for="MontoFolio" class="form-label">Monto:</label>
                            <input readonly type="text" name="MontoFolio" id="MontoFolio" class="form-control border-success">
                            @error('MontoFolio')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Numero de comprobante-->
                        <div class="col-12">
                            <label for="numero_comprobante" class="form-label">Numero de comprobante:</label>
                            <input type="text" readonly name="numero_comprobante" id="numero_comprobante" class="form-control" value="{{ $comprobantenumero }}">
                            @error('numero_comprobante')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                                                <!--Numero de factura-->
                        <div class="col-12">
                            <label for="numero_factura" class="form-label">Numero de comprobante:</label>
                            <input type="text" readonly name="numero_factura" id="numero_factura" class="form-control">
                            @error('numero_factura')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Impuesto---->
                        <div class="col-sm-6">
                            <label for="impuesto" class="form-label">Impuesto(IVA):</label>
                            <input readonly type="text" name="impuesto" id="impuesto" class="form-control border-success">
                            @error('impuesto')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Fecha--->
                        <div class="col-sm-6">
                            <label for="fecha" class="form-label">Fecha:</label>
                            <input readonly type="date" name="fecha" id="fecha" class="form-control border-success" value="<?php echo date("Y-m-d") ?>">
                            <?php

                            use Carbon\Carbon;

                            $fecha_hora = Carbon::now()->toDateTimeString();
                            ?>
                            <input type="hidden" name="fecha_hora" value="{{$fecha_hora}}">
                        </div>

                        <!----User--->
                        <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">

                        <!--Botones--->
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success" id="guardar">Realizar venta</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para cancelar la venta -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Advertencia</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Seguro que quieres cancelar la venta?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button id="btnCancelarVenta" type="button" class="btn btn-danger" data-bs-dismiss="modal">Confirmar</button>
                </div>
            </div>
        </div>
    </div>



</form>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script>

const contenedor = document.getElementById('contenedor-dinamico');

        //Variables
        let cont = 0;
        let idventacabecera=0;
        let contcc = 0;
        let MontoFol = [];
        let subtotal = [];
        let subiva = [];
        let sumas = 0;
        let sumadocdb = 0;
        let IVA = 0;
        let total = 0;
        let formulas = [];
        let monto = [];
        let tipo = [];
        let cuenta = [];
        let idcuenta = [];
        let producto= [];
        let Cantidad= [];
        let Descuento= [];
        let preciocompra= [];
        let precioventa= [];
        let nombre= [];
        let resultadoiva=0;
        let cantidadarticulos = 0;
        let formula='';
        totalMASIVA = 0;
        let formulaEvaluadaiva='';
        const plantilla = document.getElementById('plantilla-select');

    //Constantes
    const impuesto = 12;

    $(document).ready(function() {


$('#btn_agregar').click(function() {
    agregarProducto();
});

$('#btn_agregarCC').click(function() {
    agregarCuentaC();
});

$('#btnCancelarCompra').click(function() {
    cancelarCompra();
});

disableButtons();
$('#TipoFolio').on('change', function(){
    let tipoFolioSeleccionado = document.querySelector('input[name="TipoFolio"]:checked').value;
    var tableBody = $('#detalle_tbody');
    tableBody.empty();

    var tableDINAMICO = $('#contenedor-dinamico');
    tableDINAMICO.empty();

   if (tipoFolioSeleccionado === "M") {
    var tableBody = $('#detalle_tbody');
    tableBody.empty();
    var comprobanteId = document.getElementById('comprobante_id').value;

if (comprobanteId) {
    $.ajax({
        url: '/compras/detalles/' + comprobanteId + '',
        type: 'GET',
        success: function(response) {
            var detalles = response.detalles;
            var tableBody = $('#detalle_tbody');
            tableBody.empty();

            // Iterar sobre los detalles y agregar filas a la tabla
            $.each(detalles, function(index, detalle) {

                var row = '<tr>' +
                    '<td></td>' +
                    '<td></td>' +
                    '<td>' + detalle.cuenta_contable_nombre + '</td>' +
                    '<td class="small-text">N/A</td>' +
                    '<td><input type="number" class="form-control" value="' + detalle.valorminimo + '"></td>' +
                    '<td>' + detalle.Naturaleza + '</td>' +
                    '</tr>';
                tableBody.append(row);
                formulas[index]=detalle.formula;
                monto[index]=detalle.valorminimo;
                cuenta[index]=detalle.cuenta_contable_nombre;
                idcuenta[index]=detalle.id;
                tipo[index]=detalle.Naturaleza;
                formula=detalle.formuladoc;

                sumarArreglosInput(formulas,monto);

            });

            llenarTablaventas();
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar los detalles:", error);
        }
    });
}
}else if (tipoFolioSeleccionado === "A") {
    var comprobanteId = document.getElementById('comprobante_id').value;;

if (comprobanteId) {
    $.ajax({
        url: '/compras/detalles/' + comprobanteId + '',
        type: 'GET',
        success: function(response) {
            var detalles = response.detalles;
            var tableBody = $('#detalle_tbody');
            tableBody.empty();

            // Iterar sobre los detalles y agregar filas a la tabla
            $.each(detalles, function(index, detalle) {

                var row = '<tr>' +
                    '<td></td>' +
                    '<td></td>' +
                    '<td>' + detalle.cuenta_contable_nombre + '</td>' +
                    '<td class="small-text">' + detalle.formula + '</td>' +
                    '<td>' + detalle.valorminimo + '</td>' +
                    '<td>' + detalle.Naturaleza + '</td>' +
                    '</tr>';
                tableBody.append(row);
                formulas[index]=detalle.formula;
                monto[index]=detalle.valorminimo;
                cuenta[index]=detalle.cuenta_contable_nombre;
                idcuenta[index]=detalle.id;
                tipo[index]=detalle.Naturaleza;
                formula=detalle.formuladoc;

                sumarArreglos(formulas,monto);

            });

            llenarTablaventas();
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar los detalles:", error);
        }
    });
}
}
else if (tipoFolioSeleccionado === "F") {

    var tableBody = $('#detalle_tbody');
                formulas=[];
                monto=[];
                cuenta=[];
                idcuenta=[];
                tipo=[];
                formula=[];
                contcc=0;
    tableBody.empty();
    const nuevoDiv = crearNuevoDiv();


                // Agregar el nuevo div al contenedor
                contenedor.appendChild(nuevoDiv);

                // Reinicializar el Selectpicker para los nuevos elementos
                $(nuevoDiv).find('.selectpicker').selectpicker('refresh');

}
});

$('#comprobante_id').on('change', function() {

let tipoFolioSeleccionado = document.querySelector('input[name="TipoFolio"]:checked').value;

if (tipoFolioSeleccionado === "M") {

    return;
}

    var comprobanteId = $(this).val();

if (comprobanteId) {
    $.ajax({
        url: '/compras/detalles/' + comprobanteId + '',
        type: 'GET',
        success: function(response) {
            var detalles = response.detalles;
            var tableBody = $('#detalle_tbody');
            tableBody.empty();

            // Iterar sobre los detalles y agregar filas a la tabla
            $.each(detalles, function(index, detalle) {

                var row = '<tr>' +
                    '<td></td>' +
                    '<td></td>' +
                    '<td>' + detalle.cuenta_contable_nombre + '</td>' +
                    '<td class="small-text">' + detalle.formula + '</td>' +
                    '<td>' + detalle.valorminimo + '</td>' +
                    '<td>' + detalle.Naturaleza + '</td>' +
                    '</tr>';
                tableBody.append(row);
                formulas[index]=detalle.formula;
                monto[index]=detalle.valorminimo;
                cuenta[index]=detalle.cuenta_contable_nombre;
                idcuenta[index]=detalle.id;
                tipo[index]=detalle.Naturaleza;
                formula=detalle.formuladoc;

                sumarArreglos(formulas,monto);

            });

            llenarTablaventas();
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar los detalles:", error);
        }
    });
}
});

$('#producto_id').change(mostrarValores);


        $('#btn_agregar').click(function() {
            agregarProducto();
        });

        $('#btnCancelarVenta').click(function() {
            cancelarVenta();
        });

        disableButtons();

        $('#impuesto').val(impuesto + '%');
    });


            //Reiniciar valores de las variables
            cont = 0;
            subtotal = [];
            subiva = [];
            sumas = 0;
            IVA = 0;
            total = 0;
            totalMASIVA = 0;
            cantidadarticulos=0;

    function mostrarValores() {
        let dataProducto = document.getElementById('producto_id').value.split('-');
        $('#stock').val(dataProducto[1]);
        $('#precio_venta').val(dataProducto[2]);
    }

    function llenarTablaventas() {
        var tableBodyDetalle = $('#tabla_detalle_tbody');
        tableBodyDetalle.empty(); // Clear the table body

        $.each(producto, function(index) {
            let precioVentaActual = subtotal[index];

            let resultadoIva =  CalcularFormula(formula, precioVentaActual);
            subiva[index]=resultadoIva;
            // Construct the row for the table
            var fila = '<tr id="fila' + index + '">' +
                '<th>' + (index + 1) + '</th>' +
                            '<td><input type="hidden" name="arrayidproducto[]" value="' + producto[index] + '">' + nombre[index] + '</td>' +
                            '<td><input type="hidden" name="arraycantidad[]" value="' + Cantidad[index] + '">' + Cantidad[index] + '</td>' +
                            '<td><input type="hidden" name="arrayprecioventa[]" value="' + precioventa[index] + '">' + precioventa[index] + '</td>' +
                            '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[index] + '">' + subiva[index] + '</td>' +
                            '<td><input type="hidden" name="arraydescuento[]" value="' + Descuento[index] + '">' + Descuento[index] + '</td>' +
                            '<td>' + subtotal[index] + '</td>' +
                            '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                            '</tr>';

            // Append the row to the table body
            tableBodyDetalle.append(fila);
        });
        IVA=CalcularFormula(formula, total);

        $('#impuesto').val(IVA);
    }

    function CalcularFormula(formulalocal, montoA) {
            // Reemplazar "A" en la fórmula con el valor de la variable A
            formulaEvaluadaiva = formulalocal.replace(/A/g, montoA);
            // Evaluar la fórmula usando math.js
            resultadoiva = math.evaluate(formulaEvaluadaiva);
            // Redondear el resultado a 2 decimales
            resultadoiva = parseFloat(resultadoiva.toFixed(2));
            return resultadoiva;
    }

    function agregarProductoAutomatico() {

        var comprobante = document.getElementById('comprobante_id').value;

        if (comprobante === "") {
            alert("Por favor, seleccione un comprobante.");
            return false; // Detiene la ejecución de la función
            }

        var ventacabecera = @json($ventacabecera);
        // Suponiendo que ventacabecera tiene objetos con la propiedad 'producto_id' como identificador único
var ventacabeceraUnico = ventacabecera.filter((value, index, self) =>
    index === self.findIndex((t) => (
        t.id === value.id  // Comparar por producto_id
    ))
);

console.log(ventacabeceraUnico); // Ahora 'ventacabeceraUnico' debería contener solo objetos únicos


ventacabeceraUnico.forEach(function(item) {
idventacabecera=item.idventa;
        let idProducto=item.producto_id;
        let cantidad=item.cantidad;
        let descuento=item.descuento;
        let stock=item.stock;
        let precioVenta=item.precio_venta;
        let formula=item.formula;
        let nameProducto=item.nameProducto;
            if (idProducto != '' && cantidad != '') {

            //2. Para que los valores ingresados sean los correctos
            if (parseInt(cantidad) > 0 && (cantidad % 1 == 0) && parseFloat(descuento) >= 0) {

                //3. Para que la cantidad no supere el stock
                if (parseInt(cantidad) <= parseInt(stock)) {
                    //Calcular valores

                    subtotal[cont] = round(cantidad * precioVenta - descuento);
                    sumas += cantidad;
                    total+=subtotal[cont];

                    sumadocdb=round(total);
                    subiva[cont]=CalcularFormula(formula,subtotal[cont]);
                    resultadoiva=CalcularFormula(formula,total);
                    IVA = resultadoiva;
                    //Crear la fila
                    let fila = '<tr id="fila' + cont + '">' +
                        '<th>' + (cont + 1) + '</th>' +
                        '<td><input type="hidden" name="arrayidproducto[]" value="' + idProducto + '">' + nameProducto + '</td>' +
                        '<td><input type="hidden" name="arraycantidad[]" value="' + cantidad + '">' + cantidad + '</td>' +
                        '<td><input type="hidden" name="arrayprecioventa[]" value="' + precioVenta + '">' + precioVenta + '</td>' +
                        '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[cont] + '">' + subiva[cont] + '</td>' +
                        '<td><input type="hidden" name="arraydescuento[]" value="' + descuento + '">' + descuento + '</td>' +
                        '<td>' + subtotal[cont] + '</td>' +
                        '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                        '</tr>';

                    //Acciones después de añadir la fila
                    $('#tabla_detalle_tbody').append(fila);
                    limpiarCampos();

                    disableButtons();

                    resultadoiva = resultadoiva.toFixed(2);

            producto[cont]=idProducto;
            Cantidad[cont]=cantidad;
            precioventa[cont]=parseFloat(precioVenta);
            nombre[cont]=nameProducto;
            cantidadarticulos+=parseInt(Cantidad[cont],15);
            Descuento[cont]=descuento;
            cont++;
            sumarArreglos(formulas,monto);

                    //Mostrar los campos calculados
                    $('#sumas').html(sumas);
                    $('#IVA').html(IVA);
                    $('#total').html(total);
                    $('#subiva').val(subiva);
                    $('#impuesto').val(IVA);
                    $('#inputTotal').val(total);
                } else {
                    showModal('Cantidad incorrecta');
                }

            } else {
                showModal('Valores incorrectos');
            }


        }
    });
    }

    function agregarCuentaC() {
        let idDocumento = document.getElementById('cuentacontable_id').value;
        let select = document.getElementById('cuentacontable_id');
        let NombreDocumento = select.options[select.selectedIndex].text;
let debeacum=0;
let haberacumb=0;
        let Naturaleza = document.getElementById('Naturaleza').value;
        let Monto = $('#valorminimo').val();

        if (idDocumento === "") {
            alert("Por favor, seleccione una Cuenta Contable.");
            return false; // Detiene la ejecución de la función
                    }

        if (Monto === 0) {
            alert("Por favor, el valor debe de ser mayor a cero.");
            return false; // Detiene la ejecución de la función

        }

        idcuenta[contcc]=idDocumento;
        cuenta[contcc]=NombreDocumento;
        monto[contcc]=Monto;
        tipo[contcc]=Naturaleza;

        for (let i = 0; i < idcuenta.length; i++) {
            if (tipo[i]==="D") { // omite undefined, null, "" (cadena vacía), 0 y false
            debeacum=Number(debeacum)+Number(monto[i]);
            }
            if (tipo[i]==="H") { // omite undefined, null, "" (cadena vacía), 0 y false
            haberacumb=Number(haberacumb)+Number(monto[i]);
            }
          }

   if(Number(haberacumb)!==Number(debeacum)){
        document.getElementById("msj").textContent = "⚠️ Los montos Debe y Haber sumados deben de coincidir .";
        document.getElementById("msj").style.color = "red"; // opcional para que se vea en rojo
    }else{
        document.getElementById("msj").textContent ="";
    }

    if(Number(haberacumb)!==Number(total)){
        document.getElementById("msj").textContent = "⚠️ Los montos Debe y Haber sumados deben de coincidir, y tambien debe de coincidir con el Total del documento.";
        document.getElementById("msj").style.color = "red"; // opcional para que se vea en rojo
    }else{
        document.getElementById("msj").textContent ="";
    }

        construirsumarCC(idcuenta,monto[contcc]);

        contcc++;
        $('#valorminimo').val(0);
        $('#Naturaleza').val("");
  }
    function agregarProducto() {
        let dataProducto = document.getElementById('producto_id').value.split('-');
        //Obtener valores de los campos
        let idProducto = dataProducto[0];
        let nameProducto = $('#producto_id option:selected').text();
        let cantidad = $('#cantidad').val();
        let precioVenta = parseFloat($('#precio_venta').val());
        let descuento = $('#descuento').val();
        let stock = $('#stock').val();
        var comprobante = document.getElementById('comprobante_id').value;

        if (comprobante === "") {
            alert("Por favor, seleccione un comprobante.");
            return false; // Detiene la ejecución de la función
                    }

        if (descuento == '') {
            descuento = 0;
        }

        //Validaciones
        //1.Para que los campos no esten vacíos
        if (idProducto != '' && cantidad != '') {

            //2. Para que los valores ingresados sean los correctos
            if (parseInt(cantidad) > 0 && (cantidad % 1 == 0) && parseFloat(descuento) >= 0) {

                //3. Para que la cantidad no supere el stock
                if (parseInt(cantidad) <= parseInt(stock)) {
                    //Calcular valores

                    subtotal[cont] = round(cantidad * precioVenta - descuento);
                    sumas += cantidad;
                    total+=subtotal[cont];

                    sumadocdb=round(total);
                    subiva[cont]=CalcularFormula(formula,subtotal[cont]);
                    resultadoiva=CalcularFormula(formula,total);
                    IVA = resultadoiva;
                    //Crear la fila
                    let fila = '<tr id="fila' + cont + '">' +
                        '<th>' + (cont + 1) + '</th>' +
                        '<td><input type="hidden" name="arrayidproducto[]" value="' + idProducto + '">' + nameProducto + '</td>' +
                        '<td><input type="hidden" name="arraycantidad[]" value="' + cantidad + '">' + cantidad + '</td>' +
                        '<td><input type="hidden" name="arrayprecioventa[]" value="' + precioVenta + '">' + precioVenta + '</td>' +
                        '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[cont] + '">' + subiva[cont] + '</td>' +
                        '<td><input type="hidden" name="arraydescuento[]" value="' + descuento + '">' + descuento + '</td>' +
                        '<td>' + subtotal[cont] + '</td>' +
                        '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                        '</tr>';

                    //Acciones después de añadir la fila
                    $('#tabla_detalle_tbody').append(fila);
                    limpiarCampos();

                    disableButtons();

                    resultadoiva = resultadoiva.toFixed(2);

            producto[cont]=idProducto;
            Cantidad[cont]=cantidad;
            precioventa[cont]=parseFloat(precioVenta);
            nombre[cont]=nameProducto;
            cantidadarticulos+=parseInt(Cantidad[cont],15);
            Descuento[cont]=descuento;
            cont++;
            sumarArreglos(formulas,monto);

                    //Mostrar los campos calculados
                    $('#sumas').html(sumas);
                    $('#IVA').html(IVA);
                    $('#total').html(total);
                    $('#subiva').val(subiva);
                    $('#impuesto').val(IVA);
                    $('#inputTotal').val(total);
                } else {
                    showModal('Cantidad incorrecta');
                }

            } else {
                showModal('Valores incorrectos');
            }

        }

    }

    function eliminarProducto(indice) {
        //Calcular valores
        sumas -= round(subtotal[indice]);
        IVA = round(sumas / 100 * impuesto);
        total = round(sumas + IVA);

        //Mostrar los campos calculados
        $('#sumas').html(sumas);
        $('#IVA').html(IVA);
        $('#total').html(total);
        $('#subiva').html(subiva);
        $('#impuesto').val(IVA);
        $('#InputTotal').val(total);

        //Eliminar el fila de la tabla
        $('#fila' + indice).remove();

        disableButtons();
    }
    function eliminarCC(indice) {

        //Eliminar el fila de la tabla
        $('#filaCC' + indice).remove();


        idcuenta.splice(indice, 1);
         cuenta.splice(indice, 1);
         monto.splice(indice, 1);
         tipo.splice(indice, 1);
         contcc--;
        disableButtons();
    }

    function cancelarVenta() {
        //Elimar el tbody de la tabla
        $('#tabla_detalle_tbody').empty();

        //Añadir una nueva fila a la tabla
        let fila = '<tr>' +
            '<th></th>' +
            '<td></td>' +
            '<td></td>' +
            '<td></td>' +
            '<td></td>' +
            '<td></td>' +
            '<td></td>' +
            '</tr>';
        $('#tabla_detalle_tbody').append(fila);

        //Reiniciar valores de las variables
        cont = 0;

        subtotal = [];
        sumas = 0;
        IVA = 0;
        total = 0;

        //Mostrar los campos calculados
        $('#sumas').html(sumas);
        $('#IVA').html(IVA);
        $('#total').html(total);
        $('#subiva').html(subiva);
        $('#impuesto').val(impuesto + '%');
        $('#inputTotal').val(total);

        limpiarCampos();
        disableButtons();
    }

    function disableButtons() {
        if (total == 0) {
            $('#guardar').hide();
            $('#cancelar').hide();
        } else {
            $('#guardar').show();
            $('#cancelar').show();
        }
    }

    function limpiarCampos() {
        let select = $('#producto_id');
        select.selectpicker('val', '');
        $('#cantidad').val('');
        $('#subiva').val('');
        $('#precio_venta').val('');
        $('#descuento').val('');
        $('#stock').val('');
    }

    function showModal(message, icon = 'error') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        })

        Toast.fire({
            icon: icon,
            title: message
        })
    }

    function construirsumarCC(arr1, arr2, A){
        let resultados = []; // Inicializar el arreglo correctamente
        let formulaEvaluada;
        let resultado;
        var tableBody = $('#detalle_tbody');
        tableBody.empty();

                        // Iterar sobre los detalles y agregar filas a la tabla
                        $.each(arr1, function(index, arr1) {

                            var row = '<tr id="filaCC' + index + '">' +
                                '<td></td>' +
                                '<td><input name="arrayidcuenta[]" type="hidden" class="form-control" value="' + idcuenta[index] + '"></td>' +
                                '<td>' + cuenta[index] + '</td>' +
                                '<td class="small-text">N/A</td>' +
                                '<td><input name="arraymonto[]" type="number" class="form-control" value="' + monto[index] + '"></td>' +
                                '<td><input name="arraytipomovimiento[]" type="text" class="form-control" value="' + tipo[index] +'"></td>' +
                                '<td><button class="btn btn-danger" type="button" onClick="eliminarCC(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +

                                '</tr>';
                            tableBody.append(row);

                            $('#impuesto').val(IVA);

                        });

        return resultados; // Devolver el arreglo de resultados
    };
    function sumarArreglos(arr1, arr2, A){
        let resultados = []; // Inicializar el arreglo correctamente
        let formulaEvaluada;
        let resultado;
        var tableBody = $('#detalle_tbody');
        tableBody.empty();

        // Sumar los elementos correspondientes de los arreglos
        for (let i = 0; i < arr1.length; i++) {
            // Reemplazar "A" en la fórmula con el valor de la variable A
            formulaEvaluada = arr1[i].replace(/A/g, total);
            // Evaluar la fórmula usando math.js
            resultado = math.evaluate(formulaEvaluada);
            // Redondear el resultado a 2 decimales
            resultado = parseFloat(resultado.toFixed(2));
            arr2[i]=resultado;
            // Sumar el valor evaluado al valor de arr2[i]
            resultados.push(arr2[i]);
        };
                        // Iterar sobre los detalles y agregar filas a la tabla
                        $.each(arr1, function(index, arr1) {
                            var row = '<tr>' +
                                '<td></td>' +
                                '<td><input name="arrayidcuenta[]" type="hidden" class="form-control" value="' + idcuenta[index] + '" readonly></td>' +
                                '<td>' + cuenta[index] + '</td>' +
                                '<td class="small-text">' + formulas[index] + '</td>' +
                                '<td><input name="arraymonto[]"  class="form-control" value="' + resultados[index] + '" readonly></td>' +
                                '<td><input name="arraytipomovimiento[]" type="text" class="form-control" value="' + tipo[index] + '" readonly></td>' +
                                '</tr>';
                            tableBody.append(row);
                            monto[index]=resultados[index];
                            $('#impuesto').val(IVA);

                        });

        return resultados; // Devolver el arreglo de resultados
    };

    function sumarArreglosInput(arr1, arr2, A){
        let resultados = []; // Inicializar el arreglo correctamente
        let formulaEvaluada;
        let resultado;
        var tableBody = $('#detalle_tbody');
        tableBody.empty();

        // Sumar los elementos correspondientes de los arreglos
        for (let i = 0; i < arr1.length; i++) {
            // Reemplazar "A" en la fórmula con el valor de la variable A
            formulaEvaluada = arr1[i].replace(/A/g, total);
            // Evaluar la fórmula usando math.js
            resultado = math.evaluate(formulaEvaluada);
            // Redondear el resultado a 2 decimales
            resultado = parseFloat(resultado.toFixed(2));
            arr2[i]=resultado;
            // Sumar el valor evaluado al valor de arr2[i]
            resultados.push(arr2[i]);
        };
                        // Iterar sobre los detalles y agregar filas a la tabla
                        $.each(arr1, function(index, arr1) {
                            var row = '<tr>' +
                                '<td></td>' +
                                '<td><input name="arrayidcuenta[]" type="hidden" class="form-control" value="' + idcuenta[index] + '"></td>' +
                                '<td>' + cuenta[index] + '</td>' +
                                '<td class="small-text">N/A</td>' +
                                '<td><input name="arraymonto[]" type="number" class="form-control" value="' + resultados[index] + '"></td>' +
                                '<td><input name="arraytipomovimiento[]" type="text" class="form-control" value="' + tipo[index] +'"></td>' +

                                '</tr>';
                            tableBody.append(row);
                            monto[index]=resultados[index];
                            $('#impuesto').val(IVA);

                        });

        return resultados; // Devolver el arreglo de resultados
    };
    function crearNuevoDiv() {
    const nuevoDiv = document.createElement('div');
    nuevoDiv.className = 'table table-hover';
    const uniqueId = Date.now(); // Genera un ID único basado en la fecha actual
    nuevoDiv.innerHTML = `

                <div class="col-12 mb-2">
                <select id="cuentacontable_id" name="cuentacontable_id" class="form-control selectpicker" data-live-search="true" data-size="5" title="Busque un Cuenta Contable aquí">
                    @foreach ($cuentasContables as $item)
                        <option value="{{$item->id}}">{{$item->formula}}-{{$item->nombre}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 mb-2">
                <select id="Naturaleza" name="Naturaleza" class="form-control selectpicker" data-live-search="true" data-size="2" title="Elija Naturaleza de la cuenta">
                    <option value="D">Debe</option>
                    <option value="H">Haber</option>
                </select>
            </div>
            <div class="col-4 mb-2">
                <label for="valorminimo" class="form-label">Valor Minimo:</label>
                <input type="number" id="valorminimo" name="valorminimo" class="form-control" step="0.1">
                </div>
                                    <!-----botón para agregar--->
                        <div class="col-12 text-end">
                            <button id="btn_agregarCC" onclick="agregarCuentaC()" class="btn btn-primary" type="button">Agregar</button>
                        </div>
    `;
    return nuevoDiv;
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
    document.addEventListener("DOMContentLoaded", function() {

    var comprobanteId =  $('#comprobante_id').val();
if (comprobanteId) {
    $.ajax({
        url: '/compras/detalles/' + comprobanteId + '',
        type: 'GET',
        success: function(response) {
            var detalles = response.detalles;
            var tableBody = $('#detalle_tbody');
            tableBody.empty();
            agregarProductoAutomatico();
            // Iterar sobre los detalles y agregar filas a la tabla
            $.each(detalles, function(index, detalle) {

                var row = '<tr>' +
                    '<td></td>' +
                    '<td></td>' +
                    '<td>' + detalle.cuenta_contable_nombre + '</td>' +
                    '<td class="small-text">' + detalle.formula + '</td>' +
                    '<td>' + detalle.valorminimo + '</td>' +
                    '<td>' + detalle.Naturaleza + '</td>' +
                    '</tr>';
                tableBody.append(row);
                formulas[index]=detalle.formula;
                monto[index]=detalle.valorminimo;
                cuenta[index]=detalle.cuenta_contable_nombre;
                idcuenta[index]=detalle.id;
                tipo[index]=detalle.Naturaleza;
                formula=detalle.formuladoc;

                sumarArreglos(formulas,monto);

            });

            llenarTablaventas();
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar los detalles:", error);
        }
    });
}
});



    </script>



@endpush
