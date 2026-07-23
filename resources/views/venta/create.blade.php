@extends('layouts.app')

@section('title','Realizar venta')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/math.js') }}"></script>
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
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

<div class="container">
    <div class="card-bt">
        <button onclick="iniciarScanner('qr')" class="btn btn-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <rect x="1" y="1" width="4" height="4"/>
            <rect x="11" y="1" width="4" height="4"/>
            <rect x="1" y="11" width="4" height="4"/>
            <rect x="6" y="6" width="1" height="1"/>
            <rect x="8" y="6" width="1" height="1"/>
            <rect x="6" y="8" width="1" height="1"/>
            <rect x="8" y="8" width="1" height="1"/>
            <rect x="10" y="10" width="1" height="1"/>
            <rect x="12" y="8" width="1" height="1"/>
            </svg>
        </button>
        <button onclick="iniciarScanner('barra')" class="btn btn-secundary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <rect x="1" y="2" width="1" height="12"/>
            <rect x="3" y="2" width="2" height="12"/>
            <rect x="6" y="2" width="1" height="12"/>
            <rect x="8" y="2" width="2" height="12"/>
            <rect x="11" y="2" width="1" height="12"/>
            <rect x="13" y="2" width="2" height="12"/>
            </svg>
        </button>

        <button onclick="StopScanner()" class="btn btn-danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <rect x="2" y="2" width="12" height="12" rx="2"/>
            <rect x="5" y="5" width="6" height="6" fill="white"/>
            </svg>
        </button>
    </div>
        <div id="reader" style="width:100%"></div>
    <div id="readerbarra" style="width:100%"></div>

<form id="formVenta" action="{{ route('ventas.store') }}" method="post">
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
                            <label for="SKU" class="form-label">SKU:</label>
                            <input type="number" name="SKU" id="SKU" class="form-control">
                        </div>

                        <!-----Producto---->
                        <div class="col-12">

<select name="producto_id" id="producto_id" class="form-control selectpicker" data-live-search="true">
    <option value="" disabled selected>Selecciona un producto</option>
    @foreach($productos as $producto)
        <option value="{{ $producto->id }}"
                data-stock="{{ $producto->stock }}"
                data-precio="{{ $producto->precio_venta }}" 
                data-lote="{{ $producto->numero_lote ?? 'N/A' }}"
                data-vence="{{ $producto->fecha_vencimiento ?? 'N/A' }}"
                data-cantidadlote="{{ $producto->cantidad_lote ?? 'N/A' }}"
                data-detalle="{{ $producto->descripcion }}"
                data-reglas="{{ json_encode($producto->reglasPrecios) }}"> <!-- 🔥 Colección de múltiples reglas -->
            {{ $producto->nombre }} - {{ $producto->stock }}
        </option>
    @endforeach
</select>


<div class="mt-2">
    <small class="text-primary font-weight-bold" id="info_lote_guia"></small>
</div>

<button type="button" class="btn btn-primary" id="btnVerProducto">
    Ver
</button>
<button type="button" class="btn btn-primary" id="btnBuscarProducto">
    Buscar
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

                       
                                @can('cobrar-ventadirecta')
                                <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-success" id="guardar">Realizar venta</button>
                                </div>
                                @endcan



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

    <div class="modal fade" id="modalBuscarProducto" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Busqueda de Producto por categoria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">
<input type="text" name="searchproducto" id="searchproducto">
<div name="searchProductoR" id="searchProductoR"></div>



</select>
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
        let promocionesAceptadas = {};

    //Constantes
    const impuesto = 12;

