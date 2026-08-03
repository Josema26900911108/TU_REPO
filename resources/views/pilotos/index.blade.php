@extends('layouts.app')

@section('content')
@php
    $diasNombre = [];
    $tipoRutaActiva = $rutaActual ? $rutaActual->tipo_ciclo : 'semanal';

    if ($tipoRutaActiva === 'fin_de_semana') {
        $diasNombre = [1 => 'Sábado ☀️', 2 => 'Domingo ☕'];
    } elseif ($tipoRutaActiva === 'semanal') {
        $diasNombre = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    } elseif ($tipoRutaActiva === 'quincenal') {
        // Generar las 14 columnas organizadas por Semana 1 y Semana 2
        $diasSemanaBase = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        for ($i = 1; $i <= 14; $i++) {
            $indiceDia = ($i - 1) % 7;
            $semana = $i <= 7 ? 'S1' : 'S2';
            $diasNombre[$i] = $diasSemanaBase[$indiceDia] . " (" . $semana . ")";
        }
    } else {
        // Ciclo Mensual: Renderiza del Día 1 al Día 30
        for ($i = 1; $i <= 30; $i++) {
            $diasNombre[$i] = 'Día ' . $i;
        }
    }
@endphp

<div class="container-fluid mt-4">
    <!-- Meta Token para peticiones asíncronas AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Inyección nativa del motor SortableJS para garantizar el arrastre -->
    

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show p-2 small mb-3" role="alert">
            <strong>¡Éxito!</strong> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
    <h5 class="mb-0 fw-bold">Planificador de Rutas Cíclicas</h5>
    <div class="d-flex align-items-center gap-3">
        <!-- Selector de Ruta Activa -->
        <form action="{{ route('rutas.index') }}" method="GET" id="form-ruta" class="d-flex align-items-center gap-2 m-0">
            <label class="mb-0 text-white font-weight-bold text-nowrap small">Ruta Activa:</label>
            <select name="ruta_id" onchange="document.getElementById('form-ruta').submit()" class="form-select form-select-sm bg-white text-dark py-0" style="min-width: 180px; height: 30px;">
                @foreach($rutas as $r)
                    <option value="{{ $r->id }}" {{ $rutaActual && $rutaActual->id == $r->id ? 'selected' : '' }}>
                        {{ $r->nombre }} ({{ ucfirst($r->tipo_ciclo) }})
                    </option>
                @endforeach
            </select>
        </form>

        @if($rutaActual)
        <!-- COMBOBOX: Cambio automático de Centro de Costos sin botón -->
        <div class="d-flex align-items-center gap-2">
            <label class="mb-0 text-white font-weight-bold text-nowrap small">Centro Costos:</label>
<select id="cambio-rapido-cc" 
        onchange="cambiarCentroCostosRuta(this, {{ $rutaActual->id }})" 
        class="form-select form-select-sm bg-dark text-white border-secondary py-0" 
        style="min-width: 200px; height: 30px; cursor: pointer;">
    
    <!-- El valor vacío forzará el borrado del centro de costos en la base de datos -->
    <option value="" {{ is_null($rutaActual->centro_costos) ? 'selected' : '' }}>-- Sin Asignar --</option>
    
    @foreach($centrosCostos as $centro)
        <option value="{{ $centro->codigo }}" {{ $rutaActual->centro_costos === $centro->codigo ? 'selected' : '' }}>
            [{{ $centro->codigo }}] {{ $centro->nombre }}
        </option>
    @endforeach
</select>

        </div>
        @endif
    </div>
</div>

        
        <div class="card-body">
            <!-- Buscador general en tiempo real -->
            <div class="row g-2 mb-3">
                <div class="col-md-9">
                    <input type="text" id="buscador-clientes-rutas" class="form-control form-control-sm" placeholder="Buscar clientes asignados o disponibles por nombre o razón social...">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-success btn-sm w-100 font-weight-bold text-nowrap" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCrearRuta" aria-expanded="false" aria-controls="collapseCrearRuta">
                        ➕ Nueva Ruta Cíclica
                    </button>
                </div>
            </div>

            <!-- Formulario colapsable para registro de rutas nuevas -->
            <div class="collapse mb-4" id="collapseCrearRuta">
                <div class="card card-body bg-light border-success-subtle p-3 shadow-xs">
                    <form action="{{ route('rutas-ciclos.storeRuta') }}" method="POST" class="m-0">
                        @csrf
                        <div class="row align-items-end g-2">
                            <div class="col-md-4">
                                <label class="form-label text-xs fw-bold text-secondary mb-1">Nombre de la Ruta</label>
                                <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej: Distribución Norte..." required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-xs fw-bold text-secondary mb-1">Frecuencia del Ciclo</label>
