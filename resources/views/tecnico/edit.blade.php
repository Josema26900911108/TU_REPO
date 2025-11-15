@extends('layouts.app')

@section('title','Editar usuario')

@push('css')

@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Tecnico</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tecnico.lista')}}">Tecnicos</a></li>
        <li class="breadcrumb-item active">Editar Tecnico</li>
    </ol>

    <div class="card text-bg-light">
        <form action="{{ route('tecnico.update',['tecnico' => $tecnico]) }}" method="post"  enctype="multipart/form-data">
            @method('PATCH')
            @csrf
            <div class="card-header">
                <p class="">Nota: Los usuarios son los que pueden ingresar al sistema</p>
            </div>
            <div class="card-body">
                                <!---Nombre---->
                <div class="row mb-4">
                    <label for="name" class="col-lg-2 col-form-label">Nombre:</label>
                    <div class="col-lg-4">
                        <input type="text" name="name" id="name" class="form-control" value="{{old('name',$tecnico->nombre)}}">
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text">
                            Escriba un solo codigo
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('name')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>
                <!---Nombre---->
                <div class="row mb-4">
                    <label for="codigo" class="col-lg-2 col-form-label">Codigo Eta:</label>
                    <div class="col-lg-4">
                        <input type="text" name="codigo" id="codigo" class="form-control" value="{{old('codigo',$tecnico->codigo)}}">
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text">
                            Escriba un solo codigo
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('codigo')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

                <!---Email---->
                <div class="row mb-4">
                    <label for="especialidad" class="col-lg-2 col-form-label">Especialidad:</label>
                    <div class="col-lg-4">
                        <input type="especialidad" name="especialidad" id="especialidad" class="form-control" value="{{old('especialidad',$tecnico->especialidad)}}">
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text">
                            Dirección de correo eléctronico
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('email')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

                <!---Roles---->
                <div class="row mb-4">
                    <label for="fkTienda" class="col-lg-2 col-form-label">Seleccionar tienda:</label>
                    <div class="col-lg-4">
                        <select name="fkTienda" id="fkTienda" class="form-select">
                            @foreach ($tienda as $item)
                            @if ($item->idTienda == $tecnico->tienda->idTienda)

                            <option selected value="{{$item->idTienda}}" @selected(old('fkTienda')==$item->Nombre)>{{$item->Nombre}}</option>
                            @else
                            <option value="{{$item->idTienda}}" @selected(old('fkTienda')==$item->Nombre)>{{$item->Nombre}}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-text">
                            Escoja un rol para el usuario.
                        </div>
                    </div>
                    <div class="col-lg-2">
                        @error('fkTienda')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>

            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')

@endpush
