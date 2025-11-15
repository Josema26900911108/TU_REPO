@extends('layouts.app')

@section('title','Editar tienda')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Tienda</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tienda.index')}}">Tienda</a></li>
        <li class="breadcrumb-item active">Editar Tienda</li>
    </ol>

    <div class="card">
        <form action="{{ route('tienda.update',['tienda'=>$tienda]) }}" method="post" enctype="multipart/form-data">
            @method('PATCH')
            @csrf
            <div class="card-body text-bg-light">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label for="Nombre" class="form-label">Nombre:</label>
                        <input type="text" name="Nombre" id="Nombre" class="form-control" value="{{old('Nombre',$tienda->Nombre)}}">
                        @error('Nombre')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="Telefono" class="form-label">Telefono:</label>
                        <input type="text" name="Telefono" id="Telefono" class="form-control" value="{{old('Telefono',$tienda->Telefono)}}">
                        @error('Telefono')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="row g-2">

                <div class="col-md-6">
                    <label for="Direccion" class="form-label">Dirección:</label>
                    <input type="text" name="Direccion" id="Direccion" class="form-control" value="{{old('Direccion',$tienda->Direccion)}}">
                    @error('Direccion')
                    <small class="text-danger">{{'*'.$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="descripcion" class="form-label">Descripción:</label>
                    <input type="text" name="descripcion" id="descripcion" class="form-control" value="{{old('descripcion',$tienda->descripcion)}}">
                    @error('descripcion')
                    <small class="text-danger">{{'*'.$message}}</small>
                    @enderror
                </div>
                                <div class="col-md-6">
                    <label for="image" class="form-label">Logo:</label>
                    <input type="file" name="image" id="image" accept="image/*" class="form-control" value="{{old('image',$tienda->logo)}}">
                    @error('image')
                    <small class="text-danger">{{'*'.$message}}</small>
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
