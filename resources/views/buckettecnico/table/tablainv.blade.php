<!-- tablainv.blade.php -->
<div class="card">

    <!-- Panel de filtros -->

        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Buscar general:</label>
                    <input type="text" id="globalSearch" class="form-control" placeholder="Buscar...">
                </div>
            </div>
        </div>


    <div class="card-body" style="overflow-x: auto;">
        <table id="datatablesSimpleInv" class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>id</th>
                    <th>serie</th>
                    <th>Descrip</th>
                    <th>SKU</th>
                    <th>almacen</th>
                    <th>Lote</th>
                    <th>MAC1</th>
                    <th>MAC2</th>
                    <th>MAC3</th>
                    <th>ESTATUS</th>
                    <th>COSTO</th>
                    <th>CENTRO</th>
                    <th>TIPO</th>
                    <th>unidadmedida</th>
                    <th>TIPOMOVIMIENTO</th>
                    <th>Naturaleza</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($relacion as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->serie }}</td>
                    <td>{{ $item->treematerialcategoria->descripcion }}</td>
                    <td>{{ $item->SKU }}</td>
                    <td>{{ $item->almacen }}</td>
                    <td>{{ $item->Lote }}</td>
                    <td>{{ $item->MAC1 }}</td>
                    <td>{{ $item->MAC2 }}</td>
                    <td>{{ $item->MAC3 }}</td>
                    <td>{{ $item->ESTATUS }}</td>
                    <td>{{ $item->COSTO }}</td>
                    <td>{{ $item->CENTRO }}</td>
                    <td>{{ $item->TIPO }}</td>
                    <td>{{ $item->unidadmedida }}</td>
                    <td>{{ $item->TIPOMOVIMIENTO }}</td>
                    <td>{{ $item->Naturaleza }}</td>
                    <td>
                        <button class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Paginación del servidor -->
@if($relacion->hasPages())
<div class="flex justify-center mt-4">
    {!! $relacion->appends(request()->except('page'))->links('pagination::bootstrap-5') !!}
</div>
@endif

<!-- NO PONGAS SCRIPTS AQUÍ - Los scripts están en el layout principal -->
