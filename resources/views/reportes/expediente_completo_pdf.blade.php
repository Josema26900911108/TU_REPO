<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Expediente Consolidado de Servicios por Tecnología</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333333;
            font-size: 11px;
            line-height: 1.4;
        }
        .page-break {
            page-break-after: always;
        }
        
        /* Encabezado Principal */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .logo-img {
            max-height: 85px;
            max-width: 260px;
            object-fit: contain;
        }
        .logo-text {
            font-size: 18px;
            font-weight: bold;
            color: #0d47a1;
            text-transform: uppercase;
        }
        .subheader-text {
            font-size: 9px;
            color: #666;
            text-align: right;
            line-height: 1.3;
        }
        .title-section {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 15px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tablas Invisibles para Alineación de Carta */
        .invisible-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }
        .invisible-table td {
            border: none;
            padding: 4px 0;
            vertical-align: top;
        }

        /* Tablas de Contenido Físico */
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
        
        /* Bloque Informativo de Órdenes */
        .info-box {
            width: 100%;
            border: 1px solid #cccccc;
            background-color: #fafafa;
            margin-bottom: 12px;
            padding: 6px;
        }
        .info-box td {
            padding: 3px 6px;
            border: none;
        }

        /* Contenedor de Firmas */
        .signature-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .signature-table td {
            border: none;
            text-align: center;
            vertical-align: bottom;
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid #333333;
            margin-top: 45px;
            padding-top: 4px;
            font-weight: bold;
            font-size: 10px;
        }
        .img-firma {
            max-height: 55px;
            max-width: 170px;
            display: block;
            margin: 0 auto -8px auto;
        }
    </style>
