@extends('layouts.app')

@section('title','Crear Caja Registradora')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Asignar usuarios a Tienda</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('userstore.index')}}">Lista asignar usuario a tienda</a></li>
        <li class="breadcrumb-item active">Asignar usuario a tienda</li>
    </ol>

    <div class="card">
        <form action="{{ route('userstore.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-body text-bg-light">

                <div class="row g-4">

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
                    <!---Tienda---->
                    <div class="col-md-6">
                        <label for="fkUsuario" class="form-label">Usuarios:</label>
                        <select data-size="4" title="Seleccione un usuario" data-live-search="true" name="fkUsuario" id="fkUsuario" class="form-control selectpicker show-tick">
                            @foreach ($userstore as $item)
                            <option value="{{$item->id}}" {{ old('fkTienda') == $item->idT ? 'selected' : '' }}>{{$item->email}}</option>
                            @endforeach
                        </select>
                        @error('fkUsuario')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="fkTienda" class="form-label">Tienda:</label>
                        <select data-size="4" title="Seleccione una Tienda" data-live-search="true" name="fkTienda" id="fkTienda" class="form-control selectpicker show-tick">
                            @foreach ($Tienda as $item)
                            <option value="{{$item->idTienda}}" {{ old('fkTienda') == $item->idTienda ? 'selected' : '' }}>{{$item->Nombre}}</option>
                            @endforeach
                        </select>
                        @error('fkTienda')
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
@endpush
