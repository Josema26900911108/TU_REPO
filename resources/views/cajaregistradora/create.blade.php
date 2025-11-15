@extends('layouts.app')

@section('title','Crear Ingreso Caja')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Caja</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('cajaregistradora.index')}}">Caja</a></li>
        <li class="breadcrumb-item active">Crear presentación</li>
    </ol>

    <div class="card">
        <form action="{{ route('cajaregistradora.open') }}" method="post">
            @csrf
            <div class="card-body text-bg-light">

                <div class="row g-4">

                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Monto Inicial:</label>
                        <input type="number" step="0.01" required name="initial_amount" id="initial_amount" class="form-control" value="{{old('initial_amount')}}">
                        @error('nombre')
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
