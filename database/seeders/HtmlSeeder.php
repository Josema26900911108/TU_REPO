<?php

namespace Database\Seeders;

use App\Models\DocumentDesings;
use App\Models\Documento;
use App\Models\plantillahtmlgeneral;
use Illuminate\Database\Seeder;

class HtmlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documentoss=[

            [
                'Titulo' => 'Plantilla Recibo',
                'plantillahtml'=>'<!DOCTYPE  html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>babd8334-46d1-455b-a1e2-f65e3661dedf</title><meta name="author" content="HP"/><style type="text/css"> * {margin:0; padding:0; text-indent:0; }
   .s1 { color: #375522; font-family:Algerian; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }

   .s1 { color: #375522; font-family:Algerian; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }

   h1 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 10pt; }
   p { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 11pt; margin:0pt; }
   .s2 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s3 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s4 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s5 { color: #FFF; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   table, tbody {vertical-align: top; overflow: visible; }
         body {
              margin: 0;
              padding: 0;
              position: relative; /* Necesario para el fondo absoluto */
              min-height: 100vh;
          }

          /* Contenedor del fondo con opacidad */
          .background-container {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              z-index: -1; /* Lo envía al fondo */
              overflow: hidden; /* Recorta excesos */
          }

          /* Imagen de fondo (sin opacidad aquí) */
          .background-image {
              left: 50%;
              top: 50%;
              transform: translate(50%, 110%);
              object-fit: cover; /* Ajusta la imagen sin distorsión */
          }

          /* Capa semitransparente sobre la imagen (¡no sobre el texto!) */
          .background-overlay {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background-color: rgba(255, 255, 255, 0.3); /* Blanco con 70% opacidad */
              z-index: 0; /* Entre la imagen (-1) y el texto (1) */
          }

          /* Contenido (encima de todo) */
          .content-wrapper {
              position: relative;
              z-index: 1; /* Capa superior */
              padding: 20px;
              padding-bottom: 100px; /* Espacio para el pie (ajusta según necesidad) */

          }

                  /* Contenedor del total y borde (fijo al final) */
          .footer-total {
              position: absolute;
              bottom: 0;
              left: 0;
              width: 100%;
              border-top: 2px solid #000; /* Borde superior grueso */
              padding: 10px 20px;
              background-color: white; /* Fondo sólido para superponerse al contenido */
          }

          /* Estilo para el texto del total */
          .total-text {
              font-weight: bold;
              text-align: right;
              margin-right: 20px;
          }


          /* Tabla que expande el espacio disponible */
          .full-height-table {
              width: 100%;
              border-collapse: collapse;
              flex-grow: 1; /* Ocupa todo el espacio restante */
          }

          .full-height-table th,
          .full-height-table td {
              border: 1px solid #000;
              padding: 12px;
          }

          /* Ajuste proporcional de columnas */
          .full-height-table th:nth-child(1),
          .full-height-table td:nth-child(1) {
              width: 70%;
          }

          .full-height-table th:nth-child(2),
          .full-height-table td:nth-child(2) {
              width: 30%;
          }

          .table-container {
              height: calc(100vh - 80px); /* Resta la altura del footer */
              overflow: hidden; /* Evita desbordamiento */
              display: flex;
              flex-direction: column;
          }
  </style></head>

  <body aling="center">

          <!-- Contenedor del fondo -->
      <div class="background-container">
          <!-- Imagen en base64 (opacidad 100%) -->
          <img class="background-image"  width="422" height="308" src="data:image/jpg;base64,{{logo}}"/>
          <!-- Capa semitransparente (alternativa a opacity) -->
          <div class="background-overlay"></div>
      </div>
      <table aling="center">
      <tr>
          <th style="padding-left: 25px; margin-top: 25px;"></th>
  <th>
  <table>
      <tr><td>
      <p style="text-indent: 0pt;text-align: left; margin-top: 50px; padding-left: 35px;"><img width="151" height="86" src="data:image/jpg;base64,{{logo}}"/></p>
  </td>
  <td>
      <p class="s1" style="margin-top: 50px; padding-left: 0pt;text-indent: 0pt;text-align: left;">{{nombretienda}}</p>

      <h1 style="padding-top: 0pt; padding-left: 0pt;text-indent: 0pt;text-align: left;">{{representante}}</h1>

      <p style="padding-top: 1pt;padding-left: 0pt;text-indent: 0pt;text-align: left;">NIT : {{nit}}</p>
 <p style="padding-top: 0pt;padding-left: 0pt;text-indent: 0pt;line-height: 108%;text-align: left;">{{direcciontienda}}</p>

      <p style="padding-top: 1pt;padding-left: 100pt;text-indent: 0pt;line-height: 108%;text-align: left;"></p>
  </td>
  <td style="padding-top: 1pt;text-indent: 0pt;text-align: left;">
      <p style="margin-top: 50px; padding-top: 3pt;padding-right: 44pt; text-indent: 1pt;line-height: 108%; text-align: right;">{{tipo_comprobante}}</p>

          <p style="padding-top: 3pt;padding-right: 44pt;text-indent: 1pt;line-height: 108%; text-align: right;">CORRELATIVO: {{clientedoc}}</p>

          <p style="padding-top: 3pt;padding-right: 44pt;text-indent: 1pt;line-height: 108%;text-align: right;">FECHA:{{fecha}} </p>


      <p style="padding-top: 3pt;padding-right: 44pt;text-indent: 1pt;line-height: 108%;text-align: right;">VENDEDOR: {{name}}.</p>
      </td>
  <td>
 <br/><br/>
      <p style="text-indent: 0pt;text-align: right;">Numero Autorizacipon: {{numerfactura}}.</p><br/>
      <p style="text-indent: 0pt;text-align: right;">fECHA Y HORA DE EMISION: {{fecha_hora}}</p><br/>
      <p style="text-indent: 0pt;text-align: right;">Moneda: GTQ</p><br/>
  </td>
  </tr>
  </table>
      <p style="padding-top: 1pt;text-indent: 0pt;text-align: left;"><br/></p><p class="s2" style="padding-left: 7pt;text-indent: 0pt;text-align: left;">Medicamentos genericos o antirretrovirales. Exenta del IVA (art.7 n.15)</p><p style="padding-top: 1pt;padding-left: 7pt;text-indent: 0pt;text-align: left;">Sujeto a pago directo IRS, resolucion No. 726384629376283</p>

      <table style="border-collapse:collapse;border-top-style:solid;border-top-width:1pt;position: absolute;border-top: 2px solid #000;padding: 1px 2px;width: 90%;flex-grow: 1;" cellspacing="0">



          <tr style="height:15pt">
              <td style="width:64pt;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt">
              <p class="s3" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Nombre:</p></td>

              <td style="width:257pt;border-top-style:solid;border-top-width:1pt">
                  <p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;"></p>
              </td>
              <td style="width:64pt;border-top-style:solid;border-top-width:1pt">
                  <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Fecha:</p>
              </td>

              <td style="width:106pt;border-top-style:solid;border-top-width:1pt;border-right-style:solid;border-right-width:1pt">
                  <p class="s4" style="padding-left: 51pt;text-indent: 0pt;line-height: 13pt;text-align: left;">{{fecha}}</p>
              </td>
          </tr>

              <tr style="height:14pt">
                  <td style="width:64pt;border-left-style:solid;border-left-width:1pt">
                  <p class="s3" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Direccion: {{municipio}}, {{departamento}}</p>
              </td>
              <td style="width:257pt"><p class="s1" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;"></p>
              </td>
              <td style="width:64pt"><p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">NIT: {{nit}}</p></td>

              <td style="width:106pt;border-right-style:solid;border-right-width:1pt">
                  <p style="text-indent: 0pt;text-align: left;"><br/></p>
              </td>
              </tr>

              <tr style="height:14pt; border-collapse:collapse;border-top-style:solid;border-top-width:1pt;">
                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">CANT.</p>
                  </td>

                  <td style="width:257pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">DESCRIPCION</p>
                  </td>

                  <td style="width:64pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">P.U.</p>
                  </td>
                  <td style="width:106pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-right-style:solid;border-right-width:1pt;border-right-color:#1F3763" bgcolor="#538235">
                      <p class="s5" style="padding-left: 32pt;text-indent: 0pt;line-height: 13pt;text-align: left;">IMPORTE</p>
                  </td>
              </tr><tr style="height:100%; width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                      <p class="s4" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">{{cantidad}}</p>
                  </td>

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                              <p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">{{nombreproducto}}</p>
                  </td>

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                      <p class="s4" style="padding-left: 13pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q{{precio}}</p>
                  </td>
                                  <td style="width:106pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78">
                      <p class="s4" style="padding-left: 30pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q{{subtotal}}</p>
                  </td>
              </tr><tr style="height:28pt">

          <td style="width:321pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" colspan="2">
          <p style="text-indent: 0pt;text-align: left;"><br/></p>
          </td>

          <td style="width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" bgcolor="#538235">
              <p class="s5" style="padding-top: 6pt;padding-left: 17pt;text-indent: 0pt;text-align: left;">TOTAL</p>
          </td>
          <td style="width:106pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" bgcolor="#9BC2E6">
              <p class="s4" style="padding-top: 6pt;padding-left: 30pt;text-indent: 0pt;text-align: left;">Q{{total}}</p>
          </td>
      </tr>
      <tr style="height:29pt">
          <td style="width:491pt;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" colspan="4">
              <p style="text-indent: 0pt;text-align: left;"><br/></p>
          </td></tr></table>
  </th>
  </tr>
  </table>

  </body></html>',
                'descripcion'=>'plantilla para creacion de recibos.',
                'cabecera'=>'<!DOCTYPE  html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>babd8334-46d1-455b-a1e2-f65e3661dedf</title><meta name="author" content="HP"/><style type="text/css"> * {margin:0; padding:0; text-indent:0; }
   .s1 { color: #375522; font-family:Algerian; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }

   .s1 { color: #375522; font-family:Algerian; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }

   h1 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 10pt; }
   p { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 11pt; margin:0pt; }
   .s2 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s3 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s4 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s5 { color: #FFF; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   table, tbody {vertical-align: top; overflow: visible; }
         body {
              margin: 0;
              padding: 0;
              position: relative; /* Necesario para el fondo absoluto */
              min-height: 100vh;
          }

          /* Contenedor del fondo con opacidad */
          .background-container {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              z-index: -1; /* Lo envía al fondo */
              overflow: hidden; /* Recorta excesos */
          }

          /* Imagen de fondo (sin opacidad aquí) */
          .background-image {
              left: 50%;
              top: 50%;
              transform: translate(50%, 110%);
              object-fit: cover; /* Ajusta la imagen sin distorsión */
          }

          /* Capa semitransparente sobre la imagen (¡no sobre el texto!) */
          .background-overlay {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background-color: rgba(255, 255, 255, 0.3); /* Blanco con 70% opacidad */
              z-index: 0; /* Entre la imagen (-1) y el texto (1) */
          }

          /* Contenido (encima de todo) */
          .content-wrapper {
              position: relative;
              z-index: 1; /* Capa superior */
              padding: 20px;
              padding-bottom: 100px; /* Espacio para el pie (ajusta según necesidad) */

          }

                  /* Contenedor del total y borde (fijo al final) */
          .footer-total {
              position: absolute;
              bottom: 0;
              left: 0;
              width: 100%;
              border-top: 2px solid #000; /* Borde superior grueso */
              padding: 10px 20px;
              background-color: white; /* Fondo sólido para superponerse al contenido */
          }

          /* Estilo para el texto del total */
          .total-text {
              font-weight: bold;
              text-align: right;
              margin-right: 20px;
          }


          /* Tabla que expande el espacio disponible */
          .full-height-table {
              width: 100%;
              border-collapse: collapse;
              flex-grow: 1; /* Ocupa todo el espacio restante */
          }

          .full-height-table th,
          .full-height-table td {
              border: 1px solid #000;
              padding: 12px;
          }

          /* Ajuste proporcional de columnas */
          .full-height-table th:nth-child(1),
          .full-height-table td:nth-child(1) {
              width: 70%;
          }

          .full-height-table th:nth-child(2),
          .full-height-table td:nth-child(2) {
              width: 30%;
          }

          .table-container {
              height: calc(100vh - 80px); /* Resta la altura del footer */
              overflow: hidden; /* Evita desbordamiento */
              display: flex;
              flex-direction: column;
          }
  </style></head>

  <body aling="center">

          <!-- Contenedor del fondo -->
      <div class="background-container">
          <!-- Imagen en base64 (opacidad 100%) -->
          <img class="background-image"  width="422" height="308" src="data:image/jpg;base64,{{logo}}"/>
          <!-- Capa semitransparente (alternativa a opacity) -->
          <div class="background-overlay"></div>
      </div>
      <table aling="center">
      <tr>
          <th style="padding-left: 25px; margin-top: 25px;"></th>
  <th>
  <table>
      <tr><td>
      <p style="text-indent: 0pt;text-align: left; margin-top: 50px; padding-left: 35px;"><img width="151" height="86" src="data:image/jpg;base64,{{logo}}"/></p>
  </td>
  <td>
      <p class="s1" style="margin-top: 50px; padding-left: 0pt;text-indent: 0pt;text-align: left;">{{nombretienda}}</p>

      <h1 style="padding-top: 0pt; padding-left: 0pt;text-indent: 0pt;text-align: left;">{{representante}}</h1>

      <p style="padding-top: 1pt;padding-left: 0pt;text-indent: 0pt;text-align: left;">NIT : {{nit}}</p>
 <p style="padding-top: 0pt;padding-left: 0pt;text-indent: 0pt;line-height: 108%;text-align: left;">{{direcciontienda}}</p>

      <p style="padding-top: 1pt;padding-left: 100pt;text-indent: 0pt;line-height: 108%;text-align: left;"></p>
  </td>
  <td style="padding-top: 1pt;text-indent: 0pt;text-align: left;">
      <p style="margin-top: 50px; padding-top: 3pt;padding-right: 44pt; text-indent: 1pt;line-height: 108%; text-align: right;">{{tipo_comprobante}}</p>

          <p style="padding-top: 3pt;padding-right: 44pt;text-indent: 1pt;line-height: 108%; text-align: right;">CORRELATIVO: {{clientedoc}}</p>

          <p style="padding-top: 3pt;padding-right: 44pt;text-indent: 1pt;line-height: 108%;text-align: right;">FECHA:{{fecha}} </p>


      <p style="padding-top: 3pt;padding-right: 44pt;text-indent: 1pt;line-height: 108%;text-align: right;">VENDEDOR: {{name}}.</p>
      </td>
  <td>
 <br/><br/>
      <p style="text-indent: 0pt;text-align: right;">Numero Autorizacipon: {{numerfactura}}.</p><br/>
      <p style="text-indent: 0pt;text-align: right;">fECHA Y HORA DE EMISION: {{fecha_hora}}</p><br/>
      <p style="text-indent: 0pt;text-align: right;">Moneda: GTQ</p><br/>
  </td>
  </tr>
  </table>
      <p style="padding-top: 1pt;text-indent: 0pt;text-align: left;"><br/></p><p class="s2" style="padding-left: 7pt;text-indent: 0pt;text-align: left;">Medicamentos genericos o antirretrovirales. Exenta del IVA (art.7 n.15)</p><p style="padding-top: 1pt;padding-left: 7pt;text-indent: 0pt;text-align: left;">Sujeto a pago directo IRS, resolucion No. 726384629376283</p>

      <table style="border-collapse:collapse;border-top-style:solid;border-top-width:1pt;position: absolute;border-top: 2px solid #000;padding: 1px 2px;width: 90%;flex-grow: 1;" cellspacing="0">



          <tr style="height:15pt">
              <td style="width:64pt;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt">
              <p class="s3" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Nombre:</p></td>

              <td style="width:257pt;border-top-style:solid;border-top-width:1pt">
                  <p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;"></p>
              </td>
              <td style="width:64pt;border-top-style:solid;border-top-width:1pt">
                  <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Fecha:</p>
              </td>

              <td style="width:106pt;border-top-style:solid;border-top-width:1pt;border-right-style:solid;border-right-width:1pt">
                  <p class="s4" style="padding-left: 51pt;text-indent: 0pt;line-height: 13pt;text-align: left;">{{fecha}}</p>
              </td>
          </tr>

              <tr style="height:14pt">
                  <td style="width:64pt;border-left-style:solid;border-left-width:1pt">
                  <p class="s3" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Direccion: {{municipio}}, {{departamento}}</p>
              </td>
              <td style="width:257pt"><p class="s1" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;"></p>
              </td>
              <td style="width:64pt"><p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">NIT: {{nit}}</p></td>

              <td style="width:106pt;border-right-style:solid;border-right-width:1pt">
                  <p style="text-indent: 0pt;text-align: left;"><br/></p>
              </td>
              </tr>

              <tr style="height:14pt; border-collapse:collapse;border-top-style:solid;border-top-width:1pt;">
                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">CANT.</p>
                  </td>

                  <td style="width:257pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">DESCRIPCION</p>
                  </td>

                  <td style="width:64pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">P.U.</p>
                  </td>
                  <td style="width:106pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-right-style:solid;border-right-width:1pt;border-right-color:#1F3763" bgcolor="#538235">
                      <p class="s5" style="padding-left: 32pt;text-indent: 0pt;line-height: 13pt;text-align: left;">IMPORTE</p>
                  </td>
              </tr>',
                'detalle'=>'<tr style="height:100%; width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                      <p class="s4" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">{{cantidad}}</p>
                  </td>

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                              <p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">{{nombreproducto}}</p>
                  </td>

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                      <p class="s4" style="padding-left: 13pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q{{precio}}</p>
                  </td>
                                  <td style="width:106pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78">
                      <p class="s4" style="padding-left: 30pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q{{subtotal}}</p>
                  </td>
              </tr>',
                'pie'=>'<tr style="height:28pt">

          <td style="width:321pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" colspan="2">
          <p style="text-indent: 0pt;text-align: left;"><br/></p>
          </td>

          <td style="width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" bgcolor="#538235">
              <p class="s5" style="padding-top: 6pt;padding-left: 17pt;text-indent: 0pt;text-align: left;">TOTAL</p>
          </td>
          <td style="width:106pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" bgcolor="#9BC2E6">
              <p class="s4" style="padding-top: 6pt;padding-left: 30pt;text-indent: 0pt;text-align: left;">Q{{total}}</p>
          </td>
      </tr>
      <tr style="height:29pt">
          <td style="width:491pt;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" colspan="4">
              <p style="text-indent: 0pt;text-align: left;"><br/></p>
          </td></tr></table>
  </th>
  </tr>
  </table>

  </body></html>',
                'consulta'=>"
SELECT pe.razon_social as cliente, pe.direccion as clientedir, pe.numero_documento as clientedoc,us.id as iduser, us.name, t.representante, t.nit, t.departamento, t.municipio,p.Nombre as nombreproducto, v.id as idventa, v.fkfactura as numerfactura, count(pv.id) as posiciondocumento, pv.cantidad, pv.precio_venta as precio, v.numero_comprobante as numerocomprobante, (pv.cantidad*pv.precio_venta-pv.descuento) as subtotal, pv.descuento, sum(pv.descuento) as descuentototal, v.impuesto, v.total, date(v.fecha_hora) as fecha, v.fecha_hora, t.logo, t.Nombre as nombretienda, t.Direccion as direcciontienda, t.telefono as telefonotienda, v.fkFolio as Folio, cp.tipo_comprobante, t.nit as nittienda
      FROM producto_venta as pv
      inner join ventas v on pv.venta_id=v.id
      inner join tienda as t on t.idtienda=v.fkTienda
      inner join	productos as p on pv.producto_id=p.id
     inner join users as us on us.id=v.user_id
     inner join clientes as cl on cl.id=v.cliente_id
     inner join personas as pe on pe.id=cl.persona_id
 inner join comprobantes as cp on cp.id=v.comprobante_id
      where v.id=@{{idventa}} and v.fkTienda=@{{idtienda}} group by v.id, v.fkfactura, pv.precio_venta, v.numero_comprobante, pv.descuento, v.impuesto, v.total, v.fecha_hora, t.logo, t.Nombre, t.Direccion, t.telefono, v.fkFolio, pv.cantidad, p.Nombre, t.nit, t.departamento, t.municipio,t.representante,v.estado,us.name,us.id, pe.razon_social, pe.direccion, pe.numero_documento, cp.tipo_comprobante, t.nit,
SQL"],

          [
                'Titulo' => 'Plantilla Recibo 2',
                'plantillahtml'=>'<!-- Summernote CSS -->',
                'descripcion'=>'<!-- aca ingresar html -->',
                'cabecera'=>'<!DOCTYPE  html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>babd8334-46d1-455b-a1e2-f65e3661dedf</title><meta name="author" content="HP"/><style type="text/css"> * {margin:0; padding:0; text-indent:0; }
   .s1 { color: #375522; font-family:Algerian; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }

   .s1 { color: #375522; font-family:Algerian; font-style: normal; font-weight: normal; text-decoration: none; font-size: 10pt; }

   h1 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 10pt; }
   p { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: bold; text-decoration: none; font-size: 11pt; margin:0pt; }
   .s2 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s3 { color: #375522; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s4 { color: black; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   .s5 { color: #FFF; font-family:Calibri, sans-serif; font-style: normal; font-weight: normal; text-decoration: none; font-size: 11pt; }
   table, tbody {vertical-align: top; overflow: visible; }
         body {
              margin: 0;
              padding: 0;
              position: relative; /* Necesario para el fondo absoluto */
              min-height: 100vh;
          }

          /* Contenedor del fondo con opacidad */
          .background-container {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              z-index: -1; /* Lo envía al fondo */
              overflow: hidden; /* Recorta excesos */
          }

          /* Imagen de fondo (sin opacidad aquí) */
          .background-image {
              left: 50%;
              top: 50%;
              transform: translate(50%, 110%);
              object-fit: cover; /* Ajusta la imagen sin distorsión */
          }

          /* Capa semitransparente sobre la imagen (¡no sobre el texto!) */
          .background-overlay {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              background-color: rgba(255, 255, 255, 0.3); /* Blanco con 70% opacidad */
              z-index: 0; /* Entre la imagen (-1) y el texto (1) */
          }

          /* Contenido (encima de todo) */
          .content-wrapper {
              position: relative;
              z-index: 1; /* Capa superior */
              padding: 20px;
              padding-bottom: 100px; /* Espacio para el pie (ajusta según necesidad) */

          }

                  /* Contenedor del total y borde (fijo al final) */
          .footer-total {
              position: absolute;
              bottom: 0;
              left: 0;
              width: 100%;
              border-top: 2px solid #000; /* Borde superior grueso */
              padding: 10px 20px;
              background-color: white; /* Fondo sólido para superponerse al contenido */
          }

          /* Estilo para el texto del total */
          .total-text {
              font-weight: bold;
              text-align: right;
              margin-right: 20px;
          }


          /* Tabla que expande el espacio disponible */
          .full-height-table {
              width: 100%;
              border-collapse: collapse;
              flex-grow: 1; /* Ocupa todo el espacio restante */
          }

          .full-height-table th,
          .full-height-table td {
              border: 1px solid #000;
              padding: 12px;
          }

          /* Ajuste proporcional de columnas */
          .full-height-table th:nth-child(1),
          .full-height-table td:nth-child(1) {
              width: 70%;
          }

          .full-height-table th:nth-child(2),
          .full-height-table td:nth-child(2) {
              width: 30%;
          }

          .table-container {
              height: calc(100vh - 80px); /* Resta la altura del footer */
              overflow: hidden; /* Evita desbordamiento */
              display: flex;
              flex-direction: column;
          }
  </style></head>

  <body aling="center">

          <!-- Contenedor del fondo -->
      <div class="background-container">
          <!-- Imagen en base64 (opacidad 100%) -->
          <img class="background-image"  width="422" height="308" src="data:image/jpg;base64,{{logo}}"/>
          <!-- Capa semitransparente (alternativa a opacity) -->
          <div class="background-overlay"></div>
      </div>
      <table aling="center">
      <tr>
          <th style="padding-left: 25px; margin-top: 25px;"></th>
  <th>
  <table>
      <tr><td>
      <p style="text-indent: 0pt;text-align: left; margin-top: 50px; padding-left: 35px;"><img width="151" height="86" src="data:image/jpg;base64,{{logo}}"/></p>
  </td>
  <td>
      <p class="s1" style="margin-top: 50px; padding-center: 0pt;text-indent: 0pt;text-align: center;">{{nombretienda}}</p>

      <h1 style="padding-top: 0pt;padding-center: 149pt;text-indent: 0pt;text-align: center;">{{representante}}</h1>

      <p style="padding-top: 1pt;padding-center: 133pt;text-indent: 0pt;text-align: center;">NIT : 20578091</p><p style="padding-top: 1pt;padding-center: 133pt;text-indent: 0pt;line-height: 108%;text-align: center;">{{direcciontienda}}</p>
      <p style="padding-top: 1pt;padding-center: 133pt;text-indent: 0pt;line-height: 108%;text-align: center;">{{departamento}}</p>
  </td>
  <td>
      <p style="margin-top: 50px; padding-top: 3pt;padding-center: 44pt;text-indent: 1pt;line-height: 108%;text-align: center;">RECIBO DE CAJA</p>

          <p style="padding-top: 3pt;padding-center: 44pt;text-indent: 1pt;line-height: 108%;text-align: center;">CORRELATIVO: 492</p>

          <p style="padding-top: 3pt;padding-center: 44pt;text-indent: 1pt;line-height: 108%;text-align: center;">FECHA: {{fecha}} </p>

      <p style="padding-top: 1pt;text-indent: 0pt;text-align: right;"><br/></p>
      <p style="text-indent: 0pt;text-align: right;">VENDEDOR: {{name}}.</p>
      </td>
  </tr>
  </table>
      <p style="padding-top: 1pt;text-indent: 0pt;text-align: left;"><br/></p><p class="s2" style="padding-left: 7pt;text-indent: 0pt;text-align: left;">Medicamentos genericos o antirretrovirales. Exenta del IVA (art.7 n.15)</p><p style="padding-top: 1pt;padding-left: 7pt;text-indent: 0pt;text-align: left;">Sujeto a pago directo IRS, resolucion No. 726384629376283</p>

      <table style="border-collapse:collapse;border-top-style:solid;border-top-width:1pt;position: absolute;border-top: 2px solid #000;padding: 1px 2px;width: 90%;flex-grow: 1;" cellspacing="0">



          <tr style="height:15pt">
              <td style="width:64pt;border-top-style:solid;border-top-width:1pt;border-left-style:solid;border-left-width:1pt">
              <p class="s3" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Nombre:</p></td>

              <td style="width:257pt;border-top-style:solid;border-top-width:1pt">
                  <p class="s4" style=" text-transform: uppercase; padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;" >{{cliente}}</p>
              </td>
              <td style="width:64pt;border-top-style:solid;border-top-width:1pt">
                  <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Fecha:</p>
              </td>

              <td style="width:106pt;border-top-style:solid;border-top-width:1pt;border-right-style:solid;border-right-width:1pt">
                  <p class="s4" style="padding-left: 51pt;text-indent: 0pt;line-height: 13pt;text-align: left;">{{fecha}}</p>
              </td>
          </tr>

              <tr style="height:14pt">
                  <td style="width:64pt;border-left-style:solid;border-left-width:1pt">
                  <p class="s3" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Direccion:</p>
              </td>
              <td style="width:257pt"><p class="s4" style="text-transform: uppercase; padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">{{departamento}}, {{municipio}}</p>
              </td>
              <td style="width:64pt"><p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">NIT:</p></td>

              <td style="width:106pt;border-right-style:solid;border-right-width:1pt">
                  <p style="text-indent: 0pt;text-align: left;">{{clientedoc}}<br/></p>
              </td>
              </tr>

              <tr style="height:14pt; border-collapse:collapse;border-top-style:solid;border-top-width:1pt;">
                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">CANT.</p>
                  </td>

                  <td style="width:257pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">DESCRIPCION</p>
                  </td>

                  <td style="width:64pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-right-style:solid;border-right-width:1pt;border-right-color:#FFFFFF" bgcolor="#538235">
                      <p class="s5" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">P.U.</p>
                  </td>
                  <td style="width:106pt;border-left-style:solid;border-left-width:1pt;border-left-color:#FFFFFF;border-right-style:solid;border-right-width:1pt;border-right-color:#1F3763" bgcolor="#538235">
                      <p class="s5" style="padding-left: 32pt;text-indent: 0pt;line-height: 13pt;text-align: left;">IMPORTE</p>
                  </td>
              </tr>',
                'detalle'=>'<tr style="height:100%; width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                      <p class="s4" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">{{cantidad}}</p>
                  </td>

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                              <p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">{{nombreproducto}}</p>
                  </td>

                  <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                      <p class="s4" style="padding-left: 13pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q{{precio}}</p>
                  </td>
                                  <td style="width:106pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78">
                      <p class="s4" style="padding-left: 30pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q{{subtotal}}</p>
                  </td>
              </tr>',
                'pie'=>'<tr style="height:28pt">

          <td style="width:321pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" colspan="2">
          <p style="text-indent: 0pt;text-align: left;"><br/></p>
          </td>

          <td style="width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" bgcolor="#538235">
              <p class="s5" style="padding-top: 6pt;padding-left: 17pt;text-indent: 0pt;text-align: left;">TOTAL</p>
          </td>
          <td style="width:106pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" bgcolor="#9BC2E6">
              <p class="s4" style="padding-top: 6pt;padding-left: 30pt;text-indent: 0pt;text-align: left;">Q{{total}}</p>
          </td>
      </tr>
      <tr style="height:29pt">
          <td style="width:491pt;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" colspan="4">
              <p style="text-indent: 0pt;text-align: left;"><br/></p>
          </td></tr></table>
  </th>
  </tr>
  </table>

  </body></html>',
                'consulta'=>"
                SELECT pe.razon_social as cliente, pe.direccion as clientedir, pe.numero_documento as clientedoc,us.id as iduser, us.name, t.representante, t.nit, t.departamento, t.municipio,p.Nombre as nombreproducto, v.id as idventa, v.fkfactura as numerfactura, count(pv.id) as posiciondocumento, pv.cantidad, pv.precio_venta as precio, v.numero_comprobante as numerocomprobante, (pv.cantidad*pv.precio_venta-pv.descuento) as subtotal, pv.descuento, sum(pv.descuento) as descuentototal, v.impuesto, v.total, date(v.fecha_hora) as fecha, v.fecha_hora, t.logo, t.Nombre as nombretienda, t.Direccion as direcciontienda, t.telefono as telefonotienda, v.fkFolio as Folio
      FROM producto_venta as pv
      inner join ventas v on pv.venta_id=v.id
      inner join tienda as t on t.idtienda=v.fkTienda
      inner join	productos as p on pv.producto_id=p.id
     inner join users as us on us.id=v.user_id
     inner join clientes as cl on cl.id=v.cliente_id
     inner join personas as pe on pe.id=cl.persona_id
      where v.id=@{{idventa}} and v.fkTienda=@{{idtienda}} group by v.id, v.fkfactura, pv.precio_venta, v.numero_comprobante, pv.descuento, v.impuesto, v.total, v.fecha_hora, t.logo, t.Nombre, t.Direccion, t.telefono, v.fkFolio, pv.cantidad, p.Nombre, t.nit, t.departamento, t.municipio,t.representante,v.estado,us.name,us.id, pe.razon_social, pe.direccion, pe.numero_documento",
            ],
                      [
                'Titulo' => 'LIBRO DIARIO',
                'plantillahtml'=>'<table><thead><tr>
 <th>Total General Debe</th>
                 <th>{{DebeGeneral}}</th>

                 <th class="right">
 Total General Haber
                 </th>

                 <th class="right">
                     {{HaberGeneral}}
                 </th>',
                'descripcion'=>'</tr></thead></table>
 </div>',
                'cabecera'=>'<!DOCTYPE html>
 <html lang="es">
 <head>
     <meta charset="UTF-8">
     <title>Reporte Diario</title>
     <style>
         body {
             font-family: DejaVu Sans, sans-serif;
             font-size: 12px;
             margin:0;
             padding:0;
         }
         .container {
             width: 100%;
             margin: 0 auto;
             padding: 15px;
         }
         h2, h4{
             text-align: center;
             margin: 0;
             padding: 0;
         }
         .header{
             text-align: center;
             margin-bottom: 20px;
         }
         table {
             width: 100%;
             border-collapse: collapse;
             margin-bottom: 15px;
         }
         table th, table td {
             border: 1px solid #555;
             padding: 5px;
             font-size: 12px;
         }
         table th {
             background: #e5e5e5;
         }
     .logo {
         width: 90px; /* ✔ Más pequeño para 58mm */
         margin: 0 auto 5px auto;
         display: block;
     }
         .right {
             text-align: right;
         }
 .center {
     text-align: center; /* Centra contenido inline o inline-block dentro del div */
 }
         .bold {
             font-weight: bold;
         }
         .total-row{
             background: #f0f0f0;
         }
         .folio-title {
             margin-top: 40px;
             margin-bottom: 5px;
             font-weight: bold;
             font-size: 14px;
         }
         .page-break {
             page-break-after: always;
         }

     </style>
 </head>

 <body>
 <div class="container">
     <div class="center">
         <img class="logo" src="data:image/png;base64,{{logo}}">
     </div>

     <h2>LIBRO DIARIO</h2>
     <h4>{{Tienda}}</h4>
     <p style="text-align:center"><strong>Fecha:</strong>{{FechaReporte}}</p>',
                'detalle'=>'{{#detalle}}
 <div class="folio">
     Folio #{{idPivot}} – Venta cerrada por caja. - {{Fecha}}
 </div>

 <table>
     <thead>
         <tr>
             <th>Cuenta</th>
             <th>Descripción</th>
             <th>Debe</th>
             <th>Haber</th>
         </tr>
     </thead>
     <tbody>
         {{#detallehijo}}
         <tr>
             <td>{{formula}}</td>
             <td>{{nombre}}</td>
             <td>{{Debe}}</td>
             <td>{{Haber}}</td>
         </tr>
         {{/detallehijo}}
     </tbody>
 <tfoot>
         <tr>
             <td></td>
             <td><strong>TOTAL</strong></td>
             <td><strong>{{DebeTotal}}</strong></td>
             <td><strong>{{HaberTotal}}</strong></td>
         </tr>
 </foot>
 </table>
 {{/detalle}}',
                'pie'=>'</body>
 </html>',
                'consulta'=>"
                SELECT
 DATE_FORMAT(f.FechaContabilizacion, '%d/%m/%Y') as Fecha,
 f.FechaContabilizacion as FechaReporte,
     f.idFolio as idPivot,
     f.cabecera,
     f.descripcion,
     f.idOrigen,
     f.TipoMovimiento,
     df.idDetalleFolio as idPivotHijo,
     case when df.Naturaleza='H' then df .Monto else 0 end as Haber,
     case when df.Naturaleza='D' then df .Monto else 0 end as Debe,
     df.Naturaleza,
     df.fkCuenetaContable,
     cc.nombre,
     cc.formula,
     SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END)
         OVER (PARTITION BY f.idFolio) AS HaberTotal,
     SUM(CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
         OVER (PARTITION BY f.idFolio) AS DebeTotal,
 t.Nombre as Tienda, t.departamento, t.municipio, t.representante, t.Telefono, t.nit, t.logo,
 SUM(CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
     OVER () AS DebeGeneral,
 SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END)
     OVER () AS HaberGeneral
 FROM Folio AS f
 INNER JOIN DetalleFolio AS df ON f.idFolio = df.fkFolio
 INNER JOIN cuentas_contables AS cc ON df.fkCuenetaContable = cc.id
 INNER JOIN tienda as t on f.fkTienda=t.idTienda
 WHERE f.fkTienda=@{{idtienda}} and f.FechaContabilizacion between @{{idventa}}
 ORDER BY f.idFolio, df.idDetalleFolio;",
            ],
                      [
                'Titulo' => 'LIBRO MAYOR',
                'plantillahtml'=>'<table><thead><tr>
 <th>Total General Debe</th>
                 <th>Q. {{DebeGeneral}}</th>

                 <th class="right">
 Total General Haber
                 </th>

                 <th class="right">
                     Q. {{HaberGeneral}}
                 </th>',
                'descripcion'=>'</tr></thead></table>',
                'cabecera'=>'<!DOCTYPE html>
 <html lang="es">
 <head>
     <meta charset="UTF-8">
     <title>Reporte Diario</title>
     <style>
         body {
             font-family: DejaVu Sans, sans-serif;
             font-size: 10px;
             margin:0;
             padding:0;
         }
         .container {
             width: 100%;
             margin: 0;
             padding: 10px;
         }
         h2, h4{
             text-align: center;
             margin: 0;
             padding: 0;
         }
         .header{
             text-align: center;
             margin-bottom: 10px;
         }
         table {
             width: 100%;
             border-collapse: collapse;
             margin-bottom: 10px;
         }
         table th, table td {
             border: 1px solid #555;
             padding: 2px;
             font-size: 10px;
         }
         table th {
             background: #e5e5e5;
         }
 .logo {
     width: 90px;       /* Ancho del logo */
     display: block;    /* Necesario para que margin auto funcione */
     margin: 0 auto 5px; /* Arriba 0, horizontal auto (centrado), abajo 5px */
 }
         .right {
             text-align: right;
         }
 .center {
     text-align: center; /* Centra contenido inline o inline-block dentro del div */
 }
         .bold {
             font-weight: bold;
         }
         .total-row{
             background: #f0f0f0;
         }
         .folio-title {
             margin-top: 40px;
             margin-bottom: 5px;
             font-weight: bold;
             font-size: 10px;
         }
         .page-break {
             page-break-after: always;
         }
     </style>
 </head>

 <body>
 <div class="container">
     <div class="center">
         <img alt="logo" class="logo" src="data:image/png;base64,{{logo}}">
     </div>

     <h2>LIBRO MAYOR</h2>
     <h4>{{Tienda}}</h4>
     <p style="text-align:center"><strong>Fecha:</strong>{{FechaReporte}}</p>',
                'detalle'=>'{{#detalle}}

 <div class="mayor-cuenta">
     <h3 style="margin-bottom:0">{{formula}} – {{nombre}}</h3>
     <small>(Cuenta ID: {{fkCuenetaContable}})</small>

     <table class="mayor-tabla" border="1" cellspacing="0" cellpadding="4" width="100%" style="margin-top:2px">
         <thead>
             <tr>
                 <th>Fecha</th>
                 <th>Descripción</th>
                 <th>Debe</th>
                 <th>Haber</th>
                 <th>Saldo</th>
             </tr>
         </thead>
         <tbody>
             {{#detallehijo}}
             <tr>
                 <td>{{Fecha}}</td>
                 <td>{{descripcion}}</td>
                 <td>Q. {{Debe}}</td>
                 <td>Q. {{Haber}}</td>
                 <td>Q. {{SaldoLinea}}</td>
             </tr>
             {{/detallehijo}}
         </tbody>

         <tfoot>
             <tr>
                 <td colspan="2" style="text-align:right"><strong>Total</strong></td>
                 <td><strong>Q. {{DebeTotal}}</strong></td>
                 <td><strong>Q. {{HaberTotal}}</strong></td>
                 <td><strong>Q. {{SaldoFinalFolio}}</strong></td>
             </tr>
         </tfoot>
     </table>

     <br><br>
 </div>

 {{/detalle}}',
                'pie'=>'</div>
 </body>
 </html>',
                'consulta'=>"SELECT
 DATE_FORMAT(f.FechaContabilizacion, '%d/%m/%Y') as Fecha,
 f.FechaContabilizacion as FechaReporte,
     cc.id as idPivot,
     df.fkCuenetaContable as idPivotHijo,
     f.cabecera,
     f.descripcion,
     f.idOrigen,
     f.TipoMovimiento,
     case when df.Naturaleza='H' then df .Monto else 0 end as Haber,
     case when df.Naturaleza='D' then df .Monto else 0 end as Debe,
     df.Naturaleza,
     df.fkCuenetaContable,
     cc.nombre,
     cc.formula,
     SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END)
         OVER (PARTITION BY cc.id) AS HaberTotal,
     SUM(CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
         OVER (PARTITION BY cc.id) AS DebeTotal,
 t.Nombre as Tienda, t.departamento, t.municipio, t.representante, t.Telefono, t.nit, t.logo,
 SUM(CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
     OVER () AS DebeGeneral,

 SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END)
     OVER () AS HaberGeneral,
        (CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
     -
     (CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END) AS SaldoLinea,
             SUM(CASE WHEN df.Naturaleza = 'D' THEN df.Monto ELSE 0 END)
         OVER (PARTITION BY cc.id) -
     SUM(CASE WHEN df.Naturaleza = 'H' THEN df.Monto ELSE 0 END)
         OVER (PARTITION BY cc.id) AS SaldoFinalFolio
 FROM Folio AS f
 INNER JOIN DetalleFolio AS df ON f.idFolio = df.fkFolio
 INNER JOIN cuentas_contables AS cc ON df.fkCuenetaContable = cc.id
 INNER JOIN tienda as t on f.fkTienda=t.idTienda
 WHERE f.fkTienda=@{{idtienda}} and f.FechaContabilizacion between @{{idventa}}
 ORDER BY cc.id, f.idFolio, df.idDetalleFolio;",
            ],
                      [
                'Titulo' => 'KARDEX INVENTARIO',
                'plantillahtml'=>'</tbody>
         </table>',
                'descripcion'=>'<!-- aca ingresar html -->',
                'cabecera'=>'<!DOCTYPE html>
 <html lang="es">
 <head>
     <meta charset="UTF-8">
     <title>Kardex Inventario</title>
     <style>
         body {
             font-family: DejaVu Sans, sans-serif;
             font-size: 10px;
             margin:0;
             padding:0;
         }
         .container {
             width: 100%;
             margin: 0;
             padding: 10px;
         }
         h2, h4{
             text-align: center;
             margin: 0;
             padding: 0;
         }
         .header{
             text-align: center;
             margin-bottom: 10px;
         }
         table {
             width: 100%;
             border-collapse: collapse;
             margin-bottom: 10px;
         }
         table th, table td {
             border: 1px solid #555;
             padding: 2px;
             font-size: 10px;
         }
         table th {
             background: #e5e5e5;
         }
 .logo {
     width: 90px;       /* Ancho del logo */
     display: block;    /* Necesario para que margin auto funcione */
     margin: 0 auto 5px; /* Arriba 0, horizontal auto (centrado), abajo 5px */
 }
         .right {
             text-align: right;
         }
 .center {
     text-align: center; /* Centra contenido inline o inline-block dentro del div */
 }
 .left {
     text-align: center; /* Centra contenido inline o inline-block dentro del div */
 }
         .bold {
             font-weight: bold;
         }
         .total-row{
             background: #f0f0f0;
         }
         .folio-title {
             margin-top: 40px;
             margin-bottom: 5px;
             font-weight: bold;
             font-size: 10px;
         }
         .page-break {
             page-break-after: always;
         }
 @page {
     margin-top: 70px;
     margin-bottom: 40px;
 }

 .footer {
     position: fixed;
     bottom: 10px;
     left: 0;
     right: 0;
     width: 100%;
     text-align: center;    /* CENTRA EL TEXTO COMPLETO */
     font-size: 10px;
 }

 .page-number:before {
     content: counter(page);
 }

 .total-pages:before {
     content: counter(pages);
 }

     </style>



 </head>

 <body>
     <div class="left">
 {{ENCABEZADOPAGINA}}
     </div>
 <div class="container">
     <div class="center">
         <img alt="logo" class="logo" src="data:image/png;base64,{{logo}}">
     </div>

     <h2>KARDEX DE INVENTARIO</h2>

     <h4>{{Tienda}}</h4>
     <p style="text-align:center"><strong>Fecha:</strong>{{FechaReporte}}</p>
 <div class="mayor-cuenta">

 <table class="kardex">
     <thead>
         <tr>
             <th>Fecha</th>
             <th>Documento</th>
             <th>Producto</th>
             <th>Entrada</th>
             <th>Salida</th>
             <th>Saldo</th>
             <th>Costo U.</th>
             <th>Costo Total</th>
             <th>Costo Promedio</th>
         </tr>
     </thead>
         <tbody>',
                'detalle'=>'{{#detalle}}

             {{#detallehijo}}
             <tr>
                 <td>{{fecha}}</td>
                 <td>{{documento}}</td>
                 <td>{{nombre}}</td>
                 <td>{{Entradas}}</td>
                 <td>{{Salidas}}</td>
                 <td>{{stock_acumulado}}</td>
                 <td>Q. {{precio_compra}}</td>
                 <td>Q. {{costo_total}}</td>
                 <td>Q. {{costo_promedio}}</td>
             </tr>
             {{/detallehijo}}


 {{/detalle}}',
                'pie'=>'</body>
 </html>',
                'consulta'=>"SELECT
 p.id as idPivot,
 m.producto_id as idPivotHijo,
 t.Nombre as Tienda,
 t.logo,
 concat(t.municipio,', ',t.departamento) as lugar,
 p.fktienda,
 p.codigo,
 p.nombre,
     m.fecha,
     m.tipo,
     case when m.tipo='ENTRADA' then m.cantidad else 0 end as Entradas,
     case when m.tipo='SALIDA' then m.cantidad else 0 end as Salidas,
     m.documento,
     m.cantidad,
     m.precio_compra,
     ROUND(m.cantidad * m.precio_compra, 2) AS costo_total,
     SUM(
         CASE
             WHEN m.tipo = 'ENTRADA' THEN m.cantidad
             WHEN m.tipo = 'SALIDA' THEN -m.cantidad
         END
     ) OVER (PARTITION BY m.producto_id ORDER BY m.fecha) AS stock_acumulado,
     ROUND(
     SUM(
         CASE
             WHEN m.tipo = 'ENTRADA' THEN m.cantidad * m.precio_compra
             WHEN m.tipo = 'SALIDA' THEN -m.cantidad * m.precio_compra
         END
     ) OVER (PARTITION BY m.producto_id ORDER BY m.fecha)
     /
     NULLIF(
         SUM(
             CASE
                 WHEN m.tipo = 'ENTRADA' THEN m.cantidad
                 WHEN m.tipo = 'SALIDA' THEN -m.cantidad
             END
         ) OVER (PARTITION BY m.producto_id ORDER BY m.fecha),
     0), 2) AS costo_promedio

 FROM (
     SELECT
         cp.producto_id AS producto_id,
         c.fecha_hora AS fecha,
         'ENTRADA' AS tipo,
         CONCAT('COMP-', c.id, '-Doc: ',c.numero_comprobante) AS documento,
         cp.cantidad AS cantidad,
         cp.precio_compra,
         cp.fkTienda
     FROM compra_producto cp
     JOIN compras c ON c.id = cp.producto_id
     UNION ALL
     SELECT
         pv.producto_id AS producto_id,
         v.fecha_hora AS fecha,
         'SALIDA' AS tipo,
         CONCAT('VENT-', v.id,'-Comp:',v.numero_comprobante) AS documento,
         pv.cantidad AS cantidad,
         pv.precio_venta as precio_compra,
         pv.fkTienda
     FROM producto_venta pv
     JOIN ventas v ON v.id = pv.venta_id
     where v.Estado=2
 ) m
 inner join productos as p on m.producto_id=p.id
 inner join tienda as t on m.fkTienda=t.idTienda
 where t.idtienda=@{{idtienda}} and m.fecha between @{{idventa}}
 ORDER BY m.fecha;",
            ],
                      [
                'Titulo' => 'LIBRO BALANCE',
                'plantillahtml'=>'</table>',
                'descripcion'=>'<!-- aca ingresar html -->',
                'cabecera'=>'<!DOCTYPE html>
  <html lang="es">
  <head>
      <meta charset="UTF-8">
      <title>Reporte BALANCE</title>
      <style>
          body {
              font-family: DejaVu Sans, sans-serif;
              font-size: 12px;
              margin:0;
              padding:0;
          }
          .container {
              width: 100%;
              margin: 0 auto;
              padding: 15px;
          }
          h2, h4{
              text-align: center;
              margin: 0;
              padding: 0;
          }
          .header{
              text-align: center;
              margin-bottom: 20px;
          }
          table {
              width: 100%;
              border-collapse: collapse;
              margin-bottom: 15px;
          }
          table th, table td {
              border: 1px solid #555;
              padding: 5px;
              font-size: 12px;
          }
          table th {
              background: #e5e5e5;
          }
      .logo {
          width: 90px; /* ✔ Más pequeño para 58mm */
          margin: 0 auto 5px auto;
          display: block;
      }
          .right {
              text-align: right;
          }
  .center {
      text-align: center; /* Centra contenido inline o inline-block dentro del div */
  }
          .bold {
              font-weight: bold;
          }
          .total-row{
              background: #f0f0f0;
          }
          .folio-title {
              margin-top: 40px;
              margin-bottom: 5px;
              font-weight: bold;
              font-size: 14px;
          }
          .page-break {
              page-break-after: always;
          }

      </style>
 <style>
 .table-bal {
     width: 100%;
     border-collapse: collapse;
     font-size: 12px;
 }
 .table-bal th, .table-bal td {
     padding: 6px;
 }
 .section-title {
     background: #e0e0e0;
     font-weight: bold;
     border-top: 1px solid #000;
     border-bottom: 1px solid #000;
 }
 .text-right { text-align: right; }
 .text-left { text-align: left; }
 .total-row {
     font-weight: bold;
     border-top: 1px solid #000;
 }
 </style>
  </head>

  <body>
      <div class="right">
 {{ENCABEZADOPAGINA}}
 </div>

  <div class="container">
      <div class="center">
          <img class="logo" src="data:image/png;base64,{{logo}}">
      </div>

      <h2>LIBRO BALANCE</h2>
      <h4>{{Tienda}}</h4>
      <p style="text-align:center"><strong>Fecha:</strong>{{FechaReporte}}</p>
 <table class="table-bal">',
                'detalle'=>'{{#detalle}}
     <tr><td colspan="2" class="section-title">{{raiz_nombre}}</td></tr>

          {{#detallehijo}}
             <tr>
             <td class="text-left">{{formula}} {{nombre}}</td>
             <td class="text-right">{{Saldo}}</td>
         </tr>
          {{/detallehijo}}
     <tr class="total-row">
         <td>Total Activo</td>
         <td class="text-right">
             {{TOTAL}}
         </td>
     </tr>

  {{/detalle}}
 <br>',
                'pie'=>'</body>
 </html>',
                'consulta'=>"WITH RECURSIVE arbol AS (
     SELECT
         id,
         nombre,
         padre_id,
         formula,
         id AS raiz_id,
         nombre AS raiz_nombre,
         fkTienda as idTienda
     FROM cuentas_contables
     WHERE padre_id IS NULL

     UNION ALL

     SELECT
         c.id,
         c.nombre,
         c.padre_id,
         c.formula,
         a.raiz_id,
         a.raiz_nombre,
         c.fkTienda as idTienda
     FROM cuentas_contables c
     INNER JOIN arbol a ON c.padre_id = a.id
 ),

 movimientos AS (
     SELECT
         df.fkCuenetaContable AS cuenta_id,
         CASE WHEN df.Naturaleza='D' THEN df.Monto ELSE 0 END AS Debe,
         CASE WHEN df.Naturaleza='H' THEN df.Monto ELSE 0 END AS Haber,
         f.FechaContabilizacion,
         df.fkTienda as idTienda
     FROM DetalleFolio df
     INNER JOIN Folio f ON f.idFolio = df.fkFolio
         where f.fkTienda=@{{idtienda}} and f.FechaContabilizacion between @{{idventa}}

 )

 SELECT
     a.id,
     a.nombre,
     a.padre_id,
     a.formula,
     a.idTienda,
     a.raiz_id,
     a.raiz_nombre,
     a.raiz_nombre AS idPivot,
     a.nombre AS idPivotHijo,

     SUM(IFNULL(m.Debe,0)) AS DebeTotal,
     SUM(IFNULL(m.Haber,0)) AS HaberTotal,

     CASE
         WHEN a.raiz_nombre = 'ACTIVO'
             THEN SUM(IFNULL(m.Debe,0)) - SUM(IFNULL(m.Haber,0))
         WHEN a.raiz_nombre IN ('PASIVO', 'CAPITAL')
             THEN SUM(IFNULL(m.Haber,0)) - SUM(IFNULL(m.Debe,0))
         ELSE 0
     END AS Saldo,

     -- TOTAL por grupo sin doble SUM()
     SUM(
         CASE
             WHEN a.raiz_nombre = 'ACTIVO'
                 THEN IFNULL(m.Debe,0) - IFNULL(m.Haber,0)
             WHEN a.raiz_nombre IN ('PASIVO','CAPITAL')
                 THEN IFNULL(m.Haber,0) - IFNULL(m.Debe,0)
             ELSE 0
         END
     ) OVER (PARTITION BY a.raiz_nombre) AS TOTAL,

     t.logo,
     t.Nombre AS Tienda,
     t.representante,
     t.nit

 FROM arbol a
 LEFT JOIN movimientos m ON m.cuenta_id = a.id
 INNER JOIN tienda t ON a.idTienda = t.idTienda
 where a.padre_id is not null
 GROUP BY
     a.id, a.nombre, a.padre_id, a.formula,
     a.idTienda, a.raiz_id, a.raiz_nombre,
     t.logo, t.Nombre, t.representante, t.nit

 ORDER BY a.raiz_id, a.formula;",
            ],
        ];

        foreach ($documentoss as $doc) {

    plantillahtmlgeneral::updateOrCreate(
        ['Titulo' => $doc['Titulo']], // clave única
        [
            'Titulo' => $doc['Titulo'],
            'plantillahtml'=>$doc['plantillahtml'],
            'descripcion'=>$doc['descripcion'],
            'cabecera'=>$doc['cabecera'],
            'detalle'=>$doc['detalle'],
            'pie'=>$doc['pie'],
            'consulta'=>$doc['consulta'],
        ]
      );
   }
    }
}
