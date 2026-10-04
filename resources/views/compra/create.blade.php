@extends('layouts.app')

@section('title','Realizar compra')

@push('css')

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/math.js') }}"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
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


<form id="formCompra" action="{{ route('compras.store') }}" method="post">
    @csrf

    <div class="container-lg mt-4">
        <div class="row gy-4">
            <!------Compra producto---->
            <div class="col-xl-8">
                <div class="text-white bg-primary p-1 text-center">
                    Detalles de la compra
                </div>
                <div class="p-3 border border-3 border-primary">
                    <div class="row gy-4">

                        <!-----SKU---->
                        <div class="col-sm-4">
                            <label for="SKU" class="form-label">SKU:</label>
                            <input type="text" name="SKU" id="SKU" class="form-control">
                        </div>

                <div class="col-12">

<!-- 🚀 SELECT DE PRODUCTOS RECONSTRUIDO CON EVALUACIÓN DIRECTA ESTRICTA -->
<select id="producto_id" name="producto_id" class="form-control selectpicker" data-live-search="true" data-size="10" title="Busque un producto aquí">
    @foreach ($productos as $item)
        @php
            $esPerecederoReal = (int) $item->perecedero;
        @endphp
        <!-- 🚀 SIN CLASES RESERVADAS: La etiqueta <option> debe iniciar limpia -->
        <option value="{{ $item->id }}" 
                data-img="{{ $item->img_path ?? '' }}" 
                data-detalle="{{ $item->descripcion ?? '' }}" 
                data-perecedero="{{ $esPerecederoReal == 1 ? 1 : 0 }}">
            {{ $item->nombre }}
        </option>
    @endforeach
</select>





<button type="button" class="btn btn-primary" id="btnVerProducto">
    Ver
</button>


                        </div>

                        <!-- 🚀 HTML BLINDADO Y OPTIMIZADO PARA TRANSICIONES DINÁMICAS -->
                        <div class="col-md-4 d-none" id="contenedor_fecha">
                            <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento:</label>
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="form-control">
                            <small class="text-danger">Producto perecedero: requiere fecha.</small>
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
                                            <th class="text-white">Lote</th>
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
                            <select name="proveedore_id" id="proveedore_id" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona" data-size='5'>
                                @foreach ($proveedores as $item)
                                <option value="{{$item->id}}" {{ old("proveedore_id") == $item->id ? "selected" : ''}}>{{$item->persona->razon_social}}</option>
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
            <option value="{{ $item->id }}" 
                @selected(old('comprobante_id', $comprobantes->where('defauldoc', 1)->first()?->id) == $item->id)>
                {{ $item->tipo_comprobante }} {{ $item->defauldoc == 1 ? '(Por defecto)' : '' }}
            </option>
        @endforeach
    </select>
    @error('comprobante_id')
        <small class="text-danger">{{ '*'.$message }}</small>
    @enderror
</div>

                        <div class="col-12">

<div class="form-group">
    <label for="TipoFolio">Tipo de Folio:</label>
    <div>
        <label class="radio-inline">
            {{-- Solo 'A' lleva el segundo parámetro en old() como valor por defecto --}}
            <input type="radio" name="TipoFolio" value="A" {{ old('TipoFolio', 'A') == 'A' ? 'checked' : '' }}> Automatico
        </label>
        <label class="radio-inline">
            {{-- Para los demás, comparamos sin valor por defecto --}}
            <input type="radio" name="TipoFolio" value="M" {{ old('TipoFolio') == 'M' ? 'checked' : '' }}> Manual
        </label>
        <label class="radio-inline">
            <input type="radio" name="TipoFolio" value="F" {{ old('TipoFolio') == 'F' ? 'checked' : '' }}> Folio Manual
        </label>
    </div>
    @error('TipoFolio')
    <small class="text-danger">{{ '*'.$message }}</small>
    @enderror
</div>

                                                <input type="hidden" name="items_tabla" id="items_tabla">
                                                                                                <!--MontoFolio---->
                        <div class="col-sm-6">
                            <label for="MontoFolio" class="form-label">Monto:</label>
                            <input readonly type="text" name="MontoFolio" id="MontoFolio" class="form-control border-success" value="{{ old('MontoFoliio') }}">
                            @error('MontoFolio')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>
                        </div>

                        <!--Numero de comprobante-->
                        <div class="col-12">
                            <label for="numero_comprobante" class="form-label">Numero de comprobante:</label>
                            <input readonly type="text" name="numero_comprobante" id="numero_comprobante" class="form-control" value="{{ old('numero_comprobante', '0') }}" required>
                            @error('numero_comprobante')
                            <small class="text-danger">{{ '*'.$message }}</small>
                            @enderror
                        </div>

                        <!--Impuesto---->
                        <div class="col-sm-6 mb-2">
                            <label for="impuesto" class="form-label">Impuesto:</label>
                            <input readonly type="text" name="impuesto" id="impuesto" class="form-control border-success" value="{{ old('impuesto') }}">
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
</form>

