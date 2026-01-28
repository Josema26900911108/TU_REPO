<div id="tabla_pago_content">

           <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar general:</label>
                    <input type="text" id="globalSearchAsig" class="form-control" placeholder="Buscar...">
                </div>
            </div>
        </div>

<table id="datatablesSimplePago" class="table table-striped fs-12" style="min-width: 800px;">
    <thead>
        <tr>
            <th>Orden</th>
            <th>Estatus</th>
            <th>SKU</th>
            <th>Descripción</th>
            <th>OBS</th>
            <th>Cantidad</th>
            <th>Pago dinero</th>
            <th>Naturaleza</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($relacion as $item)
            <tr>
                <td>{{ $item->Orden }}</td>
                <td>{{ $item->Status }}</td>
                <td>{{ $item->SKU }}</td>
                <td>{{ $item->Descripcion }}</td>
                <td>{{ $item->OBS }}</td>
                <td>{{ $item->Cantidad }}</td>
                <td>{{ $item->COSTOPAGO }}</td>
                <td>{{ $item->Naturaleza }}</td>
                <td>
                    <!-- Aquí tu dropdown y botones exactamente como los tenías -->
                </td>
            </tr>


        @endforeach
    </tbody>
</table>

    {{-- paginación --}}
<div class="flex justify-center mt-4">
        {!! $relacion->links('pagination::bootstrap-5') !!}
    </div>
</div>