<select name="tipo_ciclo" class="form-select form-select-sm" required>
    <option value="semanal">Semanal</option>
    <option value="fin_de_semana">Fin de Semana</option>
    <option value="quincenal">Quincenal (2 Semanas)</option>
    <option value="mensual">Mensual (30 Días)</option>
    <!-- Nueva opción para manejar días flotantes como cada 20 días -->
    <option value="personalizado">Frecuencia Personalizada (30 Días Flotantes)</option>
</select>

                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-xs fw-bold text-secondary mb-1">Centro de Costos Asignado (Tu Sucursal)</label>
                                <select name="fkCentro" class="form-select form-select-sm" required>
                                    <option value="">-- Seleccionar Centro de Costos --</option>
                                    @foreach($centrosCostos as $centro)
                                        <option value="{{ $centro->id }}">[{{ $centro->codigo }}] {{ $centro->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-success btn-sm w-100 font-weight-bold" style="height: 31px;" title="Guardar Nueva Ruta">💾</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @if($rutaActual)
            <div class="row g-3">
                <!-- COLUMNAS HORIZONTALES KANBAN (9 de 12 espacios) -->
                <div class="col-md-9">
                    <div class="d-flex gap-2 overflow-auto pb-2" style="width: 100%; white-space: nowrap;">
                        @foreach($diasNombre as $numeroDia => $nombreDia)
                            <div class="flex-shrink-0" style="width: calc(14.2% - 6px); min-width: 150px;">
                                <div class="card border-secondary-subtle bg-light-subtle d-flex flex-column" style="min-height: 500px;">
                                    
                                    <!-- Encabezado individual de día -->
                                    <!-- Zona interactiva Droppable (Añadimos onclick para recibir el clon manual) -->
<!-- ANTES: -->
<!-- <div id="dia-{{ $numeroDia }}" data-dia="{{ $numeroDia }}" onclick="confirmarClonacionEnDia(this)" class="sortable-list ..."> -->

<!-- DEBE QUEDAR ASÍ (Limpio): -->
                                    <div id="dia-{{ $numeroDia }}" data-dia="{{ $numeroDia }}" class="sortable-list card-body p-2 d-flex flex-column gap-2 flex-grow-1" 
                                    style="background-color: #fcfcfc; min-height: 450px; overflow-y: auto;">
                                        <span class="fw-bold text-secondary small text-truncate" style="font-size: 0.8rem;">{{ $nombreDia }}</span>
                                        <button type="button" onclick="abrirModalMoverDia({{ $numeroDia }}, '{{ $nombreDia }}')" class="btn btn-outline-primary btn-xs py-0 px-1 border-0" style="font-size: 0.65rem;" title="Mover bloque completo">
                                            📦 Bloque
                                        </button>
                                    </div>

                                    <!-- Zona interactiva Droppable (Con altura forzada de 450px) -->
                                    <div id="dia-{{ $numeroDia }}" data-dia="{{ $numeroDia }}" class="sortable-list card-body p-2 d-flex flex-column gap-2 flex-grow-1" style="background-color: #fcfcfc; min-height: 450px; overflow-y: auto;">
                                        @foreach($clientesPorDia[$numeroDia] as $cliente)
                                            <div data-cliente-id="{{ $cliente->id }}" 
                                                oncontextmenu="activarModoClonacionManual(event, this)"
                                                class="card-cliente card shadow-sm p-2 border bg-white position-relative" 
                                                style="cursor: grab; white-space: normal;">
                                                
                                                <!-- Cabecera de la tarjeta con ID y Botón X de eliminación -->
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="badge bg-secondary" style="font-size: 0.6rem;">ID: {{ $cliente->id }}</span>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <!-- BUSCA ESTA LÍNEA CON EL ICONO: -->
<!-- <i class="fa-solid fa-copy text-muted opacity-50 small me-1" title="Clic derecho para clonar"></i> -->

<!-- REEMPLÁZALA POR ESTE SÍMBOLO HTML SEGURO (Muestra dos hojitas de copia nativas): -->
<span style="cursor: help; font-size: 0.8rem; color: #6c757d;" title="Clic derecho para clonar">📋</span>

                                                        <!-- Botón de eliminación directa por AJAX -->
                                                        <button type="button" 
                                                                onclick="quitarClienteDeDia(event, this, {{ $cliente->id }}, {{ $numeroDia }})" 
                                                                class="btn p-0 border-0 text-danger" 
                                                                style="font-size: 0.8rem; line-height: 1; cursor: pointer;" 
                                                                title="Quitar de este día">
                                                            &times;
                                                        </button>
                                                    </div>
                                                </div>

                                                <p class="mb-0 text-dark fw-bold small text-search">
                                                    {{ $cliente->persona ? $cliente->persona->razon_social : 'Persona: ' . $cliente->persona_id }}
                                                </p>
                                                @if($cliente->persona)
                                                    <small class="text-muted d-block mt-1 lh-sm" style="font-size: 0.7rem;">{{ $cliente->persona->direccion }}</small>
                                                @endif
                                            </div>
                                        @endforeach


                                    </div>

                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <!-- BARRA LATERAL: BOLSA DE CLIENTES DISPONIBLES (3 de 12 espacios) -->
                <div class="col-md-3 border-start">
                    <div class="card border-warning-subtle shadow-xs h-100 d-flex flex-column" style="min-height: 500px;">
                        <div class="card-header bg-dark text-white py-1 px-3">
                            <h6 class="mb-0 small fw-bold py-1">Bolsa de Disponibles</h6>
                        </div>
                        <div id="bolsa-clientes" data-dia="0" class="sortable-list card-body p-2 d-flex flex-column gap-2 flex-grow-1 overflow-auto" style="max-height: 480px; background-color: #f8f9fa;">
                            @foreach($todosLosClientes as $cliente)
                                <div data-cliente-id="{{ $cliente->id }}" class="card-cliente card shadow-sm p-2 border bg-white border-warning-subtle" style="cursor: grab; white-space: normal;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">Disponible</span>
                                    </div>
                                    <p class="mb-0 text-dark fw-bold small text-search">
                                        {{ $cliente->persona ? $cliente->persona->razon_social : 'Persona: ' . $cliente->persona_id }}
                                    </p>
                                    @if($cliente->persona)
                                        <small class="text-muted d-block mt-1 lh-sm" style="font-size: 0.7rem;">{{ $cliente->persona->direccion }}</small>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @else
                <div class="alert alert-info text-center my-4 p-3 fs-6">
                    No existen rutas de ciclos registradas en el sistema. Abre el panel superior para dar de alta tu primera ruta.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- MODAL BOOTSTRAP PARA MOVER EL DÍA COMPLETO -->
<div class="modal fade" id="modalMoverDia" data-bs-backdrop="static" tabindex="-1" aria-labelledby="modalMoverDiaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <h5 class="modal-title fs-6" id="modalMoverDiaLabel">Mover Día Completo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formMoverDia" action="{{ route('rutas.moverDia') }}" method="POST" class="m-0">
                @csrf
                <input type="hidden" name="ruta_origen_id" value="{{ $rutaActual?->id }}">
                <input type="hidden" name="dia_origen" id="modal-dia-origen">

                <div class="modal-body text-sm">
                    <p class="text-muted small">Vas a reubicar masivamente a todos los clientes asignados al día <strong id="modal-nombre-dia" class="text-primary"></strong> hacia otro bloque cíclico.</p>
                    
                    <div class="mb-2">
                        <label class="form-label font-weight-bold small mb-1">Ruta Destino</label>
                        <select name="ruta_destino_id" class="form-select form-select-sm">
                            @foreach($rutas as $r)
                                <option value="{{ $r->id }}" {{ $rutaActual && $rutaActual->id == $r->id ? 'selected' : '' }}>{{ $r->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label font-weight-bold small mb-1">Día Destino</label>
                        <select name="dia_destino" class="form-select form-select-sm">
                            @foreach($diasNombre as $num => $nom)
                                <option value="{{ $num }}">{{ $nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Confirmar Traspaso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="tarjeta-flotante-clon" class="position-fixed d-none shadow-lg border border-primary p-2 bg-white rounded" style="z-index: 9999; pointer-events: none; opacity: 0.85; width: 150px;">
    <small class="badge bg-primary d-block mb-1 text-center" style="font-size: 0.6rem;">📍 Soltar en día...</small>
    <p id="texto-flotante-clon" class="mb-0 fw-bold text-dark text-truncate" style="font-size: 0.75rem;"></p>
</div>

<script src="{{ asset('js/sortable.min.js') }}"></script>
<!-- LÓGICA DE CONTROL SCRIPT - PARTE 1 DE 3 -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const inputBuscador = document.getElementById('buscador-clientes-rutas');
        const rutaActualId = "{{ $rutaActual?->id }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // 1. Buscador rápido en tiempo real
        if (inputBuscador) {
            inputBuscador.addEventListener('keyup', function () {
                const termino = this.value.toLowerCase().trim();
                document.querySelectorAll('.card-cliente').forEach(function (tarjeta) {
                    if (tarjeta.textContent.toLowerCase().indexOf(termino) > -1) {
                        tarjeta.style.setProperty("display", "block", "important");
                    } else {
                        tarjeta.style.setProperty("display", "none", "important");
                    }
                });
            });
        }

        // 2. Inicialización del motor de arrastre con inyección de componentes dinámica
        document.querySelectorAll('.sortable-list').forEach(el => {
            const esBolsa = el.getAttribute('id') === 'bolsa-clientes';
            
            new Sortable(el, {
                groupName: 'compartido-clientes',
                permitirLlegada: !esBolsa,
                chosenClass: 'bg-primary-subtle'
            });

            // Escuchador del final del arrastre
            el.addEventListener('sortable-end', function (evt) {
                const datos = evt.detail;
                const tarjetaHtml = datos.item;
                const clienteId = tarjetaHtml.getAttribute('data-cliente-id');
                const diaDestino = parseInt(datos.diaDestino);
                const diaOrigen = parseInt(datos.diaOrigen);

                if (!diaDestino || diaDestino === 0) return;

                // --- INYECCIÓN DINÁMICA DE BOTÓN X Y CLIC DERECHO AL CLON ---
                if (diaOrigen === 0) {
                    // 1. Cambiar el badge de "Disponible" a "ID" gris estándar
                    const badge = tarjetaHtml.querySelector('.badge');
                    if (badge) {
                        badge.className = "badge bg-secondary";
                        badge.innerText = "ID: " + clienteId;
                    }

                    // 2. Añadir el evento de clic derecho para clonación manual
                    tarjetaHtml.setAttribute('oncontextmenu', 'activarModoClonacionManual(event, this)');

                    // 3. Crear e inyectar el botón de eliminación "X" si no lo tiene ya
                    if (!tarjetaHtml.querySelector('.btn-eliminar-dinamico')) {
                        const contenedorBadge = tarjetaHtml.querySelector('.d-flex');
                        if (contenedorBadge) {
                            const wrapperBotones = document.createElement('div');
                            wrapperBotones.className = "d-flex align-items-center gap-1";
                            wrapperBotones.innerHTML = `
                                <span style="cursor: help; font-size: 0.8rem; color: #6c757d;" title="Clic derecho para clonar">📋</span>
                                <button type="button" 
                                        onclick="quitarClienteDeDia(event, this, ${clienteId}, ${diaDestino})" 
                                        class="btn p-0 border-0 text-danger btn-eliminar-dinamico" 
                                        style="font-size: 0.8rem; line-height: 1; cursor: pointer;" 
                                        title="Quitar de este día">
                                    &times;
                                </button>
                            `;
                            contenedorBadge.appendChild(wrapperBotones);
                        }
                    }
                } else {
                    // Si se movió entre columnas de días, actualizamos el atributo del botón eliminar al nuevo día
                    const botonEliminar = tarjetaHtml.querySelector('.btn-eliminar-dinamico') || tarjetaHtml.querySelector('button[onclick*="quitarClienteDeDia"]');
                    if (botonEliminar) {
                        botonEliminar.setAttribute('onclick', `quitarClienteDeDia(event, this, ${clienteId}, ${diaDestino})`);
                    }
                }

                // Mapear la secuencia de orden completa de la columna de llegada
                let nuevoOrden = [];
                datos.to.querySelectorAll('[data-cliente-id]').forEach((card, index) => {
                    nuevoOrden.push({
                        cliente_id: parseInt(card.getAttribute('data-cliente-id')),
                        orden: index + 1
                    });
                });

                // Determinar endpoint correcto para sincronizar la Base de Datos
                const urlEndpoint = (diaOrigen === 0) ? "{{ route('rutas.asignarManual') }}" : "{{ route('rutas.moverCliente') }}";
                
                // Estructura de parámetros exacta requerida por tus controladores
                const payload = {
                    cliente_id: parseInt(clienteId),
                    ruta_id: parseInt(rutaActualId),
                    ruta_origen_id: parseInt(rutaActualId),
                    dia_origen: diaOrigen,
                    ruta_destino_id: parseInt(rutaActualId),
                    dia_destino: diaDestino,
                    nuevo_orden: nuevoOrden.findIndex(x => x.cliente_id == clienteId) + 1,
                    secuencia_completa: nuevoOrden
                };

                // Petición fetch hacia Laravel
                fetch(urlEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Sincronizado con Éxito en DB:', data.message);

                    // --- ACCIÓN VISUAL: LIMPIEZA DE BOLSA EN TIEMPO REAL ---
                    if (diaOrigen === 0) {
                        // Localiza la tarjeta idéntica que se quedó estática dentro de la bolsa lateral
                        const tarjetaOriginalEnBolsa = document.querySelector(`#bolsa-clientes [data-cliente-id="${clienteId}"]`);
                        if (tarjetaOriginalEnBolsa) {
                            tarjetaOriginalEnBolsa.style.transition = "all 0.2s ease";
                            tarjetaOriginalEnBolsa.style.opacity = "0";
                            setTimeout(() => {
                                tarjetaOriginalEnBolsa.remove(); // La remueve del DOM por completo
                            }, 200);
                        }
                    }
                })
                .catch(error => console.error('Error al persistir en DB:', error));

            });
        });
    });
</script>

<!-- LÓGICA DE CONTROL SCRIPT - PARTE 2 DE 3 -->
<script>
    // 3. Clonación manual por Clic Derecho
    function activarModoClonacionManual(event, elemento) {
        event.preventDefault();
        const clienteId = elemento.getAttribute('data-cliente-id');
        const nombreCliente = elemento.querySelector('.text-search').textContent.trim();
        
        const seleccionDia = prompt(`¿A qué día deseas CLONAR a:\n"${nombreCliente}"?\n\nEscribe el número del día:\n1 = Lunes\n2 = Martes\n3 = Miércoles\n4 = Jueves\n5 = Viernes\n6 = Sábado\n7 = Domingo`);
        if (!seleccionDia) return;
        
        const diaDestino = parseInt(seleccionDia);
        if (isNaN(diaDestino) || diaDestino < 1 || diaDestino > 7) {
            alert('Número de día no válido.');
            return;
        }

        const columnaDestino = document.getElementById(`dia-${diaDestino}`);
        if (!columnaDestino) return;

        if (columnaDestino.querySelector(`[data-cliente-id="${clienteId}"]`)) {
            alert('Este cliente ya está agendado para ese día.');
            return;
        }

        // Clonar e inyectar el nodo con sus botones correspondientes
        const clon = elemento.cloneNode(true);
        const botonX = clon.querySelector('button') || clon.querySelector('.btn-eliminar-dinamico');
        if (botonX) {
            botonX.setAttribute('onclick', `quitarClienteDeDia(event, this, ${clienteId}, ${diaDestino})`);
        }
        columnaDestino.appendChild(clon);

        fetch("{{ route('rutas.asignarManual') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                cliente_id: parseInt(clienteId),
                ruta_id: parseInt("{{ $rutaActual?->id }}"),
                dia_destino: diaDestino,
                nuevo_orden: columnaDestino.children.length
            })
        })
        .then(response => response.json())
        .then(data => console.log('Clonación guardada de forma permanente.'))
        .catch(error => console.error('Error al clonar:', error));
    }
