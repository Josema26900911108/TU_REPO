<?php

use App\Http\Controllers\AbrmanoobraController;
use App\Http\Controllers\ArbolMaterialesController;
use App\Http\Controllers\materialmanoobraController;
use App\Http\Controllers\TecnicoController;
use Illuminate\Http\Request;
use App\Http\Controllers\ArqueoCajaController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\CajaRegistradoraController;
use App\Http\Controllers\categoriaController;
use App\Http\Controllers\clienteController;
use App\Http\Controllers\compraController;
use App\Http\Controllers\homeController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\logoutController;
use App\Http\Controllers\marcaController;
use App\Http\Controllers\presentacioneController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\profileController;
use App\Http\Controllers\proveedorController;
use App\Http\Controllers\roleController;
use App\Http\Controllers\userController;
use App\Http\Controllers\ventaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\comprobantesController;
use App\Http\Controllers\CuentaContableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\detallecomprobanteController;
use App\Http\Controllers\documentosapController;
use App\Http\Controllers\etadirectController;
use App\Http\Controllers\generarPDF;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\movimientomaterialesController;
use App\Http\Controllers\pagotecnicoController;
use App\Http\Controllers\permisoController;
use App\Http\Controllers\tiendaController;
use App\Http\Controllers\treematerialescategoriaController;
use App\Http\Controllers\usuariotiendaController;
use App\Models\ArqueoCaja;
use App\Models\Compra;
use App\Models\Comprobante;
use App\Models\CuentaContable;
use App\Models\Cliente;
use App\Models\MovimientoMaterial;
use App\Models\Persona;
use App\Models\Tecnico;
use App\Models\Tienda;
use Barryvdh\DomPDF\Facade as PDF;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/',[homeController::class,'index'])->name('panel');

Route::resources([
    'categorias' => categoriaController::class,
    'presentaciones' => presentacioneController::class,
    'marcas' => marcaController::class,
    'productos' => ProductoController::class,
    'clientes' => clienteController::class,
    'proveedores' => proveedorController::class,
    'compras' => compraController::class,
    'ventas' => ventaController::class,
    'users' => userController::class,
    'roles' => roleController::class,
    'profile' => profileController::class,
    'cash' => CashRegisterController::class,
    'permiso' => permisoController::class,
    'cajaregistradora' => CajaRegistradoraController::class,
    'tienda' => tiendaController::class,
    'userstore' => usuariotiendaController::class,
    'comprobante'=>comprobantesController::class,
    'cuentas'=>CuentaContableController::class,
    'detallecomprobante'=>detallecomprobanteController::class,
    'arqueocaja'=>ArqueoCajaController::class,
    'movimientos'=>MovimientoMaterial::class,
    'tecnico'=>TecnicoController::class,
    'manoobramaterial'=>materialmanoobraController::class,
    'etadirect'=>etadirectController::class,
    'arbolmateriales'=>ArbolMaterialesController::class,
    'abrmanoobra'=>AbrmanoobraController::class,
    'treematerialescategoria'=>TreematerialescategoriaController::class
]);

Route::get('/arqueocaja/show/{arqueocaja}', [ArqueoCajaController::class, 'show'])->name('arqueocaja.show');
Route::get('/arqueocaja/panel/{arqueocaja}', [ArqueoCajaController::class, 'panel'])->name('arqueocaja.panel');
Route::get('/obtener-datos', [ArqueoCajaController::class, 'obtenerDatos']);
Route::get('/arqueocaja/compras/{arqueocaja}', [ArqueoCajaController::class, 'compras'])->name('arqueocaja.compras');
Route::get('/arqueocaja/ventas/{ventas}', [ArqueoCajaController::class, 'ventas'])->name('arqueocaja.ventas');

Route::get('/arqueocaja/cobrarventas/{ventas}', [ArqueoCajaController::class, 'cobrarventas'])->name('arqueocaja.cobrarventas');


