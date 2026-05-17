
@extends('layouts.app')

@section('title','Panel')

@push('css')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@endpush

@section('content')

@if (session('success'))
<script>
    document.addEventListener("DOMContentLoaded", function() {

        let message = "{{ session('success') }}";
        Swal.fire(message);

    });
</script>
@endif



<div class="container-fluid px-4">
    <h1 class="mt-4">Panel</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Panel</li>
    </ol>
    <div class="row">
        @can('panel-cliente')
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-people-group"></i><span class="m-1">Clientes</span>
                        </div>
                        <div class="col-4">
                            @php
                                $clientes = \App\Models\Cliente::where('fkTienda', session('user_fkTienda'))->count();
                            @endphp
                            <p class="text-center fw-bold fs-4">{{ $clientes }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('clientes.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        @endcan
@can('cobrar-ventadirecta')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #1d976c, #93f9b9); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <!-- Icono de caja registradora más acorde a venta -->
                    <i class="fa-solid fa-cash-register fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Ventas de Hoy</div>
                    <?php
                        $ventadirecta = DB::table('ventas')
                            ->whereDate('fecha_hora', now()) // Corrección: whereDate es más eficiente en Laravel
                            ->where('estado', 2)
                            ->where('fkTienda', session('user_fkTienda')) // Filtro por tienda
                            ->count(); // count() es más rápido que count(get())
                    ?>
                    <h2 class="fw-bold mb-0">{{ $ventadirecta }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between bg-black-50 border-0" style="background: rgba(0,0,0,0.1);">
            <!-- Ruta corregida: Debería ir a cobrarventasdir en lugar de presentaciones -->
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('arqueocaja.cobventasdir') }}">
                Nueva Venta Directa
            </a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan

        @can('panel-categoria')

        <!----Categoria--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-tag"></i><span class="m-1">Categorías</span>
                        </div>
                        <div class="col-4">
                            @php

                            $categorias = \App\Models\Categoria::count();
                            @endphp
                            <p class="text-center fw-bold fs-4">{{$categorias}}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('categorias.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        @endcan
        @can('panel-compra')

        <!----Compra--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-store"></i><span class="m-1">Compras</span>
                        </div>
                        <div class="col-4">
                            @php
                            $compras = \App\Models\Compra::where('fkTienda', session('user_fkTienda'))->count();
                            @endphp
                            <p class="text-center fw-bold fs-4">{{$compras}}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('compras.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        @endcan
        @can('panel-marca')

        <!----Marcas--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-bullhorn"></i><span class="m-1">Marcas</span>
                        </div>
                        <div class="col-4">
                            @php

                            $marcas = \App\Models\Marca::count();
                            @endphp
                            <p class="text-center fw-bold fs-4">{{$marcas}}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('marcas.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        @endcan
        @can('panel-presentacione')

        <!----Presentaciones--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-box-archive"></i><span class="m-1">Presentaciones</span>
                        </div>
                        <div class="col-4">
                            @php

                            $presentaciones = \App\Models\Presentacione::count();
                            @endphp
                            <p class="text-center fw-bold fs-4">{{$presentaciones}}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('presentaciones.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        @endcan
        @can('panel-producto')

        <!----Producto--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-brands fa-shopify"></i><span class="m-1">Productos</span>
                        </div>
                        <div class="col-4">
                            @php

                            $productos = \App\Models\Producto::where('fkTienda', session('user_fkTienda'))->count();
                            @endphp
                            <p class="text-center fw-bold fs-4">{{$productos}}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="{{ route('productos.index') }}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        @endcan
      <!-- CLIENTES -->
@can('panel-cliente')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #4e73df, #224abe); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <i class="fa-solid fa-people-group fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Clientes</div>
                    @php $clientes = \App\Models\Cliente::count(); @endphp
                    <h2 class="fw-bold mb-0">{{ $clientes }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between border-0" style="background: rgba(0,0,0,0.1);">
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('clientes.index') }}">Gestionar Clientes</a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan

<!-- VENTA DIRECTA -->
@can('cobrar-ventadirecta')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #1d976c, #93f9b9); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <i class="fa-solid fa-cash-register fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Ventas de Hoy</div>
                    @php
                        $ventadirecta = DB::table('ventas')
                            ->whereDate('fecha_hora', now())
                            ->where('estado', 2)
                            ->where('fkTienda', session('user_fkTienda'))
                            ->count();
                    @endphp
                    <h2 class="fw-bold mb-0">{{ $ventadirecta }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between border-0" style="background: rgba(0,0,0,0.1);">
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('arqueocaja.cobventasdir') }}">Nueva Venta Directa</a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan

<!-- CATEGORÍAS -->
@can('panel-categoria')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #f6ad55, #ed8936); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <i class="fa-solid fa-tag fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Categorías</div>
                    @php $categorias = \App\Models\Categoria::count(); @endphp
                    <h2 class="fw-bold mb-0">{{ $categorias }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between border-0" style="background: rgba(0,0,0,0.1);">
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('categorias.index') }}">Ver Categorías</a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan

<!-- COMPRAS -->
@can('panel-compra')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #38b2ac, #319795); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <i class="fa-solid fa-cart-arrow-down fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Compras Realizadas</div>
                    @php 
                        $compras = \App\Models\Compra::where('fkTienda', session('user_fkTienda'))->count(); 
                    @endphp
                    <h2 class="fw-bold mb-0">{{ $compras }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between border-0" style="background: rgba(0,0,0,0.1);">
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('compras.index') }}">Historial Compras</a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan

<!-- PRODUCTOS -->
@can('panel-producto')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #667eea, #764ba2); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <i class="fa-solid fa-boxes-stacked fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Productos en Tienda</div>
                    @php 
                        $productos = \App\Models\Producto::where('fkTienda', session('user_fkTienda'))->count(); 
                    @endphp
                    <h2 class="fw-bold mb-0">{{ $productos }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between border-0" style="background: rgba(0,0,0,0.1);">
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('productos.index') }}">Inventario</a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan
<!-- PROVEEDORES -->
@can('panel-proveedore')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #f6d365, #fda085); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <i class="fa-solid fa-truck-field fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Proveedores</div>
                    @php $proveedores = \App\Models\Proveedore::where('fkTienda', session('user_fkTienda'))->count(); @endphp
                    <h2 class="fw-bold mb-0">{{ $proveedores }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between border-0" style="background: rgba(0,0,0,0.1);">
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('proveedores.index') }}">Directorio Comercial</a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan

