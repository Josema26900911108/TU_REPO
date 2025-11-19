@extends('layouts.app')

@section('title', 'Perfil')

@push('css')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
@endpush

@section('content')

@include('layouts.partials.alert')

<div class="container-fluid">
    <h1 class="mt-4 mb-4 text-center">Configurar perfil</h1>

    <div class="card">
        <div class="card-header">
            <p class="lead">Configure y personalice su perfil</p>
        </div>
        <div class="card-body">
            <div class="">
                @if ($errors->any())
                    @foreach ($errors->all() as $item)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ $item }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endforeach
                @endif
            </div>

            <form action="{{ route('profile.update', ['profile' => $user]) }}" method="POST">
                @method('PATCH')
                @csrf

                <!----Nombre---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                            <input disabled type="text" class="form-control" value="Nombres">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input autocomplete="off" type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}">
                    </div>
                </div>

                <!----Email---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                            <input disabled type="text" class="form-control" value="Email">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input autocomplete="off" type="email" name="email" id="email" class="form-control" value="{{ old('email', $user->email) }}">
                    </div>
                </div>

                <!----Contraseña---->
                <div class="row mb-4">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input disabled type="text" class="form-control" value="Contraseña">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input type="password" name="password" id="password" class="form-control">
                    </div>
                </div>

                <div class="col text-center">
                    <input class="btn btn-success" type="submit" value="Guardar cambios">
                </div>

            </form>
        </div>
    </div>

</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush
