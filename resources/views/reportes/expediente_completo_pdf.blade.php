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
        
        /* Encabezado Principal de Tienda */
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

        /* Tablas Invisibles de Carta */
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

        /* Bloques de Firmas Inferiores */
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
            max-height: 150px; /* 🌟 Triplicado: Antes 55px */
            max-width: 320px;  /* 🌟 Expandido para firmas extendidas: Antes 170px */
            display: block;
            margin: 0 auto -5px auto;
        }

    </style>
</head>
<body>

    @foreach($tecnologias as $tec)
        <!-- ================================================================= -->
        <!-- SECCIÓN: CARTA DE PRESENTACIÓN (POR TECNOLOGÍA)                   -->
        <!-- ================================================================= -->
        <table class="header-table" style="margin-bottom: 5px; width: 100%;">
            <tr>
                <td style="width: 40%; vertical-align: middle; border: none;">
                    @if(!empty($logo_tienda))
                        <!-- Añadimos inline dimensiones estrictas para obligar a DomPDF a pintar el blob -->
                        <img src="{{ $logo_tienda }}" style="height: 55px; width: auto; display: block;" alt="Logo Corporativo">
                    @else
                        <span style="font-weight: bold; color: #0d47a1; font-size: 14px;">{{ $nombre_tienda }}</span>
                    @endif
                </td>
                <td style="text-align: right; font-weight: bold; font-size: 11px; color: #444; width: 60%; vertical-align: middle; border: none;">
                    ORDEN DE SERVICIO INDIVIDUAL ({{ strtoupper($tec['nombre']) }})
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

        <!-- ================================================================= -->
        <!-- SECCIÓN: ORDENES DE SERVICIO INDIVIDUALES LIQUIDADAS              -->
        <!-- ================================================================= -->
        @foreach($tec['expedientes'] as $exp)
            <table class="header-table" style="margin-bottom: 5px; width: 100%;">
                <tr>
                    <td style="width: 40%; vertical-align: middle; border: none;">
                        @if(!empty($logo_crudo))
                            <!-- chunk_split fragmenta el binario largo para evitar desbordamientos de búfer en DomPDF -->
                            <img src="data:image/jpeg;base64,{{ chunk_split(base64_encode($logo_crudo)) }}" style="height: 60px; width: auto; display: block;" alt="Logo Corporativo">
                        @else
                            <span style="font-weight: bold; color: #0d47a1; font-size: 14px;">{{ $nombre_tienda }}</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: bold; font-size: 11px; color: #444; width: 60%; vertical-align: middle; border: none;">
                        ORDEN DE SERVICIO INDIVIDUAL ({{ strtoupper($tec['nombre']) }})
                    </td>
                </tr>
            </table>

            <table class="info-box">
                <tr>
                    <td style="width: 15%;"><strong>NÚMERO ORDEN:</strong></td>
                    <td style="width: 35%; font-weight: bold; color: #000;">{{ $exp['Orden'] }}</td>
                    <td style="width: 15%;"><strong>CLIENTE:</strong></td>
                    <td style="width: 35%;">{{ $exp['NOMBRECLIENTE'] }}</td>
                </tr>
                <tr>
                    <td><strong>VIRTUAL:</strong></td>
                    <td>{{ $exp['virtual'] }}</td>
                    <td><strong>DIRECCIÓN:</strong></td>
                    <td>{{ $exp['DIRECCION'] }}</td>
                </tr>
                <tr>
                    <td><strong>TIPO ORDEN:</strong></td>
                    <td>{{ $exp['Tipo_orden'] ?? 'DA' }} ({{ $exp['Tipo_servicio'] }})</td>
                    <td><strong>FECHA INST:</strong></td>
                    <td>{{ $exp['FECHAINSTALACION'] }}</td>
                </tr>
                <tr>
                    <td><strong>CENTRAL / ÁREA:</strong></td>
                    <td>{{ $exp['SIGLASCENTRAL'] ?? 'N/A' }} / {{ $exp['AREA'] ?? 'N/A' }}</td>
                    <td><strong>TÉCNICO:</strong></td>
                    <td>[{{ $exp['tecnico_codigo'] }}] {{ $exp['tecnico_nombre'] }}</td>
                </tr>
            </table>

            <span style="font-weight: bold; text-transform: uppercase; font-size: 9px; color: #666; display: block; margin-top: 10px;">Materiales Utilizados en la Operación:</span>
            
            <table class="data-table" style="margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th style="width: 15%;">CÓDIGO SKU</th>
                        <th style="width: 50%;">DESCRIPCIÓN DEL MATERIAL</th>
                        <th style="width: 10%; text-align: center;">CANTIDAD</th>
                        <th style="width: 25%;">NÚMERO DE SERIE / CORRELATIVO</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exp['materiales'] as $insumo)
                        <tr>
                            <td><strong>{{ $insumo->SKU }}</strong></td>
                            <td>{{ $insumo->Descripcion }}</td>
                            <td style="text-align: center; font-weight: bold;">{{ $insumo->cantidad }}</td>
                            <td>
                                @if(!empty($insumo->serie) && strtoupper($insumo->serie) !== 'N/A' && $insumo->serie !== '0')
                                    <span style="font-family: monospace; font-weight: bold; font-size: 10.5px;">{{ $insumo->serie }}</span>
                                @else
                                    <span style="color: #777; font-size: 8.5px;">MISCELÁNEO / ACUMULADO</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: #888; padding: 10px;">No se registraron consumos físicos en este expediente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <p style="font-size: 8.5px; text-align: justify; font-style: italic; color: #555; line-height: 1.3;">
                NOTA: HAGO CONSTAR QUE EL DÍA DE HOY SE INSTALÓ EN MI DOMICILIO EL SERVICIO ESPECIFICADO DE MANERA CORRECTA, QUEDANDO TOTALMENTE SATISFECHO CON LOS MATERIALES Y EL TRABAJO REALIZADO POR EL PERSONAL TÉCNICO AUTORIZADO.
            </p>

            <table class="signature-table">
                <tr>
                    <td>
                        <div style="height: 140px;"></div>
                        <div class="signature-line">Firma del Técnico</div>
                        <div style="font-size: 9px; color: #666; margin-top: 2px;">{{ $exp['tecnico_nombre'] }}</div>
                    </td>
                    <td style="width: 10%;"></td>
                    <td>
                        @if($exp['firma_base64'])
                            <img src="{{ $exp['firma_base64'] }}" class="img-firma" alt="Firma Cliente">
                        @else
                            <div style="height: 140px; color: #bbb; font-size: 9px; padding-top: 60px; font-weight: bold;">FIRMA NO DIGITALIZADA</div>
                        @endif
                        <div class="signature-line">Firma y Aceptación del Cliente</div>
                        <div style="font-size: 9px; color: #666; margin-top: 2px;">{{ $exp['NOMBRECLIENTE'] }}</div>
                    </td>
                </tr>
            </table>

            @if(!$loop->last || !$loop->parent->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    @endforeach

</body>
</html>
