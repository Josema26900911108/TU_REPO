@extends('layouts.app')

@section('title', 'Editar usuario')

@push('css')
<!-- Bootstrap-Select -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">

<!-- jQuery UI -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<!-- Bootstrap Treeview -->
<link rel="stylesheet" href="{{ asset('css/bootstrap-treeview.min.css') }}">
<!-- SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Gijgo (si se usa) -->
<link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<style>
#preview { display: flex; flex-wrap: wrap; margin-top: 10px; gap: 10px; }
.photo-container { position: relative; display: inline-block; }
.photo-container img { max-width: 100px; border: 2px solid #ccc; border-radius: 5px; }
.btn-remove { position: absolute; top: 0; right: 0; background: red; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; }
</style>

<style>

    #itemmanoobraamterial {
    width: 100%;       /* ocupa todo el ancho del contenedor */
    white-space: normal; /* permite que el texto largo se divida en varias líneas */
    font-size: 14px;   /* ajustar tamaño en móviles */
}
    .treeview {
    min-height:20px;
    padding:19px;
    margin-bottom:20px;
    background-color:#fbfbfb;
    border:1px solid #999;
    -webkit-border-radius:4px;
    -moz-border-radius:4px;
    border-radius:4px;
    -webkit-box-shadow:inset 0 1px 1px rgba(0, 0, 0, 0.05);
    -moz-box-shadow:inset 0 1px 1px rgba(0, 0, 0, 0.05);
    box-shadow:inset 0 1px 1px rgba(0, 0, 0, 0.05)
}
.treeview li {
    list-style-type:none;
    margin:0;
    padding:10px 5px 0 5px;
    position:relative
}
.treeview li::before, .treeview li::after {
    content:'';
    left:-20px;
    position:absolute;
    right:auto
}
.treeview li::before {
    border-left:1px solid #999;
    bottom:50px;
    height:100%;
    top:0;
    width:1px
}
.treeview li::after {
    border-top:1px solid #999;
    height:20px;
    top:25px;
    width:25px
}
.treeview li span:not(.glyphicon) {
    -moz-border-radius:5px;
    -webkit-border-radius:5px;
    border-radius:5px;
    display:inline-block;
    padding:4px 9px;
    text-decoration:none
}
.treeview li.parent_li>span:not(.glyphicon) {
    cursor:pointer
}
.treeview>ul>li::before, .treeview>ul>li::after {
    border:0
}
.treeview li:last-child::before {
    height:30px
}
.treeview li.parent_li>span:not(.glyphicon):hover, .treeview li.parent_li>span:not(.glyphicon):hover+ul li span:not(.glyphicon) {
    background:#eee;
    border:1px solid #999;
    padding:3px 8px;
    color:#000
}

#contextMenu {
    background-color: white;
    border: 1px solid #ccc;
    z-index: 1000;
    display: none;
    position: absolute;
}

#contextMenu li {
    list-style: none;
    padding: 8px 12px;
}

