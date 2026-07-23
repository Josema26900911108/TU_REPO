@extends('layouts.app')

@section('title','Productos')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Productos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Productos</li>
    </ol>

    @can('crear-producto')
    <div class="mb-4">
        <a href="{{route('productos.create')}}">
            <button type="button" class="btn btn-primary">Añadir nuevo registro</button>
        </a>
    </div>
    @endcan

    @can('crear-productomasivo')
    <style>
        .gap-3 { gap: 1rem; }
        .cursor-pointer { cursor: pointer; }
        .btn-masivo-custom {
            display: inline-block;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            user-select: none;
            padding: .375rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: .25rem;
            transition: background-color .15s ease-in-out;
        }
    </style>

    <!-- CORRECCIÓN CRÍTICA: CDN Real y funcional de PapaParse -->
    <script src="https://cloudflare.com"></script>

    <div id="contenedorMasivoAislado" class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light border-0">
            <h5 class="mb-0 text-secondary font-weight-bold">
                📦 Gestión Masiva de Compras e Inventarios
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted small">
                Descarga la plantilla oficial para rellenar los datos. Si utilizas el código <strong>"SG"</strong>, el sistema autogenerará un código de barras EAN-13 válido de forma automática.
            </p>
            
            <div class="d-flex flex-wrap align-items-center gap-3">
                <a href="{{ route('compras.descargar-formato') }}" class="btn btn-outline-primary font-weight-bold px-4">
                    📥 Descargar Formato CSV
                </a>

                <input type="file" id="csvFileInput" accept=".csv" style="display: none !important; visibility: hidden !important;">
                
                <span id="triggerFileSpan" class="btn-masivo-custom bg-success text-white cursor-pointer px-4">
                    🚀 Seleccionar y Cargar CSV
                </span>
                
                <span id="fileNameDisplay" class="text-muted small font-italic">No se ha seleccionado ningún archivo</span>
            </div>
        </div>
    </div>
    @endcan


    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla productos
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped fs-6">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th>Presentación</th>
                        <th>Vencimiento</th>
                        <th>Categorías</th>
                        <th>Estado</th>
                        <!------Eliminar producto---->
                        @can('vertienda-producto')
                        <th>Tienda</th>
                        @endcan
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productos as $item)
                    <tr>
                        <td>
                            {{$item->codigo}}
                        </td>
                        <td>
                            {{$item->nombre}}
                        </td>
                        <td>
                            {{$item->marca->caracteristica->nombre}}
                        </td>
                        <td>
                            {{$item->presentacione->caracteristica->nombre}}
                        </td>
    <td>
    @php
        // Suponiendo que tienes una relación 'lotes' en tu modelo Producto
        $loteProximo = $item->lotes()->where('cantidad', '>', 0)->orderBy('fecha_vencimiento', 'asc')->first();
    @endphp

    @if($loteProximo)
        @php
            $dias = now()->diffInDays($loteProximo->fecha_vencimiento, false);
        @endphp

        @if($dias <= 0)
            <span class="badge bg-danger" title="Lote: {{ $loteProximo->codigo_lote }}">VENCIDO</span>
        @elseif($dias <= 30)
            <span class="badge bg-warning text-dark" title="Vence el: {{ $loteProximo->fecha_vencimiento }}">Próximo ({{ $dias }} días)</span>
        @else
            <span class="badge bg-info text-white">Al día</span>
        @endif
    @else
        <span class="text-muted" style="font-size: 0.8rem;">Sin stock/lotes</span>
    @endif
