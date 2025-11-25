@extends('layouts.app')

@section('title','Realizar venta')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/math.js') }}"></script>
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

<form action="{{ route('ventas.store') }}" method="post">
    @csrf
    <div class="container-lg mt-4">
        <div class="row gy-4">

            <!------venta producto---->
            <div class="col-xl-8">
                <div class="text-white bg-primary p-1 text-center">
                    Detalles de la venta
                </div>
                <div class="p-3 border border-3 border-primary">
                    <div class="row gy-4">

                        <!-----SKU---->
                        <div class="col-sm-4">
                            <label for="cantidad" class="form-label">SKU:</label>
                            <input type="number" name="SKU" id="SKU" class="form-control">
                        </div>

                        <!-----Producto---->
                        <div class="col-12">

<select name="producto_id" id="producto_id" class="form-control selectpicker" data-live-search="true" data-size="10" title="Busque un producto aquí">
    <option class="bs-title-option" value=""></option>
    @foreach($productos as $producto)
        <option value="{{ $producto->id }}"
                data-img="{{ $producto->img_path }}"
                data-detalle="{{ $producto->descripcion }}">
            {{ $producto->nombre }}
        </option>
    @endforeach

</select>



<button type="button" class="btn btn-primary" id="btnVerProducto">
    Ver
</button>


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
                </div>
                <div class="p-3 border border-3 border-success">
                    <div class="row gy-4">
                        <!--Cliente-->
                        <div class="col-12">
                            <label for="cliente_id" class="form-label">Cliente:</label>
                            <select name="cliente_id" id="cliente_id" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona" data-size='10'>
                                @foreach ($clientes as $item)
                                    <option value="{{$item->id}}">{{$item->persona->numero_documento.'-'.$item->persona->razon_social}}</option>
                                @endforeach
                            </select>
                            @error('cliente_id')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Tipo de comprobante-->
                        <div class="col-12">
                            <label for="comprobante_id" class="form-label">Comprobante:</label>
                            <select name="comprobante_id" id="comprobante_id" class="form-control selectpicker" title="Selecciona" data-size='10'>
                                @foreach ($comprobantes as $item)
                                <option value="{{$item->id}}">{{$item->tipo_comprobante}}</option>
                                @endforeach
                            </select>
                            @error('comprobante_id')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Numero de factura-->
                        <div class="col-12">
                            <label for="numero_comprobante" class="form-label">Numero de comprobante:</label>
                            <input readonly type="text" name="numero_comprobante" id="numero_comprobante" class="form-control">
                            <input type="hidden" name="TipoFolio" id="TipoFolio" value="A">
                            @error('numero_comprobante')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                                                <!--Numero de comprobante-->
                        <div class="col-12">
                            <label for="numero_factura" class="form-label">Numero de Factura:</label>
                            <input readonly type="text" name="numero_factura" id="numero_factura" class="form-control">
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

    <div class="modal fade" id="modalProducto" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Detalle del Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">
        <img id="imgProducto" src="" class="img-fluid mb-3" style="max-height:350px;">
        <p id="detalleProducto"></p>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Detalle del Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">
        <img id="imgProducto" src="" class="img-fluid mb-3" style="max-height:350px;">
        <p id="detalleProducto"></p>
      </div>

    </div>
  </div>
</div>

