@extends('layouts.app')

@section('title','Editar Caja')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Caja</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('cash.index')}}">Caja</a></li>
        <li class="breadcrumb-item active">Editar Caja</li>
    </ol>

    <div class="card text-bg-light">


            <form action="{{ route('userstore.update', ['userstore' => $userstore->idUsuarioTienda]) }}" method="post" enctype="multipart/form-data">

            @method('PATCH')
            @csrf
            <div class="card-body">

                <div class="row g-4">

                    <!---Tienda---->
                    <div class="col-md-6">
                        <label for="Tienda" class="form-label">Tienda:</label>
                        <select data-size="4" title="Seleccione una Tienda" data-live-search="true" name="fkTienda" id="fkTienda" class="form-control selectpicker show-tick">
                            @foreach ($Tienda as $item)
                            @if ($userstore->fkTienda == $item->idTienda)
                            <option selected value="{{$item->idTienda}}" {{ old('fkTienda') == $item->idTienda ? 'selected' : '' }}>{{$item->Nombre}}</option>
                            @else
                            <option value="{{$item->idTienda}}" {{ old('fkTienda') == $item->idTienda ? 'selected' : '' }}>{{$item->Nombre}}</option>
                            @endif
                            @endforeach
                        </select>
                        @error('fkTienda')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                                        <!---Usuario---->
                                        <div class="col-md-6">
                                            <label for="Tienda" class="form-label">Usuario:</label>
                                            <select data-size="4" title="Seleccione una Usuario" data-live-search="true" name="fkUsuario" id="fkUsuario" class="form-control selectpicker show-tick">
                                                @foreach ($userstore3 as $item)
                                                @if ($userstore->fkUsuario == $item->id)
                                                <option selected value="{{$item->id}}" {{ old('fkUsuario') == $item->id ? 'selected' : '' }}>{{$item->email}}</option>
                                                @else
                                                <option value="{{$item->id}}" {{ old('fkUsuario') == $item->id ? 'selected' : '' }}>{{$item->email}}</option>
                                                @endif
                                                @endforeach
                                            </select>
                                            @error('fkUsuario')
                                            <small class="text-danger">{{'*'.$message}}</small>
                                            @enderror
                                        </div>

                    <div class="col-md-6">
                        <label for="estatus" class="form-label">Estatus:</label>
                        <select name="Estatus" id="estatus" class="form-control">
                            <option value="" disabled selected>Seleccione el estatus</option>
                            <option value="EI" {{ old('Estatus', $userstore->Estatus) == 'EI' ? 'selected' : '' }}>Inactivo</option>
                            <option value="EA" {{ old('Estatus', $userstore->Estatus) == 'EA' ? 'selected' : '' }}>Activo</option>
                            <option value="EB" {{ old('Estatus', $userstore->Estatus) == 'EB' ? 'selected' : '' }}>Baja</option>
                            <option value="ER" {{ old('Estatus', $userstore->Estatus) == 'ER' ? 'selected' : '' }}>Empleado Root</option>
                        </select>
                        @error('Estatus')
                            <small class="text-danger">{{ '*'.$message }}</small>
                        @enderror
                    </div>



                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <button type="reset" class="btn btn-secondary">Reiniciar</button>
            </div>
        </form>
    </div>


</div>
@endsection

@push('js')
@endpush