#contextMenu li:hover {
    background-color: #f0f0f0;
}
.menu .accordion-heading {  position: relative; }
.menu .accordion-heading .edit {
    position: absolute;
    top: 8px;
    right: 30px;
}
.menu .treeview node-treeview { border-left: 4px solid #f38787; }
.menu .item-node { border-left: 4px solid #65c465; }
.menu .node-treeview { border-left: 4px solid #98b3fa; }
.menu .collapse.in { overflow: visible; }

/* Contenedor principal en la esquina superior derecha */
.floating-window {
    position: fixed !important;
    bottom: 5% !important;   /* Se adapta al 5% de la altura de cualquier pantalla */
    right: auto !important;    /* Se adapta al 5% del ancho de cualquier pantalla */
    top: auto !important;
    left: 5% !important;
    
    /* Tamaños máximos para que no se desborde en celulares pequeños */
    width: 300px;
    max-width: 85vw;         /* Nunca ocupará más del 85% del ancho del celular */
    height: 400px;
    max-height: 70vh;        /* Nunca ocupará más del 70% de la altura del celular */
    
    background-color: #ffffff;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.25);
    display: flex !important; 
    visibility: visible !important;
    opacity: 1 !important;    
    flex-direction: column;
    overflow: hidden;
    z-index: 99999 !important;
    
    /* Aceleración por hardware para evitar fallos de carga en móviles */
    transform: translate3d(0, 0, 0); 
    -webkit-transform: translate3d(0, 0, 0);
    transition: height 0.2s ease, width 0.2s ease;
}

/* Barra superior de arrastre */
.window-header {
    padding: 10px 14px;
    background-color: #007bff;
    color: white;
    cursor: move;
    display: flex !important;
        justify-content: space-between !important;
    align-items: center !important;
    user-select: none;
    width: 100% !important;
    box-sizing: border-box !important;

}


..window-title {
    font-weight: bold;
    font-family: sans-serif;
}

.window-controls {
    display: flex !important;
    gap: 6px;
    align-items: center !important;
    flex-shrink: 0 !important; /* Prohíbe terminantemente que el título los aplaste */
    margin-left: auto !important; /* Los empuja magnéticamente a la derecha */
}

.win-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    border-radius: 4px;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-weight: bold;
}

.win-btn:hover {
    background: rgba(255, 255, 255, 0.4);
}

.window-content {
    padding: 15px;
    flex-grow: 1;
    overflow-y: auto;
}

/* Estado Minimizado adaptable */
.floating-window.minimized {
    height: 45px !important; 
    width: 220px;
    max-width: 60vw;
}

.floating-window.minimized .window-content {
    display: none;
}

/* --- CONFIGURACIÓN DE MAXIMIZADO ABSOLUTO E INMÓVIL --- */
.floating-window.maximized {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    
    /* 🌟 CORRECCIÓN RADICAL: Le restamos 30 píxeles al ancho de la pantalla (Viewport) 
       Esto empuja a los botones a la izquierda, obligándolos a aparecer a la vista */
    width: calc(100vw - 30px) !important;
    max-width: calc(100vw - 30px) !important;
    
    /* Mantiene la altura completa de la pantalla */
    height: 100vh !important;
    max-height: 100vh !important;
    
    border-radius: 0px !important;
    margin: 0px !important;
    padding: 0px !important;
    box-sizing: border-box !important;
    z-index: 999999999 !important; /* Máxima prioridad perimetral */
    transform: none !important;  
    -webkit-transform: none !important;
}

/* 2. Forzar que el encabezado maximizado respete un colchón a la derecha */
.floating-window.maximized .window-header {
    background-color: #1e293b !important; /* Tono oscuro profesional */
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    width: 100% !important;
    height: 48px !important;
    box-sizing: border-box !important;
    
    /* COLCHÓN DE SEGURIDAD CRÍTICO: Empuja los botones hacia el interior del cristal */
    padding-right: 25px !important; 
    padding-left: 15px !important;
}

/* 3. Asegurar que los botones nunca se encojan ni se desplacen */
.floating-window.maximized .window-controls {
    display: flex !important;
    gap: 8px !important;
    align-items: center !important;
    flex-shrink: 0 !important; /* Prohíbe que el título o el espacio los aplaste */
    margin-left: auto !important; /* Los pega magnéticamente a la derecha */
}

/* 4. Forzar visibilidad y tamaño táctil en los botones */
.floating-window.maximized .win-btn {
    background: rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.4) !important;
    width: 28px !important;
    height: 28px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}



/* Contenedor del título e icono */
.window-title-container {
    display: flex !important;
    align-items: center !important;
    gap: 8px;
    min-width: 0 !important; /* Clave: permite que el contenedor se encoja en móviles */
    flex: 1 !important;
    font-weight: bold;
    font-family: sans-serif;
}

/* El icono siempre mantiene su tamaño */
.window-icon {
    font-size: 16px;
    flex-shrink: 0;
}

/* Estilo inicial del texto (Visible) */
.window-title-text {
    display: inline-block;
    max-width: 200px; /* Ajusta según el largo de tu texto */
    opacity: 1;
    white-space: nowrap;
    overflow: hidden;
    transition: max-width 0.4s ease, opacity 0.3s ease, margin 0.4s ease;
    text-overflow: ellipsis !important; /* Si el texto no cabe, muestra "..." en lugar de empujar */
    min-width: 0 !important;
}

/* --- ESTADO CONTRAÍDO (Cuando el usuario baja en la página) --- */

/* Oculta el texto suavemente */
.floating-window.scrolled .window-title-text {
    max-width: 0px;
    opacity: 0;
    margin: 0;
}

/* Opcional: Hace la ventana un poco más angosta en modo minimizado 
   cuando el usuario está leyendo el contenido del ERP abajo */
.floating-window.minimized.scrolled {
    width: 90px !important; /* Espacio suficiente solo para el icono y los botones */
}


</style>
<style>
/* 1. Forzar al contenedor principal a ocupar el 100% real de la pantalla móvil */
.bootstrap-select, 
.bootstrap-select .dropdown-toggle,
.select-buscador {
    width: 100% !important;
    max-width: 100% !important;
}

/* 2. Transformar el menú desplegable en una estructura de tabla limpia */
.bootstrap-select .dropdown-menu {
    max-width: 100% !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 5px 0 0 0 !important;
    border: 1px solid #dee2e6 !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important;
    border-radius: 8px !important;
    overflow-x: auto !important; /* Permitir scroll horizontal si el texto es muy largo en celulares */
}

/* Encabezado simulado de tabla dentro del buscador (Opcional, si deseas rotular columnas) */
.bootstrap-select .dropdown-menu::before {
    content: "CATÁLOGO DE MATERIALES DISPONIBLES";
    display: block;
    background-color: #f8f9fa;
    color: #495057;
    font-weight: bold;
    font-size: 11px;
    text-align: center;
    padding: 8px;
    border-bottom: 2px solid #dee2e6;
    letter-spacing: 0.5px;
}

/* 3. Estilizar los elementos (filas) de la lista como renglones de tabla */
.bootstrap-select .dropdown-menu li {
    border-bottom: 1px solid #edf2f7 !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Cebra interlineado para identificar filas fácilmente en el camión o ruta */
.bootstrap-select .dropdown-menu li:nth-child(even) {
    background-color: #fcfcfc !important;
}

/* 4. Centrar y dar formato al texto dentro de cada fila */
.bootstrap-select .dropdown-menu li a {
    display: block !important;
    padding: 12px 15px !important;
    text-align: center !important; /* Centrado absoluto de los datos */
    color: #2d3748 !important;
    font-size: 13px !important;
    font-family: monospace, sans-serif !important; /* Estilo ordenado para SKUs y Series */
    white-space: normal !important; /* Permitir que el texto salte de línea si la pantalla es chica */
    word-break: break-word !important;
}

/* Efecto Hover táctil para móviles al pasar el dedo o seleccionar */
.bootstrap-select .dropdown-menu li a:hover,
.bootstrap-select .dropdown-menu li.selected a {
    background-color: #e2e8f0 !important;
    color: #1a202c !important;
    font-weight: bold !important;
}

/* 5. Ajustar el cuadro de texto del buscador interno (LiveSearch) */
.bootstrap-select .bs-searchbox {
    padding: 10px !important;
    background-color: #ffffff !important;
}

.bootstrap-select .bs-searchbox .form-control {
    border-radius: 20px !important;
    padding: 8px 15px !important;
    text-align: center !important; /* Centrar también lo que escribe el técnico */
    border: 1px solid #cbd5e0 !important;
}
</style>

@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">INVENTARIO DE ORDEN</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tecnico.buckettecnico') }}">Técnicos</a></li>
        <li class="breadcrumb-item active">{{ $tecnico->nombre.' - '.$tecnico->codigo.' - '.$tecnico->especialidad }}</li>
        @php
        $id2 = $tecnico->id;
        @endphp
    </ol>

        <!-- Menú Contextual -->
    <ul id="contextMenu" class="dropdown-menu">
        <li><a href="#" id="editNode">Editar</a></li>
        <li><a href="#" id="deleteNode">Eliminar</a></li>
        <li><a href="#" id="createChildNode">Nuevo</a></li>
    </ul>

<div class="card text-bg-light">
    <form id="formulario" action="{{ route('tecnico.operartrabajo', ['tecnico' => $tecnico, 'expediente' => $orden]) }}" method="POST" enctype="multipart/form-data">
        @method('POST')
        @csrf
        <div class="card-header">
            <p class="mb-0">Nota: Los usuarios son los que pueden ingresar al sistema</p>
        </div>

        <div class="card-body">
            <!-- Información de la orden -->
            <div class="row mb-4">
                @foreach ([
                    'Orden' => $orden->Orden,
                    'Virtual' => $orden->virtual,
                    'Tipo Servicio' => $orden->Tipo_servicio,
                    'Tipo Orden' => $orden->Tipo_orden,
                    'Cliente' => $orden->NOMBRECLIENTE,
                    'Dirección' => $orden->DIRECCION,
                    'Autoriza' => $orden->AUTORIZA,
                    'TECNOLOGIA' => $orden->TECNOLOGIA,
                    'Área' => $orden->AREA,
                    'Fecha' => $orden->FECHAINSTALACION,
                    'Observaciones' => $orden->OBS,
                    'Siglas' => $orden->SIGLASCENTRAL
                ] as $label => $value)

                @php
                $id3 = $orden->id;
                $tipo_orden=$orden->Tipo_servicio;
                @endphp
                <div class="col-lg-3 col-form-label mb-2">
                    <div><strong>{{ $label }}:</strong> {{ $value }}</div>
                </div>
                @endforeach
            </div>

            <!-- Select Tecnología -->
            <div class="row mb-4">
                <label for="itemtecnologia" class="col-lg-2 col-form-label">Seleccione Tecnología:</label>
                <div class="col-lg-6">
                    <select name="itemtecnologia" id="itemtecnologia" class="form-control selectpicker" data-live-search="true" data-size="10">
                    </select>
                    @error('itemtecnologia')
                    <small class="text-danger">{{ '*'.$message }}</small>
                    @enderror
                </div>
            </div>

                <!-- Select Mano de Obra -->
                <div class="row mb-4">
                    <label for="itemmanoobra" class="col-lg-2 col-form-label">Seleccione Mano de Obra:</label>
                    <div class="col-lg-6">
                        <select name="itemmanoobra" id="itemmanoobra" class="form-control selectpicker" data-live-search="true" data-size="10">
                        </select>
                        @error('itemmanoobra')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                </div>

            <hr class="my-4">

            <!-- Sección de Asignación de Items (Reemplaza la tabla deformada) -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white text-center fw-bold">
                    Asignado
                </div>
                <div class="card-body">
<div class="card-bt">
    <!-- Botón Escáner QR -->
    <button type="button" id="btn-qr" class="btn btn-success">
        <svg xmlns="http://w3.org" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
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

    <!-- Botón Escáner de Barra -->
    <button type="button" id="btn-barra" class="btn btn-secundary">
        <svg xmlns="http://w3.org" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <rect x="1" y="2" width="1" height="12"/>
        <rect x="3" y="2" width="2" height="12"/>
        <rect x="6" y="2" width="1" height="12"/>
        <rect x="8" y="2" width="2" height="12"/>
        <rect x="11" y="2" width="1" height="12"/>
        <rect x="13" y="2" width="2" height="12"/>
        </svg>
    </button>

    <!-- Botón Detener Escáner -->
    <button type="button" id="btn-stop" class="btn btn-danger">
        <svg xmlns="http://w3.org" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <rect x="2" y="2" width="12" height="12" rx="2"/>
        <rect x="5" y="5" width="6" height="6" fill="white"/>
        </svg>
    </button>
</div>

<div id="reader" style="width:100%"></div>
<div id="readerbarra" style="width:100%"></div>



                            <!-----SKU---->
                        <div  class="row mb-3 justify-content-center">
                            <label for="SKU" class="form-label">SKU:</label>
                            <input type="text" name="SKU" id="SKU" class="form-control">
                        </div>

                    <input type="hidden" name="nodoSeleccionado" id="nodoSeleccionado">
                    
                    <!-- Seleccione Item -->
<!-- Seleccione Item Optimizado para Pantallas Móviles -->
<div class="row mb-3 justify-content-center align-items-center">
    <!-- col-12 en móviles toma el ancho completo para que no se amontone con el texto -->
    <label for="itemmanoobraamterial" class="col-12 col-lg-2 col-form-label text-center text-lg-end mb-1 mb-lg-0">
        <strong>Seleccione Item:</strong>
    </label>
    <div class="col-12 col-lg-6">
        <!-- Añadimos 'w-100' para anular la rigidez nativa del componente -->
        <select name="itemmanoobraamterial" id="itemmanoobraamterial" class="form-control select-buscador w-100" data-live-search="true" data-size="10">
        </select>
        @error('itemmanoobraamterial')
        <small class="text-danger d-block text-center mt-1">{{ '*'.$message }}</small>
        @enderror
    </div>
</div>


                    <!-- Botón Cámara -->
                    <div class="row mb-3 justify-content-center">
                        <div class="col-lg-8">
                            <button id="btnAbrirCamaraNativa" type="button" class="btn btn-primary w-100">
                                📸 Activar Cámara Nativa
                            </button>
                            <input type="file" id="inputCamaraNativa" accept="image/jpeg, image/jpg" capture="environment" style="display: none;">
                            <select name="categoriafoto" id="categoriafoto" class="form-control selectpicker mt-2" style="display:none;">
                                <option value="ANTES">ANTES</option>
                                <option value="DESPUES">DESPUES</option>
                                <option value="poste antes">poste antes</option>
                                <option value="poste despues">poste despues</option>
                                <option value="anillo postes">anillo postes</option>
                                <option value="conectividad">conectividad</option>
                                <option value="SERIE">SERIE</option>
                                <option value="PANORAMICA">PANORAMICA</option>
                                <option value="MURO">MURO</option>
                                <option value="TECHO">TECHO</option>
                                <option value="ESQUINA">ESQUINA</option>
                                <option value="ENTRE_CABLES">ENTRE CABLES</option>
                                <option value="POSTE">POSTE</option>
                                <option value="ANTENA">ANTENA</option>
                                <option value="ANTENA_WTTx">ANTENA WTTx</option>
                                <option value="MASTIL_WTTx">MASTIL WTTx</option>
                                <option value="MASTIL_DTH">MASTIL DTH</option>
                                <option value="STB">STB</option>
                                <option value="OTT">OTT</option>
                                <option value="ONT">ONT</option>
                                <option value="SWITCH">SWITCH</option>
                            </select>
                            <div id="preview" class="mt-2"></div>
                        </div>
                    </div>

                    <!-- Cantidad y Botón Agregar -->
                    <div class="row justify-content-center align-items-end">
                        <div class="col-sm-4 mb-2">
                            <label for="cantidad" class="form-label">Cantidad:</label>
                            <input type="number" name="cantidad" id="cantidad" class="form-control" step="1" min="1" value="1">
                            <input type="hidden" name="Tipo_Orden" id="Tipo_Orden" value="{{ $tipo_orden }}">
                        </div>
                        <div class="col-sm-4 mb-2 text-end">
                            <button type="button" id="btn_agregar" class="btn btn-primary w-100">Agregar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Materiales Utilizados -->
            <div class="mb-4">
                <h5 class="border-bottom pb-2 mb-3">Materiales utilizados</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered w-100">
                        <thead class="table-text text-white bg-secondary">
                            <tr>
                                <th></th>
                                <th class="text-white">Cantidad</th>
                                <th class="text-white">Descripción</th>
                                <th class="text-white">SKU</th>
                                <th class="text-white">SERIE</th>
                                <th class="text-white">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="detalle_tbody">
                            <!-- Los detalles del comprobante se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="row mb-4">
                <label for="obs" class="col-lg-2 col-form-label">Observaciones:</label>
                <div class="col-lg-6">
                    <textarea name="obs" id="obs" class="form-control"></textarea>
                    @error('obs')
                    <small class="text-danger">{{ '*'.$message }}</small>
                    @enderror
                </div>
            </div>
        </div>
<input type="hidden" name="estatus" id="estatus_input" value="">
    <input type="hidden" name="id_tecnico" id="id_tecnico" value="{{ $tecnico->id }}">

        @if ($orden->Status == 'I' or $orden->Status == 'S')
        <div class="card-footer text-center">
            <button type="button" onclick="prepareForm('I','I')" class="btn btn-primary">Actualizar</button>
        </div>
        @endif

        @if ($orden->Status == 'S')
        <div class="card-footer text-center">
            <button type="button" onclick="prepareForm('S','S')" class="btn btn-success">Cerrar y Guardar</button
        </div>
        @endif

    </form>
</div>


<!-- Ventana Flotante (Inicia minimizada) -->
    <div id="floating-window" class="floating-window minimized">
        <div id="window-header" class="window-header">
            <div class="window-title-container">
                <!-- Icono de Materiales y Mano de Obra (Opción Emoji nativa o tu SVG) -->
                <span class="window-icon">🧱🛠️</span>
                <!-- Texto que se ocultará/mostrará -->
                <span id="window-title-text" class="window-title-text">MA/MO</span>
            </div>
            <div class="window-controls">
                <button id="btn-minimize" type="button" class="win-btn">+</button>
                
            </div>
        </div>

    <div id="window-content" class="window-content">
        <div id="treeview-seleccionar" class="treeview">
            <!-- Tu contenido aquí -->
            <ul>
                <li>Nodo Raíz
                    <ul>
                        <li>Hijo 1</li>
                        <li>Hijo 2</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

@endsection

@push('js')
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap-Select -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Bootstrap Treeview -->
<script src="{{ asset('js/bootstrap-treeview.js') }}"></script>
<!-- jQuery UI -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>


<script>
    let cont = 1;
    let photosForItem = []; // fotos del ítem actual
    let allItems = [];      // todos los ítems agregados
    let stream;
    let itemsEliminados = [];


// Iniciar cámara trasera y adaptar visualización
async function startCamera() {
    try {
        // Detener streams anteriores si existen para liberar memoria
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }

        // Forzamos el uso de la cámara trasera con 'environment'
        const constraints = { 
            video: { 
                facingMode: { exact: "environment" } 
            } 
        };

        try {
            stream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch (err) {
            // Plan B: Si el dispositivo no tiene cámara trasera con identificador exacto (ej. algunas PCs de prueba)
            console.warn("No se detectó cámara trasera estricta, intentando modo preferente.");
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { facingMode: "environment" } 
            });
        }

        const videoElement = document.getElementById('video');
        videoElement.srcObject = stream;

    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error de Hardware',
            text: 'No se pudo acceder a la cámara trasera: ' + err.message
        });
    }
}

 // Tomar foto
