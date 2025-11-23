@extends('layouts.app')

@section('title','Detalle comprobante')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="{{ asset('js/math.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Detalle Comprobante</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('comprobante.index')}}">Comprobantes</a></li>
        <li class="breadcrumb-item active">Crear Detalle Comprobante</li>
    </ol>
</div>

<form action="{{ route('detallecomprobante.store') }}" method="post">
    @csrf

    <div class="container-lg mt-4">
        <div class="row gy-4">
            <!------Detalle Comprobante producto---->
            <div class="col-xl-8">
                <div class="text-white bg-primary p-1 text-center">
                    Detalles de Comprobante
                </div>
                <div class="p-3 border border-3 border-primary">
                    <div class="row">
                        <!-----Producto---->
                        <input type="hidden" id="idcomprobante" value="{{$comprobanteId}}">
                        <div class="col-12 mb-4">
                            <select name="cuentacontable_id" id="cuentacontable_id" class="form-control selectpicker" data-live-search="true" data-size="10" title="Busque un Cuenta Contable aquí">
                                @foreach ($cuentacontable as $item)
                                <option value="{{$item->id}}">{{$item->formula.'-'.$item->nombre}}</option>
                                @endforeach
                            </select>
                        </div>
                        <!-----Producto---->
                        <div class="col-12 mb-4">
                            <select name="Naturaleza" id="Naturaleza" class="form-control selectpicker" data-live-search="true" data-size="2" title="Elija Naturaleza de la cuenta">
                            <option value="D">Debe</option>
                            <option value="H">Haber</option>
                            </select>
                        </div>

                        <!-----Cantidad---->
                        <div class="col-sm-12 mb-4">
                            <label for="nombre" class="form-label">Descripcion Breve (225 letras):</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" maxlength="225" value="N/A">
                        </div>
                                                <!-----Cantidad---->
                        <div class="col-sm-12 mb-4">
                            <label for="formula" class="form-label">Formula:</label>
                            <input type="text" name="formula" id="formula" class="form-control" placeholder="(A/1.12)*12%" pattern="^[A0-9\.\+\-\*\/\(\)%]*$"
       title="Solo se permite la letra A mayúscula, números y signos matemáticos (/ * + - % () .)">
                        </div>

                        <!-----Precio de compra---->
                        <div class="col-sm-4 mb-2">
                            <label for="valorminimo" class="form-label">Valor Minimo:</label>
                            <input type="number" name="valorminimo" id="valorminimo" class="form-control" step="0.1">
                        </div>

                        <!-----botón para agregar--->
                        <div class="col-12 mb-4 mt-2 text-end">
                            <button id="btn_agregar" class="btn btn-primary" type="button">Agregar</button>
                        </div>

                        <!-----Tabla para el detalle de la compra--->
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="tabla_detalle" class="table table-hover">
                                    <thead class="bg-primary">
                                        <tr>
                                            <th class="text-white">#</th>
                                            <th class="text-white">Cuenta Contable</th>
                                            <th class="text-white">Naturaleza</th>
                                            <th class="text-white">Formula</th>
                                            <th class="text-white">Minimo</th>
                                            <th class="text-white">Descripcion</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th></th>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th></th>
                                            <th colspan="4">Sumas DEBE</th>
                                            <th colspan="2"><span id="sumasDEBE">0</span></th>
                                        </tr>
                                        <tr>
                                            <th></th>
                                            <th colspan="4">Sumas HABER</th>
                                            <th colspan="2"><span id="sumasHABER">0</span></th>
                                        </tr>
                                        <tr>
                                            <th></th>
                                            <th colspan="4">Validacion</th>
                                            <th colspan="2"> <input type="hidden" name="total" value="0" id="inputTotal"> <span id="total">0</span></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="col-12 mt-4 text-center">
                            <button type="submit" class="btn btn-success" id="guardar">Agregar Detalles Comprobante</button>
                        </div>
                        <!--Boton para cancelar compra-->
                        <div class="col-12 mt-4 text-center">
                            <button id="cancelar" type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                Cancelar
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal para cancelar la compra -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Advertencia</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Seguro que quieres cancelar la creación de detalle comprobante?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button id="btnCancelarCompra" type="button" class="btn btn-danger" data-bs-dismiss="modal" onclick="volverlista()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

