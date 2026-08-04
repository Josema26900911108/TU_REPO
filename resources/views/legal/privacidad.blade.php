<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - CODE ERP</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet" />
    <style>
        body { background-color: #f8fafc; color: #1e293b; font-family: sans-serif; }
        .legal-container { max-width: 800px; margin: 40px auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        h1 { color: #0f172a; font-weight: 800; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        h2 { color: #2563eb; font-size: 1.3rem; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="legal-container">
        <h1>Política de Privacidad</h1>
        <p class="text-muted">Última actualización: {{ date('d/m/Y') }}</p>

        <p><strong>CODE ERP</strong> se compromete a proteger la privacidad y los datos de carácter confidencial de las empresas y usuarios que utilizan nuestra plataforma.</p>

        <h2>1. Marco Legal Aplicable</h2>
        <p>Esta política se rige en estricto cumplimiento de la Constitución Política de la República de Guatemala (Garantía de Habeas Data), los preceptos de información confidencial regulados en el <strong>Decreto 57-2008 del Congreso de la República (Ley de Acceso a la Información Pública)</strong>, y adopta estándares internacionales de transparencia inspirados en el Reglamento General de Protección de Datos (GDPR).</p>

        <h2>2. Información Recopilada</h2>
        <p>Procesamos información para el correcto funcionamiento del ERP, incluyendo de manera enunciativa más no limitativa: Nombres, correos electrónicos corporativos, contraseñas encriptadas, datos de sucursales (tiendas), registros transaccionales y direcciones IP por motivos de auditoría y seguridad.</p>

        <h2>3. Derechos del Usuario (Derechos ARCO)</h2>
        <p>De conformidad con los estándares internacionales, todo usuario tiene derecho a:</p>
        <ul>
            <li><strong>Acceso:</strong> Conocer qué datos personales tenemos almacenados en el sistema.</li>
            <li><strong>Rectificación:</strong> Solicitar la corrección de datos inexactos o incompletos.</li>
            <li><strong>Cancelación/Eliminación:</strong> Solicitar el borrado de sus accesos cuando finalice su relación comercial.</li>
        </ul>

        <h2>4. Seguridad de los Datos</h2>
        <p>Implementamos medidas de seguridad técnicas y administrativas (como cifrado de contraseñas mediante hashing y uso de protocolos seguros de transferencia HTTPS) para prevenir accesos no autorizados o fugas de información.</p>
        
        <hr class="my-4">
        <div class="text-center">
            <button onclick="window.close()" class="btn btn-secondary">Cerrar Ventana</button>
        </div>
    </div>
</body>
</html>
