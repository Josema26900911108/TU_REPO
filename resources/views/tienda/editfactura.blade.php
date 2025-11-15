<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>Diseñador de Factura</title>

  <!-- Bootstrap CSS (requerido por Summernote) -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

  <!-- Summernote CSS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.css" rel="stylesheet">

  <style>
    .label { font-weight: bold; margin-top: 15px; }
    .variables { background: #f9f9f9; padding: 5px 10px; border: 1px dashed #ccc; margin: 10px 0; display: flex; gap: 10px; flex-wrap: wrap; }
    .var-token { background: #eee; padding: 10px; border: 1px solid #999; cursor: grab; }
    .preview-area { display: none; padding: 20px; background: #fff; border: 1px solid #ddd; margin-top: 20px; }
  </style>

<style>
.fixed-variable-box {
  position: fixed;
  right: 20px;
  top: 80px;
  width: 250px;
  background: #ffffff;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
  padding: 1rem;
  z-index: 1000;
  border-radius: 8px;
  border: 1px solid #ddd;
  max-height: 70vh;
  cursor: move; /* indica que se puede arrastrar */
}
</style>

</head>
<body class="container mt-4">

<h2>Diseño de Factura</h2>
<div>
    <select name="plantillas" id="plantillas"></select>
</div>
@foreach ($tienda as $t)
<div aling="center">
 <img class="background-image"  width="20%" height="20%" src="data:image/jpg;base64,{{ $t->logo }}"/>

</div>
<div class="label">Tienda {{ $t->Nombre }}</div>
<div id="draggable-box"  class="fixed-variable-box" style="resize: both; overflow: auto;">
  <div id="drag-header" class="box-header">Variables disponibles (arrastrar):
    <button id="minimize-btn" style="float: right;">_</button>
  </div>
  <div class="variables" id="variables"></div>
</div>

@endforeach
<form id="facturaForm" method="POST" action="{{ route('subir.imagen') }}">
  @csrf
  <div class="label">Cabecera</div>
Titulo: <input class="form-control" name="Titulo" id="Titulo" value="{{ $plantilla->Titulo ?? '' }}">

<div class="label">Descripcion:</div>
<textarea type="text" class="form-control" name="descripcion" id="descripcion" value="{{ $plantilla->descripcion ?? '' }}">{{ $plantilla->descripcion ?? '' }}</textarea>

    <div class="label">Consutal</div>
@php
    preg_match_all('/{{\s*(.*?)\s*}}/', $plantilla->consulta ?? '', $coincidencias);
    $variabless = $coincidencias[1]; // Solo las variables sin los corchetes
@endphp
    <div id="contenedor-inputs"></div>


<textarea class="form-control" name="consulta" id="consulta">{{$plantilla->consulta ?? '' }}</textarea>
<label for="consulta" id="lblconsulta"></label>
<div id="editor-cabecera">{{ $plantilla->cabecera ?? '' }}</div>


  <input type="hidden" name="cabecera" id="cabecera" value="{{ $plantilla->cabecera ?? '' }}">

    <input type="hidden" name="idTienda" value="{{ $fkTienda ?? '' }}">


  <div class="label">Detalle</div>
  <div id="editor-detalle">{{ $plantilla->detalle ?? '' }}</div>
  <input type="hidden" name="detalle" id="detalle" value="{{ $plantilla->detalle ?? '' }}">

  <div class="label">Pie de Página</div>
  <div id="editor-pie">{{ $plantilla->pie ?? '' }}</div>
  <input type="hidden" name="pie" id="pie" value="{{ $plantilla->pie ?? '' }}">

  <div class="label">Compartir Plantilla a comunidad: <input type="checkbox" name="chkcompartir" id="chkcompartir"></div>


  <button type="submit" class="btn btn-primary" id="btnEnviar">Guardar</button>
  <button type="button" class="btn btn-secondary" id="btnVistaPrevia">Vista Previa</button>
  <button type="button" class="btn btn-info" id="btnGenerarPDF">Vista PDF</button>
  <button type="button" class="btn btn-dark"  onclick="volverlista()">Regresar</button>


</form>

<div id="preview" class="preview-area"></div>

<!-- jQuery y Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>


<!-- Summernote JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-bs4.min.js"></script>

<script>
  let contenidoCabecera = @json($plantilla->cabecera ?? '');
  let contenidoDetalle  = @json($plantilla->detalle ?? '');
  let contenidoPie      = @json($plantilla->pie ?? '');
  let htmpdf;
  let contenidoconsulta      = @json($plantilla->consulta ?? '');
let variablesss = @json($variabless);
let detalle = [];
const columnas=[];
let variables = {};
let url = @json(route('plantilla.consulta', ['plantilla' => '__REPLACE__']));
let url2 = @json(route('plantilla.consulta', ['plantilla' => '__REPLACE__']));
let urlPDF = @json(route('plantilla.PDF')); // Sin '__REPLACE__'
obenterplantillas();
$('#plantillas').on('change',function(){
    selectLlena();
})

function obenterplantillas(){
    $('#plantillas').empty();

    $.ajax({
        url: "{{ route('plantilla.plantillas') }}",
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // necesario en Laravel
    },
    success: function(response) {
         let html ='';
        console.log("Respuesta del servidor:", response);
        html += `<option value="0">Selecccione un Plantilla</option>`;
        response.forEach(function(item){
        html += `<option value="${item.id}">${item.Titulo}</option>`;

        });
        $('#plantillas').append(html);
    },
    error: function(xhr, status, error) {
        console.error("Error en AJAX:", error);
        console.log("Respuesta del servidor:", xhr.responseText);
    }
    });
}

function selectLlena() {
    const idselect = $('#plantillas').val(); // asegurarse de que ese ID existe

    $.ajax({
        url: "{{ route('plantilla.selectplantilla') }}", // asegúrate que esta ruta existe
        method: 'POST',
    data: {
        idplantilla: idselect,
        _token: $('meta[name="csrf-token"]').attr('content')
    },
        success: function (response) {
            $('#editor-cabecera').summernote('code', response.cabecera ?? '');
            $('#editor-detalle').summernote('code', response.detalle ?? '');
            $('#editor-pie').summernote('code', response.pie ?? '');
            $('#consulta').val(response.consulta ?? '');
            $('#Titulo').val(response.Titulo ?? '');
            $('#descripcion').val(response.descripcion ?? '');
            variablesss=extraerVariables(response.consulta ?? '');
            obtnerdatos();
        },
        error: function (xhr) {
            console.error("Error en AJAX:", xhr.responseText);
        }
    });
}
function volverlista($idtienda){

        const baseUrl = "{{ url('/tienda') }}";
        window.location.href = `${baseUrl}`;

}
function extraerVariables(plantilla) {
    const consulta=plantilla;
    const regex = /@{{\s*(.*?)\s*}}/g;
    const variables = [];
    let match;

    while ((match = regex.exec(consulta)) !== null) {
        variables.push(match[1]);
    }

    return variables;
}

function renderDetalle(template) {
    obtnerdatosvalores();
  template = '@{{#detalle}}' + template + '@{{/detalle}}';
  const match = template.match(/@{{#detalle}}([\s\S]*?)@{{\/detalle}}/);
  if (!match) return template;
  const rowTemplate = match[1];
  let rows = '';
  detalle.forEach(item => {
    let row = rowTemplate;
    for (const key in item) {
      row = row.replaceAll(`@{{${key}}}`, item[key]);
    }
    rows += row;
  });
  return template.replace(match[0], rows);
};


function obtnerdatos(){
contenidoconsulta=$('#consulta').val();

$('#contenedor-inputs').empty();
$('#variables').empty();
let consultapreparada = contenidoconsulta;

    // Validación: si no existen variables, salirse
    if (typeof variablesss === 'undefined') return;

    // Insertar inputs en pantalla
    variablesss.forEach(function (col) {
        $('#contenedor-inputs').append(
            `<label for="${col}">${col}</label>
             <input type="text" name="${col}" id="${col}" value="'?'" class="form-control mb-2" />`
        );
    });

    variablesss.forEach(function (token) {
    const value = $('#' + token).val();
    const pattern = new RegExp(`@@{{\\s*${token}\\s*}}`, 'g'); // busca @{{TOKEN}}
    consultapreparada = consultapreparada.replace(pattern, value);
});

    url = @json(route('plantilla.consulta', ['plantilla' => '__REPLACE__']));

    url = url.replace('__REPLACE__', encodeURIComponent(consultapreparada));


    $.ajax({
    url: url,
    method: 'POST',
    headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Muy importante para POST en Laravel
             },
          data: {},
    success: function(response){
        console.log(response);
        var columnass=response.columnas;
        var filass=response.filas;

        detalle = filass;

        columnass.forEach(function(col) {
            var token = `@{{${col.name}}}`;
            var html = `
                <div class="var-token" draggable="true" data-token="${token}">
                    @${token}
                </div>

            `;

            const fila0 = filass.length > 0 ? filass[0] : {};


            const conArroba = false; // cámbialo a true si usas @{{variable}} en tus plantillas
            variables = {};
            for (const key in fila0) {
                const etiqueta = (conArroba ? '@{{' : '{{') + key + '}}';
                variables[etiqueta] = fila0[key];
            }

        detalle = filass;
            $('#variables').append(html);
            $('#variables .var-token').on('dragstart', function (e) {
                const token = $(this).data('token');
                e.originalEvent.dataTransfer.setData('text/plain', token);
            });
        });

    },
    error:function(xhr,status,error){
                    console.error("Error en AJAX:", error);
            console.log("Respuesta del servidor:", xhr.responseText);
    }
    });

}


let urlOriginal = url;
$('#consulta').on('input', function () {
    let datos = {};
    let contenidoconsulta = $(this).val();
    let consultapreparada2 = contenidoconsulta;


    if (typeof variablesss === 'undefined') return;

    // Reemplazar variables en la consulta
    variablesss.forEach(function (token) {
        const value = $('#' + token).val();
        const pattern = new RegExp(`@@{{\\s*${token}\\s*}}`, 'g');
        consultapreparada2 = consultapreparada2.replace(pattern, value);
        datos[token] = value;
    });

    datos['consulta'] = consultapreparada2;

    let urlValida = urlOriginal.replace('__REPLACE__', encodeURIComponent(consultapreparada2));

    $.ajax({
        url: urlValida,
        method: 'POST',
        data: {
            consulta: consultapreparada2, // 👈 Aquí estaba el error, antes usabas una variable no definida
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            $('#btnEnviar').prop('disabled', false);
            $('#btnVistaPrevia').prop('disabled', false);
            $('#btnGenerarPDF').prop('disabled', false);
            $('#lblconsulta').text('');

        var columnass=response.columnas;

        $('#variables').empty();

        columnass.forEach(function(col) {
            var token = `@{{${col.name}}}`;
            var html = `
                <div class="var-token" draggable="true" data-token="${token}">
                    @${token}
                </div>

            `;
            $('#variables').append(html);

            $('#variables .var-token').on('dragstart', function (e) {
                const token = $(this).data('token');
                e.originalEvent.dataTransfer.setData('text/plain', token);
            });
        });



        },
        error: function (xhr) {
            $('#btnEnviar').prop('disabled', true);
            $('#btnVistaPrevia').prop('disabled', true);
            $('#btnGenerarPDF').prop('disabled', true);

            try {
                const res = JSON.parse(xhr.responseText);
                $('#lblconsulta').text('Error: ' + res.error + ' | ' + res.detalle);
            } catch (err) {
                $('#lblconsulta').text('Error desconocido al validar la consulta.');
            }
        }
    });
});



function obtnerdatosvalores(){

let datos = {};
contenidoconsulta=$('#consulta').val();
let consultapreparada2 = contenidoconsulta;

    // Validación: si no existen variables, salirse
    if (typeof variablesss === 'undefined') return;

    variablesss.forEach(function (token) {
    const value = $('#' + token).val();
    const pattern = new RegExp(`@@{{\\s*${token}\\s*}}`, 'g'); // busca @{{TOKEN}}
    consultapreparada2 = consultapreparada2.replace(pattern, value);
    datos[token] = value;
});

datos['consulta'] = consultapreparada2;

    // Asegúrate de usar una copia limpia de la URL base
    const urlBase = url2;
    const finalUrl = urlBase.replace('__REPLACE__', encodeURIComponent(consultapreparada2));



    $.ajax({
    url: finalUrl,
    method: 'POST',
    headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Muy importante para POST en Laravel
             },
          data: datos,
    success: function(response){
        console.log(response);
        var columnass=response.columnas;
        var filass=response.filas;

            const fila0 = filass.length > 0 ? filass[0] : {};


            const conArroba = false; // cámbialo a true si usas @{{variable}} en tus plantillas
            variables = {};
            for (const key in fila0) {
                const etiqueta = (conArroba ? '@{{' : '{{') + key + '}}';
                variables[etiqueta] = fila0[key];
            }



        detalle = filass;
    },
    error:function(xhr,status,error){
                    console.error("Error en AJAX:", error);
            console.log("Respuesta del servidor:", xhr.responseText);
    }
    });

}


function render(template) {
  // 1. Reemplazar el bloque @{{#detalle}} ... @{{/detalle}}
  obtnerdatosvalores();
  template = template.replace(/@{{#detalle}}([\s\S]*?)@{{\/detalle}}/, function (match, rowTemplate) {
    return detalle.map(function (item) {
      let row = rowTemplate;
      for (let key in item) {
        const pattern1 = new RegExp(`@{{${key}}}`, 'g');
        const pattern2 = new RegExp(`@{{${key}}}`, 'g');
        row = row.replace(pattern1, item[key]);
        row = row.replace(pattern2, item[key]);
      }
      return row;
    }).join('');
  });

  // 2. Reemplazar variables globales de cabecera/pie
  for (const token in variables) {
    const value = variables[token];
    const pattern = new RegExp(token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
    template = template.replace(pattern, value);
  }

  // 3. Reemplazar valores de detalle[0] fuera del bloque
  if (detalle.length > 0) {
    const primerDetalle = detalle[0];
    for (let key in primerDetalle) {
      const patterns = [
        new RegExp(`@{{detalle.${key}}}`, 'g'),
        new RegExp(`@{{detalle.${key}}}`, 'g'),
        new RegExp(`@{{${key}}}`, 'g'),
        new RegExp(`@{{${key}}}`, 'g')
      ];
      patterns.forEach(pattern => {
        template = template.replace(pattern, primerDetalle[key]);
      });
    }
  }

  return template;
}




$(document).ready(function () {

obtnerdatos();
//obenterplantillas();

    function configurarDragAndDropEnEditor(selector) {
  $(selector).on('summernote.init', function () {
    const editable = this.nextElementSibling.querySelector('.note-editable');

    editable.addEventListener('dragover', function (e) {
      e.preventDefault();
    });

    editable.addEventListener('drop', function (e) {
      e.preventDefault();
      const token = e.dataTransfer.getData('text/plain');
      if (!token) return;

      // Insertar el texto plano donde está el cursor
      const sel = window.getSelection();
      if (!sel.rangeCount) return;

      const range = sel.getRangeAt(0);
      range.deleteContents();
      range.insertNode(document.createTextNode(token));

      // Mover cursor después del texto
      range.setStartAfter(range.endContainer);
      sel.removeAllRanges();
      sel.addRange(range);
    });
  });
}


$('#editor-cabecera, #editor-detalle, #editor-pie').summernote({
  height: 350,
  placeholder: 'Escribe aquí...',
  toolbar: [
    ['style', ['bold', 'italic', 'underline', 'clear']],
    ['font', ['fontname', 'fontsize']],
    ['para', ['ul', 'ol', 'paragraph']],
    ['insert', ['link', 'picture', 'table']],
    ['view', ['codeview', 'help']]
  ],
  callbacks: {
    onInit: function () {
      // Esperamos un pequeño delay para asegurar que Summernote terminó

      $('#editor-cabecera').summernote('codeview.activate');
      $('#editor-detalle').summernote('codeview.activate');
      $('#editor-pie').summernote('codeview.activate');

      setTimeout(() => {
        document.querySelectorAll('.note-editable').forEach(editable => {
          editable.addEventListener('dragover', function (e) {
            e.preventDefault();
          });

          editable.addEventListener('drop', function (e) {
            e.preventDefault();
            const token = e.dataTransfer.getData('text/plain');
            if (!token) return;

            // Insertar texto plano manualmente
            const sel = window.getSelection();
            if (!sel.rangeCount) return;

            const range = sel.getRangeAt(0);
            range.deleteContents();

            const textNode = document.createTextNode(token);
            range.insertNode(textNode);

            range.setStartAfter(textNode);
            range.setEndAfter(textNode);
            sel.removeAllRanges();
            sel.addRange(range);
          });
        });
      }, 300); // Delay mínimo para evitar conflictos con Summernote
    }
  }
});

configurarDragAndDropEnEditor('#editor-cabecera');
configurarDragAndDropEnEditor('#editor-detalle');
configurarDragAndDropEnEditor('#editor-pie');

  // Drag & Drop
  $('.var-token').on('dragstart', function (e) {
        const token = $(this).data('token');
    e.originalEvent.dataTransfer.setData('text/plain', token);
  });


  // Vista previa
  $('#btnVistaPrevia').click(function () {

    obtnerdatosvalores();
    const cabecera = $('#editor-cabecera').summernote('code');
    const detalleHtml = $('#editor-detalle').summernote('code');
    const pie = $('#editor-pie').summernote('code');

    let html = `${detalleHtml}`;
    let cab = `${cabecera}`;
    let pi = `${pie}`;

    html = render(cab) + renderDetalle(html) + render(pi);
    htmpdf=html;

    for (const [token, value] of Object.entries(variables)) {
      const regex = new RegExp(token.replace(/[{}]/g, c => `\\${c}`), 'g');
      html = html.replace(regex, value);
    }

    $('#preview').html(html).show();
    window.scrollTo(0, document.body.scrollHeight);
  });

    $('#facturaForm').on('submit', function () {

        obtnerdatosvalores();
        $('#cabecera').val($('#editor-cabecera').summernote('code'));
        $('#detalle').val($('#editor-detalle').summernote('code'));
        $('#pie').val($('#editor-pie').summernote('code'));
    });

    $('#editor-cabecera').summernote('code', `{!! $plantilla->cabecera ?? '' !!}`);
    $('#editor-detalle').summernote('code', `{!! $plantilla->detalle ?? '' !!}`);
    $('#editor-pie').summernote('code', contenidoPie);

});

$('#btnGenerarPDF').on('click', function () {

console.log('htmpdf actualizado:', htmpdf);


    $.ajax({
        url: urlPDF, // Ya es fija, no lleva HTML en la URL
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            html: htmpdf // Enviamos el HTML solo en el body
        },
        success: function(response) {
            console.log(response);
            if (response.url) {
                window.open(response.url, '_blank');
            } else {
                alert("No se recibió URL del servidor.");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error en AJAX:", error);
            console.log("Respuesta del servidor:", xhr.responseText);
        }
    });
});



</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const box = document.getElementById('draggable-box');
  const header = document.getElementById('drag-header');
  const content = document.getElementById('variables');
  const minimizeBtn = document.getElementById('minimize-btn');

  let isDragging = false;
  let offsetX = 0;
  let offsetY = 0;

  header.addEventListener('mousedown', function (e) {
    isDragging = true;
    const rect = box.getBoundingClientRect();
    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;
    box.style.right = 'auto';
  });

  document.addEventListener('mousemove', function (e) {
    if (isDragging) {
      box.style.left = `${e.clientX - offsetX}px`;
      box.style.top = `${e.clientY - offsetY}px`;
    }
  });

  document.addEventListener('mouseup', function () {
    isDragging = false;
  });

  minimizeBtn.addEventListener('click', function () {
    if (content.style.display === 'none') {
      content.style.display = '';
      minimizeBtn.textContent = '_';
      document.getElementById('draggable-box').style.height = '';

    } else {

      content.style.display = 'none';
      minimizeBtn.textContent = '◉';
      document.getElementById('draggable-box').style.height = '52px';
      document.getElementById('draggable-box').style.width='320px'

    }
  });
});
</script>

</body>
</html>
