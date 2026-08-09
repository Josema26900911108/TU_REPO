@extends('layouts.app')

@section('title', 'Perfil')

@push('css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    /* Asegurar que el canvas conserve puntero de dibujo suave */
    #canvasFirma {
        cursor: crosshair;
        touch-action: none; /* Bloquea el scroll del navegador al firmar en celulares */
    }
</style>
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="container-fluid">
    <h1 class="mt-4 mb-4 text-center">Configurar perfil</h1>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <p class="lead mb-0 text-dark">Configure y personalice su perfil</p>
        </div>
        <div class="card-body">
            <div class="">
                @if ($errors->any())
                    @foreach ($errors->all() as $item)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ $item }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endforeach
                @endif
            </div>

            <form action="{{ route('profile.update', ['profile' => $user]) }}" method="POST">
                @method('PATCH')
                @csrf

                <!----Nombre---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                            <input disabled type="text" class="form-control" value="Nombres">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input autocomplete="off" type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}">
                    </div>
                </div>

                <!----Email---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                            <input disabled type="text" class="form-control" value="Email">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input autocomplete="off" type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}">
                    </div>
                </div>

                <!----Contraseña---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input disabled type="text" class="form-control" value="Contraseña">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Dejar en blanco para no modificar">
                    </div>
                </div>

                <!----Tienda---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input disabled type="text" class="form-control" value="Organización">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" readonly name="tienda" id="tienda" class="form-control" value="{{ $tienda->Nombre }}">
                    </div>
                </div>

                <!----Centro Organización---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input disabled type="text" class="form-control" value="Centro Organización">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" readonly name="centro" id="centro" class="form-control" value="{{ $centro->codigo }}">
                    </div>
                </div>

                <!---- 🌟 NUEVA SECCIÓN: CAPTURA DE FIRMA DIGITAL 🌟 ---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-signature"></i></span>
                            <input disabled type="text" class="form-control" value="Firma Digital">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <button type="button" class="btn btn-outline-dark w-100" data-bs-toggle="modal" data-bs-target="#modalFirma">
                            <i class="fas fa-pen me-2"></i> Capturar o Cambiar Firma
                        </button>

                        <!-- Input Oculto nativo para trasladar la firma en el Submit general -->
                        <input type="hidden" name="firma_base64" id="firma_base64" value="{{ old('firma_base64', $user->firma ? 'data:image/png;base64,'.$user->firma : '') }}">

                        <!-- Contenedor adaptativo de Vista Previa -->
                        <div id="vistaPreviaFirma" class="mt-3 text-center {{ $user->firma ? '' : 'd-none' }}">
                            <small class="text-muted d-block mb-1 font-bold">Firma Registrada en el Perfil:</small>
                            <img id="imgFirma" src="{{ $user->firma ? 'data:image/png;base64,'.$user->firma : '' }}" class="img-thumbnail bg-white" style="max-height: 120px; width: auto;">
                        </div>
                    </div>
                </div>

                <div class="col text-center mt-5">
                    <button class="btn btn-success px-4" type="submit">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL FULLSCREEN DE CAPTURA CALIGRÁFICA                  -->
