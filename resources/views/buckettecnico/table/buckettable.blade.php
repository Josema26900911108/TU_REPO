<div class="card-body border-bottom">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label fw-bold">Buscar general:</label>
            <input type="text" id="globalSearchAsig" class="form-control" value="{{ request('search_global') }}" placeholder="Buscar...">
        </div>
    </div>
</div>

<div id="tabla_pago_content">
    <table id="datatablesSimpleAsig" class="table table-striped table-bordered fs-12">
        <thead>
            <tr>
                <th>Orden</th>
                <th>Virtual</th>
                <th>Estatus</th>
                <th>Tipo Servicio</th>
                <th>Tipo Orden</th>
                <th>Cliente</th>
                <th>Tecnico</th>
                <th>Codigo</th>
                <th>Dirección</th>
                <th>Obs</th>
                <th>Siglas</th>
                <th>Área</th>
                <th>Fecha</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            {{-- Usamos @foreach convencional. Si está vacío, el tbody quedará limpio y DataTables no fallará --}}
            @foreach ($relacion as $item)
            <tr>
                <td>{{ $item->Orden ?? $item->id }}</td>
                <td>{{ $item->virtual ?? 'N/A' }}</td>
                <td>
                    <span class="badge bg-info text-dark">
                        {{ $item->ESTATUS ?? 'Pendiente' }}
                    </span>
                </td>
                <td>{{ $item->Tipo_servicio ?? 'N/A' }}</td>
                <td>{{ $item->Tipo_orden ?? 'N/A' }}</td>
                <td>{{ $item->NOMBRECLIENTE ?? 'N/A' }}</td>
                <td>{{ $item->nombre_tecnico ?? 'N/A' }}</td>
                <td>{{ $item->codigo_tecnico ?? 'N/A' }}</td>
                <td>{{ $item->DIRECCION ?? 'N/A' }}</td>
                <td>{{ $item->OBS ?? 'Sin observaciones' }}</td>
                <td>{{ $item->SIGLASCENTRAL ?? 'N/A' }}</td>
                <td>{{ $item->AREA ?? 'N/A' }}</td>
                <td>{{ $item->FECHAINSTALACION ?? 'N/A' }}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-ver-orden" data-id="{{ $item->id }}" title="Ver Detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Contenedor de la Paginación en Crudo idéntico a ETA -->
    <div class="d-flex justify-content-center mt-3" id="laravel-pagination">
        {!! $relacion->links('pagination::bootstrap-5') !!}
    </div>
</div>
