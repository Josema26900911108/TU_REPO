@extends('layouts.app')

@section('title', 'Panel')

@section('content')

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
<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-treeview.js') }}"></script>
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://unpkg.com/gijgo@1.9.14/js/gijgo.min.js" type="text/javascript"></script>
@endpush
<div class="container">

    <div class="row justify-content-center">
    <h2 align="center">Cuentas Contables</h2>
    <br /><br />
    <div class="row justify-content-center ">


        <div class="menu">
            <div class="accordion">
            <h3 align="center"><u>Árbol de Cuentas Contables</u></h3>
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
    <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Editar Cuenta</h5>

                </div>
                <div class="modal-body">

                <form method="post" id="treeview_form_edita">
                        <p id="cidValue">El Cid es: </p>
                            <input type="hidden" id="id_edit" name="id_edit" class="form-control">
                        <div class="form-group">
                            <label for="formula">Cuenta</label>
                            <input type="text" name="cuenta_id_edit" id="cuenta_id_edit" class="form-control" pattern="\d{2}\.\d{2}\.\d{2}\.\d{2}" title="El formato debe ser ##.##.##.##">
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre Cuenta</label>
                            <input type="text" name="nombre_edit" id="nombre_edit" class="form-control">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cerrar" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" form="modal_form">Enviar</button>
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
                        <label for="formula">Cuenta</label>

                        <input type="text" name="cuenta_id_new" id="cuenta_id_new" class="form-control" pattern="\d{2}\.\d{2}\.\d{2}\.\d{2}" title="El formato debe ser ##.##.##.##">
                    </div>
                    <div class="form-group">
                        <label for="nombre">Nombre Cuenta</label>
                        <input type="text" name="nombre_new" id="nombre_new" class="form-control">
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
<script>
$(document).ready(function() {
    let selectedNode = null;
    // Función para llenar el árbol
    function fill_treeview() {
        $.ajax({
            url: "{{ route('fetch2') }}",
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
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar el treeview:', xhr.responseText);
            }
        });
    }

    // Función para llenar el menú desplegable de cuentas padre
    function fill_parent_category() {
        $.ajax({
            url: "{{ route('padres') }}",
            method: "GET",
            success: function(data) {
                let options = '<option value="">Seleccione una cuenta padre</option>';
                data.forEach(function(cuenta) {
                    options += '<option value="' + cuenta.id + '">' + cuenta.nombre + '</option>';
                });
                $('#padre_id').html(options);
            },
            error: function(xhr, status, error) {
                console.error('Error al obtener las cuentas padre:', xhr.responseText);
            }
        });
    }

    // Lógica para enviar el formulario de agregar cuenta
    $('#treeview_form').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            url: "{{ route('cuentas.add') }}",
            method: "POST",
            _token: "{{ csrf_token() }}",
            data: $(this).serialize(),
            success: function(data) {
                fill_treeview();
                fill_parent_category();
                $('#treeview_form')[0].reset();
                
            }
        });
    });
    document.getElementById('cerrar').addEventListener('click', function() {
        $('#modal').modal('hide'); // Cierra el modal explícitamente
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
    $('#treeview_form_edita').on('submit', function(event) {
        event.preventDefault();
        $.ajax({
            url: "{{ route('update') }}",
            method: "POST",
            data: $(this).serialize(),
            success: function(data) {
                fill_treeview();
                fill_parent_category();
                $('#treeview_form_edita')[0].reset();
                alert(data);
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
    });

    $('#contextMenu').on('click', 'a', function() {
        const action = $(this).attr('id');
        const cid = $('#contextMenu').data('selected-node-id');
        const nombre = $('#contextMenu').data('selected-node-nombre');
        const cuenta = $('#contextMenu').data('selected-node-cuenta');
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
            url: "{{ route('cuentas.generarNumeroCuenta') }}",
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
            },
            error: function(xhr, status, error) {
                console.error('Error al generar el número de cuenta:', xhr.responseText);
            }
        });
    }
});


});
</script>
@endsection
