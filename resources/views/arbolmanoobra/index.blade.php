
@extends('layouts.app')


@section('title', 'Panel')

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
<link rel="stylesheet" href="{{ asset('css/bootstrap-treeview.css') }}">
<link rel="stylesheet" href="{{ asset('css/bootstrap-treeview.min.css') }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/css/bootstrap-select.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/js/i18n/defaults-es_ES.min.js"></script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link href="https://unpkg.com/gijgo@1.9.14/css/gijgo.min.css" rel="stylesheet" type="text/css" />
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


<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://unpkg.com/gijgo@1.9.14/js/gijgo.min.js" type="text/javascript"></script>
@endpush
<div class="container">

    <div class="row justify-content-center">
    <h2 align="center">Árbol de Materiales y Manos de obra</h2>
    <h4 align="center">arbol mano obra</h4>
    <br /><br />
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

<script src="{{ asset('js/bootstrap-treeview.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Bootstrap Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function() {


    let selectedNode = null;
    // Función para llenar el árbol
    function fill_treeview() {
        $.ajax({
            url: "{{ route('fetchabrmomat') }}",
            dataType: "json",
            success: function(data) {
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
                });
            },error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al actualizar.'+xhr.responseText, 'error');
            console.error(xhr.responseText);
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
