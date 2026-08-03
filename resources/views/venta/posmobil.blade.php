@extends('layouts.app')

@section('title', 'POS Móvil')

@push('css')
<script src="https://jsdelivr.net"></script>
<script src="https://cloudflare.com"></script>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
    margin: 0;
}

.header {
    background: #2fa4c7;
    color: white;
    padding: 15px;
    text-align: center;
    font-size: 20px;
    font-weight: bold;
}

.container {
    padding: 10px;
}

.card {
    background: #fff;
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 15px;
    border: 2px solid #f2a100;
    position: relative;
    transition: all 0.25s ease-in-out;
}

/* Fondo verde y borde para las tarjetas seleccionadas */
.card-seleccionada {
    background-color: #e8f5e9 !important;
    border: 2px solid #2e7d32 !important;
    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.15);
}

.card-bt {
    background: #c1d4ff;
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 15px;
    border: 2px solid #240ef0;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
    gap: 10px;
}

.title {
    font-weight: bold;
    font-size: 1.05rem;
    color: #212529;
}

.price {
    color: #333;
    margin-top: 5px;
    font-size: 0.9rem;
}

.controls {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border-radius: 30px;
    padding: 3px;
    border: 1px solid #dee2e6;
}

.btn-circle {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: none;
    background: #f2a100;
    color: white;
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
}

.qty {
    font-weight: bold;
    width: 45px;
    text-align: center;
    border: none;
    background: transparent;
    font-size: 1rem;
}

.qty::-webkit-outer-spin-button,
.qty::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.fab-enviar {
    position: fixed;
    bottom: 90px;
    right: 20px;
    background: #28a745;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    color: white;
    font-size: 26px;
    border: none;
    cursor: pointer;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.3);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
}

.fab-search {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #2fa4c7;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    color: white;
    font-size: 26px;
    border: none;
    cursor: pointer;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.3);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
}

h6 {
    color: #6c757d;
    font-size: 13px;
    margin: 4px 0;
}
</style>
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="header">
    Preventa Móvil - Farmacias Grey's
</div>
<!-- CONTROL LOGÍSTICO MÓVIL - PARTE 2 DE 3 -->
<div class="container">
    <div class="card-bt">
        <button type="button" onclick="iniciarScanner('qr')" class="btn btn-success btn-sm fw-bold"> Gracie QR</button>
        <button type="button" onclick="iniciarScanner('barra')" class="btn btn-light btn-sm fw-bold border"> Barra</button>
        <button type="button" onclick="StopScanner()" class="btn btn-danger btn-sm"> Detener</button>
    </div>
    
    <div id="reader" class="mb-2" style="width:100%"></div>
    <div id="info_lote_guia_global"></div>

    <form id="formVenta" action="{{ route('ventas.storemobile') }}" method="POST">
        @csrf
        <input type="hidden" name="cliente_id" id="cliente_id" value="{{ $cliente_id }}">
        <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
<!-- Selector de Comprobante para Fórmulas -->
<div class="card p-3 mb-3 border-secondary bg-light">
    <label class="form-label text-xs fw-bold text-secondary mb-1">Documento Contable / Comprobante</label>
    <select name="comprobante_id" id="comprobante_id" class="form-select form-select-sm fw-bold" required>
        <option value="">-- Seleccionar Comprobante --</option>
        @foreach($comprobantes as $comp)
            <!-- CORRECCIÓN DEFINITIVA: Usamos tipo_comprobante y ClaveVista según tu estructura real -->
            <option value="{{ $comp->id }}">
                {{ $comp->tipo_comprobante }} ({{ $comp->ClaveVista }})
            </option>
        @endforeach
    </select>