<!-- ======================================================== -->
<div class="modal fade" id="modalFirma" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalFirmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen m-0 w-100 h-100" style="max-width: 100%;">
        <div class="modal-content min-vh-100 border-0 rounded-0 d-flex flex-column">
            
            <div class="modal-header bg-dark text-white border-0 rounded-0 py-3">
                <h5 class="modal-title" id="modalFirmaLabel">
                    <i class="fas fa-signature me-2"></i> Panel de Firma Caligráfica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body bg-light text-center d-flex flex-column flex-grow-1 p-2">
                <p class="small text-muted mb-2">Por favor, dibuje su firma digital dentro del recuadro blanco:</p>
                <div class="wrapper-canvas border rounded bg-white shadow-sm flex-grow-1 w-100" style="position: relative;">
                    <canvas id="canvasFirma" style="position: absolute; left: 0; top: 0; width: 100%; height: 100%;"></canvas>
                </div>
            </div>
            
            <div class="modal-footer d-flex justify-content-between border-0 rounded-0 bg-white py-3">
                <button type="button" class="btn btn-secondary btn-lg" id="btnLimpiarFirma">
                    <i class="fas fa-eraser me-1"></i> Limpiar Lienzo
                </button>
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-lg me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-lg" id="btnGuardarFirma">
                        <i class="fas fa-check me-1"></i> Confirmar Firma
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // 1. Inicialización de referencias del DOM basados en tu estructura HTML
    const canvas = document.getElementById("canvasFirma");
    const ctx = canvas.getContext("2d");
    const modalElement = document.getElementById("modalFirma");
    const btnLimpiar = document.getElementById("btnLimpiarFirma");
    const btnGuardar = document.getElementById("btnGuardarFirma");
    
    const inputFirmaBase64 = document.getElementById("firma_base64");
    const vistaPreviaFirma = document.getElementById("vistaPreviaFirma");
    const imgFirma = document.getElementById("imgFirma");

    let dibujando = false;

    // Instancia nativa del modal de Bootstrap 5 para controlar su cierre programático
    const bootstrapModal = new bootstrap.Modal(modalElement);

    // 2. Ajustar de forma exacta la resolución interna del Canvas al contenedor real
    function ajustarDimensionesCanvas() {
        const rect = canvas.parentElement.getBoundingClientRect();
        // Evitamos el parpadeo o estiramiento pixelado de líneas definiendo el ancho interno
        canvas.width = rect.width;
        canvas.height = rect.height;
        
        // Estilos del trazo de firma caligráfica suave
        ctx.strokeStyle = "#000000"; // Tinta negra de alta fidelidad
        ctx.lineWidth = 3;            // Grosor óptimo para pantallas táctiles y escritorios
        ctx.lineCap = "round";        // Bordes de trazo circulares
        ctx.lineJoin = "round";       // Uniones suavizadas sin picos rectos
    }

    // Escuchar el evento de Bootstrap cuando el modal se termina de desplegar en pantalla
    modalElement.addEventListener("shown.bs.modal", function () {
        ajustarDimensionesCanvas();
    });

    // Escuchar cambios de orientación de pantalla de celulares (vertical/horizontal)
    window.addEventListener("resize", () => {
        if (modalElement.classList.contains("show")) {
            ajustarDimensionesCanvas();
        }
    });

    // 3. Capturar coordenadas relativas de precisión (Soporte mouse y pantallas táctiles)
    function obtenerPosicion(e) {
        const rect = canvas.getBoundingClientRect();
        // Validar si el evento proviene de un dedo (Touch) o de un puntero (Mouse)
        const clienteX = e.touches ? e.touches[0].clientX : e.clientX;
        const clienteY = e.touches ? e.touches[0].clientY : e.clientY;
        
        return {
            x: clienteX - rect.left,
            y: clienteY - rect.top
        };
    }

    // 4. Lógica operativa de dibujo en Canvas
    function iniciarDibujo(e) {
        dibujando = true;
        const pos = obtenerPosicion(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        e.preventDefault(); // Detener el desplazamiento accidental de la pantalla en móviles
    }

    function trazar(e) {
        if (!dibujando) return;
        const pos = obtenerPosicion(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        e.preventDefault();
    }

    function detenerDibujo() {
        dibujando = false;
    }

    // Disparadores para Mouse (Computadoras de escritorio / Laptops)
    canvas.addEventListener("mousedown", iniciarDibujo);
    canvas.addEventListener("mousemove", trazar);
    window.addEventListener("mouseup", detenerDibujo);

    // Disparadores para eventos táctiles (Celulares / Tablets)
    canvas.addEventListener("touchstart", iniciarDibujo, { passive: false });
    canvas.addEventListener("touchmove", trazar, { passive: false });
    canvas.addEventListener("touchend", detenerDibujo);

    // 5. Botón Limpiar: Resetea el lienzo a blanco puro
    btnLimpiar.addEventListener("click", function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    // 6. Botón Confirmar Firma: Transfiere la firma al input oculto y activa la vista previa
    btnGuardar.addEventListener("click", function () {
        // Validar si el canvas está completamente vacío antes de proceder
        const lienzoVacio = !ctx.getImageData(0, 0, canvas.width, canvas.height).data.some(channel => channel !== 0);
        
        if (lienzoVacio) {
            alert("Por favor, estampe su firma antes de confirmar.");
            return;
        }

        // Extraer la cadena de texto pura Base64 (formato PNG transparente nativo)
        const dataUrlFirma = canvas.toDataURL("image/png");

        // PASO CLAVE: Inyectamos la firma en el input oculto para que viaje con el formulario principal
        inputFirmaBase64.value = dataUrlFirma;

        // Desplegar la miniatura de confirmación visual al usuario en la pantalla base
        imgFirma.src = dataUrlFirma;
        vistaPreviaFirma.classList.remove("d-none");

        // Cerrar la ventana fullscreen del modal programáticamente de forma segura
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        modalInstance.hide();
    });
});

</script>
@endpush
