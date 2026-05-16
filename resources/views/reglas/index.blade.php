@extends('layouts.app')

@section('title','Centros')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Centros</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Centros</li>
    </ol>

    @can('crear-centro')
    <div class="mb-4">
        <a href="{{route('centros.create')}}">
            <button type="button" class="btn btn-primary">Añadir nuevo registro</button>
        </a>
    </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla centros
        </div>
        <div class="container-fluid py-4">
    <!-- Barra de Filtros -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="{{ route('reglas.index') }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre de regla..." value="{{ request('buscar') }}">
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="activa" {{ request('estado') == 'activa' ? 'selected' : '' }}>Activas</option>
                        <option value="vencida" {{ request('estado') == 'vencida' ? 'selected' : '' }}>Vencidas</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
                    <a href="{{ route('reglas.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                    <a href="{{ route('reglas.create') }}" class="btn btn-primary w-100">Nueva Regla</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Reglas -->
    <div class="card shadow-sm">
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Estado</th>
                        <th>Regla / Promoción</th>
                        <th>Tipo</th>
                        <th>Configuración</th>
                        <th>Beneficio</th>
                        <th>Vigencia</th>
                        <th class="text-center">Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reglas as $regla)
                    <tr>
                        <td>
                            @php
                                $statusClass = now()->between($regla->fecha_inicio, $regla->fecha_fin) ? 'bg-success' : (now()->lt($regla->fecha_inicio) ? 'bg-warning text-dark' : 'bg-danger');
                                $statusText = now()->between($regla->fecha_inicio, $regla->fecha_fin) ? 'ACTIVA' : (now()->lt($regla->fecha_inicio) ? 'PROG' : 'VENC');
                            @endphp
                            <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $regla->nombre }}</div>
                            <small class="text-muted">ID: #{{ $regla->id }}</small>
                        </td>
                        <td><span class="text-uppercase small fw-semibold">{{ str_replace('_', ' ', $regla->tipo_regla) }}</span></td>
                        <td>
                            <span class="badge border text-dark">Min: {{ (int)$regla->cantidad_minima }}</span>
                            @if($regla->cantidad_paso > 0)
                                <span class="badge border text-dark">Paso: {{ (int)$regla->cantidad_paso }}</span>
                            @endif
                        </td>
                        <td>
                            @if($regla->tipo_beneficio == 'precio_fijo')
                                <span class="text-success fw-bold">${{ number_format($regla->valor_beneficio, 2) }}</span>
                            @elseif($regla->tipo_beneficio == 'porcentaje')
                                <span class="text-primary fw-bold">{{ number_format($regla->valor_beneficio, 0) }}% OFF</span>
                            @else
                                <span class="text-info fw-bold">+{{ (int)$regla->valor_beneficio }} Gratis</span>
                            @endif
                        </td>
                        <td>
                            <div class="small">{{ \Carbon\Carbon::parse($regla->fecha_inicio)->format('d/m/Y') }}</div>
                            <div class="small text-muted">{{ \Carbon\Carbon::parse($regla->fecha_fin)->format('d/m/Y') }}</div>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" title="Ver productos aplicados">
                                {{ $regla->aplicaciones_count }}
                            </button>
                        </td>
                        <td>
                            <!-- Botonera de acciones similar a tu sistema actual -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border dropdown-toggle" data-bs-toggle="dropdown">Opciones</button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('reglas.edit', $regla->id) }}">Editar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('reglas.destroy', $regla->id) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger">Eliminar</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No se encontraron reglas configuradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $reglas->appends(request()->query())->links() }}
        </div>
    </div>
</div>

    
    </div>

</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>
@endpush
