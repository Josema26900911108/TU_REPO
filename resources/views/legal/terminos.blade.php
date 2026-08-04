<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones - CODE ERP</title>
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
        <h1>Términos y Condiciones de Uso</h1>
        <p class="text-muted">Última actualización: {{ date('d/m/Y') }}</p>

        <h2>1. Aceptación del Contrato</h2>
        <p>El ingreso y uso del sistema <strong>CODE ERP</strong> constituye la aceptación expresa de estos Términos y Condiciones por parte del usuario y la empresa que representa, conforme al <strong>Decreto 47-2008 de Guatemala (Ley de Reconocimiento de Comunicaciones y Firmas Electrónicas)</strong>.</p>

        <h2>2. Propiedad Intelectual</h2>
        <p>Todo el código fuente, la estructura de base de datos, los logotipos, las interfaces gráficas y las marcas asociadas a CODE ERP son propiedad exclusiva de los desarrolladores o de la entidad licenciante, protegidos por la Ley de Derecho de Autor y Derechos Conexos de Guatemala (Decreto 33-98) y convenios internacionales de propiedad intelectual.</p>

        <h2>3. Responsabilidad del Uso de Credenciales</h2>
        <p>El usuario es el único responsable de la custodia de sus credenciales de acceso (correo y contraseña). CODE ERP no se hace responsable por transacciones, alteraciones de inventario o fugas de información derivadas de la negligencia en el resguardo de las contraseñas o el préstamo de cuentas a terceros.</p>

        <h2>4. Limitación de Responsabilidad</h2>
        <p>El sistema se provee "tal cual está". No garantizamos que el software sea ininterrumpido o libre de errores en situaciones fortuitas ajenas a nuestro control, tales como caídas del proveedor de infraestructura en la nube (Google Cloud), cortes de energía locales o fallas en el proveedor de internet del cliente.</p>

        <h2>5. Jurisdicción</h2>
        <p>Para cualquier controversia derivada de la interpretación o cumplimiento de este acuerdo, las partes se someten expresamente a las leyes de la República de Guatemala y a la competencia de los tribunales de la Ciudad de Guatemala.</p>

        <hr class="my-4">
        <div class="text-center">
            <button onclick="window.close()" class="btn btn-secondary">Cerrar Ventana</button>
        </div>
    </div>
</body>
</html>