$('#btnCapture').click(function() {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL("image/png");
    const timestamp = Date.now();
    const photoName = `foto_${timestamp}.png`;
    const categoriafoto = $('#categoriafoto').val();
    const itemname = $('#itemmanoobraamterial').text();
    const idTecnologiaUnificado = $('#itemtecnologia').val();



    photosForItem.push({ name: "{{ $orden->Orden.'_'.$tecnico->codigo.'_' }}"+categoriafoto, data: dataUrl, fkTecnologia: idTecnologiaUnificado });
    mostrarFotos();

    $('#btnOk').show();
    $('#btnRetry').show();
});

// Mostrar fotos
function mostrarFotos() {
    const preview = document.getElementById('preview');
    preview.innerHTML = '';
    photosForItem.forEach((photo, index) => {
        const div = document.createElement('div');
        div.classList.add('photo-container');

        const img = document.createElement('img');
        img.src = photo.data;
        img.alt = photo.name;

        const btnRemove = document.createElement('button');
        btnRemove.innerText = '✖';
        btnRemove.classList.add('btn-remove');
        btnRemove.onclick = () => {
            photosForItem.splice(index, 1);
            mostrarFotos();
            if (photosForItem.length === 0) {
                $('#btnOk').hide();
                $('#btnRetry').hide();
            }
        };

        div.appendChild(img);
        div.appendChild(btnRemove);
        preview.appendChild(div);
    });
}

// Volver a tomar
$('#btnRetry').click(function() {
    alert("Puedes tomar otra foto 📸");
});

// Confirmar fotos
$('#btnOk').click(function() {
    $('#video').hide();
    $('#btnCapture').hide();
    $('#btnRetry').hide();
    $(this).text("✅ Fotos Guardadas").prop('disabled', true);
});

