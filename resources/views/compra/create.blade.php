@extends('layouts.app')

@section('title','Realizar compra')

@push('css')

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/math.js') }}"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Compra</h1>
    <ol class="breadcrumb mb-4">
        <l class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></l>
        <li class="breadcrumb-item"><a href="{{ route('compras.index')}}">Compras</a></li>
        <li class="breadcrumb-item active">Crear Compra</li>
    </ol>
</div>

<form action="{{ route('compras.store') }}" method="post">
    @csrf

    <div class="container-lg mt-4">
        <div class="row gy-4">
            <!------Compra producto---->
            <div class="col-xl-8">
                <div class="text-white bg-primary p-1 text-center">
                    Detalles de la compra
                </div>
                <div class="p-3 border border-3 border-primary">
                    <div class="row">
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

                        <!-----Cantidad---->
                        <div class="col-sm-4 mb-2">
                            <label for="cantidad" class="form-label">Cantidad:</label>
                            <input type="number" name="cantidad" id="cantidad" class="form-control">
                        </div>

                        <!-----Precio de compra---->
                        <div class="col-sm-4 mb-2">
                            <label for="precio_compra" class="form-label">Precio de compra:</label>
                            <input type="number" name="precio_compra" id="precio_compra" class="form-control" step="0.1">
                        </div>

                        <!-----Precio de venta---->
                        <div class="col-sm-4 mb-2">
                            <label for="precio_venta" class="form-label">Precio de venta:</label>
                            <input type="number" name="precio_venta" id="precio_venta" class="form-control" step="0.1">
                        </div>

                        <!-----botón para agregar--->
                        <div class="col-12 mb-4 mt-2 text-end">
                            <button id="btn_agregar" class="btn btn-primary" type="button">Agregar</button>
                        </div>

                        <!-----Tabla para el detalle de la compra--->
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="tabla_detalle" class="table table-hover">
                                    <thead class="bg-primary">
                                        <tr>
                                            <th class="text-white">#</th>
                                            <th class="text-white">Producto</th>
                                            <th class="text-white">Cantidad</th>
                                            <th class="text-white">Precio compra</th>
                                            <th class="text-white">IVA compra</th>
                                            <th class="text-white">Precio venta</th>
                                            <th class="text-white">Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
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

                        <!--Boton para cancelar compra-->
                        <div class="col-12 mt-2">
                            <button id="cancelar" type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                Cancelar compra
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-----Compra---->
            <div class="col-xl-4">
                <div class="text-white bg-success p-1 text-center">
                    Datos generales
                </div>
                <div class="p-3 border border-3 border-success">
                    <div class="row">
                        <!--Proveedor-->
                        <div class="col-12 mb-2">
                            <label for="proveedore_id" class="form-label">Proveedor:</label>
                            <select name="proveedore_id" id="proveedore_id" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona" data-size='2'>
                                @foreach ($proveedores as $item)
                                <option value="{{$item->id}}">{{$item->persona->razon_social}}</option>
                                @endforeach
                            </select>
                            @error('proveedore_id')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Tipo de comprobante-->
                        <div class="col-12 mb-2">
                            <label for="comprobante_id" class="form-label">Comprobante:</label>
                            <select name="comprobante_id" id="comprobante_id" class="form-control selectpicker" title="Selecciona">
                                @foreach ($comprobantes as $item)
                                <option value="{{$item->id}}">{{$item->tipo_comprobante}}</option>
                                @endforeach
                            </select>
                            @error('comprobante_id')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Numero de comprobante-->
                        <div class="col-12 mb-2">
                            <label for="numero_comprobante" class="form-label">Numero de comprobante:</label>
                            <input required type="text" name="numero_comprobante" id="numero_comprobante" class="form-control">
                            @error('numero_comprobante')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Impuesto---->
                        <div class="col-sm-6 mb-2">
                            <label for="impuesto" class="form-label">Impuesto:</label>
                            <input readonly type="text" name="impuesto" id="impuesto" class="form-control border-success">
                            @error('impuesto')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Fecha--->
                        <div class="col-sm-6 mb-2">
                            <label for="fecha" class="form-label">Fecha:</label>
                            <input readonly type="date" name="fecha" id="fecha" class="form-control border-success" value="<?php echo date("Y-m-d") ?>">
                            <?php

                            use Carbon\Carbon;

                            $fecha_hora = Carbon::now()->toDateTimeString();
                            ?>
                            <input type="hidden" name="fecha_hora" value="{{$fecha_hora}}">
                        </div>

                        <!--Botones--->
                        <div class="col-12 mt-4 text-center">
                            <button type="submit" class="btn btn-success" id="guardar">Realizar compra</button>
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
        let formulas = [];
        let monto = [];
        let tipo = [];
        let cuenta = [];
        let producto= [];
        let Cantidad= [];
        let preciocompra= [];
        let precioventa= [];
        let nombre= [];
        let sumas = 0;
        let resultadoiva=0;
        let IVA = 0;
        let total = 0;
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

                            $('#impuesto').val(formula);
                            sumarArreglos(formulas,monto);
                        });

                        llenarTabla();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error al cargar los detalles:", error);
                    }
                });
            }
        });
        });

        function llenarTabla() {
    var tableBodyDetalle = $('#tabla_detalle body');
    tableBodyDetalle.empty(); // Clear the table body

    $.each(producto, function(index) {

        CalcularFormula(formula,preciocompra[index]);
        // Construct the row for the table
        var fila = '<tr id="fila' + index + '">' +
            '<th>' + (cont + 1) + '</th>' +
            '<td><input type="hidden" name="arrayidproducto[]" value="' + producto[index] + '">' + nombre[index] + '</td>' +
            '<td><input type="hidden" name="arraycantidad[]" value="' + Cantidad[index] + '">' + Cantidad[index] + '</td>' +
            '<td><input type="hidden" name="arraypreciocompra[]" value="' + preciocompra[index] + '">' + preciocompra[index] + '</td>' +
            '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[index] + '">' + subiva[index] + '</td>' +
            '<td><input type="hidden" name="arrayprecioventa[]" value="' + precioventa[index] + '">' + precioventa[index] + '</td>' +
            '<td>' + subtotal[index] + '</td>' +  // Use index instead of cont
            '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';

        // Append the row to the table body
        tableBodyDetalle.append(fila);
    });
        }


        function cancelarCompra() {
            //Elimar el tbody de la tabla
            $('#tabla_detalle tbody').empty();

            //Añadir una nueva fila a la tabla
            let fila = '<tr>' +
                '<th></th>' +
                '<td></td>' +
                '<td></td>' +
                '<td></td>' +
                '<td></td>' +
                '<td></td>' +
                '<td></td>' +
                '<td></td>' +
                '</tr>';
            $('#tabla_detalle').append(fila);

            //Reiniciar valores de las variables
            cont = 0;
            subtotal = [];
            subiva = [];
            sumas = 0;
            IVA = 0;
            total = 0;
            totalMASIVA = 0;
            cantidadarticulos=0;


            //Mostrar los campos calculados
            $('#sumas').html('Q. '+cantidadarticulos);
            $('#IVA').html('Q. '+IVA);
            $('#total').html('Q. '+total);
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
            //Obtener valores de los campos
            let idProducto = $('#producto_id').val();
            let nameProducto = ($('#producto_id option:selected').text()).split(' ')[1];
            let cantidad = $('#cantidad').val();
            let precioCompra = $('#precio_compra').val();
            let precioVenta = $('#precio_venta').val();


            var comprobante = document.getElementById('comprobante_id').value;
            if (comprobante === "") {
            alert("Por favor, seleccione un comprobante.");
            return false; // Detiene la ejecución de la función
                    }
            //Validaciones
            //1.Para que los campos no esten vacíos
            if (nameProducto != '' && nameProducto != undefined && cantidad != '' && precioCompra != '' && precioVenta != '') {

                //2. Para que los valores ingresados sean los correctos
                if (parseInt(cantidad) > 0 && (cantidad % 1 == 0) && parseFloat(precioCompra) > 0 && parseFloat(precioVenta) > 0) {

                    //3. Para que el precio de compra sea menor que el precio de venta
                    if (parseFloat(precioVenta) > parseFloat(precioCompra)) {
                        //Calcular valores

                        subtotal[cont] = round(cantidad * precioCompra);

                        if (subtotal[cont] !== undefined) {

                        sumas += subtotal[cont];

                        totalMASIVA=sumas;
                        total = totalMASIVA;

            // Reemplazar "A" en la fórmula con el valor de la variable A
            formulaEvaluadaiva = formula.replace(/A/g, total);
            // Evaluar la fórmula usando math.js
            resultadoiva = math.evaluate(formulaEvaluadaiva);
            // Redondear el resultado a 2 decimales
            resultadoiva = parseFloat(resultadoiva.toFixed(2));
            IVA = resultadoiva;

            // Reemplazar "A" en la fórmula con el valor de la variable A
            formulaEvaluadaiva = formula.replace(/A/g, subtotal[cont]);
            // Evaluar la fórmula usando math.js
            resultadoiva = math.evaluate(formulaEvaluadaiva);
            // Redondear el resultado a 2 decimales
            resultadoiva = parseFloat(resultadoiva.toFixed(2));
            subiva[cont]= resultadoiva;
            producto[cont]=idProducto;
            Cantidad[cont]=cantidad;
            preciocompra[cont]=precioCompra;
            precioventa[cont]=precioVenta;
            nombre[cont]=nameProducto;
            cantidadarticulos+=parseInt(Cantidad[cont],15);
                        //Crear la fila
                        let fila = '<tr id="fila' + cont + '">' +
                            '<th>' + (cont + 1) + '</th>' +
                            '<td><input type="hidden" name="arrayidproducto[]" value="' + idProducto + '">' + nameProducto + '</td>' +
                            '<td><input type="hidden" name="arraycantidad[]" value="' + cantidad + '">' + cantidad + '</td>' +
                            '<td><input type="hidden" name="arraypreciocompra[]" value="' + precioCompra + '">' + precioCompra + '</td>' +
                            '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[cont] + '">' + subiva[cont] + '</td>' +
                            '<td><input type="hidden" name="arrayprecioventa[]" value="' + precioVenta + '">' + precioVenta + '</td>' +
                            '<td>' + subtotal[cont] + '</td>' +
                            '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                            '</tr>';

                        //Acciones después de añadir la fila
                        $('#tabla_detalle').append(fila);
                        limpiarCampos();
                        cont++;
                        disableButtons();
                        let formulaEvaluada;
                        let resultado;
                        formulaEvaluada = formula.replace(/A/g, total);
                        // Evaluar la fórmula usando math.js
                        resultado = math.evaluate(formulaEvaluada);
                        // Redondear el resultado a 2 decimales
                        resultado = parseFloat(resultado.toFixed(2));
                        //Mostrar los campos calculados
                        $('#sumas').html(cantidadarticulos);
                        $('#IVA').html(subiva[cont]);
                        $('#total').html(total);
                        $('#impuesto').val(IVA);
                        $('#inputTotal').val(total-resultado);

                        sumarArreglos(formulas,monto);
                    }
                    } else {
                        showModal('Precio de compra incorrecto');
                    }

                } else {
                    showModal('Valores incorrectos');
                }

            } else {
                showModal('Le faltan campos por llenar');
            }



        }

        function eliminarProducto(indice) {
            //Calcular valores
            sumas -= round(subtotal[indice]);
            total = round(sumas);
            subiva[indice] = round(sumas);
            formulaEvaluadaiva = formula.replace(/A/g, total);
            // Evaluar la fórmula usando math.js
            resultadoiva = math.evaluate(formulaEvaluadaiva);
            // Redondear el resultado a 2 decimales
            resultadoiva = parseFloat(resultadoiva.toFixed(2));
            IVA = resultadoiva;
            cantidadarticulos-=parseInt(Cantidad[indice]);
            //Mostrar los campos calculados
            $('#sumas').html(cantidadarticulos);
            $('#IVA').html(IVA);
            $('#total').html(total);
            $('#impuesto').val(IVA);
            $('#InputTotal').val(total-IVA);

            //Eliminar el fila de la tabla
            $('#fila' + indice).remove();
            sumarArreglos(formulas,monto);
            disableButtons();

            producto.splice(indice, 1);
        nombre.splice(indice, 1);
        Cantidad.splice(indice, 1);
        preciocompra.splice(indice, 1);
        subiva.splice(indice, 1);
        precioventa.splice(indice, 1);
        subtotal.splice(indice, 1);

        }

        function limpiarCampos() {
            let select = $('#producto_id');
            select.selectpicker('val', '');
            $('#cantidad').val('');
            $('#precio_compra').val('');
            $('#precio_venta').val('');
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
    </script>
@endpush
