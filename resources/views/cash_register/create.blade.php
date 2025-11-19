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
    <h1 class="mt-4 text-center">Crear Caja Registradora</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('cash.index')}}">Caja Registradoras</a></li>
        <li class="breadcrumb-item active">Crear producto</li>
    </ol>

    <div class="card">
        <form action="{{ route('cash.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card-body text-bg-light">

                <div class="row g-4">

                    <!----Codigo---->
                    <div class="col-md-6">
                        <label for="Nombre" class="form-label">Nombre:</label>
                        <input type="text" name="Nombre" id="Nombre" class="form-control" value="{{old('Nombre')}}">
                        @error('Nombre')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!---Nombre---->
                    <div class="col-md-6" visible="false">
                        <label for="estatus" class="form-label">Estatus:</label>
                        <input type="text" name="estatus" id="estatus" class="form-control" value="{{ old('estatus', 'A') }}">
                        @error('estatus')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                    <!---Tienda---->
                    <div class="col-md-6">
                        <label for="fkTienda" class="form-label">Tienda:</label>
                        <select data-size="4" title="Seleccione una Tienda" data-live-search="true" name="fkTienda" id="fkTienda" class="form-control selectpicker show-tick">
                            @foreach ($tiendas as $item)
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