</div>


        <!-- CONTENEDOR UNIFICADO DONDE OPERA EL REORDENAMIENTO -->
        <div id="contenedor-productos-pos">
            @foreach ($productos as $item)
            <div class="card"
                 data-precio="{{ $item->precio_venta }}"
                 data-id="{{ $item->id }}"
                 data-codigo="{{ $item->codigo }}"
                 data-nombre="{{ $item->nombre }}"
                 data-stock="{{ $item->stock }}"
                 data-lote="{{ $item->numero_lote ?? 'N/A' }}"
                 data-vence="{{ $item->fecha_vencimiento ?? '' }}"
                 data-cantidadlote="{{ $item->cantidad_lote ?? 0 }}"
                 data-reglas="{{ json_encode($item->reglas_json) }}"
                 id="producto-{{ $item->id }}">

                <div class="title">{{ $item->nombre." - ".$item->codigo }}</div>
                
                <input type="hidden" name="arrayidproducto[]" value="{{ $item->id }}">
                <input type="hidden" name="arrayprecioventa[]" class="input-precio-venta-final" value="{{ $item->precio_venta }}">
                <input type="hidden" name="arraysubiva[]" class="input-subiva-fila" value="0">
                <input type="hidden" name="arraydescuento[]" class="input-descuento-fila" value="0">

                <div class="price">
                    Precio: Q<span class="precio">{{ number_format($item->precio_venta, 2) }}</span>
                </div>

                <div>{{ $item->descripcion }}</div>
                <h6>Existencia: {{ $item->stock }}</h6>

                @if(($item->numero_lote ?? 'N/A') !== 'N/A')
                    <div class="text-muted my-1" style="font-size: 0.7rem; background-color: #f8f9fa; padding: 4px; border-radius: 6px;">
                        📦 Sugerencia Lote: <b>{{ $item->numero_lote }}</b> (Vence: {{ $item->fecha_vencimiento }})
                    </div>
                @endif

                <div class="contenedor-promo-tag-fila my-1"></div>

                <div class="controls">
                    <button type="button" class="btn-circle btn-minus" onclick="ajustarCantidadFila(this, -1)">-</button>
                    <input type="number" class="qty" name="arraycantidad[]" value="0" min="0" oninput="evaluarCalculoFilaManual(this)">
                    <button type="button" class="btn-circle btn-plus" onclick="ajustarCantidadFila(this, 1)">+</button>
                </div>

                <input type="hidden" class="descuento" value="0">

                <div class="price border-top pt-2 mt-2 text-end text-secondary small">
                    Subtotal Q. <span class="subtotal fw-bold text-dark fs-6">0.00</span>
                    <input class="subtotalinput" type="hidden" name="Subtotal[]" value="0">
                </div>
            </div>
            @endforeach
        </div>

        <!-- Panel de Totales e Impuestos -->
        <div class="card bg-dark text-white p-3 mt-3 mb-5 border-0 rounded-3">
            <div class="d-flex justify-content-between mb-1 small text-white-50">
                <span>Retención IVA:</span>
                <span id="lbl_iva_movil">Q0.00</span>
            </div>
            <div class="d-flex justify-content-between align-items-center fs-5 border-top border-secondary pt-2">
                <span class="fw-bold text-warning">Total General:</span>
                <span style="font-weight: 900;">Q<span id="totalGeneral">0.00</span></span>
            </div>
            <input type="hidden" class="TotalGeneral" name="TotalGeneral" value="0">
        </div>

        <button type="button" class="fab-enviar" id="guardar">💾</button>
    </form>
</div>

<button type="button" class="fab-search" data-bs-toggle="modal" data-bs-target="#modalBuscar">🔍</button>

<div class="modal fade" id="modalBuscar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-sm p-2">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h5 class="modal-title fs-6 fw-bold">Buscar producto</h5>
            </div>
            <div class="modal-body p-3">
                <input type="text" id="buscarInput" class="form-control form-control-sm mb-2" placeholder="Buscar...">
                <div id="resultados" class="list-group rounded-2 gap-1"></div>
            </div>
        </div>
    </div>
</div>
<!-- CONTROL LOGÍSTICO MÓVIL - PARTE 3 DE 3 -->
@endsection

@push('js')

<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>

