@extends('layouts.app')

@section('content')

<div class="container">

    <h4 class="mb-3">Reporte de Ventas</h4>

    <form method="GET" action="{{ route('ventas.ventasreporte') }}" class="row g-3 mb-4">


        <div class="row">

            <div class="col-md-3">
            <label>Fecha Inicio</label>
            <input type="date" name="inicio" value="{{ request('inicio') }}" class="form-control">
        </div>

        <div class="col-md-3">
            <label>Fecha Final</label>
            <input type="date" name="fin" value="{{ request('fin') }}" class="form-control">
        </div>


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
{{-- Mostrar errores si la consulta falla o está vacía --}}
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fa fa-exclamation-triangle"></i> Error:</strong> {{ session('error') }}
    </div>
@endif

{{-- SOLUCIÓN: Si la URL existe en la sesión, forzamos la descarga o visualización --}}
@if(session('pdf_url'))
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Abre el PDF en una pestaña independiente sin alterar la pantalla actual
            var win = window.open("{{ session('pdf_url') }}", "_blank");
            if (win) {
                win.focus();
            } else {
                // Alerta de contingencia por si el navegador bloquea las ventanas emergentes
                alert("Por favor, permite las ventanas emergentes para ver el reporte.");
            }
        });
    </script>
@endif

<!-- Cambiado a POST para garantizar que viaje la sesión flash con el archivo -->
<form method="POST" action="{{ route('reporte.ventas.diarias') }}" class="card p-3 shadow-sm">
    @csrf
    <div class="card-header bg-light mb-3">
        <h5 class="mb-0"><i class="fa fa-file-pdf text-danger"></i> Reporte Masivo de Ventas (Corte Individual)</h5>
    </div>

    <div class="row align-items-center">
        <div class="col-md-4 mb-3">
            <label for="fecha_inicio" class="form-label font-weight-bold">Fecha Inicial:</label>
            <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ request('fecha_inicio', date('Y-m-d')) }}" class="form-control" required>
        </div>

        <div class="col-md-4 mb-3">
            <label for="fecha_fin" class="form-label font-weight-bold">Fecha Final:</label>
            <input type="date" name="fecha_fin" id="fecha_fin" value="{{ request('fecha_fin', date('Y-m-d')) }}" class="form-control" required>
        </div>

        <div class="col-md-4 mb-3 mt-md-4">
            <button type="submit" class="btn btn-danger w-100">
                <i class="fa fa-print"></i> Generar Lote de Facturas
            </button>
        </div>
    </div>
</form>




    <hr>
    <form method="POST" action="{{ route('dashboard.export.excel') }}" class="d-inline">
        @csrf
        {{-- Fechas --}}
        <input type="hidden" name="inicio" value="{{ request('inicio') }}">
        <input type="hidden" name="fin" value="{{ request('fin') }}">

        {{-- Productos múltiples --}}
        @foreach((array) request('producto', []) as $prod)
            <input type="hidden" name="producto[]" value="{{ $prod }}">
        @endforeach

        <button type="submit" class="btn btn-success">Exportar Excel</button>
    </form>

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

    const scatterData = {!! json_encode($scatterValues) !!};
    const bubbleData = {!! json_encode($bubbleData) !!};

    if (type === "bubble") {
        chart = new Chart(ctx, {   // <-- asignar a chart
            type: 'bubble',
            data: {
                datasets: [{
                    label: 'Total Ventas: {{ $totalgeneral }}',
                    data: bubbleData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { title: { display: true, text: 'Día' } },
                    y: { title: { display: true, text: 'Monto (Q)' } }
                }
            }
        });
    } else if (type === "scatter") {
        chart = new Chart(ctx, {   // <-- asignar a chart
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Total Ventas: {{ $totalgeneral }}',
                    data: scatterData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { title: { display: true, text: 'Día' } },
                    y: { title: { display: true, text: 'Monto (Q)' } }
                }
            }
        });
    } else {
        chart = new Chart(ctx, {
            type: type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Ventas: {{ $totalgeneral }}',
                    data: values,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: { title: { display: true, text: 'Fecha' } },
                    y: { title: { display: true, text: 'Monto (Q)' } }
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
