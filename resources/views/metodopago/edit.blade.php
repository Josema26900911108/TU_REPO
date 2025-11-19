@extends('layouts.app')

@section('title','Editar Comprobante')

@push('css')
<style>
    #descripcion {
        resize: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Editar Comprobante</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('comprobante.index')}}">Comprobante</a></li>
        <li class="breadcrumb-item active">Editar Comprobante</li>
    </ol>

    <div class="card">
        <form action="{{ route('comprobante.update',['comprobante'=>$comprobante]) }}" method="post">
            @method('PATCH')
            @csrf
            <div class="card-body text-bg-light">

                <div class="row g-4">

                    <div class="col-md-8">
                        <label for="tipo_comprobante" class="form-label">Nombre:</label>
                        <input type="text" name="tipo_comprobante" id="tipo_comprobante" class="form-control" value="{{old('tipo_comprobante',$comprobante->tipo_comprobante)}}">
                        @error('tipo_comprobante')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>

                </div>
                <div class="row g-4">

                    <div class="col-md-8">
                        <label for="formula" class="form-label">Formula:</label>
                        <input type="text" name="formula" id="formula" class="form-control" value="{{old('formula',$comprobante->formula)}}">
                        @error('formula')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>
                </div>
                    <!---Marca---->
                    <div class="col-md-6">
                        <label for="marca_id" class="form-label">Vista a Mostrar:</label>
                        <select data-size="4" title="Seleccione una Vista" data-live-search="true" name="clavevista" id="clavevista" class="form-control selectpicker show-tick">
                            @foreach ($clavevista as $clave => $valor)
                            @if ($comprobante->ClaveVista == $clave)
                            <option selected value="{{$clave}}" {{ old('ClaveVista') == $clave ? 'selected' : '' }}>{{$valor}}</option>
                            @else
                            <option value="{{$clave}}" {{ old('ClaveVista') == $clave ? 'selected' : '' }}>{{$valor}}</option>
                            @endif
                            @endforeach
                        </select>
                        @error('marca_id')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
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