<!-- USUARIOS -->
@can('panel-user')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #21d4fd, #b721ff); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <i class="fa-solid fa-user-gear fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Personal</div>
                    @php $users = \App\Models\User::where('fkTienda', session('user_fkTienda'))->count(); @endphp
                    <h2 class="fw-bold mb-0">{{ $users }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between border-0" style="background: rgba(0,0,0,0.1);">
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('users.index') }}">Gestionar Accesos</a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan

<!-- CAJA -->
@can('panel-caja')
<div class="col-xl-3 col-md-6">
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(45deg, #0093E9, #80D0C7); color: #fff;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-3">
                    <i class="fa-solid fa-vault fa-2x opacity-75"></i>
                </div>
                <div class="col-9 text-end">
                    <div class="text-white-50 small text-uppercase fw-bold">Cajas Registradas</div>
                    @php 
                        $cash = \App\Models\Cash_registers::where('fkTienda', session('user_fkTienda'))->count(); 
                    @endphp
                    <h2 class="fw-bold mb-0">{{ $cash }}</h2>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center justify-content-between border-0" style="background: rgba(0,0,0,0.1);">
            <a class="small text-white stretched-link text-decoration-none" href="{{ route('cash.index') }}">Control de Cajas</a>
            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
        </div>
    </div>
</div>
@endcan

    </div>

</div>
@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<!---script src="{{ asset('assets/demo/chart-area-demo.js') }}"></script--->
<!---script src="{{ asset('assets/demo/chart-bar-demo.js') }}"></script--->
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>
@endpush
