
@extends('layouts.app')


@section('title', 'Árbol de Materiales y Manos de obra')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
@if (session('success'))
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let message = "{{ session('success') }}";
        Swal.fire(message);
    });
</script>
@endif

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<link rel="stylesheet" href="{{ asset('css/bootstrap-treeview.css') }}">
<link rel="stylesheet" href="{{ asset('css/bootstrap-treeview.min.css') }}">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <style>
    .custom-file-input {
      display: none;
    }
    .custom-upload-btn {
      cursor: pointer;
    }
  </style>
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

  
<style>
    /* Estilos para el arrastre */
    .ui-draggable-dragging {
        z-index: 9999 !important;
        opacity: 0.7;
        background-color: #f8f9fa;
        border: 2px dashed #007bff;
        padding: 5px;
        border-radius: 4px;
    }

    .treeview li.drag-over {
        background-color: #e9ecef;
        border: 2px dashed #28a745;
    }

    .treeview li.drop-allowed::after {
        content: "✓ Puede soltar aquí";
        color: #28a745;
        font-size: 12px;
        margin-left: 10px;
    }

    .treeview li.drop-not-allowed::after {
        content: "✗ No puede soltar aquí";
        color: #dc3545;
        font-size: 12px;
        margin-left: 10px;
    }

    /* Estilo para el nodo que se está arrastrando */
    .dragging-node {
        cursor: move !important;
        user-select: none;
    }

    /* Indicador visual de destino válido */
    .drop-target {
        background-color: #d1ecf1 !important;
        border-left: 3px solid #17a2b8 !important;
    }
</style>
<style>
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

@push('scripts')


<script src="https://unpkg.com/gijgo@1.9.14/js/gijgo.min.js" type="text/javascript"></script>

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

@endpush
<div class="container">

    <div class="row justify-content-center">
    <h2 align="center">Árbol de Materiales y Manos de obra</h2>
    <h4 align="center">arbol mano obra</h4>
    <br /><br />

        <ul  id="context">
        <li><a href="#" id="createChildMasivaHijosPadres">Cargar Masivo General</a></li>
    </ul>
    <div class="row justify-content-center ">


        <div class="menu">
            <div class="accordion">
            <h3 align="center"><u>Árbol de Materiales y Manos de obra</u></h3>
            <br />
            <div id="treeview" class="treeview"></div>
            </div>
        </div>
    </div>
    

    <!-- Menú Contextual -->
    <ul id="contextMenu" class="dropdown-menu">
        <li><a href="#" id="editNode">Editar</a></li>
        <li><a href="#" id="deleteNode">Eliminar</a></li>
        <li><a href="#" id="createChildNode">Nuevo</a></li>
    </ul>

           <!-- Modal -->
    <div class="modal fade" id="modalDelete" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Eliminar Cuenta</h5>

                </div>
                <div class="modal-body">

                <form method="post" id="treeview_form_delete">
                    @csrf
                        <p id="cidValue">El Cid es: </p>
                            <input type="hidden" id="id_delete" name="id_delete" class="form-control">
                        <div class="form-group">
                            <label for="formula">Cuenta</label>
                            <input readonly="true" type="text" name="cuenta_id_delete" id="cuenta_id_delete" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre Cuenta</label>
                            <input readonly="true" type="text" name="nombre_delete" id="nombre_delete" class="form-control">
                        </div>
                    <div class="form-group">
                        <label for="ts_new">Tipo Servicio</label>
                        <input readonly="true" type="text" name="ts_delete" id="ts_delete" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="to_delete">Tipo Orden</label>
                        <input readonly="true" type="text" name="to_delete" id="to_delete" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="obs_delete">Observaciones</label>
                        <textarea readonly="true" name="obs_delete" id="obs_delete" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="af_delete">Fotografia</label>

                        <select readonly="true" class="form-control" name="af_delete" id="af_delete">
                            <option value="SI">SI</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cerrardel" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" form="treeview_form_delete">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

       <!-- Modal -->
    <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Editar Cuenta</h5>

                </div>
                <div class="modal-body">

                <form method="post" id="treeview_form_edita">
                    @csrf
                        <p id="cidValue">El Cid es: </p>
                            <input type="hidden" id="id_edit" name="id_edit" class="form-control">
                        <div class="form-group">
                            <label for="formula">Cuenta</label>
                            <input type="text" name="cuenta_id_edit" id="cuenta_id_edit" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre Cuenta</label>
                            <input type="text" name="nombre_edit" id="nombre_edit" class="form-control">
                        </div>

                  <div class="form-group">
                        <label for="ts_edit">Tipo Servicio</label>
                        <select name="ts_edit" id="ts_edit" class="form-control"><
                            <option value="MA">MATERIAL</option>
                            <option value="MO">MANO DE OBRA</option>
                  </select>
                    </div>

                    <div class="form-group">
                        <label for="to_edit">Tipo Orden</label>
                        <input type="text" name="to_edit" id="to_edit" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="obs_edit">Observaciones</label>
                        <textarea name="obs_edit" id="obs_edit" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="af_edit">Fotografia</label>

                        <select class="form-control" name="af_edit" id="af_edit">
                            <option value="SI">SI</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cerrar" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" form="treeview_form_edita">Enviar</button>
                </div>
            </div>
        </div>
    </div>

               <!-- Modal Masivo Hijos POR PADRE-->
    <div class="modal fade" id="modalMasivopadre" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Subir Masivo Hijos por Padres</h5>

                </div>
                <div class="modal-body">

  <form action="{{ route('arbolmamo.importarhijospadres') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <button type="submit" class="btn btn-success">Subir</button>
    <label for="archivohijospadres" class="btn btn-primary custom-upload-btn">
      <i class="fa fa-upload"></i>
    </label>
        <a href="{{route('arbolmamo.formetahijospadres')}}">
            <button type="button" class="fa fa-download">descargar formato</button>
        </a>

    <input type="file" id="archivohijospadres" name="archivohijospadres" class="custom-file-input" onchange="mostrarNombrehijoPadre(this)">
    <span id="nombre-archivohijospadres" class="ml-2 text-muted">Ningún archivo seleccionado</span>


  </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cerrardel" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>    