</td>
                        <td>
                            @foreach ($item->categorias as $category)
                            <div class="container" style="font-size: small;">
                                <div class="row">
                                    <span class="m-1 rounded-pill p-1 bg-secondary text-white text-center">{{$category->caracteristica->nombre}}</span>
                                </div>
                            </div>
                            @endforeach
                        </td>
                        @can('vertienda-producto')
                        <td>
                            @if($item->tienda)
                                {{ $item->tienda->Nombre }}
                            @else
                                Sin tienda asignada
                            @endif
                        </td>
                        @endcan
                        <td>
                            @if ($item->estado == 1)
                            <span class="badge rounded-pill text-bg-success">activo</span>
                            @else
                            <span class="badge rounded-pill text-bg-danger">eliminado</span>
                            @endif
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
                                        <!-----Editar Producto--->
                                        @can('editar-producto')
                                        <li><a class="dropdown-item" href="{{route('productos.edit',['producto' => $item])}}">Editar</a></li>
                                        @endcan
                                        <!----Ver-producto--->
                                        @can('ver-producto')
                                        <li>
                                            <a class="dropdown-item" role="button" data-bs-toggle="modal" data-bs-target="#verModal-{{$item->id}}">Ver</a>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                                <div>
                                    <!----Separador----->
                                    <div class="vr"></div>
                                </div>
                                <div>
                                    <!------Eliminar producto---->
                                    @can('eliminar-producto')
                                    @if ($item->estado == 1)
                                    <button title="Eliminar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark">
                                        <svg class="svg-inline--fa fa-trash-can" aria-hidden="true" focusable="false" data-prefix="far" data-icon="trash-can" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg="">
                                            <path fill="currentColor" d="M170.5 51.6L151.5 80h145l-19-28.4c-1.5-2.2-4-3.6-6.7-3.6H177.1c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80H368h48 8c13.3 0 24 10.7 24 24s-10.7 24-24 24h-8V432c0 44.2-35.8 80-80 80H112c-44.2 0-80-35.8-80-80V128H24c-13.3 0-24-10.7-24-24S10.7 80 24 80h8H80 93.8l36.7-55.1C140.9 9.4 158.4 0 177.1 0h93.7c18.7 0 36.2 9.4 46.6 24.9zM80 128V432c0 17.7 14.3 32 32 32H336c17.7 0 32-14.3 32-32V128H80zm80 64V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16z"></path>
                                        </svg>
                                    </button>
                                    @else
                                    <button title="Restaurar" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark">
                                        <i class="fa-solid fa-rotate"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal -->
                    <div class="modal fade" id="verModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Detalles del producto</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-12">
                                            <p><span class="fw-bolder">Descripción: </span>{{$item->descripcion}}</p>
                                        </div>
                                        <div class="col-12">
                                            <p><span class="fw-bolder">Fecha de vencimiento: </span>{{$item->fecha_vencimiento=='' ? 'No tiene' : $item->fecha_vencimiento}}</p>
                                        </div>
                                        <div class="col-12">
                                            <p><span class="fw-bolder">Stock: </span>{{$item->stock}}</p>
                                        </div>
                                        <div class="col-12">
                                            <p class="fw-bolder">Imagen:</p>
                                            <div>
                                                @if ($item->img_path != null)
                                                <img src="{{ Storage::disk('gcs_images')->url($item->img_path) }}" alt="{{$item->nombre}}" class="img-fluid img-thumbnail border border-4 rounded">
                                                @else
                                                <img src="" alt="{{$item->nombre}}">
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de confirmación-->
                    <div class="modal fade" id="confirmModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Mensaje de confirmación</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    {{ $item->estado == 1 ? '¿Seguro que quieres eliminar el producto?' : '¿Seguro que quieres restaurar el producto?' }}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <form action="{{ route('productos.destroy',['producto'=>$item->id]) }}" method="post">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Confirmar</button>
                                    </form>
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

<!-- =========================================================================
// PARTE 2.1: INTERCEPCIÓN DEL SELECTOR NEUTRO Y PARSEO DE CELDAS
// ========================================================================= -->
@push('js')
<script src="{{ asset('js/papaparse.min.js') }}"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const csvFileInput = document.getElementById('csvFileInput');
    const triggerFileSpan = document.getElementById('triggerFileSpan');
    const fileNameDisplay = document.getElementById('fileNameDisplay');

    if (triggerFileSpan && csvFileInput) {
        triggerFileSpan.addEventListener('click', function (e) {
            e.preventDefault(); 
            e.stopPropagation();
            csvFileInput.click();
        });
    }

// =========================================================================
// PARTE 1: CAPTURA CORREGIDA DEL ARCHIVO INDIVIDUAL [0] (EVITA ERROR BLOB)
// =========================================================================
    if (csvFileInput) {
        csvFileInput.addEventListener('change', function (e) {
            e.preventDefault(); 
            e.stopPropagation();
            
            const files = e.target.files;
            // Freno de seguridad si el usuario cierra la ventana sin elegir nada
            if (!files || files.length === 0) return;

            // MOSTRAR NOMBRE: Extraemos el nombre del primer archivo de la lista
            fileNameDisplay.textContent = files[0].name;

            // SOLUCIÓN AL ERROR: Pasamos files[0] (que es un Blob válido) en lugar de la lista completa
            Papa.parse(files[0], {
                header: true,
                skipEmptyLines: true,
                encoding: "UTF-8", 
                complete: function (results) {
                    if (!results.data || results.data.length === 0) {
                        Swal.fire('Error', 'El archivo CSV está vacío.', 'error');
                        return;
                    }
                    solicitarParametrosCabecera(results.data);
                },
                error: function (error) {
                    Swal.fire('Error', 'No se pudo leer el archivo: ' + error.message, 'error');
                }
            });
            
            csvFileInput.value = ''; // Limpiar campo para permitir re-subidas
        });
    }


    function solicitarParametrosCabecera(filasCSV) {
        Swal.fire({
            title: 'Confirmación de Carga',
            html: `
                <div class="text-start">
                    <p class="small text-muted mb-2">El sistema procesara automaticamente el lote asociandolo al folio contable y tipo de comprobante por defecto <strong>"DC"</strong>.</p>
                    <label class="form-label small font-weight-bold">Número Comprobante (0 para Auto-correlativo):</label>
                    <input type="text" id="swal_numero_comprobante" class="form-control" value="0">
                </div>
            `,
            focusConfirm: false, 
            showCancelButton: true,
            confirmButtonText: 'Procesar Carga Masiva 🚀', 
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                return { numero_comprobante: document.getElementById('swal_numero_comprobante').value }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                construirFormDataMasivo(filasCSV, result.value);
            }
        });
    }// =========================================================================