<script>
    // 1. Inicialización de Variables Globales del POS Móvil
    let formulaActivaComprobante = '';
    let promocionesAceptadas = {};
    let scanner = null;
    let escaneando = false;
    let totalGeneralCalculado = 0;
    let ivaGeneralCalculado = 0;

     


    // 2. Escuchador dinámico de inicialización para cada Tarjeta de Producto con Candado de Stock
    document.querySelectorAll(".card[data-precio]").forEach(card => {
        let btnPlus = card.querySelector(".btn-plus");
        let btnMinus = card.querySelector(".btn-minus");
        let qtyInput = card.querySelector(".qty");
        // Extraemos el límite de stock configurado desde la base de datos en la tarjeta
        let stockMaximo = parseInt(card.getAttribute('data-stock')) || 0;

        if (btnPlus) {
            btnPlus.addEventListener("click", () => {
                let currentVal = parseInt(qtyInput.value || 0);
                
                // CANDADO DE SEGURIDAD CONTABLE: Bloquear si se intenta superar el stock real
                if (currentVal >= stockMaximo) {
                    showToastAlarma(`Inventario insuficiente. Solo quedan ${stockMaximo} unidades disponibles.`);
                    qtyInput.value = stockMaximo; // Forzar el tope
                    return;
                }
                
                qtyInput.value = currentVal + 1;
                ejecutarCalculoYReordenarCard(card);
            });
        }

        if (btnMinus) {
            btnMinus.addEventListener("click", () => {
                let val = parseInt(qtyInput.value || 0) - 1;
                qtyInput.value = val < 0 ? 0 : val;
                ejecutarCalculoYReordenarCard(card);
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener("input", () => {
                let currentVal = parseInt(qtyInput.value || 0);
                
                // CANDADO DE SEGURIDAD PARA INGRESO MANUAL POR TECLADO
                if (currentVal > stockMaximo) {
                    showToastAlarma(`Inventario insuficiente. Se ajustó al stock máximo de ${stockMaximo} unidades.`);
                    qtyInput.value = stockMaximo; // Forzar el tope en caso de tipeo abusivo
                } else if (currentVal < 0) {
                    qtyInput.value = 0;
                }
                
                ejecutarCalculoYReordenarCard(card);
            });
        }
    });


    // 3. Escuchar el cambio del tipo de comprobante para bajar la fórmula fiscal
    document.getElementById('comprobante_id').addEventListener('change', function() {
        let comprobanteId = this.value;
        if (!comprobanteId) {
            formulaActivaComprobante = '';
            recalcularTodoElTableroMovil();
            return;
        }

        fetch('/compras/detalles/' + comprobanteId)
            .then(res => res.json())
            .then(data => {
                if (data.detalles) {
                    formulaActivaComprobante = data.detalles.formuladoc || data.detalles.formula || '';
                } else {
                    formulaActivaComprobante = '';
                }
                recalcularTodoElTableroMovil();
            })
            .catch(err => console.error("Error al obtener detalles contables:", err));
    });
    
    // 4. MOTOR TRONCAL: CALCULA, CAMBIA COLOR Y ENVÍA AL TOP DEL TABLERO
let temporizadorReordenamiento = null; // Variable global de control para el retardo

function ejecutarCalculoYReordenarCard(card) {
    let precioBase = parseFloat(card.getAttribute('data-precio'));
    let qtyInput = card.querySelector('.qty');
    let cantAcumulada = parseInt(qtyInput.value) || 0;
    let idProd = card.getAttribute('data-id');
    let reglasColeccionRaw = card.getAttribute('data-reglas') || '[]';
    
    let analisis = evaluarReglasPrecioMovil(reglasColeccionRaw, cantAcumulada, precioBase);
    
    let precioUnitarioCascada = precioBase;
    let descuentosEfectivoCascada = 0;
    let unidadesRegaloCascada = 0;
    let tagsPromosAplicadas = [];
    let pausarPorConfirmacion = false;

    Object.keys(analisis.propuestasPorFamilia).forEach(nombreFamilia => {
        if (pausarPorConfirmacion) return;

        let prop = analisis.propuestasPorFamilia[nombreFamilia];
        let tokenDecision = idProd + "_" + nombreFamilia + "_" + cantAcumulada;

        if (parseInt(prop.requiereConfirmacion, 10) === 1) {
            if (promocionesAceptadas[tokenDecision] === undefined) {
                pausarPorConfirmacion = true;
                
                Swal.fire({
                    title: 'Aplicar Promoción',
                    html: `Se detectó un beneficio:<br><span class="text-success fw-bold">${prop.mensaje}</span>.<br>¿Deseas aplicarlo al pedido móvil?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#dc3545',
                    confirmButtonText: 'Sí, aplicar',
                    cancelButtonText: 'No',
                    allowOutsideClick: false
                }).then((result) => {
                    promocionesAceptadas[tokenDecision] = !!result.isConfirmed;
                    ejecutarCalculoYReordenarCard(card);
                });
                return;
            }
            if (promocionesAceptadas[tokenDecision] === true) inyectarFase(prop);
        } else {
            inyectarFase(prop);
        }
    });

    function inyectarFase(p) {
        if (p.precioCalculado < precioUnitarioCascada) precioUnitarioCascada = p.precioCalculado;
        descuentosEfectivoCascada += p.descuentoEfectivoCalculado;
        unidadesRegaloCascada = p.regalosCalculados > 0 ? p.regalosCalculados : unidadesRegaloCascada;
        tagsPromosAplicadas.push(p.nombre);
    }

    if (pausarPorConfirmacion) return;

    let costoTotalOrdinarioLista = precioBase * cantAcumulada;
    let unidadesACobrarNetas = cantAcumulada - unidadesRegaloCascada;
    if (unidadesACobrarNetas < 0) unidadesACobrarNetas = 0;

    let subtotalCalculadoRenglon = Math.round(((unidadesACobrarNetas * precioUnitarioCascada) - descuentosEfectivoCascada) * 100) / 100;
    if (subtotalCalculadoRenglon < 0) subtotalCalculadoRenglon = 0;

    let descuentoTotalAhorrado = Math.round((costoTotalOrdinarioLista - subtotalCalculadoRenglon) * 100) / 100;

    // Actualización numérica en caliente sin reordenar de golpe
    card.querySelector('.subtotal').innerText = subtotalCalculadoRenglon.toFixed(2);
    card.querySelector('.subtotalinput').value = subtotalCalculadoRenglon.toFixed(2);
    card.querySelector('.input-precio-venta-final').value = precioUnitarioCascada;
    card.querySelector('.input-descuento-fila').value = descuentoTotalAhorrado;

    let ivaLinea = CalcularFormulaMovilJs(formulaActivaComprobante, subtotalCalculadoRenglon);
    card.querySelector('.input-subiva-fila').value = ivaLinea;

    let contenedorPromoTag = card.querySelector('.contenedor-promo-tag-fila');
    if (contenedorPromoTag) {
        if (tagsPromosAplicadas.length > 0) {
            let textoRegalo = unidadesRegaloCascada > 0 ? ` | + ${unidadesRegaloCascada} u. Gratis` : '';
            contenedorPromoTag.innerHTML = `<span class="badge bg-success" style="font-size:0.65rem;">✓ Promo: ${tagsPromosAplicadas.join(' + ')}${textoRegalo}</span>`;
        } else { contenedorPromoTag.innerHTML = ''; }
    }

    // --- ACCIÓN VISUAL DE MUTACIÓN EN CALIENTE ---
    if (cantAcumulada > 0) {
        card.classList.add('card-seleccionada'); // Se pinta verde suave de inmediato
    } else {
        card.classList.remove('card-seleccionada');
    }

    calcularTotalGeneralMobile();

    // --- AUTOMATIZACIÓN DEL REORDENAMIENTO CON RETARDO DE SEGURIDAD (DEBOUNCE) ---
    clearTimeout(temporizadorReordenamiento); // Detener el reloj si el piloto sigue presionando + o -

    temporizadorReordenamiento = setTimeout(() => {
        const contenedorTablero = document.getElementById('contenedor-productos-pos');
        if (!contenedorTablero) return;

        if (cantAcumulada > 0) {
            // El piloto dejó de presionar por 1 segundo: Elevamos la tarjeta al top
            if (contenedorTablero.firstChild !== card) {
                contenedorTablero.prepend(card);
                
                // RASTREO VISUAL AUTOMÁTICO: Sigue la tarjeta hacia arriba de forma fluida
                card.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });
            }
        } else {
            // Si regresa a cero, se limpia y se va al fondo del listado
            contenedorTablero.appendChild(card);
        }
    }, 1000); // 1 segundo de espera cómodo para evitar interrupciones táctiles
}


    function recalcularTodoElTableroMovil() {
        document.querySelectorAll('#contenedor-productos-pos .card').forEach(card => {
            let cant = parseInt(card.querySelector('.qty').value) || 0;
            if (cant > 0) ejecutarCalculoYReordenarCard(card);
        });
        calcularTotalGeneralMobile();
    }

    function calcularTotalGeneralMobile() {
        let totalAcumulado = 0; 
        let ivaAcumulado = 0;
        
        document.querySelectorAll(".subtotal").forEach(el => { totalAcumulado += parseFloat(el.innerText) || 0; });
        document.querySelectorAll(".input-subiva-fila").forEach(el => { ivaAcumulado += parseFloat(el.value) || 0; });
        
        document.getElementById("totalGeneral").innerText = totalAcumulado.toFixed(2);
        document.querySelector(".TotalGeneral").value = totalAcumulado.toFixed(2);
        document.getElementById("lbl_iva_movil").innerText = "Q" + ivaAcumulado.toFixed(2);
    }

    function CalcularFormulaMovilJs(formulalocal, montoA) {
        if (!formulalocal || formulalocal === "" || formulalocal === "0") return 0;
        try {
            let expresion = formulalocal.replace(/A/g, montoA);
            let res = math.evaluate(expresion);
            return (res !== undefined && res !== null && !isNaN(res)) ? parseFloat(parseFloat(res).toFixed(2)) : 0;
        } catch (e) { return 0; }
    }

    function evaluarReglasPrecioMovil(reglasColeccion, cantidadLlevada, precioOriginal) {
        let resultado = { precioOriginal: parseFloat(precioOriginal), cantNum: parseInt(cantidadLlevada, 10), propuestasPorFamilia: {} };
        if (!reglasColeccion || reglasColeccion === '[]' || reglasColeccion.length === 0) return resultado;
        
        let reglas = (typeof reglasColeccion === 'string') ? JSON.parse(reglasColeccion) : reglasColeccion;
        let cantNum = resultado.cantNum;
        let ahoraMilli = new Date().getTime();
        
        let reglasVigentes = reglas.filter(r => {
            if (!r.fecha_inicio && !r.fecha_fin) return true;
            let inicioMilli = r.fecha_inicio ? new Date(r.fecha_inicio).getTime() : 0;
            let finMilli = r.fecha_fin ? new Date(r.fecha_fin).getTime() : 9999999999999;
            return ahoraMilli >= inicioMilli && ahoraMilli <= finMilli;
        });

        let familias = {};
        reglasVigentes.forEach(regla => {
            let t = regla.tipo_regla; if (!familias[t]) familias[t] = []; familias[t].push(regla);
        });

        Object.keys(familias).forEach(tipoFamilia => {
            let listado = familias[tipoFamilia];
            listado.sort((a, b) => parseInt(b.cantidad_minima, 10) - parseInt(a.cantidad_minima, 10));
            let dominante = null;
            for (let r of listado) { if (cantNum >= (parseInt(r.cantidad_minima, 10) || 0)) { dominante = r; break; } }
            
            if (dominante) {
                let min = parseInt(dominante.cantidad_minima, 10) || 0;
                let beneficio = parseFloat(dominante.valor_beneficio);
                let precioBase = parseFloat(precioOriginal);
                let propuesta = { id: dominante.id, nombre: dominante.nombre, requiereConfirmacion: parseInt(dominante.requiere_confirmacion, 10) || 0, precioCalculado: precioBase, descuentoEfectivoCalculado: 0, regalosCalculados: 0, mensaje: "" };
                
                if (tipoFamilia === 'escala_cantidad' || tipoFamilia === 'combo_mixto') {
                    if (dominante.tipo_beneficio === 'precio_fijo' || dominante.tipo_beneficio === 'precio_fijo_rebajado') { propuesta.precioCalculado = (beneficio > precioBase) ? Math.round((beneficio / min) * 100) / 100 : beneficio; }
                    else if (dominante.tipo_beneficio === 'porcentaje') { propuesta.precioCalculado = precioBase - (precioBase * (beneficio / 100)); }
                    propuesta.mensaje = `Precio Preferencial: Q${propuesta.precioCalculado.toFixed(2)}`;
                } else if (tipoFamilia === 'descuento_fijo') {
                    if (dominante.tipo_beneficio === 'precio_fijo' || dominante.tipo_beneficio === 'precio_fijo_rebajado') { propuesta.precioCalculado = Math.round((beneficio / min) * 100) / 100; propuesta.mensaje = `Precio Paquete: Q${propuesta.precioCalculado.toFixed(2)}`; }
                    else { propuesta.descuentoEfectivoCalculado = (dominante.tipo_beneficio === 'porcentaje') ? Math.round(((precioBase * cantNum) * (beneficio / 100)) * 100) / 100 : beneficio; propuesta.mensaje = `Descuento Directo: -Q${propuesta.descuentoEfectivoCalculado.toFixed(2)}`; }
                } else if (tipoFamilia === 'bonificacion') {
                    let paso = parseInt(dominante.cantidad_paso, 10) || min; let bloques = Math.floor(cantNum / paso);
                    if (bloques > 0) { propuesta.regalosCalculados = bloques * 1; propuesta.mensaje = `Obsequio: Recibe ${propuesta.regalosCalculados} u. Gratis`; }
                }
                resultado.propuestasPorFamilia[tipoFamilia] = propuesta;
            }
        });
        return resultado;
    }


    let timeout = null;
    document.getElementById("buscarInput").addEventListener("keyup", function() {
        clearTimeout(timeout); let texto = this.value.trim();
        timeout = setTimeout(() => {
            if (texto.length < 2) { document.getElementById("resultados").innerHTML = ""; return; }
            fetch(`{{ route('vent.buscarmobile') }}?texto=${encodeURIComponent(texto)}`)
                .then(res => res.json())
                .then(data => {
                    let html = "";
                    data.forEach(p => {
                        html += `<button type="button" onclick="agregarProducto(${p.id})" class="list-group-item list-group-item-action text-start p-2 border-light-subtle"><div class="fw-bold text-dark text-xs">${p.nombre}</div><small class="text-primary font-monospace fw-bold">Q${parseFloat(p.precio_venta).toFixed(2)}</small></button>`;
                    });
                    document.getElementById("resultados").innerHTML = html || '<div class="text-center text-muted small py-2">Sin coincidencias</div>';
                });
        }, 300);
    });


    document.getElementById("guardar").addEventListener("click", function (event) {
        event.preventDefault();
        let selectComp = document.getElementById('comprobante_id');
        if (selectComp.value === "") { Swal.fire({ icon: 'warning', title: 'Comprobante Requerido', text: 'Por favor, selecciona un comprobante.' }); return; }
        let totalArticulos = 0; document.querySelectorAll(".qty").forEach(input => { if (parseInt(input.value) > 0) totalArticulos++; });
        if (totalArticulos === 0) { Swal.fire({ icon: 'warning', title: 'Carrito Vacío', text: 'Debes añadir al menos un producto antes de guardar.' }); return; }
        Swal.fire({ title: '¿Confirmar Preventa?', text: "Se registrará el pedido en el panel de facturación.", icon: 'info', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: 'Sí, registrar' })
        .then((result) => { if (result.isConfirmed) { document.getElementById("formVenta").submit(); } });
    });

        // 8. Enlace de Inserción desde el Buscador Predictivo o Escáner
    function agregarProducto(idProducto) {
        let card = document.getElementById(`producto-${idProducto}`) 
            || document.querySelector(`[data-id="${idProducto}"]`) 
            || document.querySelector(`[data-codigo="${idProducto}"]`);

        if (!card) { 
            alert('⚠️ El producto no se encuentra en el stock móvil.'); 
            return; 
        }
        
        let lote = card.getAttribute('data-lote'); 
        let vence = card.getAttribute('data-vence'); 
        let cantLote = card.getAttribute('data-cantidadlote');
        const contenedorInfoLotesGlobal = document.getElementById('info_lote_guia_global');
        
        if (lote !== 'N/A' && lote !== '') {
            if (contenedorInfoLotesGlobal) {
                contenedorInfoLotesGlobal.innerHTML = `<div class="alert alert-info py-2 px-3 small mb-3 border-0 rounded-3 shadow-sm" style="font-size: 0.8rem;">📦 <b>LOTE SUGERIDO:</b> Extraer del <b>Lote: ${lote}</b> (Expira: ${vence} | Disp: ${cantLote} u.)</div>`;
            }
            showToastAlarma(`Tomar del Lote: ${lote}`);
        } else { 
            if (contenedorInfoLotesGlobal) contenedorInfoLotesGlobal.innerHTML = ''; 
        }
        
        let qtyInput = card.querySelector(".qty");
        qtyInput.value = parseInt(qtyInput.value || 0) + 1;
        ejecutarCalculoYReordenarCard(card);
        card.scrollIntoView({ behavior: "smooth", block: "center" });
        
        let modalEl = document.getElementById('modalBuscar');
        if (modalEl) { 
            let modalInstance = bootstrap.Modal.getInstance(modalEl); 
            if (modalInstance) modalInstance.hide(); 
        }
        StopScanner(); 
        qtyInput.focus();
    }

    // 9. Lector de la cámara web (Escáner) libre de Swal
    function iniciarScanner(tipo = "barra") {
        if (escaneando) return;
        let selectComp = document.getElementById('comprobante_id');
        if (selectComp.value === "") { 
            alert('⚠️ Selecciona un comprobante antes de encender la cámara.'); 
            return; 
        }
        
        if (typeof Html5Qrcode === 'undefined') {
            alert('⚠️ La librería del escáner no está lista en este entorno.');
            return;
        }

        scanner = new Html5Qrcode("reader"); 
        escaneando = true;
        scanner.start({ facingMode: "environment" }, { fps: 12, qrbox: tipo === "barra" ? { width: 260, height: 130 } : 240 },
            (codigo) => {
                StopScanner();
                // CORREGIDO: Reemplazo de Swal por una alerta nativa ligera offline
                alert('📌 Código Detectado con éxito: ' + codigo);
                agregarProducto(codigo);
            }, (err) => {}
        ).catch(e => { escaneando = false; });
    }

    function StopScanner() {
        if (!scanner || !escaneando) return;
        scanner.stop().then(() => { escaneando = false; scanner = null; document.getElementById('reader').innerHTML = ''; });
    }

    // 10. Botón Guardar / Confirmar Venta libre de Swal
    document.getElementById("guardar").addEventListener("click", function (event) {
        event.preventDefault();
        let selectComp = document.getElementById('comprobante_id');
        if (selectComp.value === "") { 
            alert('⚠️ Por favor, selecciona un tipo de comprobante.'); 
            return; 
        }
        
        let totalArticulos = 0; 
        document.querySelectorAll(".qty").forEach(input => { 
            if (parseInt(input.value) > 0) totalArticulos++; 
        });
        
        if (totalArticulos === 0) { 
            alert('⚠️ El carrito de compras se encuentra vacío. Añade al menos un producto.'); 
            return; 
        }
        
        // CORREGIDO: Reemplazo de Swal por confirmación estándar del navegador
        if (confirm("¿Confirmar Preventa?\n\nSe registrará el pedido en el panel de facturación.")) {
            document.getElementById("formVenta").submit();
        }
    });

    function showToastAlarma(message) {
        alert("⚠️ ATENCIÓN: " + message);
    }

    // --- NUEVAS FUNCIONES PUENTE PARA SOLUCIONAR LOS CLICS EN CELULARES ---
    function ajustarCantidadFila(boton, incremento) {
        // Localizar la tarjeta contenedora del producto
        let card = boton.closest('.card');
        if (!card) return;

        let qtyInput = card.querySelector('.qty');
        let currentVal = parseInt(qtyInput.value || 0);
        let stockMaximo = parseInt(card.getAttribute('data-stock')) || 0;

        // Calcular el nuevo valor tentativo
        let newVal = currentVal + incremento;
        if (newVal < 0) newVal = 0;

        // CANDADO DE SEGURIDAD OPERATIVA: Bloquear si supera las existencias reales
        if (newVal > stockMaximo) {
            alert(`⚠️ INVENTARIO INSUFICIENTE: Solo quedan ${stockMaximo} unidades disponibles en el stock contable.`);
            qtyInput.value = stockMaximo; // Forzar el tope maximo
            ejecutarCalculoYReordenarCard(card);
            return;
        }

        qtyInput.value = newVal;
        ejecutarCalculoYReordenarCard(card);
    }

    function evaluarCalculoFilaManual(input) {
        let card = input.closest('.card');
        if (!card) return;

        let stockMaximo = parseInt(card.getAttribute('data-stock')) || 0;
        let currentVal = parseInt(input.value || 0);

        // CANDADO DE SEGURIDAD PARA INGRESO MANUAL CON TECLADO TÁCTIL
        if (currentVal > stockMaximo) {
            alert(`⚠️ INVENTARIO INSUFICIENTE: Se ajustó al stock máximo de ${stockMaximo} unidades.`);
            input.value = stockMaximo;
        } else if (currentVal < 0) {
            input.value = 0;
        }

        ejecutarCalculoYReordenarCard(card);
    }

            
</script>
@endpush