<!-- Modal -->
<div class="modal fade" id="modalnew" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalLabel">Agregar Cuenta</h5>

            </div>
            <div class="modal-body">
                <form method="post" id="treeview_form">
                    @csrf

                    <div class="form-group">
                        <label for="formula">Cuenta Padre</label>
                        <input type="text" id="padre_id" name="padre_id" class="form-control"></div>
                    <div class="form-group">
                        <label for="formula">SKU</label>

                        <input type="text" name="cuenta_id_new" id="cuenta_id_new" class="form-control" pattern="\d{2}\.\d{2}\.\d{2}\.\d{2}" title="El formato debe ser ##.##.##.##">
                    </div>
                    <div class="form-group">
                        <label for="nombre">Nombre Material</label>
                        <input type="text" name="nombre_new" id="nombre_new" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="ts_new">Tipo Servicio</label>
                        <select name="ts_new" id="ts_new" class="form-control">
                            <option value="MA">MATERIAL</option>
                            <option value="MO">MANO DE OBRA</option>
                  </select>
                    </div>
                    <div class="form-group">
                        <label for="to_new">Tipo Orden</label>
                        <input type="text" name="to_new" id="to_new" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="obs_new">Observaciones</label>
                        <textarea name="obs_new" id="obs_new" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="af_new">Fotografia</label>

                        <select class="form-control" name="af_new" id="af_new">
                            <option value="SI">SI</option>
                            <option value="NO">NO</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="cerrar_new" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="submit" id="Enviar" name="Enviar" class="btn btn-primary" form="modal_form">Enviar</button>
            </div>
        </div>
    </div>
</div>

</div>

<!-- PRIMERO jQuery (ya lo tienes) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- LUEGO jQuery UI -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<script src="{{ asset('js/bootstrap-treeview.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

