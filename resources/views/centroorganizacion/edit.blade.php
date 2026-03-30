@extends('layouts.app')

@section('title','Editar Centro Organizacion')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net">
<style>
    #descripcion { resize: none; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Centro Organización</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('centroorganizacion.index')}}">Centros Organizacion</a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol>

    <div class="card text-bg-light">
        {{-- Usamos el ID directamente para evitar el error de parámetro --}}
        <form action="{{ route('centroorganizacion.update', ['centroorganizacion' => $centroorganizacion->id]) }}" method="post">
            @method('PATCH')
            @csrf
            <div class="card-body">
                <div class="row g-4">

                    <!-- Tienda Destino -->
                    <div class="col-md-12">
                        <label for="fkTiendaDependiente" class="form-label">Tienda Destino:</label>
                        <select name="fkTiendaDependiente" id="fkTiendaDependiente" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona">
                        @foreach ($Tiendas as $Tienda)
                            <option value="{{ $Tienda->idTienda }}" {{ old('fkTiendaDependiente', $centroorganizacion->fkTiendaDependiente) == $Tienda->idTienda ? 'selected' : '' }}>
                                {{ $Tienda->Nombre }}
                            </option>
                        @endforeach
                        </select>
                        @error('fkTiendaDependiente')
                            <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                    <!-- Centro -->
                    <div class="col-md-12">
                        <label for="fkCentro" class="form-label">Centro:</label>
                        <select name="fkCentro" id="fkCentro" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona">
                        @foreach ($centros as $centro)
                            <option value="{{ $centro->id }}" {{ old('fkCentro', $centroorganizacion->fkCentro) == $centro->id ? 'selected' : '' }}>
                                {{ $centro->codigo.' - '.$centro->nombre }}
                            </option>
                        @endforeach
                        </select>
                        @error('fkCentro')
                            <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                                        <!-- Centro -->
<div class="col-md-12">
    <label for="status" class="form-label">Estatus:</label>
    <select name="status" id="status" class="form-control selectpicker show-tick" data-live-search="true" title="Selecciona">
        {{-- Opción Activo --}}
        <option value="A" {{ old('status', $centroorganizacion->status) == 'A' ? 'selected' : '' }}>
            Activo
        </option>

        {{-- Opción Inactivo --}}
        <option value="I" {{ old('status', $centroorganizacion->status) == 'I' ? 'selected' : '' }}>
            Inactivo
        </option>
    </select>
    @error('status')
        <small class="text-danger">{{'*'.$message}}</small>
    @enderror
</div>


                </div>
            </div>
            <div class="card-footer text-center">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="{{ route('centroorganizacion.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net"></script>
@endpush