function evaluarReglasPrecio(reglasColeccion, cantidadLlevada, precioOriginal) {
    let resultado = {
        precioOriginal: parseFloat(precioOriginal),
        cantNum: parseInt(cantidadLlevada, 10),
        precioFinalBase: parseFloat(precioOriginal),
        descuentosEfectivoAcumulados: 0,
        unidadesRegaloAcumuladas: 0,
        propuestasPorFamilia: {} // 👈 Aquí se guardará de forma dinámica cada familia de reglas
    };

    if (!reglasColeccion || reglasColeccion === 'N/A' || reglasColeccion.length === 0) {
        return resultado;
    }

    let reglas = (typeof reglasColeccion === 'string') ? JSON.parse(reglasColeccion) : reglasColeccion;
    let cantNum = resultado.cantNum;
    let ahoraMilli = new Date().getTime(); 

    // 1. Filtrar las reglas vigentes en este instante por fecha y hora real
    let reglasVigentes = reglas.filter(r => {
        if (!r.fecha_inicio && !r.fecha_fin) return true;
        let inicioMilli = r.fecha_inicio ? new Date(r.fecha_inicio).getTime() : 0;
        let finMilli = r.fecha_fin ? new Date(r.fecha_fin).getTime() : 9999999999999;
        return ahoraMilli >= inicioMilli && ahoraMilli <= finMilli;
    });

    if (reglasVigentes.length === 0) return resultado;

    // 2. AGRUPACIÓN DINÁMICA POR FAMILIA (Agrupa por el string exacto de 'tipo_regla')
    let familiasAgrupadas = {};
    $.each(reglasVigentes, function(index, regla) {
        let tipo = regla.tipo_regla;
        if (!familiasAgrupadas[tipo]) {
            familiasAgrupadas[tipo] = [];
        }
        familiasAgrupadas[tipo].push(regla);
    });

    // 3. PROCESAR CADA FAMILIA RESPETANDO LA ESCALA DE AUTORIDAD (Mayor volumen requerido domina)
    $.each(familiasAgrupadas, function(tipoFamilia, listadoReglas) {
        // Ordenar de mayor a menor cantidad mínima requerida
        listadoReglas.sort((a, b) => parseInt(b.cantidad_minima, 10) - parseInt(a.cantidad_minima, 10));
        
        // Buscar cuál regla de esta familia específica cumple con el volumen del carrito
        let reglaDominante = null;
        for (let r of listadoReglas) {
            if (cantNum >= (parseInt(r.cantidad_minima, 10) || 0)) {
                reglaDominante = r;
                break; // Cortamos: la de mayor volumen toma el mando de esta familia
            }
        }

        // Si esta familia tiene una regla calificada para la cantidad actual, creamos su propuesta dinámica
        if (reglaDominante) {
            let min = parseInt(reglaDominante.cantidad_minima, 10) || 0;
            let beneficio = parseFloat(reglaDominante.valor_beneficio);
            let precioBase = parseFloat(precioOriginal);

            let propuesta = {
                reglaId: reglaDominante.id,
                nombre: reglaDominante.nombre,
                tipo_regla: tipoFamilia,
                tipo_beneficio: reglaDominante.tipo_beneficio,
                requiereConfirmacion: parseInt(reglaDominante.requiere_confirmacion, 10) || 0,
                // Valores candidatos calculados
                precioCalculado: precioBase,
                descuentoEfectivoCalculado: 0,
                regalosCalculados: 0,
                mensaje: ""
            };

            // Matemática adaptativa según el comportamiento de la familia
            if (tipoFamilia === 'escala_cantidad' || tipoFamilia === 'combo_mixto') {
                if (reglaDominante.tipo_beneficio === 'precio_fijo' || reglaDominante.tipo_beneficio === 'precio_fijo_rebajado') {
                    propuesta.precioCalculado = (beneficio > precioBase) ? round(beneficio / min) : beneficio;
                } else if (reglaDominante.tipo_beneficio === 'porcentaje') {
                    propuesta.precioCalculado = precioBase - (precioBase * (beneficio / 100));
                }
                propuesta.mensaje = `Modificar precio unitario a $${propuesta.precioCalculado.toFixed(2)}`;
            }
            else if (tipoFamilia === 'descuento_fijo') {
                if (reglaDominante.tipo_beneficio === 'precio_fijo' || reglaDominante.tipo_beneficio === 'precio_fijo_rebajado') {
                    // Actúa como precio cerrado por bloques, altera preferencialmente el precio unitario base
                    propuesta.precioCalculado = round(beneficio / min);
                    propuesta.mensaje = `Precio Cerrado de paquete: Ajustar precio a $${propuesta.precioCalculado.toFixed(2)}`;
                } else {
                    propuesta.descuentoEfectivoCalculado = (reglaDominante.tipo_beneficio === 'porcentaje') ? round((precioBase * cantNum) * (beneficio / 100)) : beneficio;
                    propuesta.mensaje = `Oferta Directa: Restar -$${propuesta.descuentoEfectivoCalculado.toFixed(2)} al subtotal`;
                }
            }
            else if (tipoFamilia === 'bonificacion') {
                let paso = parseInt(reglaDominante.cantidad_paso, 10) || min;
                let bloques = Math.floor(cantNum / paso);
                if (bloques > 0) {
                    propuesta.regalosCalculados = bloques * 1;
                    propuesta.mensaje = `Bonificación: Otorgar ${propuesta.regalosCalculados} unidad(es) GRATIS`;
                }
            }
            // 💡 EXTENSIBLE: Si en el futuro agregas un tipo_regla llamado 'cupon_promocional', simplemente
            // agregas un "else if (tipoFamilia === 'cupon_promocional')" aquí dentro para procesar su matemática.

            // Guardamos la propuesta final usando el nombre de la familia como la llave del objeto
            resultado.propuestasPorFamilia[tipoFamilia] = propuesta;
        }
    });

    return resultado;
}



    $(document).ready(function() {




    $('#producto_id').selectpicker();


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

        document.getElementById("btnBuscarProducto").addEventListener("click", function() {



        let modal = new bootstrap.Modal(document.getElementById("modalBuscarProducto"));
        modal.show();
    });

$('#searchproducto').on('input', function () {

    let search = $(this).val();

    if (search.length < 2) {
        $('#searchProductoR').html('');
        return;
    }

    $.ajax({
        url: "{{ route('producto.buscarPorCategoria') }}",
        method: 'GET',
        data: { search: search },
        success: function(response) {

            let html = `
                <table class="table table-hover">
                    <thead class="bg-primary">
                        <tr>
                            <th class="text-white">Código</th>
                            <th class="text-white">Nombre</th>
                            <th class="text-white">Stock</th>
                            <th class="text-white">Descripción</th>
                            <th class="text-white">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            if (response.length > 0) {
                response.forEach(function (producto) {
                    html += `
                        <tr>
                            <td>${producto.codigo}</td>
                            <td>${producto.nombre}</td>
                            <td>${producto.stock}</td>
                            <td>${producto.descripcion}</td>
                            <td>
<button type="button"
        class="btn btn-sm btn-primary seleccionar-producto"
        data-id="${producto.id}">
    Seleccionar
</button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="5" class="text-center">
                            No se encontraron resultados
                        </td>
                    </tr>
                `;
            }

            html += `
                    </tbody>
                </table>
            `;

            $('#searchProductoR').html(html);
        },
        error: function(error) {
            console.error(error);
        }
    });

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

$(document).on('click', '.seleccionar-producto', function () {
    let idProducto = $(this).data('id');

    ProductoSelect(idProducto);

    let modalEl = document.getElementById("modalBuscarProducto");
    let modal = bootstrap.Modal.getInstance(modalEl);

    if (modal) {
        modal.hide();
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

// $('#btn_agregar').click(function() {    agregarProducto(); });

$('#btn_agregar').off('click').on('click', function(e) {
    agregarProducto(e);
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

    let select = document.getElementById('producto_id');
    let option = select.selectedOptions[0];

    let stock = option.getAttribute('data-stock');
    let precio = option.getAttribute('data-precio');

    document.getElementById('stock').value = stock;
    document.getElementById('precio_venta').value = precio;

    let lote = option.getAttribute('data-lote');
    let vence = option.getAttribute('data-vence');
    let cantidadlote = option.getAttribute('data-cantidadlote');

    if(lote !== 'N/A') {
        $('#info_lote_guia').html(`<i class="fas fa-box"></i> SUGERENCIA: Tomar del <b>Lote: ${lote}</b> (Vence: ${vence}) - Cantidad en lote: ${cantidadlote})`);
    } else {
        $('#info_lote_guia').text("");
    }

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

    $('#guardar').on('click', function(e) {
    e.preventDefault();

    let formElement = document.getElementById('formVenta');
    let formData = new FormData(formElement);

    // OPCIONAL: Si tu tabla no tiene inputs ocultos, puedes capturar los datos 
    // de un array global (si es que usas uno para llenar la tabla)
    // formData.append('detalles', JSON.stringify(arrayDetalles));

    $.ajax({
        url: "{{ route('ventas.store') }}",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            Swal.fire({
                title: 'Guardando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        },
        success: function(res) {
            Swal.fire('¡Éxito!', res.success, 'success')
                .then(() => location.href = "{{ route('ventas.index') }}");
        },
error: function(xhr) {
    let mensaje = "Error al guardar";
    
    if (xhr.status === 422) {
        // Captura los errores de validación de Laravel
        let errores = xhr.responseJSON.errors;
        mensaje = "Faltan campos obligatorios:<br><ul>";
        $.each(errores, function(key, value) {
            mensaje += "<li>" + value[0] + "</li>";
        });
        mensaje += "</ul>";
    } else if (xhr.responseJSON && xhr.responseJSON.error) {
        mensaje = xhr.responseJSON.error;
    }

    Swal.fire({
        icon: 'error',
        title: 'Error de Validación',
        html: mensaje // Usamos 'html' para que se vea la lista
    });
}

    });
});
function CalcularFormula(formulalocal, montoA) {
    // 1. Validar que la fórmula exista y no sea nula/indefinida
    if (!formulalocal || formulalocal === "" || formulalocal === "0") {
        return 0; 
    }

    try {
        // Reemplazar "A" en la fórmula con el valor de la variable A
        formulaEvaluadaiva = formulalocal.replace(/A/g, montoA);
        
        // Evaluar la fórmula usando math.js
        resultadoiva = math.evaluate(formulaEvaluadaiva);
        
        // 2. Asegurar que math.js haya devuelto un número antes de formatear decimales
        if (resultadoiva !== undefined && resultadoiva !== null && !isNaN(resultadoiva)) {
            resultadoiva = parseFloat(resultadoiva.toFixed(2));
            return resultadoiva;
        }
        
        return 0;
    } catch (error) {
        console.error("Error al evaluar la expresión matemática:", error);
        return 0;
    }
}

function agregarProducto(e) {
    if (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
    }

    let rawValue = document.getElementById('producto_id').value;
    if (!rawValue || rawValue === "") {
        showModal('Seleccione un producto primero');
        return false;
    }

    // 1. Extraer el ID numérico puro como un entero base 10
    let dataProducto = rawValue.split('-');
    let idProducto = parseInt(dataProducto[0], 10).toString(); 
    
    let optionActiva = $('#producto_id').find('option:selected');
    let nameProducto = optionActiva.text().split(' - ')[0].trim(); // Solo el nombre limpio

    let cantidadInput = $('#cantidad').val();
    let precioVentaInput = $('#precio_venta').val();
    let stockInput = $('#stock').val();
    var comprobante = document.getElementById('comprobante_id').value;

    if (comprobante === "") {
        alert("Por favor, seleccione un comprobante.");
        return false; 
    }

    let cantNum = parseInt(cantidadInput, 10);
    let precioBase = parseFloat(precioVentaInput);
    let stockNum = parseInt(stockInput, 10);

    if (isNaN(cantNum) || cantNum <= 0) {
        showModal('Ingrese una cantidad válida mayor a 0');
        return false;
    }

    // 2. Buscar si el ID ya existe en el carrito
    let indexExiste = producto.indexOf(idProducto);

    if (indexExiste !== -1) {
        let nuevaCantidadTotal = parseInt(Cantidad[indexExiste], 10) + cantNum;
        if (nuevaCantidadTotal > stockNum) {
            showModal('La cantidad acumulada supera el stock disponible');
            return false;
        }
        Cantidad[indexExiste] = nuevaCantidadTotal; // Suma y acumula en la misma fila
    } else {
        if (cantNum > stockNum) {
            showModal('La cantidad supera el stock disponible');
            return false;
        }
        // Insertar registro nuevo
        producto.push(idProducto);
        nombre.push(nameProducto);
        Cantidad.push(cantNum);
        precioventa.push(precioBase);
        Descuento.push(0);
        subtotal.push(0);
        subiva.push(0);
    }

    // Ejecutar recálculo masivo centralizado
    recalcularYRedibujarVenta();
    limpiarCampos();
}




// ─── FUNCIÓN AUXILIAR AISLADA: Renderiza la fila final en la tabla ───
function ejecutarInsercionTabla(idProducto, nameProducto, cantNum, precioUnidad, descuentoAplicado, stockNum) {
    if (cantNum <= stockNum) {
        let subtotalFila = round((cantNum * precioUnidad) - descuentoAplicado);
        if (subtotalFila < 0) subtotalFila = 0;

        subtotal[cont] = subtotalFila;
        sumas += cantNum;
        total += subtotalFila;
        sumadocdb = round(total);
        
        subiva[cont] = CalcularFormula(formula, subtotalFila);
        resultadoiva = CalcularFormula(formula, total);
        IVA = resultadoiva;

        let fila = '<tr id="fila' + cont + '">' +
            '<th>' + (cont + 1) + '</th>' +
            '<td><input type="hidden" name="arrayidproducto[]" value="' + idProducto + '">' + nameProducto + '</td>' +
            '<td><input type="hidden" name="arraycantidad[]" value="' + cantNum + '">' + cantNum + '</td>' +
            '<td><input type="hidden" name="arrayprecioventa[]" value="' + precioUnidad + '">' + precioUnidad + '</td>' +
            '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[cont] + '">' + subiva[cont] + '</td>' +
            '<td><input type="hidden" name="arraydescuento[]" value="' + descuentoAplicado + '">' + descuentoAplicado + '</td>' +
            '<td>' + subtotalFila + '</td>' +
            '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';

        $('#tabla_detalle_tbody').append(fila);
        limpiarCampos();
        disableButtons();

        producto[cont] = idProducto;
        Cantidad[cont] = cantNum;
        precioventa[cont] = precioUnidad;
        nombre[cont] = nameProducto;
        cantidadarticulos += cantNum;
        Descuento[cont] = descuentoAplicado;
        cont++;
        
        sumarArreglos(formulas, monto);

        $('#sumas').html(sumas);
        $('#IVA').html(IVA);
        $('#total').html(total.toFixed(2));
        $('#subiva').val(subiva);
        $('#impuesto').val(IVA);
        $('#inputTotal').val(total);
    } else {
        showModal('Cantidad incorrecta: Supera el stock disponible');
    }
}



function agregarProductoScanner(sku) {
    let dataProducto = "";
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
                    Swal.fire({
                        icon: 'warning',
                        title: 'El producto no se encuentra disponible',
                        text: 'Código: ' + comprobanteId,
                    });
                    return;
                }

                var detalle = response;
                let idProductos = detalle.producto_id;
                let seleccionable = idProductos + "-" + detalle.existencia + "-" + detalle.precio_venta;
                dataProducto = seleccionable;
                idProducto = detalle.producto_id;
                nameProducto = detalle.producto_nombre;

                // Sincronizar el selectpicker
                $("#producto_id option").each(function () {
                    let v = $(this).val();
                    if (v.startsWith(seleccionable)) {
                        $("#producto_id").val(v).change();
                        $('#producto_id').selectpicker('refresh');
                        return false; 
                    }
                });
                
                $('#precio_venta').val(detalle.precio_venta);
                $('#stock').val(detalle.existencia);

                let precioVentaBase = parseFloat(detalle.precio_venta);
                let stockMax = parseInt(detalle.existencia, 10);
                var comprobante = document.getElementById('comprobante_id').value;

                if (comprobante === "") {
                    alert("Por favor, seleccione un comprobante.");
                    return false; 
                }

                // ─── LÓGICA DE ACUMULACIÓN INTELIGENTE ───
                // Buscamos si el ID de este producto ya está en el carrito
                let indexExiste = producto.indexOf(idProducto);
                let cantidadAProcesar = 1; // Por defecto el escáner suma de 1 en 1

                if (indexExiste !== -1) {
                    // Si ya existe, calculamos cuánto tendría acumulado en total
                    cantidadAProcesar = parseInt(Cantidad[indexExiste], 10) + 1;
                    
                    if (cantidadAProcesar > stockMax) {
                        showModal('No hay suficiente stock para seguir acumulando este producto');
                        return false;
                    }

                    // Actualizamos temporalmente el arreglo de cantidades antes del recálculo
                    Cantidad[indexExiste] = cantidadAProcesar;
                } else {
                    // Si es un producto nuevo en esta venta, lo inicializamos en los arreglos
                    producto.push(idProducto);
                    nombre.push(nameProducto.split(' - ')[0].trim());
                    Cantidad.push(1);
                    precioventa.push(precioVentaBase);
                    Descuento.push(0);
                    subtotal.push(precioVentaBase);
                    subiva.push(0);
                }

                // ─── RECALCULAR Y REDIBUJAR TODA LA TABLA ───
                recalcularYRedibujarVenta();
                limpiarCampos();
            },
            error: function(xhr, status, error) {
                console.error("Error en el flujo del escáner:", error);
            }
        });
    }
}

function recalcularYRedibujarVenta() {
    var tableBody = $('#tabla_detalle_tbody');
    tableBody.empty(); // Limpiar contenedor físico de la vista

    sumas = 0;
    total = 0;
    cantidadarticulos = 0;
    cont = 0; 

    let pausarPorConfirmacion = false;

    $.each(producto, function(index, idProd) {
        if (!idProd || idProd === "" || pausarPorConfirmacion) return; 

        let cantAcumulada = parseInt(Cantidad[index], 10);
        let precioBase = parseFloat(precioventa[index]);
        let nameProd = nombre[index];

        let optionItem = $("#producto_id option").filter(function() {
            let optVal = $(this).val() ? $(this).val().toString().trim() : "";
            return optVal === idProd || optVal.startsWith(idProd + "-");
        });
        let reglasColeccion = optionItem.data('reglas') || [];

        // 1. Obtener el mapa dinámico de propuestas del motor
        let analisis = evaluarReglasPrecio(reglasColeccion, cantAcumulada, precioBase);
        
        // 2. Inicializar las variables contables base de consolidación de cascada
        let precioUnitarioCascada = precioBase;
        let descuentosEfectivoCascada = 0;
        let unidadesRegaloCascada = 0;
        let tagsPromosAplicadas = [];

        // 3. RECORRER E INTERCEPTAR DINÁMICAMENTE CADA FAMILIA CALIFICADA
        $.each(analisis.propuestasPorFamilia, function(nombreFamilia, datosPropuesta) {
            if (pausarPorConfirmacion) return false; 

            let tokenDecision = idProd + "_" + nombreFamilia + "_" + cantAcumulada;

            if (datosPropuesta.requiereConfirmacion === 1) {
                if (promocionesAceptadas[tokenDecision] === undefined) {
                    pausarPorConfirmacion = true;
                    
                    Swal.fire({
                        title: 'Confirmar Promoción',
                        html: `Se detectó un beneficio para la familia <b>${nombreFamilia}</b>:<br><b>${datosPropuesta.mensaje}</b> por la regla <b>${datosPropuesta.nombre}</b>.<br>¿Desea aplicarlo a la venta?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#dc3545',
                        confirmButtonText: 'Sí, aplicar',
                        cancelButtonText: 'No, precio regular',
                        allowOutsideClick: false
                    }).then((result) => {
                        promocionesAceptadas[tokenDecision] = !!result.isConfirmed; 
                        recalcularYRedibujarVenta(); 
                    });
                    return false; 
                }
                
                if (promocionesAceptadas[tokenDecision] === true) {
                    inyectarBeneficioAFase(datosPropuesta);
                }
            } else {
                inyectarBeneficioAFase(datosPropuesta);
            }
        });

        function inyectarBeneficioAFase(prop) {
            if (prop.precioCalculado < precioUnitarioCascada) {
                precioUnitarioCascada = prop.precioCalculado;
            }
            descuentosEfectivoCascada += prop.descuentoEfectivoCalculado;
            // 🔥 CORREGIDO: Asignación directa del volumen de regalos determinado en la Fase 3 del motor
            unidadesRegaloCascada = prop.regalosCalculados > 0 ? prop.regalosCalculados : unidadesRegaloCascada;
            
            tagsPromosAplicadas.push(prop.nombre);
        }

        if (pausarPorConfirmacion) return false; 

        // =========================================================================
        // 📊 CONSOLIDACIÓN NETO EN CASCADA COMPUESTA CORREGIDA
        // =========================================================================
        let costoTotalOrdinarioLista = precioBase * cantAcumulada;
        
        let unidadesACobrarNetas = cantAcumulada - unidadesRegaloCascada;
        if (unidadesACobrarNetas < 0) unidadesACobrarNetas = 0;

        let subtotalCalculadoRenglon = round(unidadesACobrarNetas * precioUnitarioCascada) - descuentosEfectivoCascada;
        if (subtotalCalculadoRenglon < 0) subtotalCalculadoRenglon = 0;

        let descuentoTotalAhorrado = round(costoTotalOrdinarioLista - subtotalCalculadoRenglon);
        let precioVisualProrrateado = round(subtotalCalculadoRenglon / cantAcumulada);

        // Guardar en arreglos de memoria contable locales
        subtotal[index] = subtotalCalculadoRenglon;
        Descuento[index] = descuentoTotalAhorrado;
        subiva[index] = CalcularFormula(formula, subtotalCalculadoRenglon);

        sumas += cantAcumulada;
        total += subtotalCalculadoRenglon;
        cantidadarticulos += cantAcumulada;

        let nombreVisualCelda = nameProd;
        if (tagsPromosAplicadas.length > 0) {
            nombreVisualCelda += ` <small class="text-success d-block font-weight-bold">✓ Promo: ${tagsPromosAplicadas.join(' + ')}</small>`;
        }

        // Renderizado del renglón en la tabla HTML
        let fila = '<tr id="fila' + cont + '">' +
            '<th>' + (cont + 1) + '</th>' +
            '<td><input type="hidden" name="arrayidproducto[]" value="' + idProd + '">' + nombreVisualCelda + '</td>' +
            '<td><input type="hidden" name="arraycantidad[]" value="' + cantAcumulada + '">' + cantAcumulada + '</td>' +
            '<td><input type="hidden" name="arrayprecioventa[]" value="' + precioUnitarioCascada + '">' + precioUnitarioCascada + '</td>' +
            '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[index] + '">' + subiva[index] + '</td>' +
            '<td><input type="hidden" name="arraydescuento[]" value="' + descuentoTotalAhorrado + '">' + descuentoTotalAhorrado + '</td>' +
            '<td>' + subtotalCalculadoRenglon.toFixed(2) + '</td>' +
            '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';

        tableBody.append(fila);
        cont++; 
    });

    if (pausarPorConfirmacion) return; 

    // Renderizar contadores finales unificados del pie de la tabla
    IVA = CalcularFormula(formula, total);

    $('#sumas').html(sumas);
    $('#IVA').html(IVA);
    $('#total').html(total.toFixed(2));
    $('#subiva').val(subiva);
    $('#impuesto').val(IVA);
    $('#inputTotal').val(total);

    sumarArreglos(formulas, monto);
    disableButtons();
}