<script>
            let selectedNode = null;
            let inNodeId = null;
    let selectedIdpivote = null;

    document.addEventListener('DOMContentLoaded', function () {
        const modalHeader = document.querySelector('#modalDelete .modal-header');
        const modalDialog = document.querySelector('#modalDelete .modal-dialog');

        let isDragging = false, x = 0, y = 0;

        modalHeader.style.cursor = 'move';

        modalHeader.addEventListener('mousedown', function (e) {
            isDragging = true;
            x = e.clientX - modalDialog.getBoundingClientRect().left;
            y = e.clientY - modalDialog.getBoundingClientRect().top;
            modalDialog.style.position = 'absolute';
            modalDialog.style.margin = '0';
        });

        document.addEventListener('mousemove', function (e) {
            if (isDragging) {
                modalDialog.style.left = `${e.clientX - x}px`;
                modalDialog.style.top = `${e.clientY - y}px`;
            }
        });

        document.addEventListener('mouseup', function () {
            isDragging = false;
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalHeader = document.querySelector('#modal .modal-header');
        const modalDialog = document.querySelector('#modal .modal-dialog');

        let isDragging = false, x = 0, y = 0;

        modalHeader.style.cursor = 'move';

        modalHeader.addEventListener('mousedown', function (e) {
            isDragging = true;
            x = e.clientX - modalDialog.getBoundingClientRect().left;
            y = e.clientY - modalDialog.getBoundingClientRect().top;
            modalDialog.style.position = 'absolute';
            modalDialog.style.margin = '0';
        });

        document.addEventListener('mousemove', function (e) {
            if (isDragging) {
                modalDialog.style.left = `${e.clientX - x}px`;
                modalDialog.style.top = `${e.clientY - y}px`;
            }
        });

        document.addEventListener('mouseup', function () {
            isDragging = false;
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalHeader = document.querySelector('#modalnew .modal-header');
        const modalDialog = document.querySelector('#modalnew .modal-dialog');

        let isDragging = false, x = 0, y = 0;

        modalHeader.style.cursor = 'move';

        modalHeader.addEventListener('mousedown', function (e) {
            isDragging = true;
            x = e.clientX - modalDialog.getBoundingClientRect().left;
            y = e.clientY - modalDialog.getBoundingClientRect().top;
            modalDialog.style.position = 'absolute';
            modalDialog.style.margin = '0';
        });

        document.addEventListener('mousemove', function (e) {
            if (isDragging) {
                modalDialog.style.left = `${e.clientX - x}px`;
                modalDialog.style.top = `${e.clientY - y}px`;
            }
        });

        document.addEventListener('mouseup', function () {
            isDragging = false;
        });
    });
</script>

<script>
        function mostrarNombrehijoPadre(input) {
    const nombre = input.files.length > 0 ? input.files[0].name : "Ningún archivo seleccionado";
    document.getElementById('nombre-archivohijospadres').textContent = nombre;
  }
$(document).ready(function() {


    let selectedNode = null;
    // Función para llenar el árbol
  function fill_treeview() {
    $.ajax({
        url: "{{ route('fetchabrmomat') }}",
        dataType: "json",
        success: function(data) {
            
            // 1. Inicializamos el árbol y cerramos sus opciones correctamente
            $('#treeview').treeview({
                data: data,
                selectable: true,
                highlightSelected: true,
                showBorder: false,
                levels: 199,
                expandIcon: 'fa fa-plus',
                collapseIcon: 'fa fa-minus',
                onNodeSelected: function(event, node) {
                    selectedNode = node;
                    console.log('Nodo seleccionado:', selectedNode.Cid);
                }
            }); // <-- ESTE CIERRE ERA EL QUE FALTABA O ESTABA MAL PUESTO

            // 2. El temporizador va aquí afuera, una vez que el árbol ya se creó
            setTimeout(function() {
                enableDragAndDrop();
            }, 500);

        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar.' + xhr.responseText, 'error');
            console.error(xhr.responseText);
        }
    });
}



function enableDragAndDrop() {
    // Hacer todos los nodos arrastrables
    $('.node-treeview').draggable({
        helper: 'clone',
        cursor: 'move',
        revert: 'invalid',
        start: function(event, ui) {
            $(this).addClass('dragging-node');
            ui.helper.css({
                'width': $(this).width(),
                'opacity': 0.8,
                'background-color': '#e3f2fd',
                'border': '2px dashed #2196f3',
                'border-radius': '4px',
                'padding': '5px'
            });

            // Obtener datos del nodo arrastrado
            let nodeId = $(this).data('nodeid');
            let nodeText = $(this).find('span').text();
            ui.helper.data('draggedNode', {
                id: nodeId,
                text: nodeText
            });
        },
        stop: function() {
            $(this).removeClass('dragging-node');
        }
    });

    // Hacer todos los nodos receptores (droppable)
    $('.node-treeview').droppable({
        accept: '.node-treeview',
        hoverClass: 'drop-target',
        tolerance: 'pointer',
        over: function(event, ui) {
            let draggedNode = ui.helper.data('draggedNode');

            let targetNodeId = $(this).data('nodeid');
            inNodeId = selectedNode.Cid;


        },
        out: function() {
            $(this).removeClass('drop-allowed drop-not-allowed drop-target');
        },
        drop: function(event, ui) {
            let draggedNode = ui.helper.data('draggedNode');
            let targetNodeId = $(this).data('nodeid');

            let targetNodeText = $(this).find('span').text();
simulateClickOnNode(targetNodeId)
            // Confirmar movimiento
            Swal.fire({
                title: 'Mover nodo',
                html: `¿Deseas mover <strong>${draggedNode.text}</strong> como hijo de <strong>${targetNodeText}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, mover',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {

                    moveNode(inNodeId, selectedNode.Cid);
                }
            });

            $(this).removeClass('drop-allowed drop-not-allowed drop-target');
        }
    });


}

// Función para validar si se puede soltar un nodo en otro
function canDropHere(draggedNodeId, targetNodeId) {
    // Evitar que un nodo se suelte en sí mismo
    if (draggedNodeId == targetNodeId) return false;

    // Evitar que un nodo se suelte en sus propios hijos
    // (necesitarías verificar la jerarquía completa)
    return true;
}

function simulateClickOnNode(cid) {
    // Buscar el elemento del nodo por su data-cid
    const $nodeElement = $(`.node-treeview[data-nodeid="${cid}"]`);

    if ($nodeElement.length) {
        console.log('Simulando clic en nodo:', cid);

        // Método 1: Disparar evento click de jQuery
        $nodeElement.trigger('click');

        // Método 2: Disparar evento nativo
        $nodeElement[0].click();

        // Método 3: Seleccionar el nodo en el treeview
        const tree = $('#treeview').treeview('getTree');


        return true;
    } else {
        console.log('No se encontró el elemento del nodo con Cid:', cid);
        return false;
    }
}
// Función para mover el nodo en el servidor
function moveNode(nodeId, newParentId) {
    $.ajax({
        url: "{{ route('abrmanoobra.move') }}",
        method: "POST",
        data: {
            node_id: nodeId,
            new_parent_id: newParentId,
            _token: "{{ csrf_token() }}"
        },
        beforeSend: function() {
            // Mostrar indicador de carga
            $('#treeview').append('<div class="loading-overlay">Actualizando...</div>');
        },
        success: function(response) {
            if (response.success) {
                Swal.fire('Éxito', 'Nodo movido correctamente', 'success');
                // Actualizar el árbol
                fill_treeview();
            } else {
                Swal.fire('Error', response.message || 'Error al mover el nodo', 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al mover el nodo: ' + xhr.responseText, 'error');
        },
        complete: function() {
            $('.loading-overlay').remove();
        }
    });
}


    // Función para llenar el menú desplegable de cuentas padre
    function fill_parent_category() {
        $.ajax({
            url: "{{ route('arbpadres') }}",
            method: "GET",
            success: function(data) {
                let options = '<option value="">Seleccione una cuenta padre</option>';
                data.forEach(function(cuenta) {
                    options += '<option value="' + cuenta.id + '">' + cuenta.nombre + '</option>';
                });
                $('#padre_id').html(options);
            },error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar.'+xhr.responseText, 'error');
            console.error(xhr.responseText);
        }
        });
    }
        $('#context').on('click','a',function(){
        const action = $(this).attr('id');
        if (action === 'createChildMasivaHijosPadres') {
            $('#modalMasivopadre').modal('show');
        }else if(action==='createChildMasivaRelaciones'){
        $('#modalMasivoRelacion').modal('show');
        }

    });

    // Lógica para enviar el formulario de agregar cuenta
    $('#treeview_form').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            url: "{{ route('abrmanoobra.add') }}",
            method: "POST",
            _token: "{{ csrf_token() }}",
            data: $(this).serialize(),
            success: function(data) {
                fill_treeview();
                fill_parent_category();
                $('#treeview_form')[0].reset();

            },error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar.'+xhr.responseText, 'error');
            console.error(xhr.responseText);
        }
        });
    });
    document.getElementById('cerrar').addEventListener('click', function() {
        $('#modal').modal('hide'); // Cierra el modal explícitamente
    });
        document.getElementById('cerrardel').addEventListener('click', function() {
        $('#modalDelete').modal('hide'); // Cierra el modal explícitamente
    });

    document.getElementById('Enviar').addEventListener('click', function() {
    // Realizar el submit del formulario
    $('#treeview_form').submit();
    $('#cerrar_new').trigger('click');
});


    // Menú contextual
    $(document).on('contextmenu', '#treeview .node-treeview', function(e) {
        e.preventDefault();
        $('#contextMenu')
            .css({ top: e.pageY + 'px', left: e.pageX + 'px' })
            .show();
        $('#contextMenu').data('selected-node-id', selectedNode.Cid);
        $('#contextMenu').data('selected-node-nombre', selectedNode.text);

        $('#contextMenu').data('selected-node-cuenta', selectedNode.cuenta_id);
    });
    // Lógica para enviar el formulario de editar cuenta
$('#treeview_form_edita').on('submit', function(e) {
    e.preventDefault();

    $.ajax({
        url: "{{ route('abrmanoobra.update') }}", // Asegúrate de tener esta ruta definida en Laravel
        type: "POST",
        data: $(this).serialize(),
        success: function(response) {
            $('#modal').modal('hide');
            Swal.fire('Éxito', 'Cuenta actualizada correctamente.', 'success');
            fill_treeview(); // Recarga el árbol
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar.'+xhr.responseText, 'error');
            console.error(xhr.responseText);
        }
    });
});

$('#treeview_form_delete ').on('submit', function(e) {
    e.preventDefault();

    $.ajax({
        url: "{{ route('abrmanoobra.delete') }}", // Asegúrate de tener esta ruta definida en Laravel
        type: "POST",
        data: $(this).serialize(),
        success: function(response) {
            $('#modalDelete').modal('hide');
            Swal.fire('Éxito', 'Cuenta eliminada correctamente.', 'success');
                fill_treeview();
                fill_parent_category();
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al eliminar.'+xhr.responseText, 'error');
            console.error(xhr.responseText);
        }
    });
});


    document.getElementById('cerrar').addEventListener('click', function() {
        $('#modal').modal('hide'); // Cierra el modal explícitamente
    });
    document.getElementById('cerrar_new').addEventListener('click', function() {
        $('#modalnew').modal('hide');
    });
    // Menú contextual
    $(document).on('contextmenu', '#treeview .node-treeview', function(e) {
        e.preventDefault();
        $('#contextMenu')
            .css({ top: e.pageY + 'px', left: e.pageX + 'px' })
            .show();
        $('#contextMenu').data('selected-node-id', selectedNode.Cid);
        $('#contextMenu').data('selected-node-nombre', selectedNode.nombre);
        $('#contextMenu').data('selected-node-padre', selectedNode.padre_id);
        $('#contextMenu').data('selected-node-cuenta', selectedNode.cuenta_id);
        $('#contextMenu').data('selected-node-ts_edit', selectedNode.ts_edit);
        $('#contextMenu').data('selected-node-af_edit', selectedNode.af_edit);
    });

    $('#contextMenu').on('click', 'a', function() {
        const action = $(this).attr('id');
        const cid = $('#contextMenu').data('selected-node-id');
        const nombre = $('#contextMenu').data('selected-node-nombre');
        const cuenta = $('#contextMenu').data('selected-node-cuenta');
        const ts = $('#contextMenu').data('selected-node-ts_edit');
        const af = $('#contextMenu').data('selected-node-af_edit');
        if (action === 'editNode') {
            $('#cidValue').text('Editando nodo Cid: ' + cid);
            $('#nombre_edit').val(nombre);
            $('#id_edit').val(cid);
            $('#cuenta_id_edit').val(cuenta);

            $('#modal').modal('show');




        } else if (action === 'createChildNode') {
            $('#padre_id').val(cid);
            $('#padre_id').val(cid).trigger('change');

            $('#modalnew').modal('show');
        }else if (action === 'createChildMasivaHijosPadres') {
            $('#modalMasivopadre').modal('show');
        }else if (action === 'deleteNode') {
            $('#padre_id').val(cid);
            $('#cidValue').text('nodo a Eliminar Cid: ' + cid);
            $('#nombre_delete').val(nombre);
            $('#id_delete').val(cid);
            $('#cuenta_id_delete').val(cuenta);
            $('#modalDelete').modal('show');
        }

        $('#contextMenu').hide();
    });

    // Inicializar árbol y categorías padre
    fill_treeview();
    fill_parent_category();

    // Ocultar menú contextual si se hace clic fuera
    $(document).click(function(e) {
        if (!$(e.target).closest('#contextMenu').length) {
            $('#contextMenu').hide();
        }
    });

    $(document).on('change', '#padre_id', function() {
    var padreId = $(this).val();
    console.log("Cambio detectado, nuevo padre_id:", padreId); // Verifica si el evento se detecta

    if (padreId) {
        // Hacer la llamada AJAX para obtener el nuevo número de cuenta
        $.ajax({
            url: "{{ route('abrmanoobra.generarNumeroCuenta') }}",
            method: "POST",
            data: {
                padre_id: padreId,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                if (response.nuevoNumeroCuenta) {
                    $('#cuenta_id_new').val(response.nuevoNumeroCuenta);
                }
            },error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar.'+xhr.responseText, 'error');
            console.error(xhr.responseText);
        }
        });
    }
});

  

});
</script>
@endsection
@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>

@endpush
