@extends('layouts.app')

@section('content')

<div class="container">

    <h4 class="mb-3">Libro Diario</h4>

    <form method="GET" action="{{ route('diario.reporte') }}" class="row g-3 mb-4">

        <div class="row">

            <div class="col-md-3">
                <label>Fecha Inicio</label>
                <input type="date" name="inicio" value="{{ request('inicio') }}" class="form-control">
            </div>

            <div class="col-md-3">
                <label>Fecha Final</label>
                <input type="date" name="fin" value="{{ request('fin') }}" class="form-control">
            </div>

            {{-- SELECT CUENTA CONTABLE --}}
            <div class="col-md-4">
                <label>Cuenta Contable</label>
                <select name="cuentas[]" class="form-control selectpicker" multiple data-live-search="true" data-actions-box="true">
                    @foreach($cuentas as $cta)
                <option value="{{ $cta->id }}">
                    {{ $cta->nombre }}
                </option>

                    @endforeach
                </select>
            </div>

            <div class="col-md-2 mt-4">
                <button class="btn btn-primary mt-2" type="submit">Filtrar</button>
            </div>

        </div>

    </form>

    <hr>

    {{-- Exportar a Excel --}}
    <form method="GET" action="{{ route('diario.export.excel') }}" class="d-inline">

        {{-- Fechas --}}
        <input type="hidden" name="inicio" value="{{ request('inicio') }}">
        <input type="hidden" name="fin" value="{{ request('fin') }}">

        {{-- Cuentas contables --}}
        @foreach((array) request('cuentas', []) as $cta)
            <input type="hidden" name="cuentas[]" value="{{ $cta }}">
        @endforeach

        <button type="submit" class="btn btn-success mb-4"><i class="fa fa-regular fa-file-excel"></i>
            Exportar a Excel (con filtros)
        </button>
    </form>
        {{-- Exportar a PDF --}}
    <form method="GET" action="{{ route('diario.export.pdf') }}" class="d-inline">

        {{-- Fechas --}}
        <input type="hidden" name="inicio" value="{{ request('inicio') }}">
        <input type="hidden" name="fin" value="{{ request('fin') }}">

        {{-- Cuentas contables --}}
        @foreach((array) request('cuentas', []) as $cta)
            <input type="hidden" name="cuentas[]" value="{{ $cta }}">
        @endforeach

        <button type="submit" class="btn btn-outline-danger mb-4">
            <i class="fa fa-regular fa-file-pdf"></i>
            Exportar a PDF (con fechas)
        </button>
    </form>

    {{-- ============================
            TOTALES GENERALES
       ============================ --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="alert alert-info">
                <strong>Total Debe:</strong> Q {{ number_format($totalDebe, 2) }}
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-warning">
                <strong>Total Haber:</strong> Q {{ number_format($totalHaber, 2) }}
            </div>
        </div>
    </div>

    {{-- GRAFICA --}}
    <div class="mt-4">
        <canvas id="grafica"></canvas>
    </div>

    <hr>

    {{-- TABLA DEL DIARIO --}}
    <table class="table table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>N° Folio</th>
                <th>Cuenta Contable</th>
                <th>Naturaleza</th>
                <th>Debe</th>
                <th>Haber</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($asientos as $a)
                <tr>
                    <td>{{ $a->fecha }}</td>
                    <td>{{ $a->NumeroFolio }}</td>
                    <td>{{ $a->NombreCuenta }}</td>
                    <td>{{ $a->Naturaleza }}</td>
                    <td>{{ number_format($a->Debe, 2) }}</td>
                    <td>{{ number_format($a->Haber, 2) }}</td>
                    <td>{{ $a->usuario }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/css/bootstrap-select.min.css">
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.14.0-beta3/dist/js/bootstrap-select.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const labels = {!! json_encode($labels) !!};
    const debe = {!! json_encode($debe) !!};
    const haber = {!! json_encode($haber) !!};

    const ctx = document.getElementById('grafica').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Debe',
                    data: debe,
                    borderColor: 'green',
                    borderWidth: 2
                },
                {
                    label: 'Haber',
                    data: haber,
                    borderColor: 'red',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    $('.selectpicker').selectpicker();
});
</script>
@endpush
