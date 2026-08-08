<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Expediente Consolidado Firmado</title>
    <style>
        /* --- ESTILOS GENERALES DE IMPRESIÓN --- */
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333333;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always; /* Fuerza el salto físico de página en el PDF */
        }
        
        /* --- CABECERAS Y ENCABEZADOS --- */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .logo-box {
            font-size: 18px;
            font-weight: bold;
            color: #0d47a1;
            text-transform: uppercase;
        }
        .subheader-text {
            font-size: 9px;
            color: #666;
            text-align: right;
        }
        .title-section {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* --- TABLAS DE DATOS --- */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: left;
            padding: 6px;
            border: 1px solid #dddddd;
            text-transform: uppercase;
            font-size: 9px;
        }
        .data-table td {
            padding: 5px;
            border: 1px solid #dddddd;
            vertical-align: top;
        }
        .bg-mo { th { background-color: #d0e1f9 !important; } }
        .bg-mat { th { background-color: #d1e7dd !important; } }
        
        /* --- BLOQUE DE INFO DE ORDEN --- */
        .info-box {
            width: 100%;
            border: 1px solid #cccccc;
            background-color: #fafafa;
            margin-bottom: 15px;
            padding: 8px;
            border-radius: 4px;
        }
        .info-box td {
            padding: 3px 8px;
            border: none;
        }

        /* --- SECCIÓN DE FIRMAS --- */
        .signature-container {
            width: 100%;
            margin-top: 40px;
        }
        .signature-box {
            width: 45%;
            text-align: center;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px solid #333333;
            margin-top: 50px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 10px;
        }
        .img-firma {
            max-height: 60px;
            max-width: 180px;
            display: block;
            margin: 0 auto -10px auto;
        }
    </style>
</head>
<body>

    <!-- ======================================================== -->
    <!-- PÁGINA 1: CARTA DE PRESENTACIÓN (LÓGICA DEL EXPEDIENTE) -->
    <!-- ======================================================== -->
    <table class="header-table">
        <tr>
            <td class="logo-box">Distribuidor Señor de Esquipulas</td>
            <td class="subheader-text">Imagina, crea... Evoluciona<br>Instalaciones de Servicios de TV</td>
        </tr>
    </table>

    <p style="text-align: right; font-weight: bold; margin-bottom: 25px;">Quetzaltenango, {{ $fecha_reporte }}.</p>

    <p style="margin: 0; line-height: 1.5;">
        Ingeniero<br>
        <strong>Jorge Mario Fingado</strong><br>
        Región Occidente<br>
        Quetzaltenango
    </p>

    <p style="margin-top: 25px; text-align: justify; line-height: 1.6;">
        Estimado Ingeniero:<br><br>
        Por medio de la presente se hace entrega del expediente en el cual se resume el trabajo
        ejecutado por la empresa <strong>Distribuidor Señor de Esquipulas</strong> en la actividad de instalaciones de servicios
        de Televisión Satelital durante el periodo <strong>{{ $periodo_mes }}</strong> en la región occidente.
    </p>

    <p style="margin-top: 15px; font-weight: bold;">El trabajo ejecutado se resume detallado de la siguiente manera:</p>
    
    <ul style="line-height: 2; font-size: 11px; list-style-type: square; padding-left: 20px;">
        @foreach($resumenManoObra as $mo)
            <li>{{ $mo['descripcion'] }}: <strong style="float: right; margin-right: 50px;">{{ $mo['cantidad'] }} Unidades</strong></li>
        @endforeach
    </ul>

    <p style="margin-top: 30px; text-align: justify; line-height: 1.6;">
        El contenido del expediente se adjunta para su revisión, aprobación y poder proseguir con los
        trámites de facturación correspondientes.
    </p>

    <div style="margin-top: 60px; width: 250px; text-align: center;">
        <p style="margin-bottom: 45px;">Atentamente:</p>
        <div style="border-top: 1px solid #333; padding-top: 5px; font-weight: bold;">
            Carlos Enrique López Camposeco<br>
            <span style="font-weight: normal; color:#666;">Gerente General</span>
        </div>
    </div>

    <div class="page-break"></div>


    <!-- ======================================================== -->
    <!-- PÁGINA 2: CUADRO GENERAL DE USO DE MATERIALES (RESUMEN)  -->
    <!-- ======================================================== -->
    <div class="title-section">Uso Consolidado de Materiales en Instalaciones</div>
    <p style="text-align: center; margin-top: -10px; color: #555;">REGIÓN: OCCIDENTE | PERIODO: {{ $periodo_mes }}</p>

    <table class="data-table bg-mat">
        <thead>
            <tr>
                <th style="width: 20%;">SKU / CÓDIGO</th>
                <th style="width: 60%;">DESCRIPCIÓN DEL MATERIAL</th>
                <th style="width: 20%; text-align: center;">TOTAL UTILIZADO</th>
            </tr>
        </thead>
        <tbody>
            @forelse($materialesGlobales as $matG)
                <tr>
                    <td><strong>{{ $matG['sku'] }}</strong></td>
                    <td>{{ $matG['descripcion'] }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $matG['cantidad'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; color: #999;">No hay materiales registrados en este periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>


    <!-- ======================================================== -->
    <!-- PÁGINA 3: CUADRO DE COSTOS / RESUMEN DE COBROS FINANCIERO -->
    <!-- ======================================================== -->
    <div class="title-section" style="color: #d32f2f;">Cuadro de Costos Instalaciones de Servicios</div>
    <p style="text-align: center; margin-top: -10px; color: #555;">REGIÓN: OCCIDENTE | PERIODO: {{ $periodo_mes }}</p>

    <table class="data-table bg-mo">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 45%;">DESCRIPCIÓN DE MANO DE OBRA</th>
                <th style="width: 10%; text-align: center;">UNIDAD</th>
                <th style="width: 12%; text-align: center;">CANTIDAD REALIZADA</th>
                <th style="width: 13%; text-align: right;">PRECIO UNITARIO</th>
                <th style="width: 15%; text-align: right;">TOTAL COBRO</th>
            </tr>
        </thead>
        <tbody>
            @php $index = 1; @endphp
            @foreach($resumenManoObra as $mo)
                <tr>
                    <td style="text-align: center;">{{ $index++ }}</td>
                    <td>{{ $mo['descripcion'] }}</td>
                    <td style="text-align: center;">{{ $mo['unidad'] }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $mo['cantidad'] }}</td>
                    <td style="text-align: right;">Q {{ number_format($mo['precio'], 2) }}</td>
                    <td style="text-align: right; font-weight: bold;">Q {{ number_format($mo['total'], 2) }}</td>
                </tr>
            @endforeach
            
            <!-- Bloque de Cierre Fiscal -->
            <tr>
                <td colspan="4" style="border: none;"></td>
                <td style="font-weight: bold; text-align: right; background-color: #f9f9f9;">TOTAL M.O.</td>
                <td style="text-align: right; font-weight: bold; background-color: #f9f9f9;">Q {{ number_format($totalManoObra, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" style="border: none;"></td>
                <td style="font-weight: bold; text-align: right; background-color: #f9f9f9;">IVA (12%)</td>
                <td style="text-align: right; font-weight: bold; background-color: #f9f9f9;">Q {{ number_format($iva, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" style="border: none;"></td>
                <td style="font-weight: bold; text-align: right; background-color: #e0e0e0; color: #d32f2f;">TOTAL CON IVA</td>
                <td style="text-align: right; font-weight: bold; background-color: #e0e0e0; color: #d32f2f;">Q {{ number_format($totalConIva, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>


    <!-- ======================================================== -->
    <!-- PÁGINAS 4+: ORDENES DE SERVICIO INDIVIDUALES LIQUIDADAS  -->
    <!-- ======================================================== -->
    @foreach($expedientes as $exp)
        <table class="header-table" style="margin-bottom: 10px;">
            <tr>
                <td class="logo-box" style="font-size: 15px;">Claro TV Satelital</td>
                ORDEN DE SERVICIO INDIVIDUAL
                
                Detalle de Materiales Instalados en esta Orden:
                
                NOTA: HAGO CONSTAR QUE EL DÍA DE HOY SE INSTALÓ EN MI DOMICILIO EL SERVICIO DE CLARO TV SATELITAL EN PLENA CONFORMIDAD, 
                QUEDANDO SATISFECHO CON LOS MATERIALES Y EL TRABAJO REALIZADO POR EL PERSONAL TÉCNICO AUTORIZADO.
                @if(!$loop->last)
                
                @endif
@endforeach
