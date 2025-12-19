@extends('layouts.app')

@section('content')

<div class="container">

    <h4 class="mb-3">Libro Balance</h4>

    <form method="GET" action="{{ route('balance.reporte') }}" class="row g-3 mb-4">

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
    <form method="GET" action="{{ route('balance.export.excel') }}" class="d-inline">

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
    <form method="GET" action="{{ route('balance.export.pdf') }}" class="d-inline">

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
                <strong>Total Debe:</strong> Q {{ number_format($asientos->first()->DebeGeneral ?? 0, 2) }}
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-warning">
                <strong>Total Haber:</strong> Q {{ number_format($asientos->first()->HaberGeneral ?? 0, 2) }}
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-success">
                <strong>Saldo Final:</strong>
                Q {{ number_format($asientos->first()->SaldoFinal ?? 0, 2) }}
            </div>
        </div>
    </div>

    <hr>

    {{-- GRAFICA --}}
    <div class="mt-4">
        <canvas id="grafica"></canvas>
    </div>

    <hr>

    {{-- ============================
            TABLA DE BALANCE
       ============================ --}}
    <table class="table table-bordered mt-4">
        <thead class="table-dark">
            <tr>
                <th>Cuenta</th>
                <th>Debe</th>
                <th>Haber</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>

            @foreach($asientos as $a)
                <tr>
                    <td>{{ $a->nombre }}</td>
                    <td>{{ number_format($a->Debe, 2) }}</td>
                    <td>{{ number_format($a->Haber, 2) }}</td>
                    <td>{{ number_format($a->SaldoCuenta, 2) }}</td>
                </tr>
            @endforeach

        </tbody>

        <tfoot>
            <tr class="table-secondary">
                <th>Total</th>
                <th>{{ number_format($asientos->sum('Debe'), 2) }}</th>
                <th>{{ number_format($asientos->sum('Haber'), 2) }}</th>
                <th>{{ number_format($asientos->sum('SaldoCuenta'), 2) }}</th>
            </tr>
        </tfoot>

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
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Debe',
                    data: debe,
                    borderColor: 'green',
                    borderWidth: 2,
                    tension: 0.2
                },
                {
                    label: 'Haber',
                    data: haber,
                    borderColor: 'red',
                    borderWidth: 2,
                    tension: 0.2
                }
            ]
        },
        options: {
            responsive: true,

            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.raw;
                            return `${label}: Q ${value}`;
                        },
                        title: function(context) {
                            return context[0].label;
                            // Este título será: "2025-01-12 - Caja Bancos"
                        }
                    }
                }
            },

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