function prepareForm(estatus, MSJ) {
    document.getElementById('estatus_input').value = estatus;

    // 1. Limpieza inicial obligatoria de inputs previos
    $("input[name^='items[']").remove();

    allItems.forEach((item, idx) => {
        $('<input>').attr({
            type: 'hidden',
            name: `items[${idx}][id]`,
            value: item.id
        }).appendTo('#formulario');

        $('<input>').attr({
            type: 'hidden',
            name: `items[${idx}][cantidad]`,
            value: item.cantidad
        }).appendTo('#formulario');

        // Fotos en Base64
        item.photos.forEach((photo, pidx) => {
            $('<input>').attr({
                type: 'hidden',
                name: `items[${idx}][photos][${pidx}]`,
                value: photo.data
            }).appendTo('#formulario');
        });

        // =================================================================
        // SANITIZACIÓN SEMÁNTICA DE NOMBRES EN MAYÚSCULAS (MÓDULO AUDITORÍA)
        // =================================================================
        item.photos.forEach((photo, pidx) => {
            // 1. Forzar a cadenas de texto en MAYÚSCULAS limpias
            let nombreLimpio = photo.name ? photo.name.toUpperCase().trim() : 'EVIDENCIA_FOTO';
            
            // 2. Normalización opcional: Si la foto contiene un espacio o guion, asegura la legibilidad para SQL
            nombreLimpio = nombreLimpio.replace(/[\s\u00A0]+/g, ' ');

            $('<input>').attr({
                type: 'hidden',
                name: `items[${idx}][names][${pidx}]`,
                value: nombreLimpio // Inyecta el nombre estandarizado (Ej: "ANTENA DTH.JPG")
            }).appendTo('#formulario');
        });

        // =================================================================
        // INYECCIÓN DE LA LLAVE TECNOLÓGICA DE LA FOTOGRAFÍA
        // =================================================================
        item.photos.forEach((photo, pidx) => {
            // Lee el fkTecnologia que inyectamos previamente en el photosForItem.push
            let idTecnologiaFoto = photo.fkTecnologia ?? 0;

            $('<input>').attr({
                type: 'hidden',
                name: `items[${idx}][fkTecnologia][${pidx}]`,
                value: idTecnologiaFoto // Inyecta el ID numérico (Ej: 3 o 15)
            }).appendTo('#formulario');
        });

    });

    itemsEliminados.forEach((id, idx) => {
        $('<input>').attr({
            type: 'hidden',
            name: `arrayEliminados[${idx}]`,
            value: id
        }).appendTo('#formulario');
    });

    $('<input>').attr({
        type: 'hidden',
        name: 'estatus',
        value: estatus
    }).appendTo('#formulario');

    let mensaje = MSJ === 'S' ?
        "¿Confirmar cierre de trabajo? Se registrará el consumo de materiales y se actualizará tu inventario físico." :
        "¿Confirmar actualización de trabajo? Se actualizará el consumo de materiales pero no se cerrará la orden, podrás seguir editando después.";

    let titulo = MSJ === 'S' ?
        "¿Confirmar cierre de trabajo?" :
        "¿Confirmar actualización de trabajo?";        

    let icono = MSJ === 'S' ? 'success' : 'warning';
    let textoboton = MSJ === 'S' ? 'Sí, guardar y cerrar' : 'Sí, actualizar y seguir editando';
        
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: icono,
        showCancelButton: true,
        confirmButtonColor: '#198754', 
        cancelButtonColor: '#dc3545',  
        confirmButtonText: textoboton,
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false 
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Procesando...',
                text: 'Actualizando inventario y guardando orden.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const formulario = document.getElementById('formulario');
            const formData = new FormData(formulario);
            

            fetch(formulario.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Error desconocido en el servidor.');
                }
                return data;
            })
            .then(data => {

                if (MSJ === 'S' && window.CLAVE_CACHE_COMBOS) {
                    localStorage.removeItem(window.CLAVE_CACHE_COMBOS);
                }

                Swal.fire({
                    title: '¡Completado!',
                    text: data.message || 'La operación se realizó con éxito.',
                    icon: 'success'
                }).then(() => {
                    window.location.href = "{{ route('tecnico.buckettecnico') }}";
                });
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error en el proceso',
                    text: error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

function agregarProductoScanner(sku) {
    if (!sku) return;

    let idManoObraSeleccionada = $('#itemmanoobra').val(); 
    let idTecnicoAsignado = {{ $tecnico->id }}; 

    if (!idManoObraSeleccionada) {
        Swal.fire('Atención', 'Por favor, seleccione una Mano de Obra antes de escanear.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Procesando lectura...',
        text: 'Identificando Serie / SKU: ' + sku,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    let urlBase = "{{ route('tecnico.materiales.scan_global', ['sku' => ':sku']) }}";
    let urlDestino = urlBase.replace(':sku', encodeURIComponent(sku.toString().trim()));

    $.ajax({
        url: urlDestino,
        type: 'GET',
        data: {
            id_manoobra: idManoObraSeleccionada, 
            id_tecnico: idTecnicoAsignado
        },
        success: function(response) {
            Swal.close();

            if (!response || response.status === 'no_permitido' || Object.keys(response).length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Código no autorizado',
                    text: 'El código escaneado no corresponde a ninguna Serie o SKU válido para este trabajo.',
                });
                return;
            }

            if (response.status === 'sin_stock') {
                Swal.fire({
                    icon: 'error',
                    title: 'Sin existencias',
                    text: 'El SKU ' + response.sku + ' es válido, pero no cuentas con stock en tu bodega móvil.',
                });
                return;
            }

            // =================================================================
            // CASO A: ES SERIE -> PASA DIRECTO A AÑADIR A LA TABLA
            // =================================================================
            if (response.tipo === 'serie') {
                if (typeof agregarItem === 'function') {
                    agregarItem(response.data); 
                }
                return;
            }

            // =================================================================
            // CASO B: ES SKU -> CARGAR Y SELECCIONAR EN EL COMBOBOX
            // =================================================================
            if (response.tipo === 'sku') {
                var $select = $('#itemmanoobraamterial');
                
                // Destruir e inicializar limpio el selectpicker
                $select.selectpicker('destroy');
                $select.empty();

                let options = '<option value="">Seleccione un material</option>';
                
                response.data.forEach(function(material) {
                    let skuLimpio = material.sku ? material.sku.toString().trim() : '';
                    let serieLimpia = material.serie ? material.serie.toString().trim() : '';

                    options += `<option value="${material.id}" 
                                 data-centro="${material.CENTRO}"
                                 data-sku="${skuLimpio}"
                                 data-stock="${material.cantidad}">DESCRIP: ${material.categoria_nombre} || SERIE: ${serieLimpia || 'S/N'} || CANTIDAD: ${material.cantidad} || SKU: ${skuLimpio}</option>`;
                });

                $select.html(options);

                // Auto-seleccionar la primera coincidencia real de SKU encontrada
                if (response.data.length > 0) {
                    $select.val(response.data[0].id);
                }

                // Re-inicializar el componente visual de Bootstrap
                $select.selectpicker({ liveSearch: true, size: 10 });
                $select.selectpicker('refresh');

                Swal.fire({
                    icon: 'success',
                    title: 'SKU Identificado',
                    text: 'Se han cargado las opciones de este material en el selector de ítems.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Fallo al procesar código: ' + xhr.responseText, 'error');
        }
    });
}


function agregarProductoScannerCam(sku) {
    if (typeof agregarProductoScanner === 'function') {
        agregarProductoScanner(sku);
    }
}

// 1. Declarar este array global al inicio de tu archivo JavaScript (fuera de las funciones)
function eliminarProducto(indice) {
    // Lanzamos la alerta de confirmación antes de alterar cualquier dato o el DOM
    Swal.fire({
        title: '¿Retirar este material?',
        text: "El elemento se quitará de la lista actual de materiales utilizados.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545', // Rojo Bootstrap danger (acción de borrar)
        cancelButtonColor: '#6c757d',  // Gris Bootstrap secondary
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false
    }).then((result) => {
        // Si el usuario confirma, procedemos con toda tu lógica original
        if (result.isConfirmed) {
            
            // 2. Buscar el ID real de la base de datos antes de limpiar el arreglo en memoria
            let itemABorrar = allItems.find(function(item) {
                return item.index == indice;
            });

            // 3. Si el ítem tiene un ID válido (ya existía en la base de datos), lo registramos para el backend
            if (itemABorrar && itemABorrar.id) {
                itemsEliminados.push(itemABorrar.id);
                
                // Agregamos el input oculto al formulario (usando el ID real de tu formulario '#formulario')
                $('#formulario').append(
                    '<input type="hidden" name="arrayEliminados[]" value="' + itemABorrar.id + '">'
                );
            }

            // 4. Tu lógica original: Eliminar el elemento del arreglo global en memoria
            allItems = allItems.filter(function(item) {
                return item.index != indice;
            });

            // 5. Tu lógica original: Eliminar la fila visual y sus fotos
            $('#fila' + indice).remove();
            $(`input[name^='arrayfotos[${indice}]']`).remove();

            // Opcional: Pequeña notificación de éxito que se cierra sola en 1.5 segundos
            Swal.fire({
                title: 'Eliminado',
                text: 'El material ha sido removido de la lista.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}


function llenaritems() {
    let id = "{{ $id3 ?? 0 }}";

    $.ajax({
        url: "{{ route('inventariolistadetalles') }}",
        method: 'GET',
        data: { parametros: id },
        success: function(response) {
            // 1. Limpieza inicial obligatoria
            $('#detalle_tbody').empty();
            allItems = [];
            $("input[name^='items[']").remove();

            // Conjunto temporal para rastrear IDs ya renderizados en este ciclo
            let idsProcesados = {};

            response.forEach(function(material, index) {
                // CORRECCIÓN ANTIDUPLICADOS: Si el ID ya fue pintado en este llamado, lo ignora
                if (idsProcesados[material.id]) {
                    return; // Salta al siguiente elemento sin duplicar en la vista
                }
                
                // Si ya existe un elemento físico con este ID en la tabla por otra petición asíncrona, lo ignora
                if ($('#detalle_tbody').find(`input[value='${material.id}']`).length > 0) {
                    return;
                }

                // Registramos el ID para proteger el ciclo actual
                idsProcesados[material.id] = true;

                // Sincronización de propiedades internas
                material.index = index;
                if (!material.photos) {
                    material.photos = [];
                }

                // Construcción segura de la fila
                let fila = '<tr id="fila' + index + '" data-index="' + index + '">' +
                    '<td><input type="hidden" name="arrayiditem[]" value="' + material.id + '">' + material.id + '</td>' +
                    '<td><input type="hidden" name="arraycantidad[]" value="' + material.cantidad + '">' + material.cantidad + '</td>' +
                    '<td><input type="hidden" name="arraynameProducto[]" value="' + material.Descripcion + '">' + material.Descripcion + '</td>' +
                    '<td><input type="hidden" name="arraysku[]" value="' + material.sku + '">' + material.sku + '</td>' +
                    '<td><input type="hidden" name="arrayserie[]" value="' + material.serie + '">' + material.serie + '</td>' +
                    '<td><input type="hidden" name="arrayidTecnologia[]" value="' + material.fkTecnologiaarbol + '">' + material.fkTecnologiaarbol + '</td>' +
                    '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + index + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                    '</tr>';
                
                $('#detalle_tbody').append(fila);
                allItems.push(material);
            });
        },
        error: function(xhr) {
            Swal.fire('Error', 'No se pudieron cargar los materiales: ' + xhr.responseText, 'error');
        }
    });
}



llenaritems();


function agregarItem(datosScanner = null) {
    let idItem, nameProducto, nameserie, CENTRO, sku;
    let cantidad = $('#cantidad').val() || 1;
    let idTecnologia = $('#itemtecnologia').val();
    let ordenActual = "{{ $orden->Orden }}"; 
    let tipoOrden = "{{ $orden->Tipo_servicio }}";  

  // =================================================================
    // MÓDULO DE DISCRIMINACIÓN: ¿Viene del Escáner o del Clic Manual?
    // =================================================================
    if (datosScanner) {
        // Asimilación de datos directo desde tu consulta SQL (Scan Material Global)
        idItem = datosScanner.id;
        nameProducto = datosScanner.categoria_nombre;
        nameserie = datosScanner.serie || 'S/N';
        CENTRO = datosScanner.CENTRO || 'GENERAL';
        sku = (datosScanner.sku || '').toString().trim();
    } else {
        // Flujo Manual Tradicional desde el Combobox
        idItem = $('#itemmanoobraamterial').val();
        let optionText = $('#itemmanoobraamterial option:selected').text();
        let optionSelected = $('#itemmanoobraamterial option:selected');

        if (idItem == '' || optionText == '') return;

        // =================================================================
        // [SOLUCIÓN] SEPARACIÓN SEGURA EN DOS PASOS PARA EVITAR EL TYPEERROR
        // =================================================================
        // 1. Separamos por las doble barras '||' para obtener cada bloque limpio
        let bloques = optionText.split('||');
        
        // 2. Extraemos el texto después de ': ' en cada bloque de forma segura
        let parteProducto = bloques[0] ? bloques[0].split(': ') : [];
        let parteSerie    = bloques[1] ? bloques[1].split(': ') : [];

        nameProducto = parteProducto[1] ? parteProducto[1].trim() : 'N/A';
        nameserie    = parteSerie[1]    ? parteSerie[1].trim()    : 'S/N';
        
        CENTRO       = optionSelected.data('centro') || 'GENERAL';
        sku          = (optionSelected.data('sku') || '').toString().trim();
    }

    // =================================================================
    // PROCESAMIENTO UNIFICADO DE VALIDACIÓN E INSERCIÓN (Sigue igual...)
    // =================================================================
    if (idItem != '' && nameProducto != undefined && cantidad != '') {
        if (parseInt(cantidad) > 0 && (cantidad % 1 == 0)) {

            Swal.fire({
                title: 'Validando material...',
                text: 'Consultando existencias en el servidor web',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            let allItemsSinFotos = allItems.map(item => {
                return {
                    index: item.index,
                    idTecnologia: idTecnologia,
                    idItem: item.idItem,
                    nameProducto: item.nameProducto,
                    cantidad: item.cantidad,
                    nameserie: item.nameserie,
                    sku: item.sku,
                    CENTRO: item.CENTRO
                };
            });

            let nuevoItemVirtual = {
                index: cont, 
                idTecnologia: idTecnologia,
                idItem: idItem,
                nameProducto: nameProducto,
                cantidad: parseFloat(cantidad),
                nameserie: nameserie,
                sku: sku.trim(),
                CENTRO: CENTRO,
                photos: [] 
            };
            
            let listaSimulada = [...allItemsSinFotos, nuevoItemVirtual]; 

            $.ajax({
                url: "{{ route('tecnico.validar.materiales') }}",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    Orden: ordenActual,
                    Tipo_Orden: tipoOrden,
                    SKU_Nuevo: sku.trim(),
                    Cantidad_Nueva: cantidad,
                    Items_Memoria: listaSimulada,
                    ItemVirtual: nuevoItemVirtual
                },
                success: function(response) {
                    Swal.close(); 

                    if (response.status === 'exceso' || response.status === 'falta') {
                        let textoAlertas = response.mensajes.join("\n\n");
                        let tituloAlerta = response.status === 'exceso' ? "⚠️ ALERTAS DE EXCESO DETECTADAS:\n\n" : "💡 SUGERENCIAS DE MATERIAL DETECTADAS:\n\n";

                        if (!confirm(tituloAlerta + textoAlertas + "\n\n¿Deseas agregar el ítem de todas formas?")) {
                            return; 
                        }
                    }

                    nuevoItemVirtual.photos = [...photosForItem];
                    allItems.push(nuevoItemVirtual);

                    // Insertar fila física en la tabla
                    let fila = '<tr id="fila' + cont + '" data-index="' + cont + '">' +
                        '<td><input type="hidden" name="arrayiditem[]" value="' + idItem + '">' + idItem + '</td>' +
                        '<td><input type="hidden" name="arraycantidad[]" value="' + cantidad + '">' + cantidad + '</td>' +
                        '<td><input type="hidden" name="arraynameProducto[]" value="' + nameProducto + '">' + nameProducto + '</td>' +
                        '<td><input type="hidden" name="arraysku[]" value="' + sku + '">' + sku + '</td>' +
                        '<td><input type="hidden" name="arrayserie[]" value="' + nameserie + '">' + nameserie + '</td>' +
                        '<td><input type="hidden" name="arrayidTecnologia[]" value="' + idTecnologia + '">' + idTecnologia + '</td>' +
                        '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                        '</tr>';

                    $('#detalle_tbody').append(fila);

                    // Preparar pantalla para el siguiente escaneo ultra rápido
                    photosForItem = [];
                    $('#preview').html('');
                    cont++;

                    $('#cantidad').val(1);
                    if (document.getElementById("SKU")) {
                        document.getElementById("SKU").value = "";
                        document.getElementById("SKU").focus(); // Devolver foco al lector
                    }
                    
                    $('#itemmanoobraamterial').val('');
                    if (typeof $('#itemmanoobraamterial').selectpicker === 'function') {
                        $('#itemmanoobraamterial').selectpicker('refresh');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', 'No se pudo validar el material: ' + xhr.responseText, 'error');
                }
            });

        } else {
            showModal('Valores incorrectos');
        }
    }
}


function validarRelacionMateriales() {
    // Extraemos solo SKU y Cantidad de tu array allItems (el que mostraste en la imagen)
    let materialesParaValidar = allItems.map(item => {
        return {
            sku: item.sku,
            cantidad: item.cantidad
        };
    });

    $.ajax({
        url: "{{ route('tecnico.validar.materiales') }}", // Nueva ruta para lógica pura de materiales
        method: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            materiales: materialesParaValidar,
            fkTienda: "{{ session('user_fkTienda') }}"
        },
        success: function(response) {
            let lista = $('#lista-errores-automata');
            let contenedor = $('#contenedor-alertas-automata');
            lista.empty();
            let errorEncontrado = false;

            // La respuesta recorre las relaciones (requiere, cálculo, incompatible)
            if (response.validaciones) {
                response.validaciones.forEach(function(v) {
                    if (v.Resultado > 0 || v.TipoRelacion.includes('Exceso')) {
                        errorEncontrado = true;
                        lista.append(`
                            <li class="text-danger">
                                <strong>Error en ${v.SKU_Destino}:</strong> ${v.msj} 
                                <br><small>Cálculo: ${v.Resultado} (Basado en formula: ${v.formula})</small>
                            </li>
                        `);
                    }
                });
            }

            if (errorEncontrado) {
                contenedor.fadeIn();
                $('#btn-finalizar').prop('disabled', true); // Bloquea si hay error técnico
            } else {
                contenedor.fadeOut();
                $('#btn-finalizar').prop('disabled', false);
            }
        }
    });
}



// Función aislada para realizar la inserción visual y limpieza de interfaz
function procederAAgregarFila(idItem, nameProducto, cantidad, nameserie, sku) {
    let item = {
        idItem,
        nameProducto,
        cantidad,
        nameserie,
        photos: [...photosForItem]
    };
    
    let optionSelectedtec = $('#itemtecnologia').val();
    allItems.push(item); // Sincroniza el listado en memoria

    let fila = '<tr id="fila' + cont + '">' +
        '<td><input type="hidden" name="arrayiditem[]" value="' + idItem + '">' + idItem + '</td>' +
        '<td><input type="hidden" name="arraycantidad[]" value="' + cantidad + '">' + cantidad + '</td>' +
        '<td><input type="hidden" name="arraynameProducto[]" value="' + nameProducto + '">' + nameProducto + '</td>' +
        '<td><input type="hidden" name="arraysku[]" value="' + sku + '">' + sku + '</td>' +
        '<td><input type="hidden" name="arrayserie[]" value="' + nameserie + '">' + nameserie + '</td>' +
        '<td><input type="hidden" name="arrayidTecnologia[]" value="' + optionSelectedtec + '">' + optionSelectedtec + '</td>' +
        '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
        '</tr>';

    $('#detalle_tbody').append(fila);

    // Limpiar fotos y cámara para siguiente ítem
    photosForItem = [];
    $('#preview').html('');
    $('#video').show();
    $('#btnCapture').show();
    $('#btnOk').hide().text('✅ OK').prop('disabled', false);
    $('#btnRetry').hide();

    cont++;
}


$(document).ready(function () {


//boton para agregar materiales a utilizarse
            $('#btn_agregar').click(function() {
                agregarItem();
            });

window.CLAVE_CACHE_COMBOS = "combos_orden_{{ $orden->Orden }}";

// Guardar de forma aislada la Tecnología
window.guardarSeleccionActualTecnologia = function() {
    try {
        var selectTecnologia = document.getElementById('itemtecnologia');
        if (!selectTecnologia || !selectTecnologia.value) return;

        // Leer lo que ya existía previamente en el navegador
        var datosExistentes = JSON.parse(localStorage.getItem(window.CLAVE_CACHE_COMBOS)) || {};
        
        // Modificar ÚNICAMENTE tecnología, respetando la mano de obra vieja si existía
        datosExistentes.tecnologia = selectTecnologia.value;
        datosExistentes.actualizadoEn = Date.now();

        localStorage.setItem(window.CLAVE_CACHE_COMBOS, JSON.stringify(datosExistentes));
    } catch (error) {
        console.error("Error guardando tecnología:", error);
    }
};

// Guardar de forma aislada la Mano de Obra
window.guardarSeleccionActualManoObra = function() {
    try {
        var selectManoObra = document.getElementById('itemmanoobra');
        
        // CRÍTICO: Si el combo está vacío porque apenas se está cargando el AJAX, 
        // NO guardamos nada para no borrar la memoria previa del técnico
        if (!selectManoObra || !selectManoObra.value) return;

        var datosExistentes = JSON.parse(localStorage.getItem(window.CLAVE_CACHE_COMBOS)) || {};
        
        // Modificar ÚNICAMENTE mano de obra, respetando la tecnología actual
        datosExistentes.manoObra = selectManoObra.value;
        datosExistentes.actualizadoEn = Date.now();

        localStorage.setItem(window.CLAVE_CACHE_COMBOS, JSON.stringify(datosExistentes));
    } catch (error) {
        console.error("Error guardando mano de obra:", error);
    }
};



    // Función que llena el árbol y configura el evento de selección de nodo
    function fill_treeview(id) {
        $.ajax({
            url: "{{ route('fetchabrestructura') }}",
            dataType: "json",
            data: { id: id },
            success: function (data) {
                // Limpia árbol previo
                $('#treeview-seleccionar').treeview('remove');

                $('#treeview-seleccionar').treeview({
                    data: data,
                    selectable: true,
                    highlightSelected: true,
                    showBorder: false,
                    levels: 3,
                    expandIcon: 'fa fa-plus',
                    collapseIcon: 'fa fa-minus',

                    onNodeSelected: function (event, node) {
                        console.log('Nodo seleccionado:', node);
                        if (node.Cid !== undefined) {
                            $('#nodoSeleccionado').val(node.Cid);
                            // Llamas aquí la función que lista materiales
                            listar_materiales_por_categoria(node.idpivote);
                        }
                    }
                });
            },
            error: function (xhr) {
                console.error('Error al cargar árbol:', xhr.responseText);
            }
        });
    }

function fill_manoobra(id) {
    $.ajax({
        url: "{{ url('manoobracategoria') }}/" + id,
        method: "GET",
        success: function (data) {
            var $select = $('#itemmanoobra');
            $select.empty();
            
            let optionss = `<option value="" disabled selected>Seleccione una opción</option>`;
            data.forEach(function (manoobra) {
                optionss += `<option value="${manoobra.id}">${manoobra.nombre}</option>`;
            });
            
            // 1. Inyectar HTML al DOM nativo de forma inmediata
            $select.html(optionss);

            // 2. Comprobar y meter la caché instantáneamente antes de pintar la UI
            try {
                var cache = JSON.parse(localStorage.getItem(window.CLAVE_CACHE_COMBOS));
                if (cache && cache.manoObra) {
                    var existeOp = $select.find('option[value="' + cache.manoObra + '"]').length > 0;
                    if (existeOp) {
                        $select.val(cache.manoObra);
                    }
                }
            } catch (e) { 
                console.warn(e); 
            }

            // 3. Inicializar o refrescar la interfaz visual en un único paso atómico
            if ($select.hasClass('selectpicker') || $select.data('selectpicker')) {
                $select.selectpicker('refresh');
            } else {
                $select.selectpicker();
            }

            // 4. Lanzar la cascada hacia el árbol
            if ($select.val()) {
                $select.trigger('change');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', 'Hubo un problema al cargar las opciones: ' + xhr.responseText, 'error');
        }
    });
}

    // Inicializas los selectpicker
    $('#itemtecnologia, #itemmanoobra').selectpicker();
fill_estructura();
    // Evento para llenar mano de obra según tecnología
// Vinculación segura de eventos change
$("#itemtecnologia").off('change').on('change', function () {
    const valor = $(this).val();
    if (valor) {
        fill_manoobra(valor);
        window.guardarSeleccionActualTecnologia(); 
    }
});

$("#itemmanoobra").off('change').on('change', function () {
    const valor = $(this).val();
    if (valor) {
        fill_treeview(valor);
        window.guardarSeleccionActualManoObra(); 
    }
});

// ENTRADA ULTRA RÁPIDA: Sincronización instantánea al cargar la página
(function() {
    try {
        var cache = JSON.parse(localStorage.getItem(window.CLAVE_CACHE_COMBOS));
        var $selectTec = $('#itemtecnologia');
        
        if (cache && cache.tecnologia && $selectTec.length) {
            // Inyectar el valor directamente en el DOM nativo antes de que bootstrap actúe
            $selectTec.val(cache.tecnologia);

            // Si la librería ya se creó, refrescamos. Si no, escuchamos su creación para meter el valor
            if ($selectTec.data('selectpicker')) {
                $selectTec.selectpicker('refresh');
                $selectTec.trigger('change');
            } else {
                $selectTec.one('rendered.bs.select', function() {
                    $selectTec.selectpicker('val', cache.tecnologia);
                    $selectTec.trigger('change');
                });
            }
        }
    } catch (error) {
        console.warn("Error en arranque ultra rápido:", error);
    }
})();

 function listar_materiales_por_categoria(idNodo) {
    console.log('Listar materiales para categoría con Cid:', idNodo);
    
    let id2 = {{ $tecnico->id }};
    // 🌟 CAPTURAMOS EL ID DE LA TECNOLOGÍA SELECCIONADA EN LA PANTALLA
    let idTecnologia = $('#itemtecnologia').val(); 
    
    $.ajax({
        url: "{{ route('inventariolista')}}",
        // 🌟 ENVIAMOS EL ID DE LA TECNOLOGÍA AL BACKEND EN LA VARIABLE 'id_tecnologia'
        data: { 
            id1: idNodo, 
            id2: id2,
            id_tecnologia: idTecnologia 
        },
        method: 'GET',
        success: function(materiales) {
            console.log('Materiales cargados:', materiales);
            
            let materialesArray = Object.values(materiales);
            
            // 1. Destruimos cualquier residuo del plugin
            $('#itemmanoobraamterial').selectpicker('destroy');
            
            // 2. Limpieza radical del contenedor nativo y reseteo del valor seleccionado
            $('#itemmanoobraamterial').empty().val('');

            // 3. Insertamos el marcador inicial por defecto
            let optionss = '<option value="" selected>Seleccione un material</option>';
            
            // 4. Set de control con llave compuesta (SKU + Serie) para evitar duplicados reales
            let registroFiltroUnico = new Set();

            materialesArray.forEach(function(material) {
                let skuLimpio = material.sku ? material.sku.toString().trim() : '';
                let serieLimpia = material.serie ? material.serie.toString().trim() : '';

                // CREACIÓN DE LA HUELLA ÚNICA COMPUESTA
                let huellaUnica = `${skuLimpio}-${serieLimpia}`;

                if (registroFiltroUnico.has(huellaUnica)) {
                    return; 
                }
                registroFiltroUnico.add(huellaUnica);

                optionss += `<option value="${material.id}" 
                             data-centro="${material.CENTRO}"
                             data-sku="${skuLimpio}"
                             data-stock="${material.cantidad}"
                             data-img="${material.img_path || ''}"
                             data-precio="${material.precio_venta || 0}"
                             data-detalle="${material.descripcion || ''}">DESCRIP: ${material.categoria_nombre} || SERIE: ${serieLimpia || 'S/N'} || CANTIDAD: ${material.cantidad} || SKU: ${skuLimpio}</option>`;
            });

            // 5. Inyectamos la estructura HTML limpia de opciones únicas
            $('#itemmanoobraamterial').html(optionss);

            // 6. Volvemos a inicializar manualmente la interfaz gráfica de búsqueda desde cero
            $('#itemmanoobraamterial').selectpicker({
                liveSearch: true,
                size: 10
            });
            
            // 7. Renderizado final 
            $('#itemmanoobraamterial').selectpicker('render');
        },
        error: function(xhr) {
            Swal.fire('Error', 'No se pudieron cargar los materiales: ' + xhr.responseText, 'error');
        }
    });
}

    function fill_estructura() {
    $.ajax({
        url: "{{ route('tecnologiaarb') }}",
        method: "GET",
        success: function(data) {
            // Destruye selectpicker antes de cambiar contenido para evitar duplicados
            $('#itemtecnologia').selectpicker('destroy');

            let options = `<option value="" disabled selected>Seleccione una opción</option>`;
            data.forEach(function(cuenta) {
                options += `<option value="${cuenta.id}">${cuenta.nombre}</option>`;
            });

            $('#itemtecnologia').html(options);

            // Reinicia selectpicker para que reconozca las nuevas opciones
            $('#itemtecnologia').selectpicker();
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al cargar las opciones: ' + xhr.responseText, 'error');
        }
    });
}
});


// Removemos el arranque automático de la cámara al iniciar la página
// El control se activa únicamente al presionar el botón

// Evento 1: El botón bonito activa el input de captura nativa oculto
$('#btnAbrirCamaraNativa').click(function() {
    $('#inputCamaraNativa').click();
});

// Evento 2: Escucha cuando el técnico toma la foto a pantalla completa y la acepta
document.getElementById('inputCamaraNativa').addEventListener('change', function(e) {
    // CORRECCIÓN CRÍTICA: Añadir [0] para extraer el archivo multimedia real de la lista
    const file = e.target.files[0]; 
    if (!file) return;

    Swal.fire({
        title: 'Optimizando fotografía...',
        text: 'Procesando en alta resolución para el ERP',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    // Ahora que 'file' es un archivo único legítimo, funcionará al instante sin errores
    const urlTemporalBlob = URL.createObjectURL(file);
    const img = new Image();
    img.src = urlTemporalBlob;
    
    img.onload = function() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Mantenemos la alta resolución de 1600px y calidad al 80% para máxima nitidez
        const MAX_WIDTH = 1600;
        let width = img.width;
        let height = img.height;
        
        if (width > MAX_WIDTH) {
            height *= MAX_WIDTH / width;
            width = MAX_WIDTH;
        }
        
        canvas.width = width;
        canvas.height = height;
        
        ctx.drawImage(img, 0, 0, width, height);
        
        // Conversión limpia a JPEG de alta fidelidad (unos 450 KB finales)
        const dataUrlComprimida = canvas.toDataURL('image/jpeg', 0.80);
        
        const categoriafoto = $('#categoriafoto').val();
        const nombreFotoGenerado = "{{ $orden->Orden.'_'.$tecnico->codigo.'_' }}" + categoriafoto;
        const indiceActual = $('#modal-o-contenedor-actual').data('index') || 0; 
        const idTecnologiaUnificado = $('#itemtecnologia').val();

        photosForItem.push({ 
            index: indiceActual,
            name: nombreFotoGenerado, 
            data: dataUrlComprimida,
            fkTecnologia: idTecnologiaUnificado
        });

        mostrarFotos(indiceActual);
        
        URL.revokeObjectURL(urlTemporalBlob);
        Swal.close();
        document.getElementById('inputCamaraNativa').value = ""; 
    };
});

</script>
<script type="module">



const win = document.getElementById('floating-window');
const header = document.getElementById('window-header');
const btnMinimize = document.getElementById('btn-minimize');


btnMinimize.addEventListener('click', (e) => {
    e.stopPropagation();
    
    win.classList.remove('maximized');
    win.classList.toggle('minimized');
    
    // CORRECCIÓN: Cambiar SOLO el texto interno sin alterar la estructura del botón
    if (win.classList.contains('minimized')) {
        resetToFixedPosition();
        btnMinimize.innerHTML = '+';
    } else {
        btnMinimize.innerHTML = '−';
    }
    
});

// --- 2. Lógica de Arrastre Fluido (Drag and Drop) ---
let isDragging = false;
let offsetX, offsetY;

header.addEventListener('mousedown', (e) => {
    // No permitir arrastre si está maximizado
    if (win.classList.contains('maximized')) return;

    isDragging = true;
    
    // Calcular la distancia entre el cursor y el borde de la ventana
    offsetX = e.clientX - win.getBoundingClientRect().left;
    offsetY = e.clientY - win.getBoundingClientRect().top;
    
    header.style.cursor = 'grabbing';
});

document.addEventListener('mousemove', (e) => {
    if (!isDragging) return;

    // Calculamos la nueva posición en la pantalla
    let newX = e.clientX - offsetX;
    let newY = e.clientY - offsetY;

    // Quitamos temporalmente 'right' para que responda a 'left' al arrastrar
    win.style.right = 'auto';
    win.style.left = `${newX}px`;
    win.style.top = `${newY}px`;
});

document.addEventListener('mouseup', () => {
    isDragging = false;
    header.style.cursor = 'move';
});

// --- 3. Detección de Doble Toque en Móviles (y Doble Clic en PC) ---

// Seleccionamos el contenedor del árbol
const treeview = document.getElementById('treeview-seleccionar');

// Variables para controlar el tiempo entre toques en móvil
let lastTouchTime = 0;

// A. LÓGICA PARA MÓVILES (Eventos Touch)
treeview.addEventListener('touchend', (e) => {
    // Buscamos el elemento específico del árbol que fue tocado (usualmente un <li> o un <span>)
    const targetNode = e.target.closest('li'); 
    
    if (!targetNode) return; // Si no tocó un nodo, ignorar

    const currentTime = new Date().getTime();
    const tapLength = currentTime - lastTouchTime;
    
    // Si el tiempo entre el toque anterior y el actual es menor a 300ms, es un doble toque
    if (tapLength < 300 && tapLength > 0) {
        e.preventDefault(); // Previene comportamientos raros del navegador móvil
        
        ejecutarSeleccionNodo(targetNode);
    }
    
    lastTouchTime = currentTime;
});

// B. LÓGICA PARA COMPUTADORA (Evento Click nativo por seguridad)
treeview.addEventListener('dblclick', (e) => {
    const targetNode = e.target.closest('li');
    if (targetNode) {
        ejecutarSeleccionNodo(targetNode);
    }
});

// C. FUNCIÓN DE ACCIÓN (Procesa la selección y minimiza)
function ejecutarSeleccionNodo(nodo) {
    // 1. Obtener el texto o ID del nodo seleccionado
    // Nota: Ajusta 'textContent' o usa 'nodo.dataset.id' según cómo esté estructurado tu árbol
    const nodoTexto = nodo.firstChild.textContent.trim(); 
    
    // Aquí puedes hacer lo que necesites con el nodo (ej. guardarlo en un input oculto)
    console.log("Nodo seleccionado con éxito:", nodoTexto);
    
    // 2. Minimizar la ventana flotante automáticamente
    win.classList.remove('maximized');
    win.classList.add('minimized');
    
    // 3. Actualizar el botón de minimizar al icono de expandir (+)
    btnMinimize.textContent = '+';
    
    // Opcional: Feedback visual rápido para que el usuario note que se seleccionó
    nodo.style.backgroundColor = '#d4edda'; // Fondo verde claro temporal
    setTimeout(() => { nodo.style.backgroundColor = ''; }, 500);
}
// --- 2. Lógica de Arrastre Fluido (Compatible con PC y Móvil) ---



// --- FUNCIONES INTERNAS DE MOVIMIENTO ---
function startDrag(clientX, clientY) {
    if (win.classList.contains('maximized')) return; // No arrastrar si está maximizado
    isDragging = true;
    
    // Calcular la distancia entre el dedo/cursor y el borde de la ventana
    offsetX = clientX - win.getBoundingClientRect().left;
    offsetY = clientY - win.getBoundingClientRect().top;
    
    header.style.cursor = 'grabbing';
}

// --- MODIFICA ÚNICAMENTE ESTA FUNCIÓN EN TU SCRIPT ACTUAL ---
function moveDrag(clientX, clientY, event) {
    if (!isDragging) return;
    
    // Evita que la pantalla completa del celular se desplace mientras arrastras la ventana
    if (event.cancelable) event.preventDefault();

    // 1. Calculamos las coordenadas brutas en píxeles del mouse/dedo
    let newX = clientX - offsetX;
    let newY = clientY - offsetY;

    // 2. CONTROL DE FRONTERAS EN VIVO: Evita que el técnico saque la ventana fuera de los límites de la pantalla
    if (newX < 0) newX = 0;
    if (newY < 0) newY = 0;
    if (newX > window.innerWidth - win.offsetWidth) newX = window.innerWidth - win.offsetWidth;
    if (newY > window.innerHeight - win.offsetHeight) newY = window.innerHeight - win.offsetHeight;

    // 3. CONVERSIÓN CRÍTICA A COORDENADAS DE PANTALLA VISIBLE (Viewport Units)
    // Transformamos los píxeles a porcentajes exactos de la pantalla del celular (vw y vh)
    // Esto independiza por completo a la ventana flotante del tamaño total de la página larga
    let porcentajeLeft = (newX / window.innerWidth) * 100;
    let porcentajeTop = (newY / window.innerHeight) * 100;

    // 4. Aplicamos las reglas usando setProperty con !important para ganarle a cualquier contenedor de tu ERP
    win.style.setProperty('right', 'auto', 'important');
    win.style.setProperty('bottom', 'auto', 'important');
    
    // Al usar 'vw' y 'vh' fijos, la ventana se queda congelada en la vista de la pantalla
    win.style.setProperty('left', `${porcentajeLeft}vw`, 'important');
    win.style.setProperty('top', `${porcentajeTop}vh`, 'important');
}

function endDrag() {
    isDragging = false;
    header.style.cursor = 'move';
}

// --- EVENTOS PARA MOUSE (COMPUTADORA) ---
header.addEventListener('mousedown', (e) => {
    startDrag(e.clientX, e.clientY);
});

document.addEventListener('mousemove', (e) => {
    moveDrag(e.clientX, e.clientY, e);
});

document.addEventListener('mouseup', endDrag);


// --- EVENTOS TÁCTILES (MÓVIL) ---
header.addEventListener('touchstart', (e) => {
    // Usamos e.touches[0] para leer el primer dedo que toca la pantalla
    startDrag(e.touches[0].clientX, e.touches[0].clientY);
}, { passive: false }); // passive: false permite usar preventDefault()

document.addEventListener('touchmove', (e) => {
    moveDrag(e.touches[0].clientX, e.touches[0].clientY, e);
}, { passive: false });

document.addEventListener('touchend', endDrag);

// --- Corrección de Carga Fría para Estructuras ERP/Dashboards ---
function renderizarVentanaFlotante() {
    const winFlotante = document.getElementById('floating-window');
    if (!winFlotante) return;

    winFlotante.style.display = 'none';
    const m = document.documentElement.clientHeight; 

    requestAnimationFrame(() => {
        winFlotante.style.display = 'flex';
        
        // Si el usuario no la ha movido, aseguramos el anclaje inicial abajo a la izquierda
        if (winFlotante.style.left === '' || winFlotante.style.left === 'auto') {
            winFlotante.style.setProperty('bottom', '5%', 'important');
            winFlotante.style.setProperty('left', '5%', 'important');
            winFlotante.style.setProperty('top', 'auto', 'important');
            winFlotante.style.setProperty('right', 'auto', 'important');
        }
    });
}

// Disparadores automáticos al cargar la página
document.addEventListener('DOMContentLoaded', renderizarVentanaFlotante);
window.addEventListener('load', renderizarVentanaFlotante);

// Disparador de seguridad para cuando el menú superior termine de renderizarse
setTimeout(renderizarVentanaFlotante, 150); 

// --- 4. Control Dinámico del Título con el Scroll de la Página ---

let ultimoScrollTop = 0;

window.addEventListener('scroll', () => {
    // Detectamos la posición de scroll actual de forma compatible
    const despliegueScroll = window.pageYOffset || document.documentElement.scrollTop;
    
    // Evitamos lecturas negativas en dispositivos móviles (efecto rebote de iOS/Android)
    if (despliegueScroll < 0) return; 

    if (despliegueScroll > ultimoScrollTop) {
        // A. SI EL USUARIO BAJA: Añadimos la clase para encoger el título y liberar espacio visual
        if (despliegueScroll > 30) {
            win.classList.add('scrolled');
        }
    } else {
        // B. SI EL USUARIO SUBE (En cualquier parte de la página larga):
        // Removemos la clase DE INMEDIATO para que el botón retome su posición y tamaño real
        win.classList.remove('scrolled');
    }
    
    // Actualizamos la variable de memoria para la siguiente lectura del dedo
    ultimoScrollTop = despliegueScroll <= 0 ? 0 : despliegueScroll;
}, { passive: true }); // passive: true optimiza el rendimiento del scroll en celulares


    // Variables para detectar si es lector de barras
let scanner = null;
let escaneando = false;
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



window.iniciarScanner = function(tipo = "barra") {
    if (escaneando) return;

    let elementoLector = tipo === "barra" ? "readerbarra" : "reader";
    scanner = new Html5Qrcode(elementoLector);
    escaneando = true;

    scanner.start(
        { facingMode: "environment" },
        {
            fps: 10,
            qrbox: tipo === "barra" ? { width: 250, height: 150 } : 250
        },
        (codigo) => {
            console.log("Código ver:", codigo);
            window.StopScanner();
            
            if (typeof agregarProductoScanner === 'function') {
                agregarProductoScanner(codigo);
            }

            Swal.fire({
                icon: 'warning',
                title: 'Se ha seleccionado un producto',
                text: 'Código: ' + codigo,
            });
        },
        (error) => { /* Ignorar errores de enfoque */ }
    );
};

window.StopScanner = function() {
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
};

// Delegación de eventos e interactividad segura
document.addEventListener("DOMContentLoaded", function() {
    var btnQr = document.getElementById('btn-qr');
    var btnBarra = document.getElementById('btn-barra');
    var btnStop = document.getElementById('btn-stop');

    if (btnQr) {
        btnQr.addEventListener('click', function(e) {
            e.preventDefault();
            window.iniciarScanner('qr');
        });
    }

    if (btnBarra) {
        btnBarra.addEventListener('click', function(e) {
            e.preventDefault();
            window.iniciarScanner('barra');
        });
    }

    if (btnStop) {
        btnStop.addEventListener('click', function(e) {
            e.preventDefault();
            window.StopScanner();
        });
    }
});


// Polling asíncrono para contrarrestar retrasos de renderizado por Debugbar
(function() {
    var intentos = 0;
    var maxIntentos = 100; // Límite de 5 segundos de espera activa

    var verificadorInicial = setInterval(function() {
        intentos++;
        var $selectTecnologia = $('#itemtecnologia');
        
        // Verificar si el select existe y ya posee opciones renderizadas más allá de la vacía
        if ($selectTecnologia.length && $selectTecnologia.find('option').length > 1) {
            clearInterval(verificadorInicial); // Detener el bucle inmediatamente
            
            try {
                var cacheGuardada = localStorage.getItem(window.CLAVE_CACHE_COMBOS);
                if (!cacheGuardada) return;

                var datos = JSON.parse(cacheGuardada);
                if (datos.tecnologia) {
                    // Forzar asignación mediante método nativo selectpicker
                    $selectTecnologia.selectpicker('val', datos.tecnologia);
                    $selectTecnologia.trigger('change');
                }
            } catch (error) {
                console.warn("Fallo el arranque asíncrono:", error);
            }
        }

        // Evitar bucle infinito si el elemento no se encuentra en la vista
        if (intentos >= maxIntentos) {
            clearInterval(verificadorInicial);
        }
    }, 50); // Comprobación ultra rápida cada 50 milisegundos
})();

window.addEventListener('load', function() {
    setTimeout(function() {
        try {
            var cache = JSON.parse(localStorage.getItem(window.CLAVE_CACHE_COMBOS));
            var $selectTec = $('#itemtecnologia');
            
            if (cache && cache.tecnologia && $selectTec.length) {
                // Forzar el primer combo usando el método oficial de la librería
                $selectTec.selectpicker('val', cache.tecnologia);
                $selectTec.trigger('change'); 
            }
        } catch (error) {
            console.warn("Error en arranque inicial:", error);
        }
    }, 200); 
});
// Evitar que los botones de control del escáner gatillen el envío del formulario
$(".card-bt button").off('click').on('click', function(e) {
    e.preventDefault();
});

</script>

@endpush