Route::get('/login',[loginController::class,'index'])->name('login');
Route::post('/login',[loginController::class,'login']);
Route::get('/logout',[logoutController::class,'logout'])->name('logout');
Route::get('/cash/open', [CashRegisterController::class, 'showOpenForm'])->name('cash.open');
Route::post('/cash/open', [CashRegisterController::class, 'open'])->name('cash.open.submit');
Route::get('/cajaregistradora', [CajaRegistradoraController::class, 'index'])->name('cajaregistradora.index');
Route::post('/cajaregistradora/open', [CajaRegistradoraController::class, 'open'])->name('cajaregistradora.open.submit');
Route::resource('cash', CashRegisterController::class);
Route::post('/arqueocaja/store/{arqueocaja}', [ArqueoCajaController::class, 'store'])->name('arqueocaja.store');

Route::post('/venta/storeCC', [ventaController::class, 'storeCC'])->name('ventas.storeCC');
Route::get('/ventas/cobrarventas/{ventas}', [ventaController::class, 'cobrarventas'])->name('ventas.cobrarventas');



Route::get('export/ventas', [VentaController::class, 'exportVentas']);
//Route::get('ventas/reporte', [VentaController::class, 'ventasReporte'])->name('ventas.ventasreporte');
//Reportes
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])
    ->name('dashboard.export.excel');

Route::get('/venta/ventasreporte', [VentaController::class, 'ventasReporte'])
    ->name('ventas.ventasreporte');

    Route::get('/compra/comprasreporte', [compraController::class, 'comprasReporte'])
    ->name('compra.comprareporte');

Route::get('/dashboardcompra/exportcompra-excel', [DashboardController::class, 'exportcompraExcel'])
    ->name('dashboardcompra.export.excel');
//Reporteria
        Route::get('/reporte/diario', [generarPDF::class, 'diarioindex'])
    ->name('reporte.diario');
            Route::get('/reporte/mayor', [generarPDF::class, 'mayorindex'])
    ->name('reporte.mayor');
            Route::get('/reporte/balance', [generarPDF::class, 'balanceindex'])
    ->name('reporte.balance');
                Route::get('/reporte/puntoequilibrio', [generarPDF::class, 'puntodeequilibrioindex'])
    ->name('reporte.puntodeequilibrio');
                Route::get('/reporte/utilidades', [generarPDF::class, 'utilidadesindex'])
    ->name('reporte.utilidades');
                Route::get('/reporte/flujoefectivo', [generarPDF::class, 'flujoefectivoindex'])
    ->name('reporte.flujoefectivo');



    //carga masiva
    Route::get('/cargamasiva/compra', [compraController::class, 'cargamasiva'])
    ->name('carga.masiva.compra');
    // Procesar ZIP
Route::post('productos/importar', [compraController::class, 'storeMasivo'])
    ->name('productos.importar.procesar');

// Descargar plantilla Excel
Route::get('productos/importar/plantilla', [compraController::class, 'descargarPlantilla'])
    ->name('productos.importar.plantilla');

    //Diario
    Route::get('/reporte/diario-excel', [generarPDF::class, 'exportdiarioExcel'])
    ->name('diario.export.excel');

    Route::get('/reporte/diariochart', [generarPDF::class, 'diarioindex'])
    ->name('diario.reporte');

    Route::get('/reporte/diario-pdf', [generarPDF::class, 'generarDiario'])
    ->name('diario.export.pdf');
//Mayor
        Route::get('/reporte/mayor-excel', [generarPDF::class, 'exportmayorExcel'])
    ->name('mayor.export.excel');

    Route::get('/reporte/mayor-pdf', [generarPDF::class, 'generarMayor'])
    ->name('mayor.export.pdf');
    Route::get('/reporte/mayorchart', [generarPDF::class, 'mayorindex'])
    ->name('mayor.reporte');

    //Balance
                Route::get('/reporte/balance-excel', [generarPDF::class, 'exportbalanceExcel'])
    ->name('balance.export.excel');

    Route::get('/reporte/balance-pdf', [generarPDF::class, 'generarBalance'])
    ->name('balance.export.pdf');
    Route::get('/reporte/balancechart', [generarPDF::class, 'balanceindex'])
    ->name('balance.reporte');

    //Kardex Inventario
            Route::get('/reporte/kardexinventario', [generarPDF::class, 'KardexInvenarioindex'])
    ->name('reporte.kardeinventario');

    Route::get('export/Kardexinventario', [generarPDF::class, 'exportKardexExcel'])
    ->name('export.reporte.kardeinventario');

        Route::get('/reporte/kardexinventario-pdf', [generarPDF::class, 'generarKardex'])
    ->name('kardexinv.export.pdf');


