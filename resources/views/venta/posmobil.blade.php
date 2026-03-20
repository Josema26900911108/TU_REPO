@extends('layouts.app')

@section('title','clientes')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')

@include('layouts.partials.alert')

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>|

<script>

let html5QrCode = null;
let escaneando = false;

function iniciarScanner(tipo = "barra") {
    if (escaneando) {
        console.warn("El escáner ya está en ejecución.");
        return;
    }

    // Validar si la librería cargó correctamente
    if (typeof Html5Qrcode === 'undefined') {
        alert("Error: La librería de la cámara no se ha cargado. Revisa tu conexión HTTPS.");
        return;
    }

    // Instanciar el objeto en el div con id="reader"
    html5QrCode = new Html5Qrcode("reader");
    escaneando = true;

    // 2. Configuración optimizada para Códigos de Barras (1D)
    const config = {
        fps: 20, // Velocidad de captura (más alto ayuda a enfocar barras)
        qrbox: tipo === "barra"
            ? { width: 300, height: 150 } // Rectángulo horizontal para barras
            : { width: 250, height: 250 }, // Cuadrado para QR
        aspectRatio: 1.777778, // Proporción 16:9 de celulares modernos
        formatsToSupport: [
            "ean_13", "code_128", "ean_8", "upc_a", "upc_e", "qr_code"
        ],
        experimentalFeatures: {
            useBarCodeDetectorIfSupported: true // Usa aceleración por hardware
        }
    };

    // 3. Configuración de Cámara (Resolución y Enfoque)
    const cameraConfig = {
        facingMode: "environment" // Fuerza la cámara trasera
    };

    // Iniciar la cámara
    html5QrCode.start(
        cameraConfig,
        config,
        (codigo) => {
            // ÉXITO: Código detectado
            console.log("Lectura exitosa:", codigo);

            // Vibración (solo en Android/Chrome con HTTPS)
            if (navigator.vibrate) navigator.vibrate(100);

            if (tipo === "barra") {
                buscarProductoPorCodigo(codigo); // Tu función de Laravel
            } else {
                agregarProducto(codigo); // Tu función de Laravel
            }

            // OPCIONAL: Detener después de una lectura exitosa
            // StopScanner();
        },
        (error) => {
            // Errores de "No se encontró código en este frame" (silenciados)
        }
    ).catch(err => {
        console.error("Error al iniciar cámara:", err);
        escaneando = false;
        html5QrCode = null;
        alert("No se pudo acceder a la cámara. Asegúrate de usar HTTPS.");
    });
}

/**
 * Detiene la cámara y limpia el contenedor
 */
function StopScanner() {
    if (!html5QrCode || !escaneando) {
        console.log("No hay escáner activo para detener.");
        return;
    }

    html5QrCode.stop()
        .then(() => {
            console.log("Scanner detenido.");
            escaneando = false;
            // Limpia el div para que no quede el cuadro negro
            const readerDiv = document.getElementById("reader");
            if (readerDiv) readerDiv.innerHTML = "";
            html5QrCode = null;
        })
        .catch(err => {
            console.error("Error al intentar detener:", err);
            // Reset forzado en caso de error
            escaneando = false;
            html5QrCode = null;
        });
}
</script>

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
}
.card-bt {
    background: #c1d4ff;
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 15px;
    border: 2px solid #240ef0;
    position: relative;

    display: flex;
    justify-content: center;  /* horizontal */
    align-items: center;      /* vertical */
    text-align: center;
}
.title {
    font-weight: bold;
}

.price {
    color: #333;
    margin-top: 5px;
}

.controls {
    position: absolute;
    right: 10px;
    top: 10px;
    display: flex;
    align-items: center;
}

.btn-circle {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    border: none;
    background: #f2a100;
    color: white;
    font-size: 18px;
    margin: 0 5px;
    cursor: pointer;
}

.qty {
    font-weight: bold;
}

/* BOTÓN FLOTANTE BUSCAR */
.fab-enviar {
    position: fixed;
    bottom: 90px; /* 👈 más arriba */
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
}

.fab-search {
    position: fixed;
    bottom: 20px; /* 👈 el de abajo */
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
}
h6 {
    color: #888;
    font-size: 15px;
}
.modal-body {
    max-height: 60vh;
    overflow-y: auto;
}
#resultados {
    max-height: 300px;
    overflow-y: auto;
}
</style>
@endpush

<div class="header">
    Documento de venta
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


<form id="formVenta" action="{{ route('ventas.storemobile') }}" method="POST">
    @csrf
    <input type="hidden" name="cliente_id" id="cliente_id" value="{{ $cliente_id }}">
    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">

    @foreach ($productos as $item)
    <div class="card"
            data-precio="{{ $item->precio_venta }}"
            data-id="{{ $item->id }}"
            id="producto-{{ $item->id }}">

            <div class="title">{{ $item->nombre." - ".$item->codigo }}</div>
            <div class="price">
                Precio: Q<span class="precio">{{ number_format($item->precio_venta, 2) }}</span>
            </div>

            <input type="hidden" name="arrayPrecioVenta[]" value="{{ $item->precio_venta }}">
            <input type="hidden" name="arrayidproducto[]" value="{{ $item->id }}">

            <div>{{ $item->descripcion }}</div>
            <h6>Existencia: {{ $item->stock }}</h6>

            <div class="controls">
                <button type="button" class="btn-circle btn-minus">-</button>
                <input type="number" class="qty" name="arraycantidad[]" value="0" min="0">
                <button type="button" class="btn-circle btn-plus">+</button>
            </div>

            <div class="price">
                Descuento Q.
                <input type="number" class="descuento" name="arrayDescuento[]" value="0" min="0">
            </div>

            <div class="price">
                Subtotal Q.
                <span class="subtotal">0.00</span>
                <input class="subtotalinput" type="hidden" name="Subtotal[]" value="0">
            </div>

    </div>
    @endforeach

        <div style="padding:15px; font-size:18px;">
        Total: Q <span id="totalGeneral">0.00</span>
        <input type="hidden" class="TotalGeneral" name="TotalGeneral" value="0">
        </div>

