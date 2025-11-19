@extends('layouts.app')

@section('title','Crear Tienda')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Tienda</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tienda.index')}}">Tiendas</a></li>
        <li class="breadcrumb-item active">Crear Tienda</li>
    </ol>

    <div class="card">
        <form action="{{ route('tienda.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-body text-bg-light">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label for="Nombre" class="form-label">Nombre:</label>
                        <input type="text" name="Nombre" id="Nombre" class="form-control" value="{{old('Nombre')}}">
                        @error('Nombre')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="telefono" class="form-label">Telefono:</label>
                        <input type="text" name="telefono" id="telefono" class="form-control" value="{{old('telefono')}}">
                        @error('telefono')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                <div class="col-md-6">
                    <label for="Direccion" class="form-label">Dirección:</label>
                    <textarea type="text" name="Direccion" id="Direccion" class="form-control" value="{{old('Direccion')}}"></textarea>
                    @error('Direccion')
                    <small class="text-danger">{{'*'.$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="descripcion" class="form-label">Descripción:</label>
                    <textarea type="text" name="descripcion" id="descripcion" class="form-control" value="{{old('descripcion')}}"></textarea>
                    @error('descripcion')
                    <small class="text-danger">{{'*'.$message}}</small>
                    @enderror
                </div>
                    <div class="col-md-6">
                        <label for="departamento" class="form-label">Departamento:</label>
                        <input type="text" name="departamento" id="departamento" class="form-control" value="{{old('departamento')}}">
                        @error('departamento')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="municipio" class="form-label">Municipio:</label>
                        <input type="text" name="municipio" id="municipio" class="form-control" value="{{old('municipio')}}">
                        @error('municipio')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="representante" class="form-label">Representante:</label>
                        <input type="text" name="representante" id="representante" class="form-control" value="{{old('representante')}}">
                        @error('representante')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="nit" class="form-label">Nit:</label>
                        <input type="text" name="nit" id="nit" class="form-control" value="{{old('nit')}}">
                        @error('nit')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                    <label for="imagen" class="form-label">Logo:</label>
                    <input type="file" name="image" id="image" accept="image/*" class="form-control" value="{{old('image')}}">
                    @error('image')
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

@endpush