function agregarProductoScannerCam(sku) {
    let dataProducto = "";
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
                let seleccionable = idProductos + "-" + detalle.existencia + "-" + detalle.precio_venta;
                dataProducto = seleccionable;
                idProducto = detalle.producto_id;
                nameProducto = detalle.producto_nombre;

                $("#producto_id option").each(function () {
                    let v = $(this).val();
                    if (v.startsWith(seleccionable)) {
                        $("#producto_id").val(v).change();
                        $('#producto_id').selectpicker('refresh');
                        return false; 
                    }
                });

                $('#precio_venta').val(detalle.precio_venta);
                $('#stock').val(detalle.existencia);

                let cantidad = parseInt($('#cantidad').val()) || 1; 
                let precioVenta = parseFloat(detalle.precio_venta);
                let stock = parseInt(detalle.existencia);
                var comprobante = document.getElementById('comprobante_id').value;

                if (comprobante === "") {
                    alert("Por favor, seleccione un comprobante.");
                    return false;
                }

                if (idProducto != '' && cantidad > 0) {
                    if (cantidad <= stock) {
                        
                        // Extraer reglas dinámicas desde la opción activa del selectpicker
                        let reglasColeccion = $('#producto_id').find('option:selected').data('reglas') || [];
                        let reglaEfectiva = evaluarReglasPrecio(reglasColeccion, cantidad, precioVenta);

                        // Asignación directa limpia sin duplicar variables con let
                        let descuento = reglaEfectiva.descuentoTotal;
                        let precioVentaFinal = reglaEfectiva.precioFinal;

                        if (reglaEfectiva.mensaje !== "") {
                            showModal(reglaEfectiva.mensaje, 'success'); 
                        }

                        subtotal[cont] = round(cantidad * precioVentaFinal - descuento);
                        if (subtotal[cont] < 0) subtotal[cont] = 0;

                        sumas += cantidad;
                        total += subtotal[cont];
                        sumadocdb = round(total);

                        subiva[cont] = CalcularFormula(formula, subtotal[cont]);
                        resultadoiva = CalcularFormula(formula, total);
                        IVA = resultadoiva;

                        let fila = '<tr id="fila' + cont + '">' +
                            '<th>' + (cont + 1) + '</th>' +
                            '<td><input type="hidden" name="arrayidproducto[]" value="' + idProducto + '">' + nameProducto + '</td>' +
                            '<td><input type="hidden" name="arraycantidad[]" value="' + cantidad + '">' + cantidad + '</td>' +
                            '<td><input type="hidden" name="arrayprecioventa[]" value="' + precioVentaFinal + '">' + precioVentaFinal + '</td>' +
                            '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[cont] + '">' + subiva[cont] + '</td>' +
                            '<td><input type="hidden" name="arraydescuento[]" value="' + descuento + '">' + descuento + '</td>' +
                            '<td>' + subtotal[cont] + '</td>' +
                            '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                            '</tr>';

                        $('#tabla_detalle_tbody').append(fila);
                        limpiarCampos();
                        disableButtons();

                        resultadoiva = resultadoiva.toFixed(2);

                        producto[cont] = idProducto;
                        Cantidad[cont] = cantidad;
                        precioventa[cont] = parseFloat(precioVentaFinal);
                        nombre[cont] = nameProducto;
                        cantidadarticulos += cantidad;
                        Descuento[cont] = descuento;
                        cont++;
                        
                        sumarArreglos(formulas, monto);

                        $('#sumas').html(sumas);
                        $('#IVA').html(IVA);
                        $('#total').html(total);
                        $('#subiva').val(subiva);
                        $('#impuesto').val(IVA);
                        $('#inputTotal').val(total);
                    } else {
                        showModal('Cantidad incorrecta');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error("Error al cargar los detalles:", error);
            }
        });
    }
}


