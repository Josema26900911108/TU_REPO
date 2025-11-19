<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <div class="sb-sidenav-menu-heading">Inicio</div>
                <a class="nav-link" href="{{ route('panel') }}">
                    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                    Panel
                </a>

                <!---div class="sb-sidenav-menu-heading">Interface</div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                    Layouts
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link" href="layout-static.html">Static Navigation</a>
                        <a class="nav-link" href="layout-sidenav-light.html">Light Sidenav</a>
                    </nav>
                </div>
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                    <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                    Pages
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionPages">
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseAuth" aria-expanded="false" aria-controls="pagesCollapseAuth">
                            Authentication
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="pagesCollapseAuth" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="login.html">Login</a>
                                <a class="nav-link" href="register.html">Register</a>
                                <a class="nav-link" href="password.html">Forgot Password</a>
                            </nav>
                        </div>
                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseError" aria-expanded="false" aria-controls="pagesCollapseError">
                            Error
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="pagesCollapseError" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="401.html">401 Page</a>
                                <a class="nav-link" href="404.html">404 Page</a>
                                <a class="nav-link" href="500.html">500 Page</a>
                            </nav>
                        </div>
                    </nav>
                </div--->

                <div class="sb-sidenav-menu-heading">Modulos</div>

                <!----Compras---->
                @can('ver-compra')
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCompras" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-store"></i></div>
                    Compras
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseCompras" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        @can('ver-compra')
                        <a class="nav-link" href="{{ route('compras.index') }}">Ver</a>
                        @endcan
                        @can('crear-compra')
                        <a class="nav-link" href="{{ route('compras.create') }}">Crear</a>
                        @endcan
                    </nav>
                </div>
                @endcan

                <!----Ventas---->
                @can('ver-venta')
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseVentas" aria-expanded="false" aria-controls="collapseLayouts">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                    Ventas
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse" id="collapseVentas" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        @can('ver-venta')
                        <a class="nav-link" href="{{ route('ventas.index') }}">Ver</a>
                        @endcan
                        @can('crear-compra')
                        <a class="nav-link" href="{{ route('ventas.create') }}">Crear</a>
                        @endcan
                    </nav>
                </div>
                @endcan

                @can('ver-categoria')
                <a class="nav-link" href="{{ route('categorias.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-tag"></i></div>
                    Categorías
                </a>
                @endcan

                @can('ver-presentacione')
                <a class="nav-link" href="{{ route('presentaciones.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-box-archive"></i></div>
                    Presentaciones
                </a>
                @endcan

                @can('ver-marca')
                <a class="nav-link" href="{{ route('marcas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    Marcas
                </a>
                @endcan

                @can('ver-producto')
                <a class="nav-link" href="{{ route('productos.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-brands fa-shopify"></i></div>
                    Productos
                </a>
                @endcan

                @can('ver-caja')
                <a class="nav-link" href="{{ route('cash.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-cash-register">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="rotate(0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="2.496">
                        <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        </path>
                    </g>
                </svg>
                    </i></div>
                    Caja
                </a>
                @endcan

                @can('ver-usuariotienda')
                <a class="nav-link" href="{{ route('userstore.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-shop-lock">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="rotate(0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="2.496">
                        <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        </path>
                    </g>
                </svg>
                    </i></div>
                    Usuarios Tienda
                </a>
                @endcan

                @can('ver-tienda')
                <a class="nav-link" href="{{ route('tienda.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-store">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" transform="rotate(0)"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="2.496">
                        <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g><g id="SVGRepo_iconCarrier"> <path d="M21 18L20.1703 11.7771C20.0391 10.7932 19.9735 10.3012 19.7392 9.93082C19.5327 9.60444 19.2362 9.34481 18.8854 9.1833C18.4873 9 17.991 9 16.9983 9H7.00165C6.00904 9 5.51274 9 5.11461 9.1833C4.76381 9.34481 4.46727 9.60444 4.26081 9.93082C4.0265 10.3012 3.96091 10.7932 3.82972 11.7771L3 18M21 18H3M21 18V19.4C21 19.9601 21 20.2401 20.891 20.454C20.7951 20.6422 20.6422 20.7951 20.454 20.891C20.2401 21 19.9601 21 19.4 21H4.6C4.03995 21 3.75992 21 3.54601 20.891C3.35785 20.7951 3.20487 20.6422 3.10899 20.454C3 20.2401 3 19.9601 3 19.4V18M7.5 12V12.01M10.5 12V12.01M9 15V15.01M12 15V15.01M15 15V15.01M13.5 12V12.01M16.5 12V12.01M9 9V6M5.8 6H12.2C12.48 6 12.62 6 12.727 5.9455C12.8211 5.89757 12.8976 5.82108 12.9455 5.727C13 5.62004 13 5.48003 13 5.2V3.8C13 3.51997 13 3.37996 12.9455 3.273C12.8976 3.17892 12.8211 3.10243 12.727 3.0545C12.62 3 12.48 3 12.2 3H5.8C5.51997 3 5.37996 3 5.273 3.0545C5.17892 3.10243 5.10243 3.17892 5.0545 3.273C5 3.37996 5 3.51997 5 3.8V5.2C5 5.48003 5 5.62004 5.0545 5.727C5.10243 5.82108 5.17892 5.89757 5.273 5.9455C5.37996 6 5.51997 6 5.8 6Z" stroke="#1323a0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        </path>
                    </g>
                </svg>
                    </i></div>
                    Tienda
                </a>
                @endcan

                @can('ver-cliente')
                <a class="nav-link" href="{{ route('clientes.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-users"></i></div>
                    Clientes
                </a>
                @endcan

                                @can('ver-materialmanoobra')
                <a class="nav-link" href="{{ route('manoobramaterial.lista') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-cubes"></i></div>
                    Material/Mano de Obra
                </a>
                @endcan

                @can('ver-eta')
                <a class="nav-link" href="{{ route('etadirect.lista') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-route"></i></div>
                    Eta
                </a>
                @endcan

                @can('ver-tecnico')
                <a class="nav-link" href="{{ route('pagotecnico.lista') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-credit-card"></i></div>
                    Pagos Tecnicos
                </a>
                @endcan

                @can('ver-documentosap')
                <a class="nav-link" href="{{ route('documentosap.lista') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-file-text"></i></div>
                    Cargar Documentos Sap
                </a>
                @endcan

                @can('ver-movimientomateriales')
                <a class="nav-link" href="{{ route('movimientomateriales.lista') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-dolly"></i></div>
                    Movimiento Material
                </a>
                @endcan

                @can('ver-arbolmaterial')
                <a class="nav-link" href="{{ route('arbolmateriales.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-sort-amount-desc"></i></div>
                    Estructura Expedientes Pago
                </a>
                @endcan

                @can('ver-arbrmanoobra')
                <a class="nav-link" href="{{ route('abrmanoobra.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-people-carry"></i></div>
                    Arbol Materiales y Mano de Obra
                </a>
                @endcan

                @can('ver-treematerialescategoria')
                <a class="nav-link" href="{{ route('treematerialescategoria.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-project-diagram"></i></div>
                    Arbol condicion de materiales
                </a>
                @endcan

                @can('ver-tecnico')
                <a class="nav-link" href="{{ route('tecnico.lista') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-vcard"></i></div>
                    Tecnico
                </a>
                @endcan

                @can('ver-tecnico')
                <a class="nav-link" href="{{ route('tecnico.buckettecnico') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-vcard"></i></div>
                    Bucket Tecnico
                </a>
                @endcan

                @can('ver-proveedore')
                <a class="nav-link" href="{{ route('proveedores.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-truck"></i></div>
                    Proveedores
                </a>
                @endcan

                @hasrole('administrador')
                <div class="sb-sidenav-menu-heading">OTROS</div>
                @endhasrole

                @can('ver-user')
                <a class="nav-link" href="{{ route('users.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-user"></i></div>
                    Usuarios
                </a>
                @endcan

                @can('ver-role')
                <a class="nav-link" href="{{ route('roles.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-person-circle-plus"></i></div>
                    Roles
                </a>
                @endcan
                @can('ver-permiso')
                <a class="nav-link" href="{{ route('permiso.index') }}">
                    <div class="sb-nav-link-icon">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    Permisos
                </a>
            @endcan
            @can('ver-comprobante')
            <a class="nav-link" href="{{ route('comprobante.index') }}">
                <div class="sb-nav-link-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                Comprobantes/Documentos
            </a>
        @endcan
        @can('ver-comprobante')
        <a class="nav-link" href="{{ route('cuentas.index') }}">
            <div class="sb-nav-link-icon">
                <i class="fa-solid fa-gears"></i>
            </div>
            Cuentas Contables
        </a>
    @endcan
<style>
.contenedor-centro {
  display: flex;
  justify-content: center;  /* Centra horizontalmente */
  align-items: center;      /* Centra verticalmente */
}

.img-redonda {
  width: 80px;
  height: 80px;
  object-fit: cover;
  object-position: center -55px; /* Puedes ajustar el valor vertical */
  border-radius: 50%;
  border: 2px solid #ccc;
  display: block;
  margin: 0 auto;
}


</style>

            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small" style="aling=center;">Bienvenido: {{ auth()->user()->name }}</div><br>
            <div class="contenedor-centro"><img class="img-redonda" src="data:image/jpg;base64,{{ auth()->user()->logo }}"></div>
        </div>
    </nav>
</div>
