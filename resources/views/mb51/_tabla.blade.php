@if($registros->isEmpty())
    <div class="alert alert-warning mb-0">
        <i class="fa fa-inbox me-1"></i> No hay registros importados todavía.
    </div>
@else

<div class="table-responsive">
    <table class="table table-sm table-striped table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Material</th>
                <th>Descripción</th>
                <th>Referencia</th>
                <th>Cant.</th>
                <th>Clase mov.</th>
                <th>Texto clase</th>
                <th>Centro</th>
                <th>Documento</th>
                <th>Pedido</th>
                <th>Fe. contab.</th>
                <th>Importado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->material }}</td>
                    <td class="text-truncate" style="max-width: 180px;"
                        title="{{ $r->texto_breve_material }}">
                        {{ $r->texto_breve_material }}
                    </td>
                    <td>{{ $r->referencia }}</td>
                    <td class="text-end">{{ number_format((float) $r->cantidad, 2) }}</td>
                    <td>{{ $r->clase_movimiento }}</td>
                    <td class="text-truncate" style="max-width: 160px;"
                        title="{{ $r->texto_clase_movimiento }}">
                        {{ $r->texto_clase_movimiento }}
                    </td>
                    <td>{{ $r->centro }}</td>
                    <td>{{ $r->documento_material }}</td>
                    <td>{{ $r->pedido }}</td>
                    <td>{{ optional($r->fe_contabilizacion)->format('d/m/Y') }}</td>
                    <td>{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-muted">
        Mostrando {{ $registros->firstItem() }}–{{ $registros->lastItem() }}
        de {{ $registros->total() }}
    </small>
    <div id="mb51Pagination" class="pagination-container">
        {{ $registros->links('pagination::bootstrap-5') }}
    </div>
</div>

@endif