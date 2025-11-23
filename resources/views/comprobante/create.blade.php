@extends('layouts.app')

@section('title', 'Crear Comprobantes')

@push('css')
<style>
    table {
        width: 100%;
        border-collapse: collapse;
    }
    table, th, td {
        border: 1px solid black;
    }
    th, td {
        padding: 8px;
        text-align: left;
    }
    .expandable {
        cursor: pointer;
        background-color: #f2f2f2;
    }
    .hidden-row {
        display: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Comprobante</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('comprobante.index')}}">Comprobantes</a></li>
        <li class="breadcrumb-item active">Crear comprobantes</li>
    </ol>

    <div class="card">
        <div class="card-header">
            <p>Nota: Los Comprobantes son documento de pago o creditos que estan asociado a una formula para calcular sus respectivos montos.</p>
        </div>
        <div class="card-body">
            <form action="{{ route('comprobante.store') }}" method="post">
                @csrf
                <!-- Nombre de Comprobante -->
                <div class="row mb-4">
                    <label for="tipo_comprobante" class="col-md-auto col-form-label">Nombre del comprobante:</label>
                    <div class="col-md-4">
                        <input autocomplete="off" type="text" name="tipo_comprobante" id="tipo_comprobante" class="form-control" value="{{ old('tipo_comprobante') }}">
                    </div>
                    <div class="col-md-4">
                        @error('tipo_comprobante')
                        <small class="text-danger">{{ '*' . $message }}</small>
                        @enderror
                    </div>
                </div>

                                <!-- Nombre de Formula -->
                                <div class="row mb-4">
                                    <label for="formula" class="col-md-auto col-form-label">Formula (224 max):</label>
                                    <div class="col-md-4">
                                        <input autocomplete="off" type="text" name="formula" id="formula" class="form-control" value="{{ old('formula') }}">
                                    </div>
                                    <div class="col-md-4">
                                        @error('formula')
                                        <small class="text-danger">{{ '*' . $message }}</small>
                                        @enderror
                                    </div>
                                </div>

                    <!---Vista---->
                    <div class="row mb-4">
                        <label for="clavevista" class="col-md-auto col-form-label">Vista a Mostrar:</label>
                        <select data-size="4" title="Seleccione una Vista" data-live-search="true" name="clavevista" id="clavevista" class="form-control selectpicker show-tick">
                            @foreach ($clavevista as $clave => $valor)

                            <option value="{{$clave}}" {{ old('ClaveVista') == $clave ? 'selected' : '' }}>{{$valor}}</option>

                            @endforeach
                        </select>
                        @error('clavevista')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>


                    <!---Documento---->
                    <div class="row mb-4">
                        <label for="disdoc" class="col-md-auto col-form-label">Diseño Documento:</label>
                        <select data-size="4" title="Seleccione una Diseño Documento" data-live-search="true" name="disdoc" id="disdoc" class="form-control selectpicker show-tick">
@foreach ($designs as $design)
    <option value="{{ $design->id }}"
        {{ old('disdoc') == $design->id ? 'selected' : '' }}>
        {{ $design->Titulo }}
    </option>
@endforeach
                        </select>
                        @error('disdoc')
                        <small class="text-danger">{{'*'.$message}}</small>
                        @enderror
                    </div>


                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Al cambiar el estado del checkbox principal
            $('#checkAll').change(function() {
                // Cambiar el estado de todos los checkboxes de permisos
                $('.permission').prop('checked', this.checked);
            });

            // Al cambiar el estado de un checkbox individual
            $('.permission').change(function() {
                // Si todos los checkboxes individuales están marcados, marcar el checkbox principal
                if ($('.permission:checked').length === $('.permission').length) {
                    $('#checkAll').prop('checked', true);
                } else {
                    // Si no todos están marcados, desmarcar el checkbox principal
                    $('#checkAll').prop('checked', false);
                }
            });
        });
    </script>
</div>
@endsection

@push('js')
<!-- Puedes agregar scripts adicionales aquí -->
@endpush