<button type="button" class="fab-enviar" id="guardar">
    💾
</button>

</form>
</div>


<!-- BOTÓN BUSCAR -->
<button class="fab-search" data-bs-toggle="modal" data-bs-target="#modalBuscar">
    🔍
</button>

<!-- MODAL -->
<div class="modal fade" id="modalBuscar">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5>Buscar producto</h5>
            </div>

            <div class="modal-body">
                <input type="text" id="buscarInput" class="form-control" placeholder="Buscar...">

                <div id="resultados"
                     style="margin-top:10px; max-height:300px; overflow-y:auto;">
                </div>
            </div>

        </div>
    </div>
</div>

<script>

document.getElementById("guardar").addEventListener("click", function () {

    let form = document.getElementById("formVenta");

    if (!form) {
        console.error("Form no encontrado");
        return;
    }

    form.method = "POST"; // 🔥 FORZAR POST
    form.action = "{{ route('ventas.storemobile') }}";

    form.submit();
});



    document.querySelectorAll(".card").forEach(card => {

    let btnPlus = card.querySelector(".btn-plus");
    let btnMinus = card.querySelector(".btn-minus");
    let qtyInput = card.querySelector(".qty");
    let descuentoInput = card.querySelector(".descuento");

    btnPlus.addEventListener("click", () => {
        qtyInput.value = parseInt(qtyInput.value || 0) + 1;
        calcular(card);
    });

    btnMinus.addEventListener("click", () => {
        let val = parseInt(qtyInput.value || 0) - 1;
        qtyInput.value = val < 0 ? 0 : val;
        calcular(card);
    });

    qtyInput.addEventListener("input", () => calcular(card));
    descuentoInput.addEventListener("input", () => calcular(card));



});

function calcular(card) {

    let precio = parseFloat(card.dataset.precio);
    let cantidad = parseInt(card.querySelector(".qty").value) || 0;
    let descuento = parseFloat(card.querySelector(".descuento").value) || 0;


    let subtotal = (precio * cantidad) - descuento;

    if (subtotal < 0) subtotal = 0;

    card.querySelector(".subtotal").innerText = subtotal.toFixed(2);
    card.querySelector(".subtotalinput").value = subtotal.toFixed(2);

    calcularTotalGeneral();
}

function changeQty(btn, value) {
    let qtyInput = btn.parentElement.querySelector(".qty");
    let qty = parseInt(qtyInput.value) || 0;

    qty += value;

    if (qty < 0) qty = 0;

    qtyInput.value = qty;
}

function calcularTotalGeneral() {
    let total = 0;

    document.querySelectorAll(".subtotal").forEach(el => {
        total += parseFloat(el.innerText) || 0;
    });

    document.getElementById("totalGeneral").innerText = total.toFixed(2);
    document.querySelector(".TotalGeneral").value = total.toFixed(2);
}

function cambiarCantidad(detalleId, cantidad) {

    fetch('/venta/actualizar-cantidad', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            detalle_id: detalleId,
            cantidad: cantidad
        })
    })
    .then(res => res.json())
    .then(data => {
        recargarDetalle();
    });
}

function agregarProducto(idProducto) {

    let card = document.getElementById(`producto-${idProducto}`);

    if (!card) {
        alert("Producto no está en la lista");
        return;
    }

    // 🔥 SCROLL SUAVE AL PRODUCTO
    card.scrollIntoView({
        behavior: "smooth",
        block: "center"
    });

    // 🔥 EFECTO VISUAL (resaltar)
    card.style.border = "3px solid #00c853";

    setTimeout(() => {
        card.style.border = "2px solid #f2a100";
    }, 1500);

    // 🔥 OPCIONAL: SUMAR 1 AUTOMÁTICAMENTE
    let qtyInput = card.querySelector(".qty");
    qtyInput.value = parseInt(qtyInput.value || 0) + 1;

    calcular(card);

    // 🔥 CERRAR MODAL
    let modal = bootstrap.Modal.getInstance(document.getElementById('modalBuscar'));
    modal.hide();
    qtyInput.focus();
}
let timeout = null;




document.getElementById("buscarInput").addEventListener("keyup", function() {

    clearTimeout(timeout);

    let texto = this.value;

    timeout = setTimeout(() => {

        route = "{{ route('vent.buscarmobile') }}";
        fetch(route + `?texto=${texto}`)
        .then(res => res.json())
        .then(data => {

            let html = "";

            data.forEach(p => {
                html += `
                    <div onclick="agregarProducto(${p.id})"
                        style="padding:10px; border-bottom:1px solid #ccc; cursor:pointer;">
                        ${p.nombre} - Q ${p.precio_venta}
                    </div>
                `;
            });

            document.getElementById("resultados").innerHTML = html;
        });

    }, 300);

});

</script>
@endsection
