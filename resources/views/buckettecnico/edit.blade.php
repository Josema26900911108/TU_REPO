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
        <form id="formulario" action="{{ route('tecnico.operartrabajo', ['tecnico' => $tecnico, 'expediente' => $orden]) }}"
      method="POST" enctype="multipart/form-data">
            @method('PATCH')
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
                        <select name="itemtecnologia" id="itemtecnologia" class="form-control selectpicker"
                                data-live-search="true" data-size="10">

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
                        <select name="itemmanoobra" id="itemmanoobra" class="form-control selectpicker"
                                data-live-search="true" data-size="10">

                        </select>
                        @error('itemmanoobra')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                </div>

<!-- Árbol -->
<div class="row mb-6">
    <div class="col-lg-12 text-center">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Asignado</th>
                    <th>Seleccionar</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <input type="hidden" name="nodoSeleccionado" id="nodoSeleccionado">
                                        <!-- Select Mano de Obra -->
                <div class="row mb-4">
                    <label for="itemmanoobraamterial" class="col-lg-2 col-form-label">Seleccione Item:</label>
                    <div class="col-lg-6">

                        <select name="itemmanoobraamterial" id="itemmanoobraamterial" class="form-control selectpicker"
                                data-live-search="true" data-size="10">
                        </select>
                        @error('itemmanoobraamterial')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>
                </div>

                <!-- Cámara -->
                <h5>Tomar Foto con la Cámara</h5>
                <video id="video" width="300" height="200" autoplay></video>
                <br>
                <button id="btnCapture" type="button" class="btn btn-primary">📸 Tomar Foto</button>
                <button id="btnOk" type="button" class="btn btn-success" style="display:none;">✅ OK</button>
                <select name="categoriafoto" id="categoriafoto">
                    <option value="ANTES">ANTES</option>
                    <option value="DESPUES">DESPUES</option>
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
                <div id="preview"></div>

                                        <!-----Precio de venta---->
                        <div class="col-sm-4 mb-2">
                            <label for="cantidad" class="form-label">Cantidad:</label>
                            <input type="number" name="cantidad" id="cantidad" class="form-control" step="1" min="1" value="1">
                        </div>

                        <!-----botón para agregar--->
                        <div class="col-12 mb-4 mt-2 text-end">
                            <button type="button"  id="btn_agregar" class="btn btn-primary" type="button">Agregar</button>
                        </div>

                    </td>
                    <td>
                        <div id="treeview-seleccionar" class="treeview"></div>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div id="materialesList">
                            <h5>Materiales utilizados</h5>
                        </div>
                    </td>
                </tr>
                <tr></tr>
                    <td colspan="2">
                                <table class="table table-hover">
                                    <thead class="bg-info">
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
                    </td>
                </tr>
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
                @if ($orden->Status == 'I')
                            <div class="card-footer text-center">
                                <button type="submit" onclick="prepareForm()" class="btn btn-primary">Actualizar</button>
                            </div>
                @endif

        </form>
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


// Iniciar cámara
async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true });
        document.getElementById('video').srcObject = stream;
    } catch (err) {
        alert("No se pudo acceder a la cámara: " + err);
    }
}
startCamera();

 // Tomar foto