<!-- Modal de Registro Express de Producto (Bootstrap 4) -->
<div class="modal" id="modalProductoNuevo" window-target="modal" tabindex="-1" role="dialog" aria-labelledby="modalProductoLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalProductoLabel">📦 Registrar Producto Nuevo</h5>
                    <!-- Añadida la clase "close" para que se posicione correctamente a la derecha -->
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

            
            <form id="formProductoExpress" enctype="multipart/form-data">
                <div class="modal-body bg-light">
                    <div class="row g-3">
                        
                        <!--- Código (Bloqueado con el SKU escaneado) ---->
                        <div class="col-md-4 mb-3">
                            <label for="modal_codigo" class="form-label font-weight-bold">Código / SKU:</label>
                            <input type="text" name="modal_codigo" id="modal_codigo" class="form-control border-primary font-weight-bold" readonly>
                        </div>

                        <!--- Nombre ---->
                        <div class="col-md-6 mb-3">
                            <label for="modal_nombre" class="form-label font-weight-bold">Nombre del Producto *</label>
                            <input type="text" name="nombre" id="modal_nombre" class="form-control" required>
                        </div>

                        <!--- Perecedero ---->
                        <div class="col-md-2 mb-3">
                            <div class="form-check form-switch mt-4 pt-2">
                                <input type="hidden" name="perecedero" value="0">
                                <input class="form-check-input" type="checkbox" name="perecedero" id="perecedero" value="1">
                                <label class="form-check-label font-weight-bold" for="perecedero">¿Perecedero?</label>
                            </div>
                        </div>

                        <!--- Descripción ---->
                        <div class="col-12 mb-3">
                            <label for="modal_descripcion" class="form-label font-weight-bold">Descripción:</label>
                            <textarea name="descripcion" id="modal_descripcion" rows="2" class="form-control"></textarea>
                        </div>




                        <!--- Marca (Creable con Selectpicker) ---->
                        <div class="col-md-4 mb-3">
                            <label for="modal_marca_id" class="form-label font-weight-bold">Marca:</label>
                            <select data-size="4" title="Seleccione o escriba..." data-live-search="true" name="modal_marca_id" id="modal_marca_id" class="form-control select-modal-express show-tick">
                                @foreach ($marcas as $item)
                                    <option value="{{$item->id}}">{{$item->caracteristica->nombre ?? 'Marca '.$item->id}}</option>
                                @endforeach
                            </select>
                        </div>

                        <!--- Presentación (Creable con Selectpicker) ---->
                        <div class="col-md-4 mb-3">
                            <label for="modal_presentacione_id" class="form-label font-weight-bold">Presentación:</label>
                            <select data-size="4" title="Seleccione o escriba..." data-live-search="true" name="modal_presentacione_id" id="modal_presentacione_id" class="form-control select-modal-express show-tick">
                                @foreach ($presentaciones as $item)
                                    <option value="{{$item->id}}">{{$item->caracteristica->nombre ?? 'Presentación '.$item->id}}</option>
                                @endforeach
                            </select>
                        </div>

                        <!--- Categorías (Múltiple) ---->
                        <div class="col-md-4 mb-3">
                            <label for="modal_categorias" class="form-label font-weight-bold">Categorías:</label>
                            <select data-size="4" title="Seleccione o escriba..." data-live-search="true" name="modal_categorias[]" id="modal_categorias" class="form-control select-modal-express show-tick" multiple>
                                @foreach ($categorias as $item)
                                    <option value="{{$item->id}}">{{$item->caracteristica->nombre ?? 'Categoría '.$item->id}}</option>
                                @endforeach
                            </select>
                        </div>

                        <!--- SECCIÓN MULTIMEDIA TOTAL ---->
                        <div class="col-12 mt-2">
                            <label class="form-label font-weight-bold">Fotografía del Producto:</label>
                            <div class="row align-items-center bg-white p-3 border rounded mx-0">
                                
                                <!-- Botones Izquierda -->
                                <div class="col-md-6 px-1">
                                    <div class="d-flex flex-column gap-2">
                                        <label class="btn btn-outline-primary w-100 mb-2 py-2" for="modal_img_path">
                                            📁 Elegir Imagen de Galería
                                        </label>
                                        <input type="file" name="img_path" id="modal_img_path" class="d-none" accept="image/*">

                                        <button type="button" class="btn btn-outline-success w-100 py-2" id="btn-activar-camara">
                                            📷 Usar Cámara Web
                                        </button>
                                    </div>
                                    
                                    <!-- Stream de la Cámara -->
                                    <div id="contenedor-camara-web" class="d-none mt-3">
                                        <video id="video-camara" autoplay playsinline class="w-100 border rounded bg-dark" style="max-height: 180px;"></video>
                                        <button type="button" class="btn btn-sm btn-success w-100 mt-2" id="btn-capturar-foto">📸 Capturar Fotografía</button>
                                    </div>
                                </div>

                                <!-- Previsualización Derecha -->
                                <div class="col-md-6 text-center border-start">
                                    <div class="border rounded p-2 bg-light d-flex align-items-center justify-content-center mx-auto" style="height: 160px; max-width: 220px;">
                                        <img id="vista-previa-img" src="" class="img-fluid d-none" style="max-height: 140px;" alt="Preview">
                                        <span id="texto-sin-foto" class="text-muted small">Sin imagen seleccionada</span>
                                    </div>
                                    <canvas id="canvas-foto" class="d-none"></canvas>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer bg-white">
                    <!-- Cambiado data-bs-dismiss por data-dismiss para Bootstrap 4 -->
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">Guardar e Inyectar a Compra</button>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script src="https://cloudflare.com"></script>
<script src="https://unpkg.com"></script>
<script>
    // =========================================================================
    // 1. VARIABLES Y CONSTANTES GLOBALES
    // =========================================================================
    const contenedor = document.getElementById('contenedor-dinamico');
    let cont = 0;
    let idventacabecera = 0;
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
    let producto = [];
    let Cantidad = [];
    let Descuento = [];
    let preciocompra = [];
    let precioventa = [];
    let nombre = [];
    let resultadoiva = 0;
    let cantidadarticulos = 0;
    let formula = '';
    let totalMASIVA = 0;
    let formulaEvaluadaiva = '';
    const plantilla = document.getElementById('plantilla-select');
    const impuesto = 12;

    // Variables de Control Express y Multimedia
    let skuTimeout = null;
    let timeoutEscaner = null; 
    let nuevaMarca = null;
    let nuevaPresentacion = null;
    let nuevasCategorias = [];
    let streamCamara = null;
    let estaProcesandoScan = false; 
    let scanner = null;
    let escaneando = false;

    // =========================================================================
    // 2. DEPURADOR DE OPCIONES DUPLICADAS EN EL DOM NATIVO
    // =========================================================================
    function purgarDuplicadosVisuales() {
        $('.select-modal-express, .combo-modal, .selectpicker').each(function() {
            let textosRegistrados = {};
            
            $(this).find('option').each(function() {
                let textoLimpio = $(this).text().trim().toLowerCase();
                let valorActual = $(this).val();

                if (textoLimpio !== "" && valorActual !== "") {
                    if (textosRegistrados[textoLimpio]) {
                        $(this).remove(); 
                    } else {
                        textosRegistrados[textoLimpio] = true;
                    }
                }
            });
        });
    }
    // =========================================================================
    // 3. INICIALIZACIÓN GENERAL (DOCUMENT READY)
    // =========================================================================
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

 $('#comprobante_id').on('change', function() {
    var comprobanteId = $(this).val();

    if (comprobanteId) {
        $.ajax({
            url: '/compras/detalles/' + comprobanteId,
            type: 'GET',
            success: function(response) {
                var detalles = response.detalles;
                var tableBody = $('#detalle_tbody');
                tableBody.empty(); // Limpiar el cuerpo contable de forma segura

                // Reiniciar los arreglos globales de forma limpia antes de capturar
                formulas = [];
                monto = [];
                cuenta = [];
                tipo = [];
                formula = '';

                // 1. Primer paso: Guardar datos en memoria y pintar únicamente las filas HTML crudas
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

                    // Almacenar en las variables indexadas globales
                    formulas[index] = detalle.formula;
                    monto[index] = detalle.valorminimo;
                    cuenta[index] = detalle.cuenta_contable_nombre;
                    tipo[index] = detalle.Naturaleza;
                    formula = detalle.formuladoc;
                });

                // 2. 🚀 CLAVE DEFINITIVA: Ejecutar el cálculo contable UNA SOLA VEZ fuera del bucle
                // Esto previene que las órdenes de refresco visual colapsen y dupliquen textos
                if (formula && formula !== "") {
                    $('#impuesto').val(formula);
                }

                if (formulas.length > 0) {
                    sumarArreglos(formulas, monto);
                }

                // 3. Renderizar los totales de los artículos en paralelo de forma segura
                if (typeof llenarTablaventas === 'function') {
                    llenarTablaventas();
                }
            },
            error: function(xhr, status, error) {
                console.error("Error crítico al cargar los detalles del comprobante:", error);
            }
        });
    }
});


        $('#guardar').on('click', function(e) {
    e.preventDefault();

    let formElement = document.getElementById('formCompra');
    let formData = new FormData(formElement);

    // OPCIONAL: Si tu tabla no tiene inputs ocultos, puedes capturar los datos 
    // de un array global (si es que usas uno para llenar la tabla)
    // formData.append('detalles', JSON.stringify(arrayDetalles));

    $.ajax({
        url: "{{ route('compras.store') }}",
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
                .then(() => location.href = "{{ route('compras.index') }}");
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

// =========================================================================
// 🛡️ GUARDIÁN DEL SELECTOR: PURGA AUTOMÁTICA DE DUPLICADOS EN LA LISTA AZUL
// =========================================================================
$(document).on('refreshed.bs.select loaded.bs.select render.bs.select', '#producto_id', function () {
    let dropdownMenu = $(this).closest('.bootstrap-select').find('ul.dropdown-menu.inner');
    if (dropdownMenu.length > 0) {
        let textosVistosVisuales = {};
        
        dropdownMenu.find('li').each(function() {
            let elementoTexto = $(this).find('.text');
            let textoCompleto = elementoTexto.text().trim();
            
            if (textoCompleto !== "") {
                // Reparar de inmediato strings pegados (Ej: "Alka-seltzerAlka-seltzer" -> "Alka-seltzer")
                let mitadLargo = textoCompleto.length / 2;
                let primeraMitad = textoCompleto.substring(0, mitadLargo);
                let segundaMitad = textoCompleto.substring(mitadLargo);
                
                if (primeraMitad === segundaMitad) {
                    elementoTexto.text(primeraMitad);
                    textoCompleto = primeraMitad;
                }

                // Borrar el elemento físico repetido de la lista desplegable visual
                let textoLimpioMinusc = textoCompleto.toLowerCase();
                if (textosVistosVisuales[textoLimpioMinusc]) {
                    $(this).remove(); 
                } else {
                    textosVistosVisuales[textoLimpioMinusc] = true;
                }
            }
        });
    }
});



        // 1. Inicializar de golpe y de forma única los selectpickers maestros de la pantalla trasera
        $('#proveedor_id, #comprobante_id, #producto_id').selectpicker('destroy');

                $('#producto_id').selectpicker({
            liveSearch: true,           // Activa el buscador manual escribiendo
            size: 10,                   // Muestra hasta 10 elementos antes de poner scroll
            noneResultsText: 'No se encontró ningún producto con: {0}',
            liveSearchPlaceholder: 'Escriba para buscar el artículo...',
            selectOnTab: false          // 🛑 DETIENE LA PRESELECCIÓN AUTOMÁTICA AL USAR EL TECLADO
        });

                $('#proveedor_id, #comprobante_id').selectpicker({
            liveSearch: true,
            size: 10,
            noneResultsText: 'No se encontraron coincidencias: {0}'
        });


        // 3. Inicializar los selectores estándar del modal express de forma aislada
        $('.combo-modal, .select-modal-express').selectpicker({
            noneResultsText: 'No se encontró, presione enter para registrar: {0}'
        });

        // Asegurar selección ÚNICA estricta para Marca y Presentación del modal express
        $('#modal_marca_id, #modal_presentacione_id').selectpicker({
            noneResultsText: 'No se encontró, presione enter para registrar: {0}',
            multiple: false 
        });


        // 2. Inicializar de forma aislada los componentes específicos del modal express
        $('#modal_marca_id, #modal_presentacione_id').selectpicker({
            noneResultsText: 'No se encontró, presione enter para registrar: {0}',
            multiple: false 
        });

        $('#modal_categorias').selectpicker({
            noneResultsText: 'No se encontró, presione enter para registrar: {0}'
        });

                // 3. Carga asíncrona de catálogos nativos (Evita colisiones en la red)
        if (typeof cargarProveedoresInicio === 'function') { cargarProveedoresInicio(); }
        if (typeof cargarComprobantesInicio === 'function') { cargarComprobantesInicio(); }


        // =========================================================================
        // DISPARO CONTROLADO DE ENTRADA (SUSTITUTO SIN REFRESH DESTRUCTIVO)
        // =========================================================================
        setTimeout(function() {
            let selectComprobante = $('#comprobante_id');
            let valorActual = selectComprobante.val();

            if (valorActual && valorActual !== "") {
                console.log("🚀 [Entrada] Comprobante inicial detectado:", valorActual, ". Forzando carga de póliza contable.");
                
                // 1. Despertar el evento change de forma limpia para que cargue el AJAX contable
                selectComprobante.trigger('change');
            } else {
                // 2. Si no hay valor por defecto, simplemente forzar el estado vacío visual sin redibujar el DOM
                $('.selectpicker').selectpicker('val', '');
            }
            
            // 🛑 ELIMINADO: $('.selectpicker').selectpicker('refresh'); 
            // Ya no es necesario porque los combos se inicializaron correctamente arriba
        }, 200);

                setTimeout(function() {
            if ($('#comprobante_id').val() && $('#comprobante_id').val() !== "") {
                $('#comprobante_id').trigger('change');
            }
        }, 200);


                // 5. 🚀 RECONSTRUCCIÓN SEGURA DE OLD ITEMS DE LARAVEL (CORREGIDA)
        // Mapea correctamente hacia tu función centralizada de renderizado 'llenarTablaventas'
        let datosViejos = {!! json_encode(old('items_tabla')) !!};
        if (datosViejos && datosViejos !== "null" && datosViejos !== "") {
            try {
                let productosViejos = typeof datosViejos === 'string' ? JSON.parse(datosViejos) : datosViejos;
                if (Array.isArray(productosViejos) && productosViejos.length > 0) {
                    
                    // Inyectar en los arreglos globales antes de pintar
                    productosViejos.forEach((p, idx) => {
                        producto[idx] = p.id_producto || p.producto_id;
                        nombre[idx] = p.nombre || '';
                        Cantidad[indexActual] = parseInt(p.cantidad, 10) || 0;
                        preciocompra[idx] = parseFloat(p.precio_compra) || 0;
                        precioventa[idx] = parseFloat(p.precio_venta) || 0;
                        subtotal[idx] = parseFloat((Cantidad[idx] * preciocompra[idx]).toFixed(2));
                    });

                    // Invocar una única vez el redibujado en lugar de hacerlo en un bucle pesado
                    llenarTablaventas();
                    disableButtons();
                }
            } catch (err) {
                console.error("Advertencia en el mapeo de Old Items:", err);
            }
        }

        // Control manual de cierre y limpieza del modal express
        $(document).on('click', '#modalProductoNuevo [data-dismiss="modal"]', function() {
            $('#modalProductoNuevo').modal('hide');
            $('#modalProductoNuevo').css({ 'display': 'none', 'opacity': '0' });
            $('.modal-backdrop').remove(); 
            $('body').removeClass('modal-open').css('overflow', 'auto'); 
            purgarDuplicadosVisuales();
        });             

        // Interceptor para gatillar el cambio preventivo en Comprobante
        $('#comprobante_id').on('change', function() {
            console.log("Comprobante seleccionado:", $(this).val());
        });

        setTimeout(function() {
            $('#comprobante_id').trigger('change');
        }, 100);

        // Control de botones de la interfaz
        disableButtons();

        // =========================================================================
        // 4. CAPTURAR ENTRADA DEL SKU (ESCRITURA MANUAL O PISTOLA DE ESCANEO)
        // =========================================================================
        $('#SKU').on('keydown', function(e) {
            clearTimeout(skuTimeout); 

            if (estaProcesandoScan) {
                e.preventDefault();
                return false;
            }

            if (e.keyCode === 13 || e.which === 13) { 
                e.preventDefault(); 
                let valor = $(this).val().trim();
                if (valor !== '') {
                    agregarProductoScanner(valor);
                }
                return false;
            }

            skuTimeout = setTimeout(function() {
                if (!estaProcesandoScan) {
                    let valor = $('#SKU').val().trim();
                    if (valor !== '') {
                        agregarProductoScanner(valor);
                    }
                }
            }, 500); 
        });

// =========================================================================
// 🔄 INTERCEPTOR DE CAMBIO: DETECTOR DE PRODUCTO SELECCIONADO (BLINDADO)
// =========================================================================
$('#producto_id').on('change', function() {

    // 1. Localizar la opción que el usuario seleccionó físicamente con el mouse
    let selectedOption = $(this).find('option:selected');
    
    // 2. Leer el atributo del HTML de forma directa saltándose la caché de jQuery
    let rawPerecedero = selectedOption.attr('data-perecedero') || selectedOption.data('perecedero') || 0;
    let esPerecedero = (rawPerecedero == 1 || rawPerecedero === true || rawPerecedero === 'true' || rawPerecedero === '1') ? 1 : 0;

    console.log("🔄 [Cambio Manual] Producto:", selectedOption.text().trim(), "| ¿Es Perecedero?:", esPerecedero);

    // 3. Ejecutar la acción visual sin invocar jamás a .selectpicker('refresh') para evitar bucles
    if (esPerecedero === 1) {
        console.log("⏰ [Lote] Producto perecedero detectado de entrada. Mostrando campo de fecha.");
        $('#contenedor_fecha').removeClass('d-none');
        $('#fecha_vencimiento').prop('required', true);
        
        // Mover el foco al cuadro de fecha de forma automática
        setTimeout(function() {
            $('#fecha_vencimiento').focus();
        }, 100);
    } else {
        console.log("📦 [Regular] Producto estándar. Ocultando campo de fecha.");
        $('#contenedor_fecha').addClass('d-none');
        $('#fecha_vencimiento').prop('required', false).val('');
    }
    
    // 🛑 PROHIBIDO COLOCAR AQUÍ: $('#producto_id').selectpicker('refresh');
});




        // =========================================================================
        // 5. FUNCIÓN CORE: ESCANEO Y BUSQUEDA ASÍNCRONA EN CATÁLOGO
        // =========================================================================
function agregarProductoScanner(sku) {
    if (estaProcesandoScan) return;
    
    var comprobante = document.getElementById('comprobante_id').value;
    if (comprobante === "") {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione un comprobante primero.' });
        return false;
    }

    let skuAAsignar = sku || $('#SKU').val().trim();
    if (skuAAsignar === '') return;

    // Encender el escudo protector contra dobles ráfagas de la pistola
    estaProcesandoScan = true;
    $('#modal_codigo').val(skuAAsignar); 

    console.log("🔍 [Escáner] Iniciando verificación cruzada para el SKU:", skuAAsignar);

    $.ajax({
        url: '/comprar/SCANdetalles/' + skuAAsignar,
        type: 'GET',
        success: function(response) {
            console.log("🟢 [Servidor] Respuesta de verificación de catálogo:", response);

            // 1. Normalizar la respuesta por si Laravel devuelve un array u objeto envuelto
            let detalle = null;
            if (Array.isArray(response) && response.length > 0) {
                detalle = response[0];
            } else if (response && typeof response === 'object' && !Array.isArray(response)) {
                // Si la respuesta viene envuelta en un nodo "data" o "producto"
                detalle = response.producto || response.data || response;
            }

            // 2. Extraer el ID real usando flexibilidad de nombres de columnas comunes
            let idEncontrado = null;
            if (detalle) {
                idEncontrado = detalle.producto_id || detalle.id || (detalle.producto && detalle.producto.id);
            }

            // 3. 🚨 CONTROL DE SEGURIDAD SECUNDARIO (BÚSQUEDA LOCAL):
            // Si el servidor falló o no devolvió el ID exacto, escudriñamos el select nativo
            // por si el código coincide con el valor o con parte del texto del catálogo actual.
            if (!idEncontrado) {
                let codigoSinCeros = skuAAsignar.replace(/^0+/, '');
                $('#producto_id option').each(function() {
                    let valorOption = $(this).val();
                    let textoOption = $(this).text().trim().toLowerCase();
                    
                    if (valorOption == skuAAsignar || textoOption.includes(skuAAsignar.toLowerCase()) || (codigoSinCeros !== "" && textoOption.includes(codigoSinCeros.toLowerCase()))) {
                        idEncontrado = valorOption;
                        return false; // Romper bucle de búsqueda local
                    }
                });
            }

            // =========================================================================            
            // DECISIÓN FINAL: ¿EXISTE O SE ABRE EL MODAL EXPRESS?
            // =========================================================================
            // =========================================================================
            // DECISIÓN FINAL: ¿EXISTE O SE ABRE EL MODAL EXPRESS?
            // =========================================================================
            if (idEncontrado) {
                console.log("🎯 [Éxito] Producto localizado con ID:", idEncontrado);

                // 1. Desempaquetar la respuesta asíncrona de red [{...}]
                let itemData = Array.isArray(response) ? response : (response.producto || response.data || response);
                if (Array.isArray(itemData) && itemData.length > 0) {
                    itemData = itemData[0]; // Asegurar la extracción del objeto crudo limpio
                }
                
                // 2. Extraer el 1 o 0 real verificado desde la base de datos de Laravel
                let rawPerecedero = itemData.perecedero !== undefined ? itemData.perecedero : 0;
                let esPerecederoReal = (rawPerecedero == 1 || rawPerecedero === true || rawPerecedero === 'true' || rawPerecedero === '1') ? 1 : 0;
                let nombreProdClean = (itemData.nombre || 'Producto').trim();

                // Verificar si la opción ya existe físicamente en el HTML original de entrada
                let opcionExistente = $('#producto_id option[value="' + idEncontrado + '"]');
                
                if (opcionExistente.length === 0) {
                    // 🚀 SI NO EXISTE: Lo inyectamos de cero de forma limpia con sus atributos reales
                    let nuevaOpt = $('<option></option>')
                        .val(idEncontrado)
                        .text(nombreProdClean)
                        .attr('data-perecedero', esPerecederoReal)
                        .data('perecedero', esPerecederoReal);
                    $('#producto_id').append(nuevaOpt);
                } else {
                    // 🚀 SI YA EXISTE: ¡PROHIBIDO USAR .text()! 
                    // Únicamente actualizamos el atributo 'data-perecedero' para machacar el '0' viejo del HTML
                    opcionExistente.attr('data-perecedero', esPerecederoReal)
                                   .data('perecedero', esPerecederoReal);
                }

                // =========================================================================
                // 🚀 ELIMINAR CACHÉ VISUAL ANTES DE SELECCIONAR:
                // Desmarcar limpiamente cualquier selección previa para evitar textos encimados
                // =========================================================================
                $('#producto_id').selectpicker('val', ''); 

                // Asignar el nuevo valor, reconstruir la interfaz gráfica limpia y disparar el change
                $('#producto_id').val(idEncontrado).selectpicker('').trigger('change'); 
                
                // Limpiar campos de búsqueda e inputs del escáner
                $('#modalProductoNuevo').modal('hide');
                $('#SKU').val(''); 
                $('.bootstrap-select .bs-searchbox input').val('').trigger('input');

                // 3. Retraso de protección visual para mover el foco del cursor
                setTimeout(function() {
                    if (esPerecederoReal === 1) {
                        $('#fecha_vencimiento').focus();
                    } else {
                        if ($('#cantidad').length) $('#cantidad').focus().select();
                    }
                }, 150);

            } else {


                // ÚNICAMENTE si no se localizó ni en la consulta de red ni en la local, es un producto NUEVO de verdad
                console.log("🆕 [Nuevo] El producto no existe en el catálogo. Abriendo modal express para:", skuAAsignar);
                
                $('#SKU').val(''); 
                $('#modalProductoNuevo').modal('show');
                purgarDuplicadosVisuales();
                
                setTimeout(function() {
                    $('#modalProductoNuevo').css({ 'display': 'block', 'opacity': '1', 'z-index': '1060' });
                    $('.modal-backdrop').css('z-index', '1040');
                    $('#modal_nombre').focus(); 
                }, 150);
            }
        },
        error: function(xhr) {
            console.error("❌ [Error AJAX] Fallo al validar el SKU en el servidor:", xhr.responseText);
            // Caída segura preventiva: Si el servidor está caído (Error 404 o 500), abrir el modal express para no truncar la caja
            $('#modalProductoNuevo').modal('show');
        },
        complete: function() {
            // Apagar la bandera de control de concurrencia al terminar para liberar el escáner
            estaProcesandoScan = false; 
        }
    });
}

        // =========================================================================
        // REINICIO DE COMBOS AL ABRIR EL MODAL EXPRESS (SHOW)
        // =========================================================================
        $('#modalProductoNuevo').on('show.bs.modal', function () {
            let skuRespaldado = $('#modal_codigo').val();

            nuevaMarca = null; 
            nuevaPresentacion = null; 
            nuevasCategorias = [];

            $('.select-modal-express, .combo-modal, #modal_marca_id, #modal_presentacione_id, #modal_categorias').selectpicker('val', '');

            if ($('#formProductoExpress').length > 0) {
                $('#formProductoExpress')[0].reset(); 
            }

            $('#modal_codigo').val(skuRespaldado);
            
            $('.select-modal-express option, .combo-modal option, #modal_marca_id option, #modal_presentacione_id option, #modal_categorias option').each(function() {
                if (isNaN($(this).val()) && $(this).val() !== "") {
                    $(this).remove(); 
                }
            });

            // ❌ ANTES (Causaba acumulación infinita de nombres al redibujar el DOM):
// $('.select-modal-express, .combo-modal, #modal_marca_id, #modal_presentacione_id, #modal_categorias').selectpicker('refresh');

// 🎯 AHORA (Limpio, directo y libre de duplicados):
$('.select-modal-express, .combo-modal, #modal_marca_id, #modal_presentacione_id, #modal_categorias').selectpicker('val', '');


            detenerHardwareCamara();
            $('#vista-previa-img').addClass('d-none').attr('src', ''); 
            $('#texto-sin-foto').removeClass('d-none'); 
            
            console.log("Formulario express reseteado de forma aislada sin duplicar instancias.");
        });

        // =========================================================================
        // LIMPIEZA TOTAL AL CERRAR EL MODAL EXPRESS (HIDDEN)
        // =========================================================================
        $('#modalProductoNuevo').on('hidden.bs.modal', function () {
            if ($('#formProductoExpress').length > 0) {
                $('#formProductoExpress')[0].reset(); 
            }
            
            $('#vista-previa-img').attr('src', '').addClass('d-none');
            $('#texto-sin-foto').removeClass('d-none');

            $('.select-modal-express, .combo-modal, #modal_marca_id, #modal_presentacione_id, #modal_categorias').each(function() {
                $(this).selectpicker('deselectAll'); 
                
                $(this).find('option').each(function() {
                    let valor = $(this).val();
                    if (isNaN(valor) && valor !== "") {
                        $(this).remove(); 
                    } else {
                        $(this).prop('selected', false).removeAttr('selected');
                    }
                });
            });

            $(this).find('.bs-searchbox input').val('');
            $('.select-modal-express, .combo-modal, #modal_marca_id, #modal_presentacione_id, #modal_categorias').selectpicker('refresh');
        });

        // =========================================================================
// 🛒 INTERCEPTOR DE BUSCADOR: MODO PASIVO MANUAL / ACTIVO POR ENTER (CORREGIDO)
// =========================================================================
$(document).on('keydown', '.bootstrap-select .bs-searchbox input', function(e) {
    let inputBuscador = $(this);
    
    // Verificar si estamos parados específicamente en el buscador del combo de productos
    if (inputBuscador.closest('.bootstrap-select').find('select').attr('id') === 'producto_id') {
        
        // 🚀 CLAVE: ÚNICAMENTE procesar la búsqueda forzada si presionan la tecla Enter
        // Esto confirma que pasaron un escáner físico o que el usuario terminó y dio Enter manual
        if (e.keyCode === 13 || e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            
            let codigoCrudo = inputBuscador.val().trim();
            if (codigoCrudo !== "") {
                console.log("🎯 [Buscador] Confirmación por Enter detectada. Evaluando código:", codigoCrudo);
                if (typeof evaluarCodigoEscaneado === 'function') {
                    evaluarCodigoEscaneado(codigoCrudo);
                }
            }
            return false;
        }

        // 🛑 ELIMINADO POR COMPLETO EL TIMEOUT DE 250ms:
        // Dejamos que el usuario escriba libremente a su propio ritmo. El plugin filtrará
        // las opciones de forma nativa y visual sin disparar funciones de selección automáticas en ráfaga.
    }
});

// =========================================================================
// 🚀 REGLA DE ORO DE INYECCIÓN CONTABLE: DETENER DUPLICADOS FÍSICOS
// =========================================================================
function actualizarCatalogoProductosVisual(productosNuevos) {
    let select = $('#producto_id');
    
    // 🛑 1. EL PASO CLAVE: Vaciar físicamente el HTML anterior del select nativo.
    // Si no pones esta línea, cada llamada duplicará las opciones infinitamente.
    select.empty(); 

    // 2. Inyectar la opción en blanco inicial por defecto (Placeholder)
    select.append('<option value="">Busque un producto aquí</option>');

    // 3. Recorrer la colección e inyectar las etiquetas limpias
    $.each(productosNuevos, function(index, item) {
        let esPerecederoReal = parseInt(item.perecedero, 10) || 0;
        
        let optionHtml = $('<option></option>')
            .val(item.id)
            .text((item.nombre || '').trim())
            .attr('data-img', item.img_path || '')
            .attr('data-detalle', item.descripcion || '')
            .attr('data-perecedero', esPerecederoReal)
            .data('perecedero', esPerecederoReal);
            
        select.append(optionHtml);
    });

    // 4. Refrescar visualmente la interfaz de Bootstrap de forma aislada
    select.selectpicker('refresh');
}


function llenarTablaventas() {
    // 🚀 CLAVE: Apuntar con precisión quirúrgica al tbody de la tabla de ítems de productos
    var tableBodyDetalle = $('#tabla_detalle_tbody');
    
    // Si en tu HTML el ID se llama diferente (ej: #detalle_tbody o #tabla_detalle tbody),
    // nos aseguramos de limpiar ambos por si acaso:
    tableBodyDetalle.empty(); 
    $('#tabla_detalle tbody').empty(); 

    $.each(producto, function(index) {
        let precioVentaActual = subtotal[index] || 0;
        let resultadoIva = CalcularFormula(formula, precioVentaActual);
        subiva[index] = resultadoIva;

        // Construir la fila exacta con los nombres de inputs array correspondientes para Laravel
        var fila = '<tr id="fila' + index + '">' +
            '<th>' + (index + 1) + '</th>' +
            '<td><input type="hidden" name="arrayidproducto[]" value="' + producto[index] + '">' + nombre[index] + '</td>' +
            '<td><input type="hidden" name="arraycantidad[]" value="' + Cantidad[index] + '">' + Cantidad[index] + '</td>' +
            '<td><input type="hidden" name="arraypreciocompra[]" value="' + (preciocompra[index] || 0) + '">' + (preciocompra[index] || 0) + '</td>' +
            '<td><input type="hidden" name="arrayfecha_vencimiento[]" value="' + (window.arrayFechasGlobal ? window.arrayFechasGlobal[index] : 'N/A') + '">' + (window.arrayFechasGlobal ? window.arrayFechasGlobal[index] : 'N/A') + '</td>' +
            '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[index] + '">' + subiva[index] + '</td>' +
            '<td><input type="hidden" name="arrayprecioventa[]" value="' + (precioventa[index] || 0) + '">' + (precioventa[index] || 0) + '</td>' +
            '<td>' + subtotal[index] + '</td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onClick="eliminarProducto(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';
        
        // Inyectar la fila de forma garantizada en el contenedor de los ítems
        tableBodyDetalle.append(fila);
        
        // Caída segura: si el primer selector falló, la inyectamos en el cuerpo nativo de la tabla
        if (tableBodyDetalle.children().length === 0) {
            $('#tabla_detalle tbody').append(fila);
        }
    });

    // Calcular el IVA global y mostrarlo en la interfaz de forma aislada
    IVA = CalcularFormula(formula, total);
    if ($('#total_iva_visual').length > 0) {
        $('#total_iva_visual').html('Q. ' + IVA);
    }
}


// =========================================================================
// 🚀 CONTROL ABSOLUTO CONTRA DUPLICACIÓN DE OPCIONES AL PRESIONAR ENTER
// =========================================================================
function evaluarCodigoEscaneado(codigo) {
    if (!codigo || codigo.length < 3) return;

    let codigoSinCeros = codigo.replace(/^0+/, '');
    let idEncontradoLocal = null;

    // 1. Escudriñar de forma estricta si el código o el texto ya existen en el HTML
    $('#producto_id option').each(function() {
        let valorOption = $(this).val();
        let textoOption = $(this).text().trim().toLowerCase();
        
        if (valorOption == codigo || textoOption === codigo.toLowerCase() || (codigoSinCeros !== "" && textoOption === codigoSinCeros.toLowerCase())) {
            idEncontradoLocal = valorOption;
            return false; // Romper bucle si ya existe físicamente
        }
    });

    // ESCENARIO A: Si el producto ya existe de entrada en el select nativo, NO INYECTAR CLONES
    if (idEncontradoLocal) {
        console.log("🎯 [Match Local] El producto ya existe en el combo. Seleccionando ID: " + idEncontradoLocal);
        
        // Limpiar la selección rota anterior, asignar el ID legítimo y disparar el change contable
        $('#producto_id').selectpicker('val', '');
        $('#producto_id').val(idEncontradoLocal).selectpicker('refresh').trigger('change');
        
        // Limpiar la caja de texto del buscador para la siguiente operación
        $('.bootstrap-select .bs-searchbox input').val('').trigger('input');
        return; // Finalizar ejecución de forma segura
    }

    // ESCENARIO B: Consultar al servidor únicamente si de verdad es un código de barras nuevo
    $.ajax({
        url: '/comprar/SCANdetalles/' + codigo,
        type: 'GET',
        success: function(response) {
            let detalle = Array.isArray(response) ? response[0] : (response.producto || response.data || response);
            
            if (detalle && (detalle.producto_id || detalle.id)) {
                let idServer = detalle.producto_id || detalle.id;
                let nombreProd = (detalle.nombre || 'Producto Recuperado').trim();
                let rawPerecedero = detalle.perecedero !== undefined ? detalle.perecedero : 0;
                let esPerecederoReal = (rawPerecedero == 1 || rawPerecedero === true || rawPerecedero === 'true' || rawPerecedero === '1') ? 1 : 0;

                // Doble escudo: Verificar que el ID del servidor no esté duplicado de entrada
                let existeYaId = $('#producto_id option[value="' + idServer + '"]').length > 0;
                
                if (!existeYaId) {
                    console.log("🆕 Inyectando producto nuevo legítimo desde el servidor:", nombreProd);
                    let nuevaOpt = $('<option></option>')
                        .val(idServer)
                        .text(nombreProd)
                        .attr('data-perecedero', esPerecederoReal)
                        .data('perecedero', esPerecederoReal);
                    $('#producto_id').append(nuevaOpt);
                }

                $('#producto_id').selectpicker('val', '');
                $('#producto_id').val(idServer).selectpicker('refresh').trigger('change');
                $('.bootstrap-select .bs-searchbox input').val('').trigger('input');
            }
        }
    });
}


        // =========================================================================
        // 6. CONTROLADOR DE ENTER EN EL BUSCADOR DEL MODAL (CREACIÓN EN CALIENTE)
        // =========================================================================
        $(document).on('keydown', '#modalProductoNuevo .bootstrap-select .bs-searchbox input', function(e) {
            let textoBusqueda = $(this).val().trim();
            let selectpickerContenedor = $(this).closest('.bootstrap-select');
            let selectOriginal = selectpickerContenedor.find('select');
            let selectId = selectOriginal.attr('id');

            if (e.keyCode === 13 || e.which === 13) {
                if (textoBusqueda !== "") {
                    e.preventDefault(); 

                    let existeOpcion = false;
                    selectOriginal.find('option').each(function() {
                        if ($(this).text().trim().toLowerCase() === textoBusqueda.toLowerCase()) {
                            existeOpcion = true;
                            textoBusqueda = $(this).val(); 
                        }
                    });

                    if (!existeOpcion) {
                        let nuevaOpcionHtml = `<option value="${textoBusqueda}">${textoBusqueda}</option> `;
                        selectOriginal.append(nuevaOpcionHtml);
                    }

                    if (selectId === 'modal_marca_id' || selectId === 'modal_presentacione_id') {
                        selectOriginal.val(textoBusqueda); 
                        if (selectId === 'modal_marca_id') nuevaMarca = textoBusqueda;
                        if (selectId === 'modal_presentacione_id') nuevaPresentacion = textoBusqueda;
                    } else {
                        let valoresActuales = selectOriginal.val() || [];
                        if (!valoresActuales.includes(textoBusqueda)) {
                            valoresActuales.push(textoBusqueda);
                        }
                        selectOriginal.val(valoresActuales);
                        
                        if (!nuevasCategorias.includes(textoBusqueda)) {
                            nuevasCategorias.push(textoBusqueda);
                        }
                    }

                    selectOriginal.selectpicker('refresh');
                    selectOriginal.trigger('change');
                    selectOriginal.selectpicker('toggle'); 
                }
                return false;
            }
        });
        // =========================================================================
        // 7. ENVÍO AJAX DEL FORMULARIO EXPRESS HACIA LARAVEL
        // =========================================================================
        $(document).on('submit', '#formProductoExpress', function(e) {
            e.preventDefault();
            
            let botonGuardar = $(this).find('button[type="submit"]');
            botonGuardar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            var formData = new FormData(this);
            formData.append('_token', '{{ csrf_token() }}');

            let selectMarca = $('#modal_marca_id');
            let valorMarca = selectMarca.val();
            if (!valorMarca || valorMarca === "") {
                valorMarca = selectMarca.find('option:selected').val() || selectMarca.find('option').filter(function() { return this.selected; }).val();
            }
            if (valorMarca) {
                if (isNaN(valorMarca)) { formData.append('nueva_marca_texto', valorMarca.trim()); }
                else { formData.append('modal_marca_id', valorMarca); }
            }

            let selectPres = $('#modal_presentacione_id');
            let valorPresentacion = selectPres.val();
            if (!valorPresentacion || valorPresentacion === "") {
                valorPresentacion = selectPres.find('option:selected').val() || selectPres.find('option').filter(function() { return this.selected; }).val();
            }
            if (valorPresentacion) {
                if (isNaN(valorPresentacion)) { formData.append('nueva_presentacion_texto', valorPresentacion.trim()); }
                else { formData.append('modal_presentacione_id', valorPresentacion); }
            }
            
            let valoresCategorias = $('#modal_categorias').val() || [];
            valoresCategorias.forEach(function(cat) {
                if (isNaN(cat)) { formData.append('nuevas_categorias_texto[]', cat.trim()); }
                else { formData.append('categorias[]', cat); }
            });

            let srcPrevia = $('#vista-previa-img').attr('src');
            if (srcPrevia && srcPrevia.startsWith('data:image')) {
                formData.append('imagen_base64', srcPrevia);
            }

            Swal.fire({
                title: 'Procesando registro...',
                text: 'Por favor espere un momento',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: "{{ route('productos.storeExpress') }}", 
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.close();

                    if (response.marca) {
                        let existeId = $('#modal_marca_id option[value="' + response.marca.id + '"]').length > 0;
                        if (!existeId) {
                            let opcionTemporal = $('#modal_marca_id option[value="' + response.marca.nombre + '"]');
                            if (opcionTemporal.length > 0) {
                                opcionTemporal.val(response.marca.id).attr('value', response.marca.id).text(response.marca.nombre);
                            } else {
                                $('#modal_marca_id').append(`<option value="${response.marca.id}">${response.marca.nombre}</option>`);
                            }
                        }
                    }

                    if (response.presentacion) {
                        let existeId = $('#modal_presentacione_id option[value="' + response.presentacion.id + '"]').length > 0;
                        if (!existeId) {
                            let opcionTemporal = $('#modal_presentacione_id option[value="' + response.presentacion.nombre + '"]');
                            if (opcionTemporal.length > 0) {
                                opcionTemporal.val(response.presentacion.id).attr('value', response.presentacion.id).text(response.presentacion.nombre);
                            } else {
                                $('#modal_presentacione_id').append(`<option value="${response.presentacion.id}">${response.presentacion.nombre}</option>`);
                            }
                        }
                    }

                    if (response.categorias_procesadas && response.categorias_procesadas.length > 0) {
                        let categoriasSeleccionadas = $('#modal_categorias').val() || [];

                        response.categorias_procesadas.forEach(function(catReal) {
                            let existeOptionId = $(`#modal_categorias option[value="${catReal.id}"]`).length > 0;
                            if (!existeOptionId) {
                                let opcionTemporal = $(`#modal_categorias option[value="${catReal.nombre}"]`);
                                if (opcionTemporal.length > 0) {
                                    opcionTemporal.val(catReal.id).attr('value', catReal.id).text(catReal.nombre);
                                    categoriasSeleccionadas = categoriasSeleccionadas.filter(item => item !== catReal.nombre);
                                    categoriasSeleccionadas.push(catReal.id.toString());
                                } else {
                                    $('#modal_categorias').append(`<option value="${catReal.id}">${catReal.nombre}</option>`);
                                    categoriasSeleccionadas.push(catReal.id.toString());
                                }
                            }
                        });
                        $('#modal_categorias').val(categoriasSeleccionadas);
                    }

                    $('#modal_marca_id, #modal_presentacione_id, #modal_categorias').selectpicker('val', '');

                    $('#modalProductoNuevo').modal('hide');
                    
                    botonGuardar.prop('disabled', false).text('Guardar e Inyectar a Compra');
                    Swal.fire('¡Éxito!', 'Producto registrado correctamente.', 'success');

                    let rawPerecedero = response.producto.perecedero;
                    let esPerecedero = (rawPerecedero == 1 || rawPerecedero === true || rawPerecedero === 'true') ? 1 : 0;
                    

                    let nombreLimpio = response.producto.nombre.trim();

                    let $nuevaOpcion = $('<option></option>')
                        .val(response.producto.id)
                        .text(nombreLimpio)
                        .attr('data-stock', '0')
                        .attr('data-precio', '0.00')
                        .attr('data-perecedero', esPerecedero) 
                        .attr('data-img', response.producto.img_path)
                        .attr('data-detalle', response.producto.descripcion)
                        .data('perecedero', esPerecedero);     

                    $('#producto_id').append($nuevaOpcion).val(response.producto.id).selectpicker('refresh').trigger('change');
                    $('#producto_id').val(response.producto.id).selectpicker('refresh').trigger('change');

                        // 4. Forzar el disparo visual de la fecha de vencimiento manualmente
    if (esPerecedero === 1) {
        $('#contenedor_fecha').fadeIn();
        $('#fecha_vencimiento').prop('required', true);
    } else {
        $('#contenedor_fecha').fadeOut();
        $('#fecha_vencimiento').prop('required', false).val('');
    }


                    setTimeout(function() { $('#cantidad').focus().select(); }, 300);
                },
                error: function(xhr) {
                    Swal.close();
                    botonGuardar.prop('disabled', false).text('Guardar e Inyectar a Compra');
                    
                    let errorMsg = "No se pudo completar el registro express.";
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        });

        // =========================================================================
        // CONTROL MULTIMEDIA NATIVO (IMAGEN / WEBCAM)
        // =========================================================================
        $('#modal_img_path').on('change', function(e) {
            detenerHardwareCamara();
            let archivo = e.target.files;
            if (archivo) {
                let lector = new FileReader();
                lector.onload = function(event) {
                    $('#texto-sin-foto').addClass('d-none');
                    $('#vista-previa-img').attr('src', event.target.result).removeClass('d-none');
                };
                lector.readAsDataURL(archivo);
            }
        });

        $('#btn-activar-camara').on('click', function() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
                .then(function(stream) {
                    streamCamara = stream;
                    let videoElement = document.getElementById('video-camara');
                    videoElement.srcObject = stream;
                    $('#contenedor-camara-web').removeClass('d-none');
                })
                .catch(function(err) {
                    alert("No se pudo iniciar la cámara web. Elija una foto manualmente.");
                });
            }
        });

        $('#btn-capturar-foto').on('click', function() {
            let video = document.getElementById('video-camara');
            let canvas = document.getElementById('canvas-foto');
            let ctx = canvas.getContext('2d');

            if (video.videoWidth > 0) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                let fotoBase64 = canvas.toDataURL('image/jpeg', 0.9);
                $('#texto-sin-foto').addClass('d-none');
                $('#vista-previa-img').attr('src', fotoBase64).removeClass('d-none');
detenerHardwareCamara();
}
});
function detenerHardwareCamara() {
if (streamCamara) {
streamCamara.getTracks().forEach(track => track.stop());
streamCamara = null;
}
$('#contenedor-camara-web').addClass('d-none');
}
    // =========================================================================
    // 8. LOGICA MATEMÁTICA Y PARTIDA DOBLE DE LA COMPRA
    // =========================================================================
        function disableButtons() {
            if (total == 0) {
                $('#guardar').hide();
                $('#cancelar').hide();
            } else {
                $('#guardar').show();
                $('#cancelar').show();
            }
        }

function sumarArreglos(arr1, arr2, A) {
    let resultados = []; 
    let formulaEvaluada;
    let resultado;
    var tableBody = $('#detalle_tbody');
    tableBody.empty(); // Limpiar el cuerpo de la tabla contable de forma segura

    // 1. Recorrer las fórmulas y calcular los montos contables con math.js
    for (let i = 0; i < arr1.length; i++) {
        if (arr1[i] && arr1[i] !== "") {
            formulaEvaluada = arr1[i].replace(/A/g, total);
            resultado = math.evaluate(formulaEvaluada);
            resultado = parseFloat(resultado.toFixed(2));
            arr2[i] = resultado;
        } else {
            arr2[i] = parseFloat(monto[i]) || 0;
        }
        resultados.push(arr2[i]);
    }

    // 2. Redibujar las filas asegurando la inyección de los IDs y nombres de inputs correctos
    $.each(arr1, function(index, value) {
        // 🚀 CONTROL EXTRA-SEGURO DE ID CONTABLE:
        // Si el ID de la cuenta en el array idcuenta viene como undefined o vacío,
        // usamos el índice actual o una caída segura para que nunca ponga el texto "undefined".
        let idReal = idcuenta[index];
        if (!idReal || idReal === "undefined" || idReal === undefined) {
            idReal = index + 1; // Caída segura temporal si no se definió
        }

        var row = '<tr id="filaCC' + index + '">' +
            '<td></td>' +
            '<td>' +
                '<input name="idcuenta[]" type="hidden" value="' + idReal + '">' +
                '<input name="arrayidcuenta[]" type="hidden" value="' + idReal + '">' +
                '<input name="cuentacontable_id[]" type="hidden" value="' + idReal + '">' +
                '<input name="fkCuenetaContable[]" type="hidden" value="' + idReal + '">' + // Error ortográfico de tu DB
            '</td>' +
            '<td>' + (cuenta[index] || 'Cuenta Contable') + '</td>' +
            '<td class="small-text">N/A</td>' +
            '<td><input name="arraymonto[]" type="number" class="form-control form-control-sm" value="' + resultados[index] + '" readonly></td>' +
            '<td><input name="arraytipomovimiento[]" type="text" class="form-control form-control-sm" value="' + tipo[index] + '" readonly></td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onClick="eliminarCC(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';
        
        tableBody.append(row);
        monto[index] = resultados[index];
    });

    return resultados; 
}

function sumarArreglosInput(arr1, arr2, A) {
    let resultados = []; 
    let formulaEvaluada;
    let resultado;
    var tableBody = $('#detalle_tbody');
    tableBody.empty();

    for (let i = 0; i < arr1.length; i++) {
        if (arr1[i] && arr1[i] !== "") {
            formulaEvaluada = arr1[i].replace(/A/g, total);
            resultado = math.evaluate(formulaEvaluada);
            resultado = parseFloat(resultado.toFixed(2));
            arr2[i] = resultado;
        } else {
            arr2[i] = parseFloat(monto[i]) || 0;
        }
        resultados.push(arr2[i]);
    }

    $.each(arr1, function(index, value) {
        let idReal = idcuenta[index];
        if (!idReal || idReal === "undefined" || idReal === undefined) {
            idReal = index + 1;
        }

        var row = '<tr id="filaCC' + index + '">' +
            '<td></td>' +
            '<td>' +
                '<input name="idcuenta[]" type="hidden" value="' + idReal + '">' +
                '<input name="arrayidcuenta[]" type="hidden" value="' + idReal + '">' +
                '<input name="cuentacontable_id[]" type="hidden" value="' + idReal + '">' +
                '<input name="fkCuenetaContable[]" type="hidden" value="' + idReal + '">' +
            '</td>' +
            '<td>' + (cuenta[index] || 'Cuenta Contable') + '</td>' +
            '<td class="small-text">N/A</td>' +
            '<td><input name="arraymonto[]" type="number" class="form-control form-control-sm" value="' + resultados[index] + '"></td>' +
            '<td><input name="arraytipomovimiento[]" type="text" class="form-control form-control-sm" value="' + tipo[index] + '" readonly></td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onClick="eliminarCC(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';
        
        tableBody.append(row);
        monto[index] = resultados[index];
    });

    return resultados; 
}


  function CalcularFormula(formulalocal, montoA) {
    try {
        // 1. Validar que la fórmula no sea nula o vacía
        if (!formulalocal) return 0;

        // 2. Reemplazar "A" con el valor de montoA
        // Usamos (montoA) entre paréntesis para evitar errores en fórmulas como A*2 -> (10)*2
        let formulaEvaluadaiva = formulalocal.replace(/A/g, `(${montoA})`);

        // 3. Evaluar con math.js
        let resultadoiva = math.evaluate(formulaEvaluadaiva);

        // 4. VALIDACIÓN CLAVE: Si mathjs devuelve un objeto complejo, obtener el valor primitivo
        if (typeof resultadoiva === 'object' && resultadoiva.hasOwnProperty('value')) {
            resultadoiva = resultadoiva.value;
        }

        // 5. Convertir a número y aplicar toFixed de forma segura
        let numeroFinal = parseFloat(resultadoiva) || 0;
        return parseFloat(numeroFinal.toFixed(2));

    } catch (error) {
        console.error("Error al calcular fórmula: " + formulalocal, error);
        return 0;
    }
}

// =========================================================================
// 🔄 INTERCEPTOR CORREGIDO: ESCUCHAR EL CAMBIO POR NOMBRE DEL RADIO (input[name="TipoFolio"])
// =========================================================================
$(document).on('change', 'input[name="TipoFolio"]', function() {
    // Capturar el valor del radio seleccionado (A, M o F)
    let tipoFolioSeleccionado = $(this).val();
    
    console.log("🎛️ [TipoFolio] Opción contable cambiada a:", tipoFolioSeleccionado);

    var tableBody = $('#detalle_tbody');
    tableBody.empty(); // Limpiar el cuerpo de la póliza contable de forma segura

    var tableDINAMICO = $('#contenedor-dinamico');
    tableDINAMICO.empty(); // Limpiar el bloque dinámico libre si existía alguno anterior

    // Reiniciar los arreglos globales para evitar que los datos viejos se queden encimados
    formulas = [];
    monto = [];
    cuenta = [];
    idcuenta = [];
    tipo = [];
    formula = '';
    contcc = 0;

    var comprobanteId = document.getElementById('comprobante_id').value;

    // =========================================================================
    // ESCENARIO M: MODO ASISTIDO MANUAL
    // =========================================================================
    if (tipoFolioSeleccionado === "M") {
        if (comprobanteId) {
            $.ajax({
                url: '/compras/detalles/' + comprobanteId,
                type: 'GET',
                success: function(response) {
                    var detalles = response.detalles;
                    tableBody.empty();

                    // 1. Pintar las filas e indexar datos en la memoria intermedia
                    $.each(detalles, function(index, detalle) {
                        // 🚀 COMPATIBILIDAD ROBUSTA DE VARIABLES PARA EL ID CONTABLE:
                        // Captura la columna real sin importar si Laravel la manda como id, id_cuenta, o cuenta_contable_id
                        let idCuentaVerificada = detalle.id || detalle.id_cuenta || detalle.cuenta_contable_id || detalle.fkCuenta || detalle.id_cuenta_contable;
                        
                        // Si por alguna razón sigue fallando, le asignamos de contingencia el índice + 1 para que nunca sea undefined
                        if (!idCuentaVerificada || idCuentaVerificada === "undefined" || idCuentaVerificada === undefined) {
                            idCuentaVerificada = index + 1;
                        }

                        formulas[index] = detalle.formula;
                        monto[index] = detalle.valorminimo;
                        cuenta[index] = detalle.cuenta_contable_nombre;
                        idcuenta[index] = idCuentaVerificada; // <-- Guardamos el ID legítimo verificado
                        tipo[index] = detalle.Naturaleza;
                        formula = detalle.formuladoc;
                    });

                    // 2. 🚀 CLAVE: Ejecutar la sumatoria una única vez fuera del bucle para evitar duplicados y textos encimados
                    if (formulas.length > 0) {
                        sumarArreglosInput(formulas, monto);
                    }
                    
                    if (typeof llenarTablaventas === 'function') { llenarTablaventas(); }
                },
                error: function(xhr, status, error) {
                    console.error("Error al cargar detalles manuales:", error);
                }
            });
        }
    }
    // =========================================================================
    // ESCENARIO A: MODO ASISTIDO AUTOMÁTICO (FÓRMULAS FIJAS)
    // =========================================================================
    else if (tipoFolioSeleccionado === "A") {
        if (comprobanteId) {
            $.ajax({
                url: '/compras/detalles/' + comprobanteId,
                type: 'GET',
                success: function(response) {
                    var detalles = response.detalles;
                    tableBody.empty();

                    // 1. Pintar las filas contables automáticas en el DOM
                    $.each(detalles, function(index, detalle) {
                        // 🚀 COMPATIBILIDAD ROBUSTA DE VARIABLES PARA EL ID CONTABLE:
                        let idCuentaVerificada = detalle.id || detalle.id_cuenta || detalle.cuenta_contable_id || detalle.fkCuenta || detalle.id_cuenta_contable;
                        
                        if (!idCuentaVerificada || idCuentaVerificada === "undefined" || idCuentaVerificada === undefined) {
                            idCuentaVerificada = index + 1;
                        }

                        formulas[index] = detalle.formula;
                        monto[index] = detalle.valorminimo;
                        cuenta[index] = detalle.cuenta_contable_nombre;
                        idcuenta[index] = idCuentaVerificada; // <-- Guardamos el ID legítimo verificado
                        tipo[index] = detalle.Naturaleza;
                        formula = detalle.formuladoc;
                    });

                    // 2. 🚀 CLAVE: Calcular póliza balanceada automatizada fuera del bucle
                    if (formulas.length > 0) {
                        sumarArreglos(formulas, monto);
                    }
                    
                    if (typeof llenarTablaventas === 'function') { llenarTablaventas(); }
                },
                error: function(xhr, status, error) {
                    console.error("Error al cargar detalles automáticos:", error);
                }
            });
        }
    }
    // =========================================================================
    // ESCENARIO F: MODO LIBRE (CREACIÓN DINÁMICA DE CUENTAS EN CALIENTE)
    // =========================================================================
    else if (tipoFolioSeleccionado === "F") {
        if (contenedor) {
            const nuevoDiv = crearNuevoDiv();
            contenedor.appendChild(nuevoDiv);
            
            // Usar la alternativa val visual en lugar de refrescar masivamente para que no duplique nombres
            $(nuevoDiv).find('.selectpicker').selectpicker('destroy').selectpicker({
                noneResultsText: 'No se encontró, presione enter para registrar: {0}'
            }).selectpicker('val', '');
        }
    }
});


function crearNuevoDiv() {
    // 1. Crear el contenedor principal de forma segura
    const nuevoDiv = document.createElement('div');
    nuevoDiv.className = 'card p-3 mb-3 bg-light boundary-cc-block';
    
    // 2. Construir la estructura por piezas para que el editor de código no se rompa
    let filaContenedora = $('<div class="row"></div>');
    
    // Bloque del selector de cuenta contable (Inyectando el Blade de Laravel de forma aislada)
    let bloqueCuenta = $('<div class="col-12 mb-2"></div>');
    let selectCuenta = $('<select name="cuentacontable_id" class="form-control selectpicker clase_dinamica_cuenta" data-live-search="true" data-size="5" title="Busque un Cuenta Contable aquí"></select>');
    
    @foreach ($cuentasContables as $item)
        selectCuenta.append('<option value="{{$item->id}}">{{$item->formula}} - {{$item->nombre}}</option>');
    @endforeach
    
    bloqueCuenta.append(selectCuenta);
    
    // Bloque de la Naturaleza (Debe / Haber)
    let bloqueNaturaleza = $('<div class="col-12 mb-2"></div>');
    let selectNaturaleza = $('<select name="Naturaleza" class="form-control selectpicker clase_dinamica_naturaleza" title="Elija Naturaleza de la cuenta"><option value="D">Debe</option><option value="H">Haber</option></select>');
    bloqueNaturaleza.append(selectNaturaleza);
    
    // Bloque del Monto Mínimo
    let bloqueMonto = $('<div class="col-4 mb-2"><label class="form-label">Valor Minimo:</label><input type="number" name="valorminimo" class="form-control clase_dinamica_monto" step="0.1" value="0"></div>');
    
    // Bloque del Botón de Agregar
    let bloqueBoton = $('<div class="col-12 text-end"><button onclick="ejecutarAdicionDinamicaCC(this)" class="btn btn-primary" type="button">Agregar</button></div>');
    
    // 3. Unificar todo dentro del div principal
    filaContenedora.append(bloqueCuenta).append(bloqueNaturaleza).append(bloqueMonto).append(bloqueBoton);
    $(nuevoDiv).append(filaContenedora);
    
    return nuevoDiv;
}




    // =========================================================================
    // 9. GESTIÓN DE PRODUCTOS SELECCIONADOS (AGREGAR / ELIMINAR / CUADRES)
    // =========================================================================
// 🚀 CORREGIDO: Recibe el parámetro 'e' (evento) del clic para poder frenar la duplicación
function agregarProducto(e) {
    // Si el navegador envió el evento, congelamos de inmediato la doble ejecución
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    console.log("🚀 [Añadir] Iniciando flujo de inyección de artículo por captura posicional...");

    // 1. Localizar el selector principal y su ID
    let selectNativo = document.getElementById('producto_id');
    let idProducto = selectNativo ? selectNativo.value : '';
    if (!idProducto || idProducto === "" || idProducto === "undefined") {
        idProducto = $('#producto_id').find('option:selected').val() || '';
    }

    let optionSeleccionada = $('#producto_id option[value="' + idProducto + '"]').length > 0 
        ? $('#producto_id option[value="' + idProducto + '"]') 
        : $('#producto_id').find('option:selected');

    let nameProducto = optionSeleccionada.text() ? optionSeleccionada.text().trim() : '';

    // Captura posicional blindada
    let cantidadRaw = $('#cantidad').val() || $('input[type="number"]').eq(0).val() || '';
    let precioCompraRaw = $('#precio_compra').val() || $('input[id*="compra"]').val() || $('input[name*="compra"]').val() || $('input[type="number"]').eq(1).val() || '';
    let precioVentaRaw = $('#precio_venta').val() || $('input[id*="venta"]').val() || $('input[name*="venta"]').val() || $('input[type="number"]').eq(2).val() || '';
    
    let cantNum = parseInt(cantidadRaw, 10);
    let compraNum = parseFloat(precioCompraRaw);
    let ventaNum = parseFloat(precioVentaRaw);

    let rawPerecedero = optionSeleccionada.attr('data-perecedero') || optionSeleccionada.data('perecedero') || 0;
    let esPerecedero = (rawPerecedero == 1 || rawPerecedero === true || rawPerecedero === 'true' || rawPerecedero === '1') ? 1 : 0;
    let fechaVencimiento = $('#fecha_vencimiento').val() || $('input[type="date"]').val() || '';

    var comprobante = document.getElementById('comprobante_id').value;
    if (comprobante === "") { 
        alert("Por favor, seleccione un comprobante primero."); 
        return false; 
    }
    if (!formula) { 
        alert("Error: No se ha cargado la fórmula contable del comprobante."); 
        return false; 
    }

    // 🔍 INSPECCIÓN CRÍTICA EN LA CONSOLA (F12)
    console.log("📊 [Auditoría Completa de Inputs Reales]:", {
        idProducto: idProducto,
        nameProducto: nameProducto,
        cantidadRaw: cantidadRaw,
        precioCompraRaw: precioCompraRaw,
        precioVentaRaw: precioVentaRaw,
        cantNum: cantNum,
        compraNum: compraNum,
        ventaNum: ventaNum,
        esPerecedero: esPerecedero,
        fechaVencimiento: fechaVencimiento
    });

    // =========================================================================
    // 2. CONDICIONALES DE REBOTE CONTROLADOS
    // =========================================================================
    if (!idProducto || idProducto === "" || idProducto === "undefined" || idProducto === "null") {
        alert("Por favor, seleccione un producto válido del catálogo.");
        return false;
    }

    if (isNaN(cantNum) || isNaN(compraNum) || isNaN(ventaNum)) {
        alert("Por favor, llene la cantidad, precio de compra y precio de venta con valores numéricos válidos.");
        return false;
    }

    if (cantNum <= 0 || compraNum <= 0 || ventaNum <= 0) {
        alert("Valores incorrectos. La cantidad debe ser un número entero y los precios mayores a cero.");
        return false;
    }

    if (ventaNum <= compraNum) {
        alert("El precio de venta debe ser mayor al precio de compra.");
        return false;
    }

    if (esPerecedero === 1 && (!fechaVencimiento || fechaVencimiento === "")) {
        alert("Este producto es perecedero. Por favor, asigne una fecha de vencimiento válida.");
        return false;
    }

    // =========================================================================
    // 3. PROCESAMIENTO CONTABLE E INSERCIÓN EN TABLAS
    // =========================================================================
    try {
        let indexActual = producto.length;
        
        subtotal[indexActual] = parseFloat((cantNum * compraNum).toFixed(2));
        sumas = parseFloat((sumas + subtotal[indexActual]).toFixed(2));
        totalMASIVA = sumas;
        total = totalMASIVA;

        formulaEvaluadaiva = formula.replace(/A/g, total);
        IVA = parseFloat(math.evaluate(formulaEvaluadaiva).toFixed(2));

        formulaEvaluadaiva = formula.replace(/A/g, subtotal[indexActual]);
        subiva[indexActual] = parseFloat(math.evaluate(formulaEvaluadaiva).toFixed(2));

        producto[indexActual] = idProducto;
        Cantidad[indexActual] = cantNum;
        preciocompra[indexActual] = compraNum;
        precioventa[indexActual] = ventaNum;
        nombre[indexActual] = nameProducto !== "" ? nameProducto : "Producto ID " + idProducto;
        
        if (!window.arrayFechasGlobal) window.arrayFechasGlobal = [];
        window.arrayFechasGlobal[indexActual] = (esPerecedero === 1) ? fechaVencimiento : 'N/A';
        
        cantidadarticulos += cantNum;

        if (typeof llenarTablaventas === 'function') { llenarTablaventas(); }
        
        limpiarCampos();
        disableButtons();

        $('#sumas').html(cantidadarticulos);
        $('#IVA').html(IVA);
        $('#total').html(total);
        $('#impuesto').val(IVA);
        $('#inputTotal').val(total - IVA);

        if (typeof sumarArreglos === 'function') { 
            sumarArreglos(formulas, monto); 
        }

        console.log("🎯 [Éxito] Artículo inyectado correctamente en ambas tablas.");
        
        // Retornar falso para romper la burbuja del dispatcher de jQuery
        return false;

    } catch (error) {
        console.error("❌ Error crítico en el cálculo matemático interno:", error);
        alert("Ocurrió un inconveniente al calcular los subtotales del comprobante.");
        return false;
    }
}



function eliminarProducto(indice) {
        sumas -= round(subtotal[indice]);
        total = round(sumas);
        subiva[indice] = round(sumas);
        formulaEvaluadaiva = formula.replace(/A/g, total);
        resultadoiva = math.evaluate(formulaEvaluadaiva);
        IVA = parseFloat(resultadoiva.toFixed(2));
        cantidadarticulos -= parseInt(Cantidad[indice], 10);

        $('#sumas').html(cantidadarticulos);
        $('#IVA').html(IVA);
        $('#total').html(total);
        $('#impuesto').val(IVA);
        $('#InputTotal').val(total - IVA);

        $('#fila' + indice).remove();
        
        producto.splice(indice, 1);
        nombre.splice(indice, 1);
        Cantidad.splice(indice, 1);
        preciocompra.splice(indice, 1);
        subiva.splice(indice, 1);
        precioventa.splice(indice, 1);
        subtotal.splice(indice, 1);
        if(window.arrayFechasGlobal) window.arrayFechasGlobal.splice(indice, 1);

        sumarArreglos(formulas, monto);
        disableButtons();
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

function limpiarCampos() {
    // 🚀 ALTERNATIVA SEGURA: Deselecciona el producto sin disparar eventos recursivos destructivos
    // y limpia los inputs numéricos de forma instantánea
    $('#producto_id').val('').selectpicker('deselectAll');
    
    // Forzar el redibujado limpio del botón visual a su marcador de posición (Placeholder)
    $('#producto_id').selectpicker('render');

    $('#cantidad').val('');
    $('#precio_compra').val('');
    $('#precio_venta').val('');
    $('#fecha_vencimiento').val('');
}

function round(num, decimales = 2) {
var signo = (num >= 0 ? 1 : -1);
num = num * signo;
if (decimales === 0) return signo * Math.round(num);
num = num.toString().split('e');
num = Math.round(+(num + 'e' + (num ? (+num + decimales) : decimales)));
num = num.toString().split('e');
return signo * (num + 'e' + (num ? (+num - decimales) : -decimales));
}
    // =========================================================================
    // 10. LECTOR DE CÓDIGOS QR / BARRAS EXTERNO (HTML5-QRCODE)
    // =========================================================================
    function iniciarScanner(tipo = "barra") {
        if (escaneando) return;
        scanner = new Html5Qrcode("reader");
        escaneando = true;

        scanner.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: tipo === "barra" ? { width: 250, height: 150 } : 250
            },
            (codigo) => {
                console.log("Código ver:", codigo);
                StopScanner();
                agregarProductoScanner(codigo.trim());
            },
            (error) => { /* Silenciar advertencias */ }
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
        .catch(err => console.error("Error al detener:", err));
    }

// 🚀 ¡AQUÍ ESTÁ LA SOLUCIÓN! Cerramos el $(document).ready AL FINAL de todo el código
}); 

// =========================================================================
// 🚀 GESTIÓN DE CUENTAS CONTABLES Y PARTIDA DOBLE (CORREGIDO)
// =========================================================================

function ejecutarAdicionDinamicaCC(boton) {
    // 1. Obtener el contenedor padre usando jQuery nativo para evitar colisiones
    let $bloquePadre = $(boton).closest('.boundary-cc-block');
    if ($bloquePadre.length === 0) return false;

    // 2. 🚀 CAPTURA QUIRÚRGICA DIRECTA POR ATRIBUTO NAME:
    // Buscamos el elemento nativo usando el atributo name="cuentacontable_id" que inyectamos en el HTML.
    // Esto es 100% inmune a las clases visuales intermedias de Bootstrap Select.
    let selectCuentaNat = $bloquePadre.find('select[name="cuentacontable_id"]');
    let idDocumento = selectCuentaNat.val();
    
    // Caída segura preventiva por si el selectpicker alteró el .val() en el nodo raíz
    if (!idDocumento || idDocumento === "" || idDocumento === "undefined" || idDocumento === undefined) {
        idDocumento = selectCuentaNat.find('option:selected').val() || '';
    }

    // Capturar la naturaleza (Debe / Haber) buscando por su atributo NAME nativo
    let selectNaturalezaNat = $bloquePadre.find('select[name="Naturaleza"]');
    let Naturaleza = selectNaturalezaNat.val();
    if (!Naturaleza || Naturaleza === "" || Naturaleza === "undefined") {
        Naturaleza = selectNaturalezaNat.find('option:selected').val() || '';
    }

    // Capturar el Monto
    let inputMontoNat = $bloquePadre.find('input[name="valorminimo"]').length ? $bloquePadre.find('input[name="valorminimo"]') : $bloquePadre.find('.clase_dinamica_monto');
    let MontoRaw = inputMontoNat.val();
    let Monto = parseFloat(MontoRaw) || 0;

    // Obtener el texto de la cuenta seleccionada para la visualización de la tabla
    let NombreDocumento = selectCuentaNat.find('option:selected').text() || '';

    // 🔍 LOG DE AUDITORÍA EN CONSOLA (F12)
    console.log("📊 [Modo Libre F] Verificación de Variables Crudas:", {
        idDocumento: idDocumento,
        NombreDocumento: NombreDocumento.trim(),
        Naturaleza: Naturaleza,
        Monto: Monto
    });

    // 3. 🚨 ESCUDO PROTECTOR: Validación estricta que frena el código si detecta un 'undefined'
    // Impide físicamente que se ensucie el arreglo global idcuenta si el select falló en el DOM
    if (!idDocumento || idDocumento === "" || idDocumento === "undefined" || idDocumento === undefined ||
        !Naturaleza || Naturaleza === "" || Naturaleza === "undefined" || Naturaleza === undefined || Monto <= 0) {
        alert("Por favor, complete todos los campos de la cuenta contable de forma válida (Seleccione Cuenta, Naturaleza y asigne un Monto mayor a cero).");
        return false;
    }
    
    // =========================================================================
    // 4. INYECTAR DIRECTAMENTE EN LOS ARREGLOS GLOBALES CONTABLES
    // =========================================================================
    let indexCC = idcuenta.length;
    
    idcuenta[indexCC] = idDocumento;
    cuenta[indexCC] = NombreDocumento.trim();
    monto[indexCC] = Monto;
    tipo[indexCC] = Naturaleza;

    // 5. Calcular balance de sumas acumuladas locales para control visual de partida doble
    let debeacum = 0;
    let haberacumb = 0;
    for (let i = 0; i < idcuenta.length; i++) {
        if (tipo[i] === "D") debeacum = Number(debeacum) + Number(monto[i]);
        if (tipo[i] === "H") haberacumb = Number(haberacumb) + Number(monto[i]);
    }

    let msgContenedor = document.getElementById("msj");
    if (msgContenedor) {
        if (Number(haberacumb) !== Number(debeacum)) {
            msgContenedor.textContent = "⚠️ Los montos Debe y Haber sumados deben de coincidir.";
            msgContenedor.style.color = "red";
        } else if (Number(haberacumb) !== Number(total)) {
            msgContenedor.textContent = "⚠️ Los montos Debe y Haber sumados deben de coincidir con el Total del documento.";
            msgContenedor.style.color = "red";
        } else { 
            msgContenedor.textContent = ""; 
        }
    }

    // 6. Invocar al dibujador oficial de la tabla con el arreglo 100% limpio y libre de undefined
    construirsumarCC(idcuenta, monto[indexCC]);

    // 7. Limpieza de los selectores del bloque dinámico para el siguiente registro
    inputMontoNat.val(0);
    selectCuentaNat.selectpicker('val', '');
    selectNaturalezaNat.selectpicker('val', '');
}


function agregarCuentaC() {
    let idDocumento = document.getElementById('cuentacontable_id') ? document.getElementById('cuentacontable_id').value : '';
    let select = document.getElementById('cuentacontable_id');
    let NombreDocumento = (select && select.selectedIndex >= 0) ? select.options[select.selectedIndex].text : '';
    let debeacum = 0;
    let haberacumb = 0;
    let Naturaleza = document.getElementById('Naturaleza') ? document.getElementById('Naturaleza').value : '';
    let Monto = $('#valorminimo').val() || 0;

    if (idDocumento === "") { alert("Por favor, seleccione una Cuenta Contable."); return false; }
    if (Monto == 0) { alert("Por favor, el valor debe de ser mayor a cero."); return false; }

    let indexCC = idcuenta.length;
    idcuenta[indexCC] = idDocumento;
    cuenta[indexCC] = NombreDocumento;
    monto[indexCC] = Monto;
    tipo[indexCC] = Naturaleza;

    for (let i = 0; i < idcuenta.length; i++) {
        if (tipo[i] === "D") debeacum = Number(debeacum) + Number(monto[i]);
        if (tipo[i] === "H") haberacumb = Number(haberacumb) + Number(monto[i]);
    }

    let msgContenedor = document.getElementById("msj");
    if(msgContenedor) {
        if (Number(haberacumb) !== Number(debeacum)) {
            msgContenedor.textContent = "⚠️ Los montos Debe y Haber sumados deben de coincidir.";
            msgContenedor.style.color = "red";
        } else if (Number(haberacumb) !== Number(total)) {
            msgContenedor.textContent = "⚠️ Los montos Debe y Haber sumados deben de coincidir con el Total del documento.";
            msgContenedor.style.color = "red";
        } else { msgContenedor.textContent = ""; }
    }

    construirsumarCC(idcuenta, monto[indexCC]);
    if (document.getElementById('valorminimo')) $('#valorminimo').val(0);
    if (document.getElementById('Naturaleza')) $('#Naturaleza').val("");
}

function eliminarCC(indice) {
    $('#filaCC' + indice).remove();
    idcuenta.splice(indice, 1);
    cuenta.splice(indice, 1);
    monto.splice(indice, 1);
    tipo.splice(indice, 1);
    construirsumarCC(idcuenta, 0);
    disableButtons();
}

function construirsumarCC(arr1, arr2) {
    var tableBody = $('#detalle_tbody');
    tableBody.empty(); // Limpiar el cuerpo de la tabla de forma segura antes de redibujar

    $.each(arr1, function(index, value) {
        // 🚀 TRIPLE COMPATIBILIDAD DE INPUTS OCULTOS:
        // Inyectamos las tres llaves posibles para que Laravel capture el ID legítimo (como el '13')
        // y nunca más vuelva a enviar un string 'undefined' a la base de datos.
        var row = '<tr id="filaCC' + index + '">' +
            '<td>' +
                '<input name="idcuenta[]" type="hidden" value="' + idcuenta[index] + '">' +
                '<input name="arrayidcuenta[]" type="hidden" value="' + idcuenta[index] + '">' +
                '<input name="cuentacontable_id[]" type="hidden" value="' + idcuenta[index] + '">' +
            '</td>' +
            '<td>' + cuenta[index] + '</td>' +
            '<td class="small-text">N/A</td>' +
            '<td><input name="arraymonto[]" type="number" class="form-control form-control-sm" value="' + monto[index] + '"></td>' +
            '<td><input name="arraytipomovimiento[]" type="text" class="form-control form-control-sm" value="' + tipo[index] + '" readonly></td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onClick="eliminarCC(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';
        
        tableBody.append(row);
    });
}



function llenarTablaventas() {
    var tableBodyDetalle = $('#tabla_detalle_tbody');
    tableBodyDetalle.empty(); 
    $('#tabla_detalle tbody').empty(); 

    $.each(producto, function(index) {
        let precioVentaActual = subtotal[index] || 0;
        let resultadoIva = CalcularFormula(formula, precioVentaActual);
        subiva[index] = resultadoIva;

        var fila = '<tr id="fila' + index + '">' +
            '<th>' + (index + 1) + '</th>' +
            '<td><input type="hidden" name="arrayidproducto[]" value="' + producto[index] + '">' + nombre[index] + '</td>' +
            '<td><input type="hidden" name="arraycantidad[]" value="' + Cantidad[index] + '">' + Cantidad[index] + '</td>' +
            '<td><input type="hidden" name="arraypreciocompra[]" value="' + (preciocompra[index] || 0) + '">' + (preciocompra[index] || 0) + '</td>' +
            '<td><input type="hidden" name="arrayfecha_vencimiento[]" value="' + (window.arrayFechasGlobal ? window.arrayFechasGlobal[index] : 'N/A') + '">' + (window.arrayFechasGlobal ? window.arrayFechasGlobal[index] : 'N/A') + '</td>' +
            '<td><input type="hidden" name="arraysubiva[]" value="' + subiva[index] + '">' + subiva[index] + '</td>' +
            '<td><input type="hidden" name="arrayprecioventa[]" value="' + (precioventa[index] || 0) + '">' + (precioventa[index] || 0) + '</td>' +
            '<td>' + subtotal[index] + '</td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onClick="eliminarProducto(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +
            '</tr>';
        
        tableBodyDetalle.append(fila);
        if (tableBodyDetalle.children().length === 0) {
            $('#tabla_detalle tbody').append(fila);
        }
    });

    IVA = CalcularFormula(formula, total);
    if ($('#total_iva_visual').length > 0) {
        $('#total_iva_visual').html('Q. ' + IVA);
    }
}

function CalcularFormula(formulalocal, montoA) {
    try {
        if (!formulalocal) return 0;
        let formulaEvaluadaiva = formulalocal.replace(/A/g, `(${montoA})`);
        let resultadoiva = math.evaluate(formulaEvaluadaiva);
        if (typeof resultadoiva === 'object' && resultadoiva.hasOwnProperty('value')) {
            resultadoiva = resultadoiva.value;
        }
        let numeroFinal = parseFloat(resultadoiva) || 0;
        return parseFloat(numeroFinal.toFixed(2));
    } catch (error) {
        console.error("Error al calcular fórmula:", error);
        return 0;
    }
}

</script>
@endpush