Route::get('/php-gd-check', function () {
    return extension_loaded('gd') ? 'GD cargado' : 'GD no está cargado';
});



Route::get('/php-version-check', function () {
    return [
        'version' => PHP_VERSION,
        'ini_file' => php_ini_loaded_file(),
        'gd' => extension_loaded('gd') ? 'GD cargado' : 'GD no está cargado',
    ];
});


Route::post('/arqueocaja/CierreCaja/{arqueocaja}', [ArqueoCajaController::class, 'CierreCaja'])->name('arqueocaja.cierre');
Route::delete('/cash/{cash}', [CashRegisterController::class, 'destroy'])->name('cash.destroy');
// routes/web.php
// web.php

Route::delete('arqueocaja/{arqueocaja}', [ArqueoCajaController::class, 'destroy'])->name('arqueocaja.destroy');

Route::post('arqueocaja/venta/{arqueocaja}', [ArqueoCajaController::class, 'generarRecibo'])
->name('arqueocaja.vistapreviapdfventa');

Route::post('arqueocaja/compra/{arqueocaja}', [compraController::class, 'generarRecibo'])
->name('arqueocaja.vistapreviapdfcompra');



Route::post('/clientes', [clienteController::class, 'store'])
    ->name('clientes.store')

    ->middleware('permission:crear-cliente');  // Mantener la restricción de permisos

Route::post('/clientes/existente', [clienteController::class, 'exist'])
    ->name('clientes.storexist')

    ->middleware('permission:crear-cliente');  // Mantener la restricción de permisos


Route::get('/compras/detalles/{idComprobante}', [compraController::class, 'mostrarDetalles']);
Route::get('/compras/detallesSCAN/{SKU}', [compraController::class, 'mostrarDetallesScanner']);
Route::post('/compras/store', [compraController::class, 'store'])->middleware('web')->name('compras.store');

Route::get('/metodopago/detalle/{id}', [MetodoPagoController::class, 'detalle']);
Route::post('/metodopago/detalle/{id}', [MetodoPagoController::class, 'detalle']);

Route::get('/comprobantes/{id}/detalles', [ComprobantesController::class, 'getDetalles']);

Route::delete('destroy/{comprobante}', [comprobantesController::class, 'destroy'])->name('comprobante.destroy');


Route::get('/create/{comprobante}', [detallecomprobanteController::class, 'create'])->name('detallecomprobante.create');
Route::get('/edit/{comprobante}', [detallecomprobanteController::class, 'edit'])->name('detallecomprobante.edit');
Route::post('/detallecomprobante/detalles',[detallecomprobanteController::class,'obtenerdetalles'])->name('detallecomprobante.obtener');

Route::post('/cuentas/agregar', [CuentaContableController::class, 'agregar']);
Route::get('/cuentas/obtener/{id}', [CuentaContableController::class, 'obtener']);
Route::put('/cuentas/editar/{id}', [CuentaContableController::class, 'editar']);
//Route::delete('/cuetas/eliminar/{id}', [CuentaContableController::class, 'delete'])->name('cuentas.eliminar');
Route::post('/cuentas/delete', [CuentaContableController::class, 'delete'])
    ->name('cuentas.delete');
Route::post('/cuentas/agregar', [CuentaContableController::class, 'store']);
Route::get('/cuentas/fetch', [CuentaContableController::class, 'fetch'])->name('cuentas.fetch');
Route::get('/crear-cuentas-raiz', [CuentaContableController::class, 'createRootCuentasIfNotExist']);
Route::get('/verificar-raiz-tienda/{tiendaId}', [CuentaContableController::class, 'checkRootForTienda']);
Route::get('/padres', [CuentaContableController::class, 'fillParentCategory'])->name('padres');
Route::get('/fetch', [CuentaContableController::class, 'fetch'])->name('fetch');
Route::get('/fetch2', [CuentaContableController::class, 'fetch2'])->name('fetch2');
Route::post('/cuentas/add', [CuentaContableController::class, 'add'])->name('cuentas.add');
Route::post('/cuentas/delete', [CuentaContableController::class, 'delete'])->name('cuentas.delete');
Route::post('/cuentas/generarNumeroCuenta', [CuentaContableController::class, 'generarNumeroCuenta'])->name('cuentas.generarNumeroCuenta');
Route::post('/update', [CuentaContableController::class, 'update'])->name('update');

