@extends('layouts.app')

@section('title','Crear Producto')

@push('css')
<style>
    #descripcion {
        resize: none;
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

</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
<script>
    let scanner = null;
let escaneando = false;

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

            const inputCodigo = document.getElementById("codigo");
    if (inputCodigo) {
        inputCodigo.value = codigo;
        console.log("Valor asignado al input");
    }


            StopScanner();

                    Swal.fire({
    icon: 'warning',
    title: 'Se ha seleccionado un producto',
    text: 'Codigo: ' + codigo,

});




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


</script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Producto</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productos.index')}}">Productos</a></li>
        <li class="breadcrumb-item active">Crear producto</li>
    </ol>
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

    <div class="card">
        <form action="{{ route('productos.store') }}" method="post" enctype="multipart/form-data">
            @csrf

    <!-- 💡 AGREGA ESTE BLOQUE TEMPORALMENTE PARA VER EL ERROR REAL -->
    @if ($errors->any())
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin-bottom: 20px; border-radius: 4px;">
            <strong>No se pudo guardar el producto debido a los siguientes errores:</strong>
            <ul style="margin-top: 5px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
            <div class="card-body text-bg-light">


                <div class="row g-4">

<!----Codigo---->
<div class="col-md-4">
    <label for="codigo" class="form-label">Código:</label>
    <div class="input-group">
        <input type="text" name="codigo" id="codigo" class="form-control" value="{{old('codigo')}}">
        <!-- 💡 AGREGA EXPLICITAMENTE type="button" AQUÍ -->
        <button type="button" id="btn-generar-codigo" class="btn btn-secondary">Generar</button>
    </div>
    @error('codigo')
    <small class="text-danger">{{'*'.$message}}</small>
    @enderror
</div>



                    <!---Nombre---->
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" value="{{old('nombre')}}">
                        @error('nombre')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

<div class="col-md-2">
    <div class="form-check form-switch mt-4">
        <!-- 💡 ELIMINA EL INPUT HIDDEN POR COMPLETO -->

        <!-- Deja únicamente el checkbox -->
        <input class="form-check-input" type="checkbox"
            name="perecedero"
            id="perecedero"
            value="1"
            {{ old('perecedero', $producto->perecedero ?? 0) == 1 ? 'checked' : '' }}>

        <label class="form-check-label" for="perecedero">¿Es perecedero?</label>
    </div>
</div>




                    <!---Descripción---->
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción:</label>
                        <textarea name="descripcion" id="descripcion" rows="3" class="form-control">{{old('descripcion')}}</textarea>
                        @error('descripcion')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>


                    <!---Imagen---->
                    <div class="col-md-6">
                        <label for="img_path" class="form-label">Imagen:</label>
                        <input type="file" name="img_path" id="img_path" class="form-control" accept="image/*">
                        @error('img_path')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Marca---->
                    <div class="col-md-6">
                        <label for="marca_id" class="form-label">Marca:</label>
                        <select data-size="4" title="Seleccione una marca" data-live-search="true" name="marca_id" id="marca_id" class="form-control selectpicker show-tick">
                            @foreach ($marcas as $item)
                            <option value="{{$item->id}}" {{ old('marca_id') == $item->id ? 'selected' : '' }}>{{$item->nombre}}</option>
                            @endforeach
                        </select>
                        @error('marca_id')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Presentaciones---->
                    <div class="col-md-6">
                        <label for="presentacione_id" class="form-label">Presentación:</label>
                        <select data-size="4" title="Seleccione una presentación" data-live-search="true" name="presentacione_id" id="presentacione_id" class="form-control selectpicker show-tick">
                            @foreach ($presentaciones as $item)
                            <option value="{{$item->id}}" {{ old('presentacione_id') == $item->id ? 'selected' : '' }}>{{$item->nombre}}</option>
                            @endforeach
                        </select>
                        @error('presentacione_id')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Categorías---->
                    <div class="col-md-6">
                        <label for="categorias" class="form-label">Categorías:</label>
                        <select data-size="4" title="Seleccione las categorías" data-live-search="true" name="categorias[]" id="categorias" class="form-control selectpicker show-tick" multiple>
                            @foreach ($categorias as $item)
                            <option value="{{$item->id}}" {{ (in_array($item->id , old('categorias',[]))) ? 'selected' : '' }}>{{$item->nombre}}</option>
                            @endforeach
                        </select>
                        @error('categorias')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="card-footer text-center">
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>


</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script>
$('#btn-generar-codigo').on('click', function(e) {
    // 1. Detiene de golpe cualquier intento del formulario de recargar la página
    e.preventDefault(); 
    e.stopPropagation();

    $.ajax({
        // 2. Apunta directo a la ruta del código único (puedes usar tu URL actual)
        url: '/ajax-interno/generar-codigo-barras-unico',
        type: 'GET',
        success: function(response) {
            console.log("Respuesta del servidor:", response);
            
            // Evaluamos la respuesta de forma segura
            var datos = (typeof response === 'object') ? response : JSON.parse(response.trim());
            
            if (datos && datos.codigo) {
                // 3. Pintamos el valor en el input
                $('#codigo').val(datos.codigo);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al procesar AJAX:", status, error);
            console.error("Respuesta del servidor:", xhr.responseText);
        }
    });
});


    </script>
@endpush
