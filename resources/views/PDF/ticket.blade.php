<!DOCTYPE  html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
        <img class="background-image"  width="422" height="308" src="data:image/jpg;base64,{{ $Tienda->logo }}"/>
        <!-- Capa semitransparente (alternativa a opacity) -->
        <div class="background-overlay"></div>
    </div>
    <table aling="center">
    <tr>
        <th style="padding-left: 25px; margin-top: 25px;"></th>
<th>
<table>
    <tr><td>
    <p style="text-indent: 0pt;text-align: left; margin-top: 50px; padding-left: 35px;"><img width="151" height="86" src="data:image/jpg;base64,{{ $compressed }}"/></p>
</td>
<td>
    <p class="s1" style="margin-top: 50px; padding-center: 0pt;text-indent: 0pt;text-align: center;">DISTRIBUIDORA HOSPITALARIA</p>

    <h1 style="padding-top: 0pt;padding-center: 149pt;text-indent: 0pt;text-align: center;">GRISELDA MARISOL HUINAC DE ALVAREZ</h1>

    <p style="padding-top: 1pt;padding-center: 133pt;text-indent: 0pt;text-align: center;">NIT : 20578091</p><p style="padding-top: 1pt;padding-center: 133pt;text-indent: 0pt;line-height: 108%;text-align: center;">14 CALLE 4-36 ZONA 7</p>
    <p style="padding-top: 1pt;padding-center: 133pt;text-indent: 0pt;line-height: 108%;text-align: center;">QUETZALTENANGO.</p>
</td>
<td>
    <p style="margin-top: 50px; padding-top: 3pt;padding-center: 44pt;text-indent: 1pt;line-height: 108%;text-align: center;">RECIBO DE CAJA</p>

        <p style="padding-top: 3pt;padding-center: 44pt;text-indent: 1pt;line-height: 108%;text-align: center;">CORRELATIVO: 492</p>

        <p style="padding-top: 3pt;padding-center: 44pt;text-indent: 1pt;line-height: 108%;text-align: center;">FECHA: 22/05/2025</p>

    <p style="padding-top: 1pt;text-indent: 0pt;text-align: right;"><br/></p>
    <p style="text-indent: 0pt;text-align: right;">VENDEDOR: ADMINISTRACION.</p>
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
            </tr>



            <tr style="height:100%; width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
@php
    $subtotal = 0;
@endphp
            @foreach ($ventas as $item)
@php
    $subtotal=$subtotal+($item->precioventa*$item->cantidad);
@endphp
                <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                    <p class="s4" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">{{ $item->cantidad }}</p>
                </td>

                <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                            <p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">{{$item->nombreproducto}}</p>
                </td>

                <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                    <p class="s4" style="padding-left: 13pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q{{ $item->precioventa*$item->cantidad }}</p>
                </td>
                                <td style="width:106pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78">
                    <p class="s4" style="padding-left: 30pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q{{ $subtotal }}</p>
                </td>
            @endforeach
            </tr>
                        <tr style="height:421pt; width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">


                <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                    <p class="s4" style="padding-left: 1pt;text-indent: 0pt;line-height: 13pt;text-align: center;">7</p>
                </td>

                <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                            <p class="s4" style="padding-left: 2pt;text-indent: 0pt;line-height: 13pt;text-align: left;">ERTAPENEM 1G</p>
                </td>

                <td style="width:64pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78">
                    <p class="s4" style="padding-left: 13pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q345.00</p>
                </td>

                <td style="width:106pt;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78">
                    <p class="s4" style="padding-left: 30pt;text-indent: 0pt;line-height: 13pt;text-align: left;">Q2,415.00</p>
                </td>
            </tr>

    <tr style="height:28pt">

        <td style="width:321pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" colspan="2">
        <p style="text-indent: 0pt;text-align: left;"><br/></p>
        </td>

        <td style="width:64pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" bgcolor="#538235">
            <p class="s5" style="padding-top: 6pt;padding-left: 17pt;text-indent: 0pt;text-align: left;">TOTAL</p>
        </td>
        <td style="width:106pt;border-top-style:solid;border-top-width:1pt;border-top-color:#1F4E78;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" bgcolor="#9BC2E6">
            <p class="s4" style="padding-top: 6pt;padding-left: 30pt;text-indent: 0pt;text-align: left;">Q2,415.00</p>
        </td>
    </tr>
    <tr style="height:29pt">
        <td style="width:491pt;border-left-style:solid;border-left-width:1pt;border-left-color:#1F4E78;border-bottom-style:solid;border-bottom-width:1pt;border-bottom-color:#1F4E78;border-right-style:solid;border-right-width:1pt;border-right-color:#1F4E78" colspan="4">
            <p style="text-indent: 0pt;text-align: left;"><br/></p>
        </td></tr></table>
</th>
</tr>
</table>

</body></html>
