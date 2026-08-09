@extends('layouts.app')

@section('title', 'Editar tienda')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
    /* Asegurar que el canvas conserve puntero de dibujo suave */
    #canvasFirma {
        cursor: crosshair;
        touch-action: none; /* Bloquea el scroll del navegador al firmar en celulares */
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Tienda</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tienda.index')}}">Tienda</a></li>
        <li class="breadcrumb-item active">Editar Tienda</li>
    </ol>

    <div class="card shadow-sm">
        <form action="{{ route('tienda.update', ['tienda' => $tienda]) }}" method="post" enctype="multipart/form-data">
            @method('PATCH')
            @csrf
            
            <div class="card-body text-bg-light">
                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <label for="Nombre" class="form-label fw-semibold">Nombre:</label>
                        <input type="text" name="Nombre" id="Nombre" class="form-control" value="{{ old('Nombre', $tienda->Nombre) }}">
                        @error('Nombre')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="Telefono" class="form-label fw-semibold">Telefono:</label>
                        <input type="text" name="Telefono" id="Telefono" class="form-control" value="{{ old('Telefono', $tienda->Telefono) }}">
                        @error('Telefono')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <label for="Direccion" class="form-label fw-semibold">Dirección:</label>
                        <input type="text" name="Direccion" id="Direccion" class="form-control" value="{{ old('Direccion', $tienda->Direccion) }}">
                        @error('Direccion')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="descripcion" class="form-label fw-semibold">Descripción:</label>
                        <input type="text" name="descripcion" id="descripcion" class="form-control" value="{{ old('descripcion', $tienda->descripcion) }}">
                        @error('descripcion')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row g-4 mb-3">
                    <div class="col-md-6">
                        <label for="image" class="form-label fw-semibold">Logo:</label>
                        <input type="file" name="image" id="image" accept="image/*" class="form-control">
                        @error('image')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="centro" class="form-label fw-semibold">Centro:</label>
                        <select class="form-select" name="centro" id="centro" required>
                            <option value="" selected disabled>Seleccione una opción</option>
                            @foreach ($centros as $item)
                                <option value="{{ $item->id }}" 
                                    {{ (old('centro') == $item->id || $tienda->fkCentro == $item->id) ? 'selected' : '' }}>
                                    {{ $item->codigo }} - {{ $item->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('centro')
                            <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                </div>
                <!---- SECCIÓN INTEGRADA: FIRMA DEL REPRESENTANTE LEGAL ---->
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Firma del Representante:</label>
                        <button type="button" class="btn btn-outline-dark w-100 form-control" data-bs-toggle="modal" data-bs-target="#modalFirma">
                            <i class="fas fa-signature me-2"></i> Capturar / Cambiar Firma del Representante
                        </button>

                        <!-- Input oculto para transportar la firma en Base64 -->
                        <input type="hidden" name="firma_base64" id="firma_base64" value="{{ old('firma_base64', $tienda->firma_representante ? 'data:image/png;base64,'.$tienda->firma_representante : '') }}">

                        <!-- Contenedor adaptativo de Vista Previa -->
                        <div id="vistaPreviaFirma" class="mt-3 text-center {{ $tienda->firma_representante ? '' : 'd-none' }}">
                            <small class="text-muted d-block mb-1">Firma Registrada:</small>
                            <img id="imgFirma" src="{{ $tienda->firma_representante ? 'data:image/png;base64,'.$tienda->firma_representante : '' }}" class="img-thumbnail bg-white" style="max-height: 120px; width: auto;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-center">
                <button type="submit" class="btn btn-primary px-4">Actualizar</button>
                <button type="reset" class="btn btn-secondary px-4">Reiniciar</button>
            </div>
        </form>
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
                    <i class="fas fa-signature me-2"></i> Captura de Firma del Representante Legal
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body bg-light text-center d-flex flex-column flex-grow-1 p-2">
                <p class="small text-muted mb-2">Por favor, estampe la firma del representante dentro del recuadro blanco:</p>
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
<script src="https://jsdelivr.net"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("canvasFirma");
    const ctx = canvas.getContext("2d");
    const modalElement = document.getElementById("modalFirma");
    const btnLimpiar = document.getElementById("btnLimpiarFirma");
    const btnGuardar = document.getElementById("btnGuardarFirma");
    
    const inputFirmaBase64 = document.getElementById("firma_base64");
    const vistaPreviaFirma = document.getElementById("vistaPreviaFirma");
    const imgFirma = document.getElementById("imgFirma");

    let dibujando = false;

    // Calcular dimensiones internas del canvas basándose en su contenedor físico real
    function ajustarDimensionesCanvas() {
        const rect = canvas.parentElement.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
        
        // Estilos caligráficos del trazo
        ctx.strokeStyle = "#000000"; 
        ctx.lineWidth = 3;            
        ctx.lineCap = "round";        
        ctx.lineJoin = "round";       
    }

    // Adaptar el lienzo cada vez que se abre el modal o cambia el tamaño de pantalla
    modalElement.addEventListener("shown.bs.modal", function () {
        ajustarDimensionesCanvas();
    });

    window.addEventListener("resize", () => {
        if (modalElement.classList.contains("show")) {
            ajustarDimensionesCanvas();
        }
    });

    // Obtener coordenadas de precisión exactas relativas al canvas (Mouse y Pantallas Táctiles)
    function obtenerPosicion(e) {
        const rect = canvas.getBoundingClientRect();
        const clienteX = e.touches ? e.touches[0].clientX : e.clientX;
        const clienteY = e.touches ? e.touches[0].clientY : e.clientY;
        
        return {
            x: clienteX - rect.left,
            y: clienteY - rect.top
        };
    }

    function iniciarDibujo(e) {
        dibujando = true;
        const pos = obtenerPosicion(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        if (e.touches) e.preventDefault(); // Detiene el scroll accidental en móviles al dibujar
    }

    function trazar(e) {
        if (!dibujando) return;
        const pos = obtenerPosicion(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        if (e.touches) e.preventDefault();
    }

    function detenerDibujo() {
        dibujando = false;
    }

    // Escuchadores para Mouse (Escritorio)
    canvas.addEventListener("mousedown", iniciarDibujo);
    canvas.addEventListener("mousemove", trazar);
    window.addEventListener("mouseup", detenerDibujo);

    // Escuchadores Táctiles (Celulares / Tablets)
    canvas.addEventListener("touchstart", iniciarDibujo, { passive: false });
    canvas.addEventListener("touchmove", trazar, { passive: false });
    canvas.addEventListener("touchend", detenerDibujo);

    // Botón Limpiar: Resetea el lienzo a blanco
    btnLimpiar.addEventListener("click", function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    // Botón Confirmar: Pasa los datos al input oculto y cierra modal
    btnGuardar.addEventListener("click", function () {
        // Validar si el canvas está vacío leyendo el mapa de bits
        const lienzoVacio = !ctx.getImageData(0, 0, canvas.width, canvas.height).data.some(channel => channel !== 0);
        
        if (lienzoVacio) {
            Swal.fire({
                icon: 'warning',
                title: 'Lienzo Vacío',
                text: 'Por favor, estampe la firma antes de confirmar.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const dataUrlFirma = canvas.toDataURL("image/png");

        // Almacenamos el String Base64 transparente completo en el input oculto
        inputFirmaBase64.value = dataUrlFirma;
        imgFirma.src = dataUrlFirma;
        vistaPreviaFirma.classList.remove("d-none");

        // Cerrar modal vía JS nativo de Bootstrap 5
        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        modalInstance.hide();
    });
});
</script>
@endpush
