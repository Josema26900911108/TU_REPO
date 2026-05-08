@extends('layouts.app')

@section('title','Crear centro')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Centro Organizacion</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('centroorganizacion.index')}}">Centros Organizacion</a></li>
        <li class="breadcrumb-item active">Crear Centro Organizacion</li>
    </ol>

    <div class="card text-bg-light">
        <form action="{{ route('centroorganizacion.store') }}" method="post">
            @csrf
            <div class="card-body">
                <div class="row g-4">

                    <div class="col-md-12">
                        <label for="fkTiendaDependiente" class="form-label">Tienda Destino:</label>
                        <select name="fkTiendaDependiente" id="fkTiendaDependiente" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona" data-size='11'>
                        @foreach ($Tiendas as $Tienda)
                            <option value="{{ $Tienda->idTienda }}">{{ $Tienda->Nombre }}</option>
                        @endforeach
                        </select>
                        @error('fkTiendaDependiente')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="fkCentro" class="form-label">Centro:</label>
                        <select name="fkCentro" id="fkCentro" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona" data-size='11'>
                        @foreach ($centros as $centro)
                            <option value="{{ $centro->id }}">{{ $centro->codigo.' - '.$centro->nombre }}</option>
                        @endforeach
                        </select>
                        @error('fkCentro')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
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
