@extends('layouts.app')

@section('title','Ver compra')

@push('css')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<style>
    @media (max-width:575px) {
        #hide-group {
            display: none;
        }
    }

    @media (min-width:576px) {
        #icon-form {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <h1 class="mt-4 text-center">Carga Masiva Compra</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('panel') }}">Inicio</a></li>
        <li class="breadcrumb-item active">Carga Masiva Compra</li>
    </ol>
</div>

~~~json
{"id":"58211","variant":"standard"}
<form action="{{ route('productos.importar.procesar') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="container mt-4">

        <h3>Importar productos masivamente</h3>
        <p>Sube un archivo ZIP que contenga:</p>

        <ul>
            <li>Un archivo <strong>productos.xlsx</strong> o <strong>productos.csv</strong></li>
            <li>Una carpeta <strong>imagenes/</strong> dentro del ZIP</li>
        </ul>

        <div class="mb-3">
            <label class="form-label">Archivo ZIP</label>
            <input type="file" name="zipfile" class="form-control" required>
        </div>

        <button class="btn btn-primary">Procesar Archivo</button>

        @if (session('errores'))
            <div class="alert alert-danger mt-4">
                <h4>Errores detectados:</h4>
                <ul>
                    @foreach(session('errores') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success mt-4">
                <h4>✔ {{ session('success') }}</h4>
            </div>
        @endif

    </div>
</form>


@endsection

@push('js')
<script>
    //Variables
    let filasSubtotal = document.getElementsByClassName('td-subtotal');
    let cont = 0;
    let impuesto = $('#input-impuesto').val();

    $(document).ready(function() {
        calcularValores();
    });

    function calcularValores() {
        for (let i = 0; i < filasSubtotal.length; i++) {
            cont += parseFloat(filasSubtotal[i].innerHTML);
        }

        $('#th-suma').html(cont-impuesto);
        $('#th-IVA').html(impuesto);
        $('#th-total').html(round(cont));
    }

    function round(num, decimales = 2) {
        var signo = (num >= 0 ? 1 : -1);
        num = num * signo;
        if (decimales === 0) //con 0 decimales
            return signo * Math.round(num);
        // round(x * 10 ^ decimales)
        num = num.toString().split('e');
        num = Math.round(+(num[0] + 'e' + (num[1] ? (+num[1] + decimales) : decimales)));
        // x * 10 ^ (-decimales)
        num = num.toString().split('e');
        return signo * (num[0] + 'e' + (num[1] ? (+num[1] - decimales) : -decimales));
    }
    //Fuente: https://es.stackoverflow.com/questions/48958/redondear-a-dos-decimales-cuando-sea-necesario
</script>
@endpush