$('#btnCapture').click(function() {
    const video = document.getElementById('video');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL("image/png");
    const timestamp = Date.now();
    const photoName = `foto_${timestamp}.png`;
    const categoriafoto = $('#categoriafoto').val();
    const itemname = $('#itemmanoobraamterial').text();



    photosForItem.push({ name: "{{ $orden->Orden.'_'.$tecnico->codigo.'_' }}"+categoriafoto, data: dataUrl });
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

function prepareForm() {
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

        // Fotos Base64
        item.photos.forEach((photo, pidx) => {
            $('<input>').attr({
                type: 'hidden',
                name: `items[${idx}][photos][${pidx}]`,
                value: photo.data
            }).appendTo('#formulario');
        });

        item.photos.forEach((photo, pidx) => {
            $('<input>').attr({
                type: 'hidden',
                name: `items[${idx}][names][${pidx}]`,
                value: photo.name
            }).appendTo('#formulario');
        });
    });
}


    function eliminarProducto(indice) {

            //Eliminar el fila de la tabla
            $('#fila' + indice).remove();
            $(`input[name^='arrayfotos[${indice}]']`).remove();

        };

    function llenaritems() {

                id = "{{ $id3 ?? 0 }}";


        $.ajax({
            url: "{{ route('inventariolistadetalles') }}",
            method: 'GET',
            data: { parametros: id },
            success: function(response) {

                response.forEach(function(material) {
                   let fila = '<tr>' +
                            '<td>' + material.id + '</td>' +
                            '<td>' + material.cantidad + '</td>' +
                            '<td>' + material.Descripcion + '</td>' +
                            '<td>' + material.sku + '</td>' +
                            '<td>' + material.serie + '</td>' +
                            '</tr>';
                    $('#detalle_tbody').append(fila);

                });
            },
            error: function(xhr) {
                Swal.fire('Error', 'No se pudieron cargar los materiales: ' + xhr.responseText, 'error');
            }
        });

        }


        function agregarItem() {
            //Obtener valores de los campos
            let idItem = $('#itemmanoobraamterial').val();
            let nameProductoTXT = ($('#itemmanoobraamterial option:selected').text()).split('||')[0];
            let nameProducto = nameProductoTXT.split(': ')[1];
            let nameserieTXT = ($('#itemmanoobraamterial option:selected').text()).split('||')[1];
            let nameserie = nameserieTXT.split(': ')[1];
            let skutxt = ($('#itemmanoobraamterial option:selected').text()).split('||')[3];
            let sku = skutxt.split(': ')[1];
            let cantidad = $('#cantidad').val();

            //1.Para que los campos no esten vacíos
            if (idItem != '' && nameProducto != undefined && cantidad != '' ) {

                //2. Para que los valores ingresados sean los correctos
                if (parseInt(cantidad) > 0 && (cantidad % 1 == 0)) {

                            let item = {
            idItem,
            nameProducto,
            cantidad,
            nameserie,
            photos: [...photosForItem] // guardamos fotos del item
        };

        allItems.push(item);


                        //Crear la fila
                        let fila = '<tr id="fila' + cont + '">' +
                            '<td><input type="hidden" name="arrayiditem[]" value="' + idItem + '">' + idItem + '</td>' +
                            '<td><input type="hidden" name="arraycantidad[]" value="' + cantidad + '">' + cantidad + '</td>' +
                            '<td><input type="hidden" name="arraynameProducto[]" value="' + nameProducto + '">' + nameProducto + '</td>' +
                            '<td><input type="hidden" name="arraysku[]" value="' + sku + '">' + sku + '</td>' +
                            '<td><input type="hidden" name="arrayserie[]" value="' + nameserie + '">' + nameserie + '</td>' +
                            '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                            '</tr>';

                        //Acciones después de añadir la fila
                        $('#detalle_tbody').append(fila);

                            // Limpiar fotos y cámara para siguiente ítem
    photosForItem = [];
    $('#preview').html('');
    $('#video').show();
    $('#btnCapture').show();
    $('#btnOk').hide().text('✅ OK').prop('disabled', false);
    $('#btnRetry').hide();

    cont++;

                } else {
                    showModal('Valores incorrectos');
                        }

        }

        }

$(document).ready(function () {


//boton para agregar materiales a utilizarse
            $('#btn_agregar').click(function() {
                agregarItem();
            });


    // Inicializas los selectpicker
    $('#itemtecnologia, #itemmanoobra').selectpicker();
fill_estructura();
    // Evento para llenar mano de obra según tecnología
    $("#itemtecnologia").off('change').on('change', function () {
        const valor = $(this).val();
        if (valor) {
            fill_manoobra(valor);
        }
    });

    // Evento para llenar árbol según mano de obra seleccionada
    $("#itemmanoobra").off('change').on('change', function () {
        const valor = $(this).val();
        if (valor) {
            fill_treeview(valor);
        }
    });

llenaritems();
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


    // Función para cargar mano de obra (select)
    function fill_manoobra(id) {
        $.ajax({
            url: "{{ url('manoobracategoria') }}/" + id,
            method: "GET",
            success: function (data) {
                $('#itemmanoobra').selectpicker('destroy');
                let optionss = `<option value="" disabled selected>Seleccione una opción</option>`;
                data.forEach(function (manoobra) {
                    optionss += `<option value="${manoobra.id}">${manoobra.nombre}</option>`;
                });
                $('#itemmanoobra').html(optionss);
                $('#itemmanoobra').selectpicker();
            },
            error: function (xhr) {
                Swal.fire('Error', 'Hubo un problema al cargar las opciones: ' + xhr.responseText, 'error');
            }
        });
    }

    // Función para listar materiales según categoría (idNodo)
    function listar_materiales_por_categoria(idNodo) {
        // Aquí haces tu llamada AJAX o lógica para cargar la lista de materiales
        console.log('Listar materiales para categoría con Cid:', idNodo);
    let id2 = {{ $tecnico->id }};
        // Ejemplo básico de llamada AJAX (ajusta la URL y manejo según tu backend)
        $.ajax({
            url: "{{ route('inventariolista')}}",
            data: { id1: idNodo, id2: id2 },
            method: 'GET',
            success: function(materiales) {

                // Aquí puedes manejar la respuesta y actualizar tu UI
                console.log('Materiales cargados:', materiales);
                console.log(materiales);
                console.log(Array.isArray(materiales));
                let materialesArray = Object.values(materiales);
                $('#itemmanoobraamterial').selectpicker('destroy');
                    let optionss = '<option value="">Seleccione un material</option>';
                    materialesArray.forEach(function(material) {
                        optionss += `<option value="${material.id}">DESCRIP: ${material.categoria_nombre} || SERIE: ${material.serie} || CANTIDAD: ${material.cantidad} || SKU: ${material.sku}</option>`;
                    });

                    document.getElementById("itemmanoobraamterial").innerHTML = optionss;

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




</script>
@endpush
