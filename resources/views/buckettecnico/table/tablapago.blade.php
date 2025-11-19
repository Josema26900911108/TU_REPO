<div id="tabla_pago_content">

<table id="datatablesSimple" class="table table-striped fs-12" style="min-width: 800px;">
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
        @forelse ($relacion as $item)
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
        @empty
            <tr>
                <td colspan="4">No hay registros</td>
            </tr>


        @endforelse
    </tbody>
</table>

    {{-- paginación --}}
<div class="flex justify-center mt-4">
        {!! $relacion->links('pagination::bootstrap-5') !!}
    </div>
</div>