</script>

<!-- LÓGICA DE CONTROL SCRIPT - PARTE 3 DE 3 -->
<script>
    // 4. Función de eliminación reactiva con retorno controlado a la bolsa
    function quitarClienteDeDia(event, boton, clienteId, diaSemana) {
        event.stopPropagation();
        if (!confirm('¿Deseas remover a este cliente de este día específico?')) return;

        const tarjetaHtml = boton.closest('.card-cliente');
        const bolsaClientes = document.getElementById('bolsa-clientes');

        fetch("{{ route('rutas-ciclos.eliminarCliente') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                cliente_id: parseInt(clienteId),
                ruta_id: parseInt("{{ $rutaActual?->id }}"),
                dia_semana: parseInt(diaSemana)
            })
        })
        .then(response => response.json())
        .then(data => {
            tarjetaHtml.style.transition = "all 0.25s ease";
            tarjetaHtml.style.opacity = "0";
            tarjetaHtml.style.transform = "scale(0.8)";
            
            setTimeout(() => {
                tarjetaHtml.remove();
                const restantes = document.querySelectorAll(`.sortable-list:not(#bolsa-clientes) [data-cliente-id="${clienteId}"]`).length;
                const enBolsa = bolsaClientes.querySelector(`[data-cliente-id="${clienteId}"]`) !== null;

                if (restantes === 0 && !enBolsa) {
                    const clonBolsa = tarjetaHtml.cloneNode(true);
                    clonBolsa.style.removeProperty('opacity');
                    clonBolsa.style.removeProperty('transform');
                    const badge = clonBolsa.querySelector('.badge');
                    if (badge) { badge.className = "badge bg-warning text-dark"; badge.innerText = "Disponible"; }
                    const btnX = clonBolsa.querySelector('button') || clonBolsa.querySelector('.btn-eliminar-dinamico');
                    if (btnX) btnX.remove();
                    bolsaClientes.insertBefore(clonBolsa, bolsaClientes.firstChild);
                }
            }, 250);
        });
    }

    // 5. Apertura del Modal masivo de días
    let bootstrapModal = null;
    window.abrirModalMoverDia = function(diaNum, diaNom) {
        document.getElementById('modal-nombre-dia').innerText = diaNom;
        document.getElementById('modal-dia-origen').value = diaNum;
        const modalElement = document.getElementById('modalMoverDia');
        if (modalElement) {
            bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
        }
    }

function cambiarCentroCostosRuta(selectElement, rutaId) {
    const codigoCC = selectElement.value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Efecto visual temporal de guardado (opacidad)
    selectElement.style.opacity = "0.5";

    fetch("{{ route('rutas.actualizarCentroCostos') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            ruta_id: Number(rutaId),
            // Si está vacío, enviamos null de forma explícita en el JSON
            centro_costos_codigo: codigoCC ? String(codigoCC).trim() : null
        })
    })
    .then(response => response.json())
    .then(data => {
        // Restaurar estado visual indicando éxito
        selectElement.style.opacity = "1";
        selectElement.classList.add('border-success');
        console.log('Respuesta del servidor:', data.message);
        
        setTimeout(() => {
            selectElement.classList.remove('border-success');
        }, 1000);
    })
    .catch(error => {
        selectElement.style.opacity = "1";
        console.error('Error al actualizar el centro de costos:', error);
        alert('Error de conexión al guardar el centro de costos.');
    });
}


</script>

<style>
    .card-cliente { cursor: grab !important; user-select: none; }
    .card-cliente:active { cursor: grabbing !important; }
    .sortable-chosen { border: 2px dashed #0d6efd !important; opacity: 0.6; }
</style>
@endsection
