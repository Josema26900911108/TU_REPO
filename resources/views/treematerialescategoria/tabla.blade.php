Requerida:
<table class="table table-bordered table-hover">
  <thead>
    <tr>
      <th>Nombre</th>
      <th>SKU</th>
      <th>Tipo Relacion</th>
    </tr>
  </thead>
  <tbody>
    @foreach($relacion as $material)
    <tr>
      <td>{{ $material->nombre }}</td>
      <td>{{ $material->SKU }}</td>
      <td>{{ $material->tipo_relacion }}</td>
      <td>
        <button class="btn btn-sm btn-primary seleccionar-material" onclick="Eliminar({{ $material->id }})">X</button>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>
<br>
Incompatible
<table class="table table-bordered table-hover">
  <thead>
    <tr>
      <th>Nombre</th>
      <th>SKU</th>
      <th>Tipo Relacion</th>
    </tr>
  </thead>
  <tbody>
    @foreach($incompatible as $material)
    <tr>
      <td>{{ $material->nombre }}</td>
      <td>{{ $material->SKU }}</td>
      <td>{{ $material->tipo_relacion }}</td>
      <td>
        <button class="btn btn-sm btn-primary seleccionar-material" onclick="Eliminar({{ $material->id }})">X</button>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>

<div class="d-flex justify-content-center">
  {!! $incompatible->links() !!}
</div>
