
@extends('layouts.app')

@section('title','Panel Caja')

@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush
@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
@section('content')

<?php
use Illuminate\Support\Facades\DB;
?>
<div class="container-fluid px-4">
    <h1 class="mt-4">Bienvenido a Caja {{ $caja->Nombre }}</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Panel</li>
    </ol>
    <div class="row">
        @can('panel-caja-venta')
        <!----Ventas--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-cart-shopping"></i><span class="m-1">Cobro Ventas</span>
                        </div>
                        <div class="col-4">
                            <?php

                            $categorias = count(DB::table('cash_registers as cr ')
                            ->join('caja as c', 'c.idCaja', '=', 'cr.id')
                            ->select('c.id')
                            ->where('c.idCaja', $caja->id)  // Asegúrate que este sea el campo correcto
                            ->whereNot('c.idventa', NULL)
                            ->get());
                            ?>
                            <p class="text-center fw-bold fs-4">{{$categorias}}</p>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex align-items-center justify-content-between" >
                    <a class="small text-white stretched-link" href="{{route('arqueocaja.ventas',['ventas'=>$caja->id])}}">Ver más</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        @endcan
        <!----Compra--->
        @can('panel-caja-comprar')
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-money-check"></i><span class="m-1">Pagar Compras</span>
                        </div>
                        <div class="col-4">
                            <?php

                            $compras = count(DB::table('cash_registers as cr')
                            ->join('caja as c', 'c.idCaja', '=', 'cr.id')
                            ->select('c.id')
                            ->where('c.idCaja', $caja->id)  // Asegúrate que este sea el campo correcto
                            ->whereNot('c.idcompra', NULL)
                            ->get());
                            ?>
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
        @can('panel-caja-otro')
        <!----Marcas--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-wallet"></i><span class="m-1">Otras Cuentas</span>
                        </div>
                        <div class="col-4">
                            <?php

                            $marcas = count(DB::table('cash_registers as cr')
                            ->join('caja as c', 'c.idCaja', '=', 'cr.id')
                            ->select('c.id')
                            ->where('c.idCaja', $caja->id)  // Asegúrate que este sea el campo correcto
                            ->whereNot('c.fkOtro', NULL)
                            ->get());
                            ?>
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
        @can('panel-caja-banco')
        <!----Presentaciones--->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-dark text-white mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <i class="fa-solid fa-vault"></i><span class="m-1">Bancos</span>
                        </div>
                        <div class="col-4">
                            <?php
                            $banco = count(DB::table('cash_registers as cr')
                            ->join('caja as c', 'c.idCaja', '=', 'cr.id')
                            ->select('c.id')
                            ->where('c.idCaja', $caja->id)  // Asegúrate que este sea el campo correcto
                            ->whereNot('c.fkBanco', NULL)
                            ->get());
                            ?>
                            <p class="text-center fw-bold fs-4">{{$banco}}</p>
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

@endsection

@push('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<!---script src="{{ asset('assets/demo/chart-area-demo.js') }}"></script--->
<!---script src="{{ asset('assets/demo/chart-bar-demo.js') }}"></script--->
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>
@endpush