route::get('/etadirect',[etadirectController::class,'index'])->name('etadirect.lista');
Route::get('/etadirect-formatoEta',[etadirectController::class,'descargarFormeta'])->name('etadirect.formeta');
Route::post('/etadirect-JoboCommand',[etadirectController::class,'JoboCommand'])->name('etadirect.JoboCommand');
Route::post('/etadirect/importar',[etadirectController::class,'importarMAMO'])->name('etadirect.importar');
Route::post('/exportar-etadirect', [etadirectController::class, 'exportar'])->name('etadirect.exportar');

Route::get('/abrmateriales', [ArbolMaterialesController::class, 'fetch2'])->name('fetchabrmateriales');
Route::get('/abrmatpadres', [ArbolMaterialesController::class, 'fillParentCategory'])->name('abrpadres');
Route::get('/abrmatcategoria', [ArbolMaterialesController::class, 'fillEstructura'])->name('abrcategoria');
Route::post('/abrmat/add', [ArbolMaterialesController::class, 'add'])->name('arbolmateriales.add');
Route::post('/abrmatupdate', [ArbolMaterialesController::class, 'update'])->name('arbolmateriales.update');
Route::post('/abrmateriales/generarabrmat', [ArbolMaterialesController::class, 'generarNumeroCuenta'])->name('arbolmateriales.generarNumeroCuenta');
Route::post('/abrmateriales/delete', [ArbolMaterialesController::class, 'delete'])->name('arbolmateriales.delete');

Route::get('/arbmanoobra', [AbrmanoobraController::class, 'fetch2'])->name('fetchabrmomat');
Route::get('/arbmatmopadres', [AbrmanoobraController::class, 'fillParentCategory'])->name('arbpadres');
Route::post('/arbmanoobramatmo/add', [AbrmanoobraController::class, 'add'])->name('abrmanoobra.add');
Route::post('/arbmanoobramatmoupdate', [AbrmanoobraController::class, 'update'])->name('abrmanoobra.update');
Route::post('/arbamtmo/generarabrmatmo', [AbrmanoobraController::class, 'generarNumeroCuenta'])->name('abrmanoobra.generarNumeroCuenta');
Route::post('/arbmatmo/delete', [AbrmanoobraController::class, 'delete'])->name('abrmanoobra.delete');

Route::get('/tree', [TreematerialescategoriaController::class, 'fetch2'])->name('fetchtree');
Route::post('/treerelacion', [TreematerialescategoriaController::class, 'fetchrelacion'])->name('treerelacion');



Route::get('/treepadres', [TreematerialescategoriaController::class, 'fillParentCategory'])->name('treepadres');
Route::get('/treecategoria', [TreematerialescategoriaController::class, 'fillEstructura'])->name('treecategoria');
Route::POST('/treecategoriaELIMINAR', [TreematerialescategoriaController::class, 'eliminarvalidacion'])->name('treecategoriaeliminar');
Route::post('/tree/add', [TreematerialescategoriaController::class, 'add'])->name('treematerialescategoria.add');
Route::post('/treeupdate', [TreematerialescategoriaController::class, 'update'])->name('treematerialescategoria.update');
Route::post('/tree/generartree', [TreematerialescategoriaController::class, 'generarNumeroCuenta'])->name('treematerialescategoria.generarNumeroCuenta');
Route::post('/tree/delete', [TreematerialescategoriaController::class, 'delete'])->name('treematerialescategoria.delete');
Route::post('/tree/importar',[TreematerialescategoriaController::class,'importarMAMO'])->name('treematerialescategoria.importar');
Route::post('/tree/importarhijos',[TreematerialescategoriaController::class,'importarmasivohijos'])->name('treematerialescategoria.importarhijos');
Route::post('/tree/importarpadreshijos',[TreematerialescategoriaController::class,'importarmasivohijospadres'])->name('treematerialescategoria.importarhijospadres');
Route::post('/tree/importargeneral',[TreematerialescategoriaController::class,'importarmasivorelaciones'])->name('treematerialescategoria.importargeneralrealciones');
Route::post('/treematerialescategoria/move', [TreematerialescategoriaController::class, 'moveNode'])->name('treematerialescategoria.move');