// SECCIÓN 2.2: CONSTRUCCIÓN DE MATRICES INDEXADAS Y ENVÍO SEGURO POST (FETCH)
// =========================================================================
    function construirFormDataMasivo(filas, cabecera) {
        Swal.fire({
            title: 'Procesando inserción masiva...',
            text: 'Por favor espera un momento, aplicando cambios en Kárdex y Contabilidad.',
            allowOutsideClick: false, 
            didOpen: () => { Swal.showLoading(); }
        });

        let formData = new FormData();
        formData.append('numero_comprobante', cabecera.numero_comprobante);
        formData.append('proveedore_id', ''); 

        const ahora = new Date();
        formData.append('fecha_hora', ahora.toISOString().slice(0, 19).replace('T', ' '));
        formData.append('fecha', ahora.toISOString().split('T'));

        // Extraer los datos del proveedor utilizando la primera fila útil de tu Excel
        if (filas.length > 0) {
            formData.append('proveedor_nit', filas['PROVEEDOR_NIT'] || filas['PROVEEDOR_NIT'] || '');
            formData.append('proveedor_nombre', filas['PROVEEDOR_NOMBRE'] || filas['PROVEEDOR_NOMBRE'] || '');
        }

        let totalGeneral = 0;
        let impuestoGeneral = 0;

        // Recorrer filas mapeando los nombres exactos de tu imagen
        filas.forEach((fila) => {
            const cantidad = parseInt(fila['CANTIDAD']) || 0;
            const precioCompra = parseFloat(fila['PRECIO_CON']) || parseFloat(fila['PRECIO COMPRA']) || 0;
            const subtotalIva = parseFloat(fila['SUBTOTAL_IVA']) || 0;

            totalGeneral += (cantidad * precioCompra);
            impuestoGeneral += subtotalIva;

            const textoPerecedero = (fila['ES_PERECED'] || fila['ES PERECEDERO'] || '').trim().toUpperCase();
            const esPerecederoEntero = (textoPerecedero === 'SI' || textoPerecedero === 'SÍ') ? 1 : 0;

            formData.append('array_codigo[]', fila['CODIGO_PRO'] || fila['PRODUCTO CODIGO'] || 'SG');
            formData.append('array_nombre[]', fila['NOMBRE_PR'] || 'Producto Masivo');
            formData.append('array_categoria_nombre[]', fila['CATEGORIA_'] || fila['CATEGORIA'] || '');
            formData.append('array_presentacion_nombre[]', fila['PRESENTACI'] || fila['PRESENTACION'] || '');
            formData.append('array_marca_nombre[]', fila['MARCA_ID'] || fila['MARCA'] || '');
            formData.append('array_marca_nombre[]', fila['MARCA_ID'] || fila['MARCA'] || '');
            formData.append('array_proveedor_nombre[]', fila['PROVEEDOR_NOMBRE'] || fila['PROVEEDOR_NOMBRE'] || '');
            formData.append('array_proveedor_nit[]', fila['PROVEEDOR_NIT'] || fila['PROVEEDOR_NIT'] || '');
            
            formData.append('array_perecedero[]', esPerecederoEntero);
            formData.append('arraycantidad[]', cantidad);
            formData.append('arraypreciocompra[]', precioCompra);
            formData.append('arrayprecioventa[]', parseFloat(fila['PRECIO_VEN']) || parseFloat(fila['PRECIO VENTA']) || 0);
            formData.append('arraysubiva[]', subtotalIva);
            formData.append('arrayfecha_vencimiento[]', fila['FECHA_VEN'] || fila['FECHA VENCIMIENTO'] || '');
        });

        formData.append('total', totalGeneral.toFixed(2));
        formData.append('impuesto', impuestoGeneral.toFixed(2));

        despacharPeticionBackend(formData);
    }

    function despacharPeticionBackend(formData) {
        const tokenCsrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        fetch("{{ route('compras.storeMasivoExcel') }}", {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': tokenCsrf, 'Accept': 'application/json' }
        })
        .then(async response => {
            const textResponse = await response.text();
            let jsonData;
            try { 
                jsonData = JSON.parse(textResponse); 
            } catch (err) {
                console.error("Respuesta cruda del servidor:", textResponse);
                throw new Error("El backend devolvio un fallo de codigo en lugar de JSON.");
            }
            if (!response.ok) {
                throw new Error(jsonData.message || 'Error operativo en base de datos.');
            }
            return jsonData;
        })
        .then(data => {
            Swal.fire({ icon: 'success', title: '¡Todo listo!', text: data.message }).then(() => {
                window.location.reload(); 
            });
        })
        .catch(error => {
            console.error(error);
            Swal.fire('Fallo en insercion masiva', error.message, 'error');
        });
    }
});
</script>
@endpush
