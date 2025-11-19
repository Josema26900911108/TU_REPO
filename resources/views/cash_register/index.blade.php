@extends('layouts.app')

@section('title','Caja Registradora')
@push('css-datatable')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
@endpush
@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="container-fluid px-4">
    <h1 class="mt-4 text-center">Caja</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Caja</li>
    </ol>

    @can('crear-caja')
    <div class="mb-4">
        <a href="{{route('cash.create')}}">
            <button type="button" class="btn btn-primary">Añadir nuevo registro</button>
        </a>
    </div>
    @endcan

    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Tabla presentaciones
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped fs-6">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tienda</th>
                        <th>Monto Inicial</th>
                        <th>Monto Cierre</th>
                        <th>Estatus Caja</th>
                        <th>Fecha/Hora Apertura</th>
                        <th>Fecha/Hora Cierre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                        @foreach ($cashRegister as $item)
                        <tr>
                        <td>
                            {{$item->Nombre}}
                        </td>
                        <td>
                            {{$item->tienda->Nombre;}}
                        </td>
                        <td>
                            {{$item->initial_amount}}
                        </td>
                        <td>
                            {{$item->closing_amount}}
                        </td>
                        <td>
                            @if ($item->Estatus == "I")
                            <span class="badge rounded-pill text-bg-info">Inicial</span>
                            @endif
                            @if ($item->Estatus == "A")
                            <span class="badge rounded-pill text-bg-primary">Nuevo</span>
                            @endif
                            @if ($item->Estatus == "B")
                            <span class="badge rounded-pill text-bg-danger">Baja</span>
                            @endif
                            @if ($item->Estatus == "O")
                            <span class="badge rounded-pill text-bg-success">Abierto</span>
                            @endif
                            @if ($item->Estatus == "C")
                            <span class="badge rounded-pill text-bg-white">Cerrado</span>
                            @endif
                        </td>

                        <td>
                            {{$item->opened_at}}
                        </td>

                        <td>
                            {{$item->closed_at}}
                        </td>
                        <td>
                            <div class="d-flex justify-content-around">
                                <div>
                                    <button title="Opciones" class="btn btn-datatable btn-icon btn-transparent-dark me-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg class="svg-inline--fa fa-ellipsis-vertical" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="ellipsis-vertical" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 512" data-fa-i2svg="">
                                            <path fill="currentColor" d="M56 472a56 56 0 1 1 0-112 56 56 0 1 1 0 112zm0-160a56 56 0 1 1 0-112 56 56 0 1 1 0 112zM0 96a56 56 0 1 1 112 0A56 56 0 1 1 0 96z"></path>
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu text-bg-light" style="font-size: small;">
                                        <!-----Editar presentacione--->
                                        @can('editar-caja')
                                        <li><a class="dropdown-item" href="{{route('cash.edit',['cash'=>$item])}}">Editar</a></li>
                                        @endcan
                                        @can('eliminar-caja')
                                        <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#confirmModal-{{$item->id}}"  href="{{route('cash.edit',['cash'=>$item])}}">Eliminar</a></li>
                                        @endcan
                                    </ul>
                                </div>
                                <div>
                                    <!----Separador----->
                                    <div class="vr"></div>
                                </div>

                                <div>
                                    <a title="Historial" href="{{route('arqueocaja.show',['arqueocaja'=>$item])}}">
                                        <svg viewBox="0 -2.5 1029 1029" fill="#102c7f" class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" stroke="#102c7f"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M773.069631 1024c-141.295516 0-256.163536-114.868021-256.163537-256.163536s114.868021-256.163536 256.163537-256.163537 256.163536 114.868021 256.163536 256.163537-114.868021 256.163536-256.163536 256.163536z m0-433.698735c-97.991057 0-177.666028 79.674971-177.666028 177.666028s79.674971 177.666028 177.666028 177.666028 177.666028-79.674971 177.666028-177.666028-79.674971-177.666028-177.666028-177.666028zM886.62936 807.085218h-113.559729c-21.717644 0-39.248754-17.53111-39.248755-39.248754v-91.580427c0-21.717644 17.53111-39.248754 39.248755-39.248755s39.248754 17.53111 39.248754 39.248755v52.331672h74.310975c21.717644 0 39.248754 17.53111 39.248754 39.248755s-17.53111 39.248754-39.248754 39.248754zM715.766449 444.16507l-99.822665-36.763-25.904178 70.25527 26.165837 9.681359c29.698224-16.615306 62.143861-28.651591 96.551935-35.193049l3.009071-7.98058zM391.179251 100.738469l485.114604 178.712662-62.40552 169.554618c26.689153 3.401559 52.200843 10.073847 76.404242 19.493548l86.739747-235.623355L344.604063 0 147.575316 535.09135c29.044078-26.819982 63.452153-50.107576 103.224224-53.116647L391.179251 100.738469zM429.917772 278.744653l25.864929-70.229104 99.822665 36.763-25.878012 70.229104zM634.547694 354.089178l25.851846-70.216021 99.822665 36.763-25.864929 70.216021zM451.491504 767.836464v-1.046634c-17.269452 15.045356-34.669733 33.361441-52.72416 53.116648-3.532388 3.924875-6.933947 7.588093-9.812189 10.727992-14.783697 15.437843-34.800562 25.51169-49.191772 24.203399-4.579021-0.392488-10.073847-1.962438-15.437843-10.073847-4.84068-7.326434-13.213747-30.875687-5.363997-46.444359l11.905456-23.418424-17.138623-19.886035c-14.39121-16.615306-32.838124-25.51169-53.639964-25.773349h-1.046634c-47.88348 0-90.533793 46.706018-124.811038 84.384822-3.663217 3.924875-7.064776 7.718922-10.073847 10.989651-12.952089 13.606235-28.651591 21.717644-40.818705 20.671011-4.448192-0.261658-10.989651-1.962438-17.923597-12.428772l-65.414591 43.435288c18.446915 27.866616 45.659384 44.612751 76.404242 47.229334 36.370512 3.1399 74.441804-13.213747 104.794174-45.136067 3.401559-3.663217 7.064776-7.718922 10.989651-11.905456 9.288872-10.204676 28.78242-31.529833 45.266897-45.397726 1.700779 28.913249 12.82126 54.032452 21.586814 67.246199 17.400281 26.296665 43.696946 42.126996 74.049317 44.74358 38.071292 3.27073 81.375751-15.307014 113.036412-48.668455 3.27073-3.532388 6.803117-7.457263 10.597164-11.512968 2.485754-2.747413 5.625655-6.148972 9.158043-9.943018-9.288872-29.959882-14.39121-61.882203-14.39121-95.112814zM76.404242 715.112304c36.370512 3.1399 74.441804-13.213747 104.794174-45.136068 3.401559-3.663217 7.064776-7.718922 10.858822-11.905455 9.288872-10.204676 28.78242-31.529833 45.266896-45.397726 1.700779 28.913249 12.82126 54.032452 21.586815 67.246199 17.400281 26.296665 43.696946 42.126996 74.049317 44.74358 38.071292 3.1399 81.375751-15.307014 113.036412-48.668456 3.27073-3.532388 6.803117-7.457263 10.597164-11.512967 4.709851-5.102338 11.905455-12.952089 19.886035-21.325157 18.839402-44.481922 47.360164-83.861505 82.945701-115.522167a68.68532 68.68532 0 0 0-27.735786-6.148971h-1.046634c-45.790213 0-86.870576 40.818704-131.875814 90.010476-3.532388 3.924875-6.933947 7.588093-9.812189 10.727993-14.783697 15.568673-34.800562 25.51169-49.191772 24.203399-4.579021-0.392488-10.073847-1.962438-15.437843-10.073847-4.84068-7.326434-13.213747-30.875687-5.363997-46.44436l11.905456-23.418423-17.138623-19.886036c-14.39121-16.615306-32.838124-25.51169-53.639964-25.773348h-1.046634c-47.88348 0-90.533793 46.706018-124.811038 84.384821-3.663217 3.924875-7.064776 7.718922-10.073847 10.989652-12.952089 13.606235-28.651591 21.848473-40.818705 20.67101-4.448192-0.261658-10.989651-1.962438-17.923597-12.428772l-65.414591 43.435288c18.446915 27.866616 45.659384 44.612751 76.404242 47.229335zM385.553597 402.33898l25.864929-70.229104 99.809582 36.763-25.864929 70.216021z"></path></g></svg>
                                        &nbsp;&nbsp;&nbsp;
                                    </a>
                                </div>
                                <div>
                                    <!----Separador----->
                                    <div class="vr"></div>
                                </div>

                                    <!------Eliminar Presentacione---->
                                    @can('ingresar-caja')
                                    @if ($item->Estatus == "O")
                                    <div>
                                        <a title="Ingresar Caja" href="{{route('arqueocaja.panel',['arqueocaja'=>$item])}}">
                                            <svg viewBox="0 0 24.00 24.00" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#f3b4b4" transform="matrix(1, 0, 0, 1, 0, 0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="2.112"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1c6e11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1c6e11" stroke-width="0.9120000000000001" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                            &nbsp;&nbsp;&nbsp;
                                        </a>
                                    </div>

                                    <div>
                                        <button title="Cierre Caja" data-bs-toggle="modal" data-id="{{ $item->id }}" data-bs-target="#cierreModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark  AbrircierreModal">
                                            <svg viewBox="0 0 64 64" id="calculator" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><defs><style>.cls-1{fill:#d8d8fc;}.cls-2{fill:#f03800;}.cls-3{fill:#f2f2fc;}.cls-4{fill:#4bb9ec;}.cls-5{fill:#fdbf00;}</style></defs><title></title><path class="cls-1" d="M55,17H37V34.992H55ZM45.2,34.046a6.784,6.784,0,1,1,0-13.568,1,1,0,0,1,1,1v4.784h4.784a1,1,0,0,1,1,1A6.787,6.787,0,0,1,45.2,34.046Zm8.676-8.519H48.1a1,1,0,0,1-1-1V18.743a1,1,0,0,1,1-1,6.787,6.787,0,0,1,6.784,6.784A1,1,0,0,1,53.88,25.527Z"></path><path class="cls-1" d="M33,53a1,1,0,0,1-1,1H19.015v5H59V13.008H33Zm2-37a1,1,0,0,1,1-1H56a1,1,0,0,1,1,1V35.992a1,1,0,0,1-1,1H36a1,1,0,0,1-1-1Zm0,24a1,1,0,0,1,1-1H56a1,1,0,0,1,1,1v4a1,1,0,0,1-1,1H36a1,1,0,0,1-1-1Zm1,7.008H56a1,1,0,0,1,0,2H36a1,1,0,0,1,0-2Zm0,4H56a1,1,0,0,1,0,2H36a1,1,0,0,1,0-2Zm0,4H56a1,1,0,0,1,0,2H36a1,1,0,0,1,0-2Z"></path><path d="M36,36.992H56a1,1,0,0,0,1-1V16a1,1,0,0,0-1-1H36a1,1,0,0,0-1,1V35.992A1,1,0,0,0,36,36.992ZM37,17H55V34.992H37Z"></path><rect class="cls-2" height="2" width="18" x="37" y="41"></rect><path d="M36,45H56a1,1,0,0,0,1-1V40a1,1,0,0,0-1-1H36a1,1,0,0,0-1,1v4A1,1,0,0,0,36,45Zm1-4H55v2H37Z"></path><path d="M36,49.008H56a1,1,0,0,0,0-2H36a1,1,0,0,0,0,2Z"></path><path d="M36,53.008H56a1,1,0,0,0,0-2H36a1,1,0,0,0,0,2Z"></path><path d="M36,57.008H56a1,1,0,0,0,0-2H36a1,1,0,0,0,0,2Z"></path><path class="cls-3" d="M31,52V5H5V52Zm-1.995-3.984a1,1,0,0,1-1,1h-4a1,1,0,0,1-1-1v-12a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1Zm-12.99-13h4a1,1,0,0,1,1,1v4a1,1,0,0,1-1,1h-4a1,1,0,0,1-1-1v-4A1,1,0,0,1,16.015,35.016Zm-1-3.008v-4a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1v4a1,1,0,0,1-1,1h-4A1,1,0,0,1,15.015,32.008Zm13.99,0a1,1,0,0,1-1,1h-4a1,1,0,0,1-1-1v-4a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1ZM28.99,24a1,1,0,0,1-1,1h-4a1,1,0,0,1-1-1V20a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1ZM7,8A1,1,0,0,1,8,7H28a1,1,0,0,1,1,1v8a1,1,0,0,1-1,1H8a1,1,0,0,1-1-1ZM7,20a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1v4a1,1,0,0,1-1,1H8a1,1,0,0,1-1-1Zm6.015,28.008a1,1,0,0,1-1,1h-4a1,1,0,0,1-1-1v-4a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1Zm0-7.992a1,1,0,0,1-1,1h-4a1,1,0,0,1-1-1v-4a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1Zm0-8.008a1,1,0,0,1-1,1h-4a1,1,0,0,1-1-1v-4a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1ZM15,20a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1v4a1,1,0,0,1-1,1H16a1,1,0,0,1-1-1Zm.015,28.008v-4a1,1,0,0,1,1-1h4a1,1,0,0,1,1,1v4a1,1,0,0,1-1,1h-4A1,1,0,0,1,15.015,48.008Z"></path><path d="M60,11.008H33V4a1,1,0,0,0-1-1H4A1,1,0,0,0,3,4V53a1,1,0,0,0,1,1H17.015v6a1,1,0,0,0,1,1H60a1,1,0,0,0,1-1V12.008A1,1,0,0,0,60,11.008ZM5,5H31V52H5ZM59,59H19.015V54H32a1,1,0,0,0,1-1V13.008H59Z"></path><rect class="cls-4" height="6" width="18" x="9" y="9"></rect><path d="M8,17H28a1,1,0,0,0,1-1V8a1,1,0,0,0-1-1H8A1,1,0,0,0,7,8v8A1,1,0,0,0,8,17ZM9,9H27v6H9Z"></path><rect class="cls-5" height="2" width="2" x="9" y="21"></rect><path d="M8,25h4a1,1,0,0,0,1-1V20a1,1,0,0,0-1-1H8a1,1,0,0,0-1,1v4A1,1,0,0,0,8,25Zm1-4h2v2H9Z"></path><rect class="cls-5" height="2" width="2" x="9.015" y="29.008"></rect><path d="M12.015,27.008h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1v-4A1,1,0,0,0,12.015,27.008Zm-1,4h-2v-2h2Z"></path><rect class="cls-5" height="2" width="2" x="9.015" y="37.016"></rect><path d="M12.015,35.016h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1v-4A1,1,0,0,0,12.015,35.016Zm-1,4h-2v-2h2Z"></path><rect class="cls-5" height="2" width="2" x="9.015" y="45.008"></rect><path d="M12.015,43.008h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1v-4A1,1,0,0,0,12.015,43.008Zm-1,4h-2v-2h2Z"></path><rect class="cls-5" height="2" width="2" x="17" y="21"></rect><path d="M16,25h4a1,1,0,0,0,1-1V20a1,1,0,0,0-1-1H16a1,1,0,0,0-1,1v4A1,1,0,0,0,16,25Zm1-4h2v2H17Z"></path><rect class="cls-5" height="2" width="2" x="17.015" y="29.008"></rect><path d="M16.015,33.008h4a1,1,0,0,0,1-1v-4a1,1,0,0,0-1-1h-4a1,1,0,0,0-1,1v4A1,1,0,0,0,16.015,33.008Zm1-4h2v2h-2Z"></path><rect class="cls-5" height="2" width="2" x="17.015" y="37.016"></rect><path d="M15.015,40.016a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1v-4a1,1,0,0,0-1-1h-4a1,1,0,0,0-1,1Zm2-3h2v2h-2Z"></path><rect class="cls-5" height="2" width="2" x="17.015" y="45.008"></rect><path d="M20.015,49.008a1,1,0,0,0,1-1v-4a1,1,0,0,0-1-1h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1Zm-3-4h2v2h-2Z"></path><rect class="cls-5" height="2" width="2" x="24.99" y="21"></rect><path d="M27.99,19h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1V20A1,1,0,0,0,27.99,19Zm-1,4h-2V21h2Z"></path><rect class="cls-5" height="2" width="2" x="25.005" y="29.008"></rect><path d="M28.005,27.008h-4a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1v-4A1,1,0,0,0,28.005,27.008Zm-1,4h-2v-2h2Z"></path><rect class="cls-2" height="10" width="2" x="25.005" y="37.016"></rect><path d="M28.005,35.016h-4a1,1,0,0,0-1,1v12a1,1,0,0,0,1,1h4a1,1,0,0,0,1-1v-12A1,1,0,0,0,28.005,35.016Zm-1,12h-2v-10h2Z"></path><path class="cls-5" d="M45.2,28.262a1,1,0,0,1-1-1V22.583a4.784,4.784,0,1,0,5.679,5.679Z"></path><path d="M50.988,26.262H46.2V21.479a1,1,0,0,0-1-1,6.784,6.784,0,1,0,6.784,6.784A1,1,0,0,0,50.988,26.262ZM45.2,32.046a4.784,4.784,0,0,1-1-9.463v4.679a1,1,0,0,0,1,1h4.679A4.788,4.788,0,0,1,45.2,32.046Z"></path><path class="cls-4" d="M49.1,19.848v3.679h3.679A4.8,4.8,0,0,0,49.1,19.848Z"></path><path d="M48.1,17.743a1,1,0,0,0-1,1v5.784a1,1,0,0,0,1,1H53.88a1,1,0,0,0,1-1A6.787,6.787,0,0,0,48.1,17.743Zm1,5.784V19.848a4.8,4.8,0,0,1,3.679,3.679Z"></path></g></svg>
                                        </button>
                                    </div>

                                    @elseif ($item->Estatus == "C")
                                    <div>
                                        <button title="Ingresar" data-bs-toggle="modal" data-id="{{ $item->id }}" data-bs-target="#aperturarModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                            <svg viewBox="0 0 24.00 24.00" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#f3b4b4" transform="matrix(-1, 0, 0, 1, 0, 0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="2.112"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#ed0202" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#ed0202" stroke-width="0.9120000000000001" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                            </svg>
                                        </button>
                                    </div>
                                    @elseif ($item->Estatus == "A")
                                    <div>
                                        <button title="Aperturar Caja" data-bs-toggle="modal" data-id="{{ $item->id }}" data-bs-target="#aperturarModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                            <svg viewBox="0 0 24.00 24.00" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#f3b4b4" transform="matrix(-1, 0, 0, 1, 0, 0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="2.112"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#ed0202" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#ed0202" stroke-width="0.9120000000000001" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                            </svg>
                                        </button>
                                    </div>
                                    @elseif ($item->Estatus == "I")
                                    <div>
                                        <button title="Ingresar" data-bs-toggle="modal" data-id="{{ $item->id }}" data-bs-target="#aperturarModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark abrirModal">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="rotate(0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="2.496">
                                                    <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    </path>
                                                </g>
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <div>
                                        <button title="Restaurar" data-bs-toggle="modal" data-bs-target="#aperturarModal-{{$item->id}}" class="btn btn-datatable btn-icon btn-transparent-dark">
                                            <i class="fa-solid fa-rotate"></i>
                                        </button>
                                    </div>
                                @endif
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Modal -->
                    <div class="modal fade" id="confirmModal-{{$item->id}}" tabindex="-2" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Mensaje de confirmación</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    {{ $item->closed_at == "" ? '¿Seguro que eliminara caja?' : '¿Desea eliminar caja?' }}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    <form action="{{ route('cash.destroy',['cash'=>$item->id]) }}" method="post">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Confirmar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="aperturarModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">Se va a aperturar Caja</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    {{ $item->closed_at == "" ? '¿Seguro que aperturara caja? Favor verificar que datos coinsidan con caja' : '¿Desea aperturar caja?' }}
                                    <form action="{{ route('arqueocaja.store',['arqueocaja'=>$item->id]) }}" method="post">
                                            <div class="col-sm-6 mb-4">
                                                <label for="CEF-{{$item->id}}" class="form-label">Efectivo al momento del cierre:</label>
                                                <input type="number" name="CEF-{{$item->id}}" id="CEF-{{$item->id}}" class="form-control">
                                            </div>
                                            <div class="col-sm-6 mb-4">
                                                <label for="VD-{{$item->id}}" class="form-label">Suma de ventas diarias:</label>
                                                <input type="number" name="VD-{{$item->id}}" id="VD-{{$item->id}}" class="form-control">
                                            </div>
                                            <div class="col-sm-6 mb-4">
                                                <label for="VO-{{$item->id}}" class="form-label">Pagos mediante el resto de medios habilitados:</label>
                                                <input type="number" name="VO-{{$item->id}}" id="VO-{{$item->id}}" class="form-control">
                                            </div>
                                            <div class="col-sm-6 mb-4">
                                                <label for="D-{{$item->id}}" class="form-label">Descuentos :</label>
                                                <input type="number" name="D-{{$item->id}}" id="D-{{$item->id}}" class="form-control">
                                            </div>
                                            <div class="col-sm-6 mb-4">
                                                <label for="CC-{{$item->id}}" class="form-label">Ventas a crédito:</label>
                                                <input type="number" name="CC-{{$item->id}}" id="CC-{{$item->id}}" class="form-control">
                                            </div>
                                            <div class="col-sm-6 mb-4">
                                                <label for="OG-{{$item->id}}" class="form-label">Salida de dinero para otras partidas de la empresa:</label>
                                                <input type="number" name="OG-{{$item->id}}" id="OG-{{$item->id}}" class="form-control">
                                            </div>
                                            <div class="col-sm-6 mb-4">
                                                <label for="CEI-{{$item->id}}" class="form-label">Saldo inicial de la caja:</label>
                                                <input type="number" name="CEI-{{$item->id}}" id="CEI-{{$item->id}}" class="form-control">
                                            </div>

                                        @csrf
                                            <button type="submit" class="btn btn-danger">Confirmar</button>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="cierreModal-{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel-{{$item->id}}" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel-{{$item->id}}">Se va a cerrar Caja {{ $item->Nombre }}</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    {{ $item->closed_at == "" ? '¿Seguro que cerrara caja? Favor verificar que datos coincidan con caja' : '¿Desea aperturar caja?' }}
                                    <form action="{{ route('arqueocaja.cierre',['arqueocaja'=>$item->id]) }}" method="post">
                                        <div class="col-sm-6 mb-4">
                                            <label for="CEFC-{{$item->id}}" class="form-label">Efectivo al momento del cierre:</label>
                                            <input type="number" name="CEFC" id="CEFC-{{$item->id}}" class="form-control">
                                        </div>
                                        <div class="col-sm-6 mb-4">
                                            <label for="VDC-{{$item->id}}" class="form-label">Suma de ventas diarias:</label>
                                            <input type="number" name="VDC" id="VDC-{{$item->id}}" class="form-control">
                                        </div>
                                        <div class="col-sm-6 mb-4">
                                            <label for="VOC-{{$item->id}}" class="form-label">Pagos mediante el resto de medios habilitados:</label>
                                            <input type="number" name="VOC" id="VOC-{{$item->id}}" class="form-control">
                                        </div>
                                        <div class="col-sm-6 mb-4">
                                            <label for="DC-{{$item->id}}" class="form-label">Descuentos :</label>
                                            <input type="number" name="DC" id="DC-{{$item->id}}" class="form-control">
                                        </div>
                                        <div class="col-sm-6 mb-4">
                                            <label for="CCC-{{$item->id}}" class="form-label">Ventas a crédito:</label>
                                            <input type="number" name="CCC" id="CCC-{{$item->id}}" class="form-control">
                                        </div>
                                        <div class="col-sm-6 mb-4">
                                            <label for="OGC-{{$item->id}}" class="form-label">Salida de dinero para otras partidas de la empresa:</label>
                                            <input type="number" name="OGC" id="OGC-{{$item->id}}" class="form-control">
                                        </div>
                                        <div class="col-sm-6 mb-4">
                                            <label for="CEIC-{{$item->id}}" class="form-label">Saldo inicial de la caja:</label>
                                            <input type="number" readonly="true" name="CEIC" id="CEIC-{{$item->id}}" class="form-control">
                                        </div>
                                        @csrf
                                        <button type="submit" class="btn btn-danger">Confirmar</button>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @endforeach
                </tbody>
            </table>

        </div>
    </div>

</div>
@endsection


@push('js')
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
<script src="{{ asset('js/datatables-simple-demo.js') }}"></script>
<script>

$(document).on('click', '.AbrircierreModal', function() {
    var tiendaId = $(this).data('id');
    var modalSelector = '#cierreModal-' + tiendaId;
    var RECORRER=0;
    $(modalSelector).find('#CEFC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#VDC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#VOC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#DC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#CCC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#OGC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#CEIC-' + tiendaId).val(0).prop('readonly', true);
    $.ajax({
        url: '/metodopago/detalle/' + tiendaId,
        method: 'GET',
        success: function(data) {

            if (data.length > 0) {
                let firstItem = data[RECORRER];
            // Populate the specific modal fields using modalSelector
            if(firstItem.MetodoPago == 'CEF') {
                $(modalSelector).find('#CEFC-' + tiendaId).val(firstItem.Monto);
            } else if (firstItem.MetodoPago == 'VD') {
                $(modalSelector).find('#VDC-' + tiendaId).val(firstItem.Monto);
            } else if (firstItem.MetodoPago == 'VO') {
                $(modalSelector).find('#VOC-' + tiendaId).val(firstItem.Monto);
            } else if (firstItem.MetodoPago == 'D') {
                $(modalSelector).find('#DC-' + tiendaId).val(firstItem.Monto);
            } else if (firstItem.MetodoPago == 'CC') {
                $(modalSelector).find('#CCC-' + tiendaId).val(firstItem.Monto);
            } else if (firstItem.MetodoPago == 'OG') {
                $(modalSelector).find('#OGC-' + tiendaId).val(firstItem.Monto);
            } else if (firstItem.MetodoPago == 'CEI') {
                $(modalSelector).find('#CEIC-' + tiendaId).val(firstItem.Monto);
            } else {
                $(modalSelector).find('#CEFC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#VDC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#VOC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#DC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#CCC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#OGC-' + tiendaId).val(0).prop('readonly', true);
                $(modalSelector).find('#CEIC-' + tiendaId).val(0).prop('readonly', true);
            }
            RECORRER=RECORRER+1;
        }
        },
        error: function(xhr) {
            console.error('Error al obtener los datos:', xhr);
        }
    });
});


        $(document).on('click', '.abrirModal', function() {
        var tiendaId = $(this).data('id'); // Obtener el ID de la tienda

        // Realizar una solicitud AJAX para obtener los datos de la tienda
        $.ajax({
            url: '/metodopago/detalle/' + tiendaId, // Cambia a la ruta correspondiente en Laravel
            method: 'GET',
            success: function(data) {
                // Llenar los campos de la ventana modal con los datos obtenidos
                if(data.MetodoPago=='CEF'){
                    $('#CEF-' + tiendaId).val(data.Monto);
                }else if (data.MetodoPago=='VD'){
                    $('#VD-' + tiendaId).val(data.Monto);
                }else if(data.MetodoPago=='VO'){
                    $('#VO-' + tiendaId).val(data.Monto);
                }else if (data.MetodoPago=='D'){
                    $('#D-' + tiendaId).val(data.Monto);
                }else if (data.MetodoPago=='CC'){
                    $('#CC-' + tiendaId).val(data.Monto);
                }else if (data.MetodoPago=='OG'){
                    $('#OG-' + tiendaId).val(data.Monto);
                }else if (data.MetodoPago=='CEI'){
                    $('#CEI-' + tiendaId).val(data.Monto);
                }else{
                    $('#CEF-' + tiendaId).val(0);
                    $('#VD-' + tiendaId).val(0);
                    $('#VO-' + tiendaId).val(0);
                    $('#D-' + tiendaId).val(0);
                    $('#CC-' + tiendaId).val(0);
                    $('#OG-' + tiendaId).val(0);
                    $('#CEI-' + tiendaId).val(0);
                }
            },
            error: function(xhr) {
                console.error('Error al obtener los datos:', xhr);
            }
        });
    });
</script>
@endpush
