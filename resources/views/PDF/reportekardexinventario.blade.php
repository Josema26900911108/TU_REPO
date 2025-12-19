@extends('layouts.app')

@section('content')

<div class="container">

    <h4 class="mb-3">Reporte de Compra</h4>

    <form method="GET" action="{{ route('reporte.kardeinventario') }}" class="row g-3 mb-4">


        <div class="row">
            {{-- SELECT PRODUCTO --}}
            <div class="col-md-4">
                <label>Producto</label>
                <select name="producto[]" class="form-control selectpicker" multiple data-live-search="true" data-actions-box="true">
                    @foreach($productos as $prod)
                        <option value="{{ $prod->id }}"
                            @if( is_array(request('producto')) && in_array($prod->id, request('producto')) ) selected @endif>
                            {{ $prod->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label>Tipo de Grafica</label>
                <select name="idtipografica" id="idtipografica" class="form-control">
                    <option value="bar">gráfico de barras verticales</option>
                    <option value="line">gráfico de líneas</option>
                    <option value="pie">gráfico circular</option>
                    <option value="doughnut">gráfico de rosquilla</option>
                    <option value="radar">gráfico de radar</option>
                    <option value="polarArea">gráfico de área polar</option>
                    <option value="bubble">gráfico de burbujas</option>
                    <option value="scatter">gráfico de dispersión</option>
                </select>
            </div>

            <div class="col-md-2 mt-4">
                <button class="btn btn-primary mt-2" type="submit">Filtrar</button>
            </div>

        </div>

    </form>

    <hr>

    <form method="GET" action="{{ route('export.reporte.kardeinventario') }}" class="d-inline">
<div class="row">
    <div class="row-md-6" style="text-align: center;">
        {{-- Fechas --}}
        <input type="hidden" name="inicio" value="{{ request('inicio') }}">
        <input type="hidden" name="fin" value="{{ request('fin') }}">

        {{-- Productos múltiples --}}
        @foreach((array) request('producto', []) as $prod)
            <input type="hidden" name="producto[]" value="{{ $prod }}">
        @endforeach

        <button type="submit" class="btn btn-success mb-4">
            Exportar a Excel (con filtros)
        </button>
        </div>
        </div>
    </form>

<hr>
            {{-- Exportar a PDF --}}
<form method="GET" action="{{ route('kardexinv.export.pdf') }}" class="d-inline">

    {{-- Cuentas contables --}}
    @foreach((array) request('cuentas', []) as $cta)
        <input type="hidden" name="cuentas[]" value="{{ $cta }}">
    @endforeach

    <div class="d-flex align-items-center mb-3">

        <div class="me-3 d-flex align-items-center">
            <label class="me-2 mb-0">Fecha Inicio:</label>
            <input type="date" name="inicio" value="{{ request('inicio') }}" class="form-control" style="width: 180px;">
        </div>

        <div class="d-flex align-items-center">
            <label class="me-2 mb-0">Fecha Final:</label>
            <input type="date" name="fin" value="{{ request('fin') }}" class="form-control" style="width: 180px;">
        </div>

    </div>

    <div>
        <button type="submit" class="btn btn-outline-danger">
            <i class="fa fa-file-pdf"></i> Exportar a PDF (con fechas)
        </button>
    </div>

</form>

<hr>
        <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-info" style="text-align: center;">
                <strong>Total Costo Promedio:</strong> Q {{ number_format($totalHaber ?? 0, 2) }}
            </div>
        </div>

    </div>

    <hr>

    {{-- GRAFICA --}}
    <div class="mt-4">
    <canvas id="grafica"></canvas>
    </div>

</div>

@endsection


{{-- ============================
      CSS SIN MODIFICAR LAYOUTS
   ============================ --}}
@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
@endpush


{{-- ============================
      JS SIN MODIFICAR LAYOUTS
   ============================ --}}
@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const labels = {!! json_encode($labels) !!};
    const values = {!! json_encode($values) !!};
    let chart = null; // almacena la instancia de la gráfica


function renderChart(type) {
    if (chart) chart.destroy(); // destruye la instancia anterior
    const ctx = document.getElementById('grafica').getContext('2d');



    if (type === "bubble") {

    } else if (type === "scatter") {

    } else {
chart = new Chart(ctx, {
    type: type,
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Cantidad',
                data: {!! json_encode($debe) !!}, // 100, 90
                borderWidth: 3,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
            },
            {
                label: 'Costo',
                data: {!! json_encode($haber) !!}, // 1350, 1500
                borderWidth: 3,
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
            }
            ,
            {
                label: 'Venta',
                data: {!! json_encode($venta) !!}, // 1350, 1500
                borderWidth: 3,
                backgroundColor: 'rgba(34, 139, 34, 0.5)',
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { title: { display: true, text: 'Producto' }},
            y: { title: { display: true, text: 'Valores' }},
        }
    }
});

    }
}

    // Inicializa selectpicker
    $('.selectpicker').selectpicker();

    // Inicializa gráfico con 'bar' por defecto
    renderChart('bar');

    // Cambiar tipo de gráfica al seleccionar
    $('#idtipografica').change(function() {
        const selectedType = $(this).val();
        renderChart(selectedType);
    });

});

</script>
@endpush
