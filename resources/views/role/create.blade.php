@extends('layouts.app')

@section('title', 'Crear rol')

@push('css')
<!-- Puedes agregar CSS adicional aquí -->
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Crear Rol</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('roles.index')}}">Roles</a></li>
        <li class="breadcrumb-item active">Crear rol</li>
    </ol>

    <div class="card">
        <div class="card-header">
            <p>Nota: Los roles son un conjunto de permisos</p>
        </div>
        <div class="card-body">
            <form action="{{ route('roles.store') }}" method="post">
                @csrf
                <!-- Nombre de rol -->
                <div class="row mb-4">
                    <label for="name" class="col-md-auto col-form-label">Nombre del rol:</label>
                    <div class="col-md-4">
                        <input autocomplete="off" type="text" name="name" id="name" class="form-control" value="{{ old('name') }}">
                    </div>
                    <div class="col-md-4">
                        @error('name')
                        <small class="text-danger">{{ '*' . $message }}</small>
                        @enderror
                    </div>
                </div>

                <!-- Permisos -->
                <div class="col-12">
                    <p class="text-muted">Permisos para el rol:</p>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="checkAll" id="checkAll" class="form-check-input">
                        <label class="form-check-label" for="checkAll">Seleccionar Todos</label>
                    </div>
                    @foreach ($permisos as $item)
                    <div class="form-check mb-2">
                        <input type="checkbox" name="permission[]" id="{{ $item->id }}" class="form-check-input permission" value="{{ $item->name }}">
                        <label for="{{ $item->id }}" class="form-check-label">{{ $item->name }}</label>
                    </div>
                    @endforeach
                </div>
                @error('permission')
                <small class="text-danger">{{ '*' . $message }}</small>
                @enderror

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