</head>
<body>

    @foreach($tecnologias as $tec)
        <!-- ================================================================= -->
        <!-- SECCIÓN: CARTA DE PRESENTACIÓN (POR TECNOLOGÍA)                   -->
        <!-- ================================================================= -->
        <table class="header-table">
            <tr>
                <td>
                    <!-- Validación del Logo LONGBLOB convertido a Base64 -->
                    @if(!empty($logo_tienda))
                        <img src="{{ $logo_tienda }}" class="logo-img" alt="Logo Corporativo">
                    @else
                        <span class="logo-text">{{ $nombre_tienda }}</span>
                    @endif
                </td>
                <td class="subheader-text">
                    Imagina, crea... Evoluciona<br>
                    Instalaciones de Servicios de Telecomunicaciones<br>
                    Especialidad: <strong>{{ strtoupper($tec['nombre']) }}</strong>
                </td>
            </tr>
        </table>

        <p style="text-align: right; font-weight: bold; margin-bottom: 25px;">Quetzaltenango, {{ $fecha_reporte }}.</p>

        <p style="margin: 0; line-height: 1.5;">
            Ingeniero<br>
            <strong>Jorge Mario Fingado</strong><br>
            Región Occidente<br>
            Quetzaltenango
        </p>

        <p style="margin-top: 25px; text-align: justify; line-height: 1.6; text-indent: 30px;">
            Por medio de la presente se hace entrega del expediente en el cual se resume el trabajo
            ejecutado por la empresa <strong>{{ $nombre_tienda }}</strong> en la actividad de instalaciones de servicios
            en la rama tecnológica de <strong>{{ $tec['nombre'] }}</strong> durante el periodo <strong>{{ $periodo_mes }}</strong> en la región occidente.
        </p>

        <p style="margin-top: 15px; font-weight: bold;">El trabajo ejecutado se resume detallado de la siguiente manera:</p>
        
        <!-- Tabla Invisible calcando la alineación rígida del documento físico -->
        <table class="invisible-table">
            <tbody>
                @foreach($tec['resumenManoObra'] as $mo)
                    <tr>
                        <td style="width: 4%; text-align: right; color: #555;">•</td>
                        <td style="width: 66%; padding-left: 12px;">{{ $mo['descripcion'] }}</td>
                        <td style="width: 30%; text-align: right; font-weight: bold; padding-right: 40px;">
                            {{ $mo['cantidad'] }} Unidades
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="margin-top: 25px; text-align: justify; line-height: 1.6;">
            El contenido del expediente se adjunta para su revisión, aprobación y poder proseguir con los
            trámites de facturación correspondientes.
        </p>

        <div style="margin-top: 50px; width: 260px; text-align: center;">
            <p style="margin-bottom: 45px;">Atentamente:</p>
            <div style="border-top: 1px solid #333; padding-top: 5px; font-weight: bold; line-height: 1.3;">
                Carlos Enrique López Camposeco<br>
                <span style="font-weight: normal; color:#666; font-size: 10px;">Gerente General<br>{{ $nombre_tienda }}</span>
            </div>
        </div>

        <div class="page-break"></div>


        <!-- ================================================================= -->
        <!-- SECCIÓN: REPORTE GENERAL DE MATERIALES (POR TECNOLOGÍA)           -->
        <!-- ================================================================= -->
        <div class="title-section">Uso Consolidado de Materiales - Tecnología: {{ $tec['nombre'] }}</div>
        <p style="text-align: center; margin-top: -10px; color: #555;">REGIÓN: OCCIDENTE | PERIODO: {{ $periodo_mes }}</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%; background-color: #d1e7dd;">SKU / CÓDIGO</th>
                    <th style="width: 60%; background-color: #d1e7dd;">DESCRIPCIÓN DEL MATERIAL</th>
                    <th style="width: 20%; background-color: #d1e7dd; text-align: center;">TOTAL UTILIZADO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tec['materialesGlobales'] as $matG)
                    <tr>
                        <td><strong>{{ $matG['sku'] }}</strong></td>
                        <td>{{ $matG['descripcion'] }}</td>
                        <td style="text-align: center; font-weight: bold; background-color: #f9fffb;">{{ $matG['cantidad'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center; color: #999; padding: 15px;">No se registraron materiales físicos en esta categoría.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="page-break"></div>


        <!-- ================================================================= -->
        <!-- ================================================================= -->
        <!-- SECCIÓN: CUADRO DE COSTOS FINANCIERO (POR TECNOLOGÍA)             -->
        <!-- ================================================================= -->
        <div class="title-section" style="color: #cc0000;">Cuadro de Costos de Instalación - {{ $tec['nombre'] }}</div>
        <p style="text-align: center; margin-top: -10px; color: #555;">REGIÓN: OCCIDENTE | LIQUIDACIÓN DE MANO DE OBRA</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%; background-color: #d0e1f9; text-align: center;">No</th>
                    <th style="width: 45%; background-color: #d0e1f9;">DESCRIPCIÓN DE MANO DE OBRA</th>
                    <th style="width: 10%; background-color: #d0e1f9; text-align: center;">UNIDAD</th>
                    <th style="width: 12%; background-color: #d0e1f9; text-align: center;">CANTIDAD</th>
                    <th style="width: 13%; background-color: #d0e1f9; text-align: right;">PRECIO U.</th>
                    <th style="width: 15%; background-color: #d0e1f9; text-align: right;">TOTAL COBRO</th>
                </tr>
            </thead>
            <tbody>
                @php $numCo = 1; @endphp
                @foreach($tec['resumenManoObra'] as $cobro)
                    <tr>
                        <td style="text-align: center;">{{ $numCo++ }}</td>
                        <td>{{ $cobro['descripcion'] }}</td>
                        <td style="text-align: center;">{{ $cobro['unidad'] }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $cobro['cantidad'] }}</td>
                        <td style="text-align: right;">Q {{ number_format($cobro['precio'], 2) }}</td>
                        <td style="text-align: right; font-weight: bold;">Q {{ number_format($cobro['total'], 2) }}</td>
                    </tr>
                @endforeach
                
                <!-- Totales estructurados correctamente en filas y celdas HTML -->
                <tr>
                    <td colspan="4" style="border: none;"></td>
                    <td style="font-weight: bold; text-align: right; background-color: #fcfcfc;">TOTAL M.O.</td>
                    <td style="text-align: right; font-weight: bold; background-color: #fcfcfc;">Q {{ number_format($tec['totalManoObra'], 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4" style="border: none;"></td>
                    <td style="font-weight: bold; text-align: right; background-color: #fcfcfc;">IVA (12%)</td>
                    <td style="text-align: right; font-weight: bold; background-color: #fcfcfc;">Q {{ number_format($tec['iva'], 2) }}</td>
                </tr>
                <tr style="font-size: 11px;">
                    <td colspan="4" style="border: none;"></td>
                    <td style="font-weight: bold; text-align: right; background-color: #e6f4ea; color: #137333;">TOTAL CON IVA</td>
                    <td style="text-align: right; font-weight: bold; background-color: #e6f4ea; color: #137333;">Q {{ number_format($tec['totalConIva'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="page-break"></div>