</form>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script>
    $(document).ready(function() {
        $('#btn_agregar').click(function() {
            agregarProducto();
        });

        $('#btnCancelarCompra').click(function() {
            cancelarCompra();
        });

        disableButtons();
        MostrarCuentas();

    });

    //Variables
    let cont = 0;
    let subtotal = [];
    let subiva = [];
    let arrayNaturaleza = [];
    let arrayvalorminimo = [];
    let sumasDEBE = 0;
    let sumasHABER = 0;
    let IVA = 0;
    let total = 0;
    totalMASIVA = 0;

    function cancelarCompra() {
        //Elimar el tbody de la tabla
        $('#tabla_detalle tbody').empty();

        //Añadir una nueva fila a la tabla
        let fila = '<tr>' +
            '<th></th>' +
            '<th></th>' +
            '<td></td>' +
            '<td></td>' +
            '<td></td>' +
            '<td></td>' +
            '<td></td>' +
            '</tr>';
        $('#tabla_detalle').append(fila);

        //Reiniciar valores de las variables
        cont = 0;
        subtotal = [];
        subiva = [];
        let arrayNaturaleza = 0;
        let arrayvalorminimo = 0;
        sumasDEBE = 0;
        sumasHABER = 0;
        IVA = 0;
        total = 0;
        totalMASIVA = 0;
        //Mostrar los campos calculados
        $('#sumasDEBE').html('Q. '+sumasDEBE);
        $('#sumasHABER').html('Q. '+sumasHABER);
        $('#inputTotal').html(total);

        limpiarCampos();
        disableButtons();


    }

    function volverlista($idtienda){

        const baseUrl = "{{ url('/comprobante') }}";
        window.location.href = `${baseUrl}`;

}

    function disableButtons() {
        if (total < 0.00) {
            $('#guardar').hide();
            $('#cancelar').hide();
        } else {
            $('#guardar').show();
            $('#cancelar').show();
        }
    }

    function agregarProducto() {
        //Obtener valores de los campos
        let idcomprobante = $('#idcomprobante').val();
        let idcuentacontable = ($('#cuentacontable_id option:selected').val());
        let txtcuentacontable = ($('#cuentacontable_id option:selected').text());

        let Naturaleza = ($('#Naturaleza option:selected').val());
        let txtNaturaleza = ($('#Naturaleza option:selected').text());
        let nombre = $('#nombre').val();
        let formulas = $('#formula').val();
        let valorminimo = $('#valorminimo').val();
        let verv = 0;
        let A = valorminimo;
        let formulaEvaluada = formulas.replace(/A/g, A); // Reemplaza todas las apariciones de A por 100
        let resultado = math.evaluate(formulaEvaluada);
        resultado = parseFloat(resultado.toFixed(2));
        //Validaciones
        //1.Para que los campos no esten vacíos
        if (nombre != '' && nombre != undefined && formula != '' && formula != undefined && resultado != '' ) {



                if (parseFloat(resultado) > 0) {


                    if (Naturaleza === 'D') {
                        sumasDEBE += round(resultado); // Resta el valor al DEBE

                    } else {
                        sumasHABER += round(resultado); // Resta el valor al HABER

                    }
                    //Calcular valores
                    subtotal[cont] = round(sumasDEBE-sumasHABER);
                    arrayNaturaleza[cont]=Naturaleza;
                    total= sumasDEBE-sumasHABER;
                    verv=total;

                    //Crear la fila
                    let fila = '<tr id="fila' + cont + '">' +
                        '<th>' + (cont + 1) + '</th>' +
                        '<td><input type="hidden" name="arrayidcomprobante[]" value="' + idcomprobante + '">' + idcomprobante + '</td>' +
                        '<td><input type="hidden" name="arraycuentacontable_id[]" value="' + idcuentacontable + '">' + txtcuentacontable + '</td>' +
                        '<td><input type="hidden" name="arrayNaturaleza[]" value="' + Naturaleza + '">' + txtNaturaleza + '</td>' +
                        '<td><input type="hidden" name="arrayformula[]" value="' + formulas + '">' + formulas + '</td>' +
                        '<td><input type="hidden" name="arrayvalorminimo[]" value="' + resultado + '">' + resultado + '</td>'+
                        '<td><input type="hidden" name="arraynombre[]" value="' + nombre + '">' + nombre + '</td>' +
                        '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + cont + ')"><i class="fa-solid fa-trash"></i></button></td>' + +
                        '</tr>';

                    //Acciones después de añadir la fila
                    $('#tabla_detalle').append(fila);
                    limpiarCampos();
                    cont++;
                    disableButtons();

                    //Mostrar los campos calculados
                    $('#sumasDEBE').html(sumasDEBE);
                    $('#sumasHABER').html(sumasHABER);
                    $('#total').html(total);
                    $('#inputTotal').val(total);

            } else {
                showModal('Los valores no pueden ser negativos, ya que estos se estan determinando en su naturaleza contable DEBE/HABER');
            }

        } else {
            showModal('Le faltan campos por llenar');
        }
    }

    function MostrarCuentas() {
        //Obtener valores de los campos
        let idcomprobantes = $('#idcomprobante').val();


        $.ajax({
            url: "{{ route('detallecomprobante.obtener') }}",
            method: 'POST',
            data:{
                idcomprobante:idcomprobantes,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response){
                let html='';
                let contar=0;
                let txtNaturaleza='';
                response.forEach(function(row){
                      let idcuentacontable = row.idcuentacontable;
        let txtcuentacontable = row.nombre;
        let Naturaleza = row.Naturaleza;
        let formulas = row.formula;
        let resultado = row.resultado;
        let nombre = row.nombre;
        cont++;
                         if(Naturaleza==='H'){
                        txtNaturaleza='HABER';
                    }
                    if(Naturaleza==='D'){
                        txtNaturaleza='DEBE';
                    }

                    html += '<tr id="fila' + contar + '">' +
                        '<th>' + cont + '</th>' +
                        '<input type="hidden" name="arrayidcomprobante[]" value="' + idcomprobantes + '">' +
                        '<td><input type="hidden" name="arraycuentacontable_id[]" value="' + idcuentacontable + '">' + txtcuentacontable + '</td>' +
                        '<td><input type="hidden" name="arrayNaturaleza[]" value="' + Naturaleza + '">' + txtNaturaleza + '</td>' +
                        '<td><input type="hidden" name="arrayformula[]" value="' + formulas + '">' + formulas + '</td>' +
                        '<td><input type="hidden" name="arrayvalorminimo[]" value="' + resultado + '">' + resultado + '</td>'+
                        '<td><input type="hidden" name="arraynombre[]" value="' + nombre + '">' + nombre + '</td>' +
                        '<td><button class="btn btn-danger" type="button" onClick="eliminarProducto(' + contar + ')"><i class="fa-solid fa-trash"></i></button></td>' +
                        '</tr>';



                });
                    $('#tabla_detalle').append(html);


            },
            error:function(xhr,status,error){
            console.error("Error en AJAX:", error);
            console.log("Respuesta del servidor:", xhr.responseText);
            }
        });
    }

    function eliminarProducto(indice) {
        //Calcular valores
$ver=0;
        Naturaleza = arrayNaturaleza[indice];
        if (Naturaleza === 'D') {
        sumasDEBE -= Math.round(arrayvalorminimo[indice]); // Resta el valor al DEBE
        $ver=sumasDEBE;
    } else {
        sumasHABER -= Math.round(arrayvalorminimo[indice]); // Resta el valor al HABER
        $ver=sumasHABER;
    }
    total= sumasDEBE-sumasHABER;


        //Mostrar los campos calculados
        $('#sumasDEBE').html(sumasDEBE);
        $('#sumasHABER').html(sumasHABER);
        $('#total').html(total);
        $('#inputTotal').val(total);

        //Eliminar el fila de la tabla
        $('#fila' + indice).remove();

        disableButtons();

    }

    function limpiarCampos() {
        let select = $('#cuentacontable_id');
        select.selectpicker('val', '');
        let selectw = $('#Naturaleza');
        selectw.selectpicker('val', '');
        $('#nombre').val('');
        $('#formula').val('');
    }

    function round(num, decimales = 2) {
        var signo = (num >= 0 ? 1 : -1);
        num = num * signo;
        if (decimales === 0) //con 0 decimales
            return signo * Math.round(num);
        // round(x * 10 ^ decimales)
        num = num.toString().split('e');
        num = Math.round(+(num[0] + 'e' + (num[1] ? (+num[1] + decimales) : decimales)));
        // x * 10 ^ (-decimales)
        num = num.toString().split('e');
        return signo * (num[0] + 'e' + (num[1] ? (+num[1] - decimales) : -decimales));
    }
    //Fuente: https://es.stackoverflow.com/questions/48958/redondear-a-dos-decimales-cuando-sea-necesario

    function showModal(message, icon = 'error') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        })

        Toast.fire({
            icon: icon,
            title: message
        })
    }
</script>
@endpush