Route::get('/tree-formatoEta',[TreematerialescategoriaController::class,'descargarFormeta'])->name('treematerialescategoria.formeta');
Route::get('/tree-formatoHijo',[TreematerialescategoriaController::class,'descargarFormHijos'])->name('treematerialescategoria.formetahijos');
Route::get('/tree-formatoHijoPadres',[TreematerialescategoriaController::class,'descargarFormHijosPadres'])->name('treematerialescategoria.formetahijospadres');
Route::get('/tree-formatoRelaciones',[TreematerialescategoriaController::class,'descargarFormMasivoRelaciones'])->name('treematerialescategoria.formetarelaciones');

route::get('/materialmanoobra/lista',[materialmanoobraController::class,'index'])->name('manoobramaterial.lista');
Route::get('/materialmanoobra/descargarformato',[materialmanoobraController::class,'descargarFormato'])->name('manoobramaterial.formato');
Route::post('/materialmanoobra/importar',[materialmanoobraController::class,'importarMAMO'])->name('manoobramaterial.importar');

Route::get('/pagotecnico/lista', [PagotecnicoController::class, 'index'])->name('pagotecnico.lista');

Route::get('/documentosap/lista',[documentosapController::class,'index'])->name('documentosap.lista');

Route::get('/movimientomateriales/lista',[movimientomaterialesController::class,'index'])->name('movimientomateriales.lista');


Route::get('/tecnico',[TecnicoController::class,'index'])->name('tecnico.lista');
Route::post('/tecnico/crear',[TecnicoController::class,'store'])->name('tecnico.store');
Route::post('/tecnico/editar',[TecnicoController::class,'exist'])->name('tecnico.exist');
Route::post('/tecnico/exist',[TecnicoController::class,'exist'])->name('tecnico.storexist');
Route::get('/tecnico/{user}/edit',[TecnicoController::class,'edit'])->name('tecnico.edit');
Route::get('buckettecnicoconstruccion/{tecbucket}', [TecnicoController::class,'inventariotecnicoorden'])
    ->name('tecnico.inventario');

Route::get('/verbtecnico/{usbucket}/ver-bucket',[TecnicoController::class,'bucket'])->name('tecnico.bucket');
Route::get('/buckettecnicos',[TecnicoController::class,'bucketlista'])->name('tecnico.buckettecnico');
Route::patch('tecnico/{tecnico}/operartrabajo/{expediente}', [TecnicoController::class, 'operarTrabajo'])
    ->name('tecnico.operartrabajo');
Route::get('/pctecnico/{user}/pago-cobro',[TecnicoController::class,'pagocobro'])->name('tecnico.pagocobro');
Route::get('/vptecnico/{user}/ver-produccion',[TecnicoController::class,'produccion'])->name('tecnico.produccion');
Route::get('/crear/tecnico',[TecnicoController::class,'create'])->name('tecnico.create');
Route::get('/tecnico-formatoExpediente',[TecnicoController::class,'descargarFormeta'])->name('tecnico.formexpediente');
Route::get('/tecnico-formatoinventario',[TecnicoController::class,'descargarinventario'])->name('tecnico.forminventario');
Route::post('/tecnicoim/importar',[TecnicoController::class,'importarMAMO'])->name('tecnico.importar');
Route::post('/tecnicoinv/importar',[TecnicoController::class,'importarInvTecnico'])->name('tecnico.invimportar');
Route::post('/exportar-tecnicoordenes', [TecnicoController::class, 'exportar'])->name('tecnico.exportar');
Route::get('/tecnicoTtabla', [TecnicoController::class, 'fetchrelacionTecnico'])->name('fetchtablaT');


Route::get('/tecnicoTtabla', [etadirectController::class, 'fetchrelacionEta'])->name('fetchrelacionEta');


Route::get('/tecnicotabla', [TecnicoController::class, 'fetchrelacion'])->name('fetchtabla');
Route::get('/tecnicotablaS', [TecnicoController::class, 'fetchrelacionS'])->name('fetchtablaS');
Route::get('/tecnicotablaP', [TecnicoController::class, 'fetchrelacionP'])->name('fetchtablaP');
Route::get('/tecnicoinvtabla', [TecnicoController::class, 'fetchrelacioninv'])->name('fetchinvtabla');
Route::get('/tecnologiacategoria', [TecnicoController::class, 'fillEstructura'])->name('tecnologiaarb');
Route::get('/manoobracategoria/{id}', [TecnicoController::class, 'fillEstructuraMO'])->name('manoobrarb');
Route::get('/inventariolista', [TecnicoController::class, 'InventarioLista'])->name('inventariolista');

