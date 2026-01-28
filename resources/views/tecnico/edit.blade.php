@extends('layouts.app')

@section('title', 'Editar cliente')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/math.js') }}"></script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Tecnico</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tecnico.lista') }}">Tecnico</a></li>
        <li class="breadcrumb-item active">Editar Tecnico</li>
    </ol>

    <!-- Formulario para nuevo cliente -->
    <div class="card" id="form-nuevo-cliente">
        <form id="formNuevoCliente" action="{{ route('tecnico.exist') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-body text-bg-light">
                <div class="row g-3">

                    <div class="col-md-6" id="numero_documento_div">
                        <label for="numero_eta" class="form-label">Codigo Tecnico Eta:</label>
                        <input type="hidden" name="idtecnico" id="idtecnico" value="{{ $id }}">

                        <input type="text" name="numero_eta" id="numero_eta" class="form-control" value="{{ old('numero_eta', $tecnico->codigo ?? '') }}" required>
                        @error('numero_eta')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6" id="numero_documento_div">
                        <label for="especialidad" class="form-label">Especialidad Tecnico:</label>
                        <input type="text" name="especialidad" id="especialidad" class="form-control" value="{{ old('especialidad', $tecnico->especialidad ?? '') }}" required>
                        @error('especialidad')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>

                                                                        <!---IMG LOGO---->
                <div class="col-md-6">
                    <label for="password_confirm" class="col-lg-2 col-form-label">Imagen:</label>
                    <div class="col-lg-4">
                        <input type="file" name="image" id="image" accept="image/*" class="form-control" value="{{old('image')}}">
                        @error('image')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text">
                            Elija una fotografia para el perfil de usuario.
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('password_confirm')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>


                <!---Tiendas---->
                <div class="col-md-6">
                    <label for="tienda" class="col-lg-2 col-form-label">Tienda:</label>
                    <div class="col-lg-4">
                        <select name="tienda" id="tienda" class="form-select" aria-labelledby="rolHelpBlock">
                            <option value="" selected disabled>Seleccione:</option>
                            @foreach ($tienda as $item)
                            <option value="{{$item->idTienda}}" @selected($tecnico->fkTienda==$item->idTienda)>{{$item->Nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-text" id="rolHelpBlock">
                            Escoja la tienda del Tecnico.
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('tienda')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

             
                                                <!---Roles---->
                <div class="col-md-6">
                    <label for="role" class="col-lg-2 col-form-label">Usuario:</label>
                    <div class="col-lg-4">
                        <select name="user" id="user" class="form-select" aria-labelledby="rolHelpBlock">
                            <option value="" selected disabled>Seleccione:</option>
                            @foreach ($users as $item)
                            <option value="{{$item->id}}" @selected($tecnico->fkuser==$item->id)>{{$item->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text" id="rolHelpBlock">
                            Elija un Usuario.
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('role')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

                </div>
            </div>
            <div class="card-footer text-center" id="btnguardarnuevo">
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>

    <!-- Formulario para cliente existente -->
    <div class="card mt-4" id="form-cliente-existente" style="display: none;">
        <form id="formClienteExistente" action="{{ route('tecnico.storexist') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-body text-bg-light">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="persona_id" class="form-label">Cliente:</label>
                        <select name="persona_id" id="persona_id" class="form-control selectpicker show-tick" data-live-search="true" required>
                            <option value="" disabled selected>Selecciona un cliente</option>
                        </select>
                        @error('persona_id')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>

                                                                        <!---IMG LOGO---->
                <div class="col-md-6">
                    <label for="password_confirm" class="col-lg-2 col-form-label">Imagen:</label>
                    <div class="col-lg-4">
                        <input type="file" name="image" id="image" accept="image/*" class="form-control" value="{{old('image')}}">
                        @error('image')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text">
                            Elija una fotografia para el perfil de usuario.
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('password_confirm')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

                <!---Email---->
                <div class="col-md-6">
                    <label for="email" class="col-lg-2 col-form-label">Email:</label>
                    <div class="col-lg-4">
                        <input autocomplete="off" type="email" name="email" id="email" class="form-control" value="{{old('email')}}" aria-labelledby="emailHelpBlock">
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text" id="emailHelpBlock">
                            Dirección de correo eléctronico
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('email')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

                <!---Password---->
                <div class="col-md-6">
                    <label for="password" class="col-lg-2 col-form-label">Contraseña:</label>
                    <div class="col-lg-4">
                        <input type="password" name="password" id="password" class="form-control" aria-labelledby="passwordHelpBlock">
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text" id="passwordHelpBlock">
                            Escriba una constraseña segura. Debe incluir números.
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('password')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

                <!---Confirm_Password---->
                <div class="col-md-6">
                    <label for="password_confirm" class="col-lg-2 col-form-label">Confirmar:</label>
                    <div class="col-lg-4">
                        <input type="password" name="password_confirm" id="password_confirm" class="form-control" aria-labelledby="passwordConfirmHelpBlock">
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text" id="passwordConfirmHelpBlock">
                            Vuelva a escribir su contraseña.
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('password_confirm')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

                                    <div class="col-md-6" id="numero_documento_div">
                        <label for="numero_eta" class="form-label">Codigo Tecnico Eta:</label>
                        <input type="text" name="numero_eta" id="numero_eta" class="form-control" value="{{ old('numero_eta') }}" required>
                        @error('numero_eta')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6" id="numero_documento_div">
                        <label for="especialidad" class="form-label">Especialidad Tecnico:</label>
                        <input type="text" name="especialidad" id="especialidad" class="form-control" value="{{ old('especialidad') }}" required>
                        @error('especialidad')
                        <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>

                                <!---Roles---->
                <div class="col-md-6">
                    <label for="role" class="col-lg-2 col-form-label">Rol:</label>
                    <div class="col-lg-4">
                        <select name="role" id="role" class="form-select" aria-labelledby="rolHelpBlock">
                            <option value="" selected disabled>Seleccione:</option>
                            @foreach ($rol as $item)
                            <option value="{{$item->name}}" @selected(old('role')==$item->name)>{{$item->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text" id="rolHelpBlock">
                            Elija un rol.
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('role')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

                                    <div class="col-md-6">
                        <label for="Estatus" class="form-label">Estatus:</label>
                        <select name="Estatus" id="Estatus" class="form-control">
                            <option value="" disabled selected>Seleccione el Estatus</option>
                            <option value="EI">Inactivo</option>
                            <option value="EA">Activo</option>
                            <option value="EB">Baja</option>
                            <option value="ER">Usuario Root</option>
                        </select>
                        @error('Estatus')
                            <small class="text-danger">{{ '*'.$message }}</small>
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
    $(document).ready(function() {
        // Función para cargar clientes existentes
        function actualizarClientes() {
            $.ajax({
                url: "{{ route('client.obtenerfill') }}",
                method: 'GET',
                success: function(response) {
                    var $select = $('#persona_id');
                    $select.html('').selectpicker('destroy');
                    response.forEach(function(cliente) {
                        $select.append('<option value="' + cliente.id + '">' + cliente.numero_documento + ' - ' + cliente.razon_social + '</option>');
                    });
                    $select.selectpicker();
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        }

        // Manejar el cambio en el tipo de persona
        $('#tipo_persona').on('change', function() {
            let selectValue = $(this).val();

            if (selectValue === 'natural' || selectValue === 'juridica') {
                $('#form-nuevo-cliente').show();
                $('#form-cliente-existente').hide();
                $('#label-natural').toggle(selectValue === 'natural');
                $('#label-juridica').toggle(selectValue === 'juridica');
            } else if (selectValue === 'existe') {
                $('#form-nuevo-cliente').hide();
                $('#form-cliente-existente').show();
                actualizarClientes();
            }
        });

    });
</script>
@endpush