</form>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script>

        //Variables
        let cont = 0;
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

    //Constantes
    const impuesto = 12;
    $(document).ready(function() {



    document.getElementById("btnVerProducto").addEventListener("click", function() {

        let select = document.getElementById("producto_id");
        let selected = select.selectedOptions[0];

        if (!selected) {
            alert("Seleccione un producto primero");
            return;
        }

let imagen = selected.dataset.img; // ya no será undefined
let detalle = selected.dataset.detalle;
let ruta = "/storage/productos/" + imagen;

document.getElementById("imgProducto").src = ruta;
document.getElementById("detalleProducto").textContent = detalle;

        let modal = new bootstrap.Modal(document.getElementById("modalProducto"));
        modal.show();
    });


        $(document).on('keydown', '.bs-searchbox input', function(event) {
    if (event.keyCode === 13) { // 13 = Enter
        event.preventDefault(); // Evita que el Enter seleccione un elemento automáticamente
        var searchTerm = $(this).val().trim();

        if (searchTerm.length > 0) {
            var $select = $('#cliente_id');

            $.ajax({
                url: "{{ route('client.obtener') }}",
                method: 'GET',
                data: { search: searchTerm },
                success: function(response) {
                    $select.html('').selectpicker('destroy'); // 🔄 Limpiar y destruir selectpicker

                    if (response.length > 0) {
                        response.forEach(function(cliente) {
                            $select.append('<option value="' + cliente.id + '">' +
                                           cliente.persona.numero_documento + ' - ' +
                                           cliente.persona.razon_social + '</option>');
                        });
                    } else {
                        $select.append('<option value="">No se encontraron resultados</option>');
                        actualizarClientes();
                    }

                    $select.selectpicker(); // 🔄 Reinicializar selectpicker
                    setTimeout(() => $('.bs-searchbox input').val(searchTerm).trigger('focus'), 50);
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        } else {
            actualizarClientes(); // Si no hay búsqueda, cargar todos los clientes
        }
    }
});


// ✅ **Función para cargar todos los clientes al inicio o cuando no hay búsqueda**
function actualizarClientes() {
    $.ajax({
        url: "{{ route('client.obtener') }}",
        method: 'GET',
        success: function(response) {
            var $select = $('#cliente_id');

            // 🔥 **Eliminar opciones previas y reinicializar selectpicker**
            $select.html('').selectpicker('destroy');

            response.forEach(function(cliente) {
                $select.append('<option value="' + cliente.id + '">' + cliente.persona.numero_documento+' - '+cliente.persona.razon_social + '</option>');
            });

            // 🔄 **Reiniciar selectpicker**
            $select.selectpicker();
        },
        error: function(xhr, status, error) {
            console.error(error);
        }
    });
}


$('#btn_agregar').click(function() {
    agregarProducto();
});

$('#btnCancelarCompra').click(function() {
    cancelarCompra();
});

disableButtons();
$('#comprobante_id').on('change', function() {
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

    function mostrarValoresScanner() {


var comprobanteId = $("#SKU").val();
if (comprobanteId) {
    $.ajax({
        url: '/compras/detallesSCAN/' + comprobanteId + '',
        type: 'GET',
        success: function(response) {

                if (!response || response.length === 0) {
                    console.warn("No se encontró producto.");
                    return;
                }

            var detalle = response[0];

                let idProducto = detalle.producto_id;
                let seleccionable = idProducto + "-" +detalle.existencia+"-"+detalle.precio_venta;

                $("#producto_id option").each(function () {
                    let v = $(this).val();
                    if (v.startsWith(seleccionable)) {
                        $("#producto_id").val(v).change();
                        $('.selectpicker').selectpicker('refresh');
                        return false; // salir del each
                    }
                });
                $('#precio_venta').val(detalle.precio_venta);
                $('#stock').val(detalle.existencia);
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar los detalles:", error);
        }
    });
}

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

    function agregarProducto() {
        let dataProducto = document.getElementById('producto_id').value.split('-');
        //Obtener valores de los campos
        let idProducto = dataProducto[0];
        let nameProducto = $('#producto_id').find('option:selected').text();

if(nameProducto) {
    // Do something with the selected product name
} else {
    console.log("Producto no seleccionado");
}

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

      function agregarProductoScanner(sku) {
        let dataProducto = "";
        //Obtener valores de los campos
        let idProducto = 0;
        let nameProducto = "";

        var comprobanteId = sku;
if (comprobanteId) {
    $.ajax({
        url: '/compras/detallesSCAN/' + comprobanteId + '',
        type: 'GET',
        success: function(response) {

                if (!response || response.length === 0) {
                    console.warn("No se encontró producto.");
                    return;
                }

            var detalle = response[0];

                let idProductos = detalle.producto_id;
                let seleccionable = idProductos + "-" +detalle.existencia+"-"+detalle.precio_venta;
                dataProducto = seleccionable;
                idProducto = detalle.producto_id;
                nameProducto = detalle.producto_nombre;

                $("#producto_id option").each(function () {
                    let v = $(this).val();
                    if (v.startsWith(seleccionable)) {
                        $("#producto_id").val(v).change();
                        $('#producto_id').selectpicker('refresh');
                        return false; // salir del each
                    }
                });
                $('#precio_venta').val(detalle.precio_venta);
                $('#stock').val(detalle.existencia);

                if(nameProducto) {
    // Do something with the selected product name
} else {
    console.log("Producto no seleccionado");
}

        let cantidad = $('#cantidad').val();
        let precioVenta = detalle.precio_venta;
        let descuento = $('#descuento').val();
        let stock = detalle.existencia;
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


        },
        error: function(xhr, status, error) {
            console.error("Error al cargar los detalles:", error);
        }
    });
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
                                '<td></td>' +
                                '<td>' + cuenta[index] + '</td>' +
                                '<td class="small-text">' + formulas[index] + '</td>' +
                                '<td>' + resultados[index] + '</td>' +
                                '<td>' + tipo[index] + '</td>' +
                                '</tr>';
                            tableBody.append(row);
                            monto[index]=resultados[index];
                            $('#impuesto').val(IVA);

                        });

        return resultados; // Devolver el arreglo de resultados
    };

    // Variables para detectar si es lector de barras
let lastInputTime = 0;

document.getElementById("SKU").addEventListener("keydown", function (e) {

    // Registrar tiempo entre teclas
    const now = Date.now();
    const delta = now - lastInputTime;
    lastInputTime = now;

    // Si presiona ENTER
    if (e.key === "Enter") {
        e.preventDefault();

        let sku = this.value.trim();

        // Si no hay nada, solo limpiar y enfocar
        if (sku === "") {
            this.focus();
            return;
        }

        // Detectar si fue lector de código de barras:
        // Si la escritura fue demasiado rápida (<80ms por tecla)
        const isScanner = delta < 80;
        const cantidades = $("#cantidad").val();


        if (isScanner) {
            // Caso lector de código de barras
            if(cantidades===1 || cantidades===""){
                $("#cantidad").val(1);
            }
            $("#descuento").val(0);
            $("#SKU").val("");     // limpiar
            $("#SKU").focus();     // regresar el foco
            agregarProductoScanner(sku);
        } else {
            // Caso ingreso manual con teclado
            $("#cantidad").val(sku);  // copiar número del SKU a cantidad
            $("#SKU").val("");        // limpiar
            $("#SKU").focus();        // regresar el foco
        }
    }
});


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