function ProductoSelect(idProducto) {
    $('#producto_id').selectpicker('val', idProducto.toString());
    $('#producto_id').trigger('change');
}


function eliminarProducto(indice) {
    // Remover el elemento directamente de todos los arreglos usando su posición
    producto.splice(indice, 1);
    nombre.splice(indice, 1);
    Cantidad.splice(indice, 1);
    precioventa.splice(indice, 1);
    Descuento.splice(indice, 1);
    subtotal.splice(indice, 1);
    subiva.splice(indice, 1);

    // 🔥 Volver a ejecutar el motor para que redibuje la tabla limpia con los que quedan
    recalcularYRedibujarVenta();
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

function iniciarScanner(tipo = "barra") {

    if (escaneando) return;

    scanner = new Html5Qrcode("reader");

    escaneando = true;

    scanner.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: tipo === "barra"
                ? { width: 250, height: 150 }
                : 250
        },

        (codigo) => {

            console.log("Código ver:", codigo);

            StopScanner();
            if (tipo === "barra") {
                agregarProductoScanner(codigo);
            } else {
                agregarProductoScanner(codigo);
            }
                                Swal.fire({
    icon: 'warning',
    title: 'Se ha seleccionado un producto',
    text: 'Codigo: ' + codigo,

});



            // 🔥 si quieres escaneo continuo → NO detener aquí
            // scanner.stop();
        },

        (error) => {
            // ignorar errores
        }
    );
}

function StopScanner() {

    if (!scanner || !escaneando) return;

    scanner.stop()
    .then(() => {
        console.log("Scanner detenido");
        escaneando = false;
        scanner = null;
    })
    .catch(err => {
        console.error("Error al detener:", err);
    });
}


let scanner = null;
let escaneando = false;
</script>
@endpush