Route::get('/inventariolistadetalle', [TecnicoController::class, 'obtenerdetalless'])->name('inventariolistadetalles');


Route::get('/abrinventariotecnico', [TecnicoController::class, 'fetch2'])->name('fetchabrestructura');

// routes/web.php
Route::get('/tienda/facturas/{tienda}/edit', [tiendaController::class, 'editfactura'])
    ->name('tienda.editfactura');

    Route::post('/subir-imaen', [tiendaController::class, 'editfacturaplantilla'])
    ->name('subir.imagen');

Route::get('/mostrarconsulta/{plantilla}/plantilla', [tiendaController::class, 'ejecutarConsultaConMetadata'])->name('plantilla.consulta');
Route::post('/plantilla/consulta', [tiendaController::class, 'ejecutarConsultaConMetadata'])->name('plantilla.consulta');
Route::post('/plantilla/PDF', [tiendaController::class, 'PDF'])->name('plantilla.PDF');

//Route::get('/clientes/lista', [clienteController::class, 'lista'])->name('lista');
//Route::get('/clientes/lista', [compraController::class, 'lista'])->middleware('web')->name('clientes.lista');

Route::get('clientes/lista', [clienteController::class, 'listaClientes'])
    ->name('clientes.lista')
    ->withoutMiddleware(['auth']);

//Route::get('/clientes/obtener', [clienteController::class, 'obtenerClientes'])
  //  ->name('clientes.obtener')
//    ->withoutMiddleware(['auth']);

    Route::get('clientes/obtener', [clienteController::class, 'obtenerClientes'])
    ->name('clientes.obtener')
    ->withoutMiddleware(['auth', VerifyCsrfToken::class]);

    Route::middleware(['auth'])->group(function () {
        Route::get('/panel', [homeController::class, 'index'])->name('panel.index');
    });

    Route::post('/plantilla/obtenerplantillas',[tiendaController::class,'obtenerplantillas'])->name('plantilla.plantillas');
    Route::post('/obtener/plantilla', [tiendaController::class, 'obtenerplantillaselect'])->name('plantilla.selectplantilla');
    Route::post('/obtener/TiendaPlantilla', [tiendaController::class, 'obtenerplantillaselectTienda'])->name('plantilla.selectplantillaTienda');

    /*Route::get('/test-clientes', function() {
        $clientes = Cliente::with('persona')->get();

        return response()->json($clientes);
    })->name('client.obtener');*/

    Route::get('/test-clientes', function(Request $request) {
        // Obtener el término de búsqueda desde la URL
        $searchTerm = $request->get('search', '');

        // Filtrar los clientes según el término de búsqueda
        $clientes = Cliente::with('persona')

            ->whereHas('persona', function ($query) use ($searchTerm) {
                $query->where('estado', '<>', 0)
                ->where(function ($q) use ($searchTerm) {
                    $q->where('razon_social', 'like', "%{$searchTerm}%")
                      ->orWhere('numero_documento', 'like', "%{$searchTerm}%");
                });

            })
            ->get();

        // Retornar los resultados como JSON
        return response()->json($clientes);
    })->name('client.obtener');


    Route::get('/test-clientesfill', function(Request $request) {
        // Obtener el término de búsqueda desde la URL
        $searchTerm = $request->get('search', '');
        // Filtrar los clientes según el término de búsqueda
        $persona = Persona::with('cliente')
        ->whereDoesntHave('cliente', function ($query) {
            $query->where('estado', '<>', 0);
        })
        ->get();


        // Retornar los resultados como JSON
        return response()->json($persona);
    })->name('client.obtenerfill');

Route::resource('userstore', usuariotiendaController::class);

Route::post('/tiendas', [userController::class, 'getTiendasByEmail'])->middleware('web');


Route::get('/401', function () {
    return view('pages.401');
});
Route::get('/404', function () {
    return view('pages.404');
});
Route::get('/500', function () {
    return view('pages.500');
});
