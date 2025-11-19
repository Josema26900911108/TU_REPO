<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
   public function up(): void
    {
        Schema::create('plantillahtmlgeneral', function (Blueprint $table) {
            $table->id();
            $table->string('Titulo');
            $table->longText('plantillahtml');
            $table->text('descripcion');
            $table->longText('cabecera');
            $table->longText('detalle');
            $table->longText('pie');
            $table->text('consulta');
            $table->timestamps();
        });

            // Insertar datos después de que la tabla ya fue creada
    DB::table('plantillahtmlgeneral')->insert([
        ['Titulo' => 'Plantilla Recibo',

        'plantillahtml' => '<!DOCTYPE  html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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

         <p style="padding-top: 3pt;padding-center: 44pt;text-indent: 1pt;line-height: 108%;text-align: center;">FECHA:{{fecha}} </p>

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
                 <p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Sanatorio San Pablo</p>
             </td>
             <td style="width:64pt;border-top-style:solid;border-top-width:1pt">
                 <p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Fecha:</p>
             </td>

             <td style="width:106pt;border-top-style:solid;border-top-width:1pt;border-right-style:solid;border-right-width:1pt">
                 <p class="s4" style="padding-left: 51pt;text-indent: 0pt;line-height: 13pt;text-align: left;">22/05/2025</p>
             </td>
         </tr>

             <tr style="height:14pt">
                 <td style="width:64pt;border-left-style:solid;border-left-width:1pt">
                 <p class="s3" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Direccion:</p>
             </td>
             <td style="width:257pt"><p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Quetzaltenango, Quetzaltenango</p>
             </td>
             <td style="width:64pt"><p class="s3" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">NIT:</p></td>

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

        'descripcion' => 'plantilla para creacion de recibos.',

        'cabecera' => '<!DOCTYPE  html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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

        'detalle' => '<tr style="height:100%; width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">

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

        'pie' => '<tr style="height:28pt">

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

        'consulta'=>'SELECT pe.razon_social as cliente, pe.direccion as clientedir, pe.numero_documento as clientedoc,us.id as iduser, us.name, t.representante, t.nit, t.departamento, t.municipio,p.Nombre as nombreproducto, v.id as idventa, v.fkfactura as numerfactura, count(pv.id) as posiciondocumento, pv.cantidad, pv.precio_venta as precio, v.numero_comprobante as numerocomprobante, (pv.cantidad*pv.precio_venta-pv.descuento) as subtotal, pv.descuento, sum(pv.descuento) as descuentototal, v.impuesto, v.total, date(v.fecha_hora) as fecha, v.fecha_hora, t.logo, t.Nombre as nombretienda, t.Direccion as direcciontienda, t.telefono as telefonotienda, v.fkFolio as Folio, cp.tipo_comprobante, t.nit as nittienda
     FROM producto_venta as pv
     inner join ventas v on pv.venta_id=v.id
     inner join tienda as t on t.idtienda=v.fkTienda
     inner join	productos as p on pv.producto_id=p.id
    inner join users as us on us.id=v.user_id
    inner join clientes as cl on cl.id=v.cliente_id
    inner join personas as pe on pe.id=cl.persona_id
    inner join comprobantes as cp on cp.id=v.comprobante_id
     where v.id=@{{idventa}} and v.fkTienda=@{{idtienda}} group by v.id, v.fkfactura, pv.precio_venta, v.numero_comprobante, pv.descuento, v.impuesto, v.total, v.fecha_hora, t.logo, t.Nombre, t.Direccion, t.telefono, v.fkFolio, pv.cantidad, p.Nombre, t.nit, t.departamento, t.municipio,t.representante,v.estado,us.name,us.id, pe.razon_social, pe.direccion, pe.numero_documento, cp.tipo_comprobante, t.nit',
    ],
    ]);
    }

    public function down(): void
{
    Schema::dropIfExists('plantillahtmlgeneral');
}


};
