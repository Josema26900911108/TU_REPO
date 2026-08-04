<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Inicio de sesión del sistema CODE ERP" />
    <meta name="author" content="SakCode" />
    
    <title>CODE ERP - Login</title>
    
    <!-- Bootstrap 5.3 CSS Local -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
    
    <!-- Carga de JQuery Local al inicio -->
    <script src="{{ asset('js/jquery.min.js') }}"></script>

    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
        }
        .card-erp {
            background-color: #ffffff;
            border-radius: 16px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25) !important;
        }
        .brand-title {
            font-weight: 800;
            letter-spacing: 2px;
            color: #0f172a;
        }
        .brand-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-erp {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
            font-weight: 600;
            transition: all 0.2s ease-in-out;
        }
        .btn-erp:hover {
            background-color: #1d4ed8 !important;
            transform: translateY(-1px);
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.15);
        }
    </style>
</head>

<body class="min-vh-100 d-flex flex-column justify-content-between">

    <div id="layoutAuthentication" class="w-100 my-auto">
        <main>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-5 col-md-7">
                        <div class="card card-erp border-0 p-3 mt-5">
                            
                            <div class="card-header bg-transparent border-0 text-center pt-4 pb-2">
                                <h2 class="brand-title m-0">CODE <span class="text-primary">ERP</span></h2>
                                <p class="brand-subtitle m-0 mt-1">Gestión Empresarial e Integral</p>
                            </div>
                            
                            <div class="card-body px-4">
                                @if ($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                                        <ul class="mb-0 ps-3 small">
                                            @foreach ($errors->all() as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                @endif

                                <form action="{{ url('/login') }}" method="POST">
                                    @csrf
                                    
                                    <div class="form-floating mb-3">
                                        <input autofocus autocomplete="email" class="form-control" name="email" id="inputEmail" type="email" placeholder="name@example.com" required />
                                        <label for="inputEmail">Correo electrónico</label>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <!-- Se agrega autocomplete="current-password" para solucionar la advertencia del navegador -->
                                        <input class="form-control" name="password" id="inputPassword" type="password" autocomplete="current-password" placeholder="Password" required />
                                        <label for="inputPassword">Contraseña</label>
                                    </div>
                                    
                                    <div class="form-floating mb-4 position-relative">
                                        <select name="idTienda" id="idTienda" class="form-select" required>
                                            <option value="" disabled selected>Introduzca su correo primero...</option>
                                        </select>
                                        <label for="idTienda">Sucursal / Tienda</label>
                                        
                                        <div id="loadingTiendas" class="spinner-border spinner-border-sm text-primary position-absolute d-none" style="top: 22px; right: 35px;" role="status"></div>
                                    </div>

                                    <div class="d-grid gap-2 mb-2">
                                        <button class="btn btn-erp btn-primary btn-lg fs-6 py-2.5" type="submit">
                                            Ingresar al Sistema
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <footer class="py-3 mt-5 w-100" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(5px);">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center justify-content-between small text-secondary">
                <div>Copyright &copy; CODE ERP {{ date('Y') }}</div>
                <div>
                    <a href="#" class="text-decoration-none text-secondary me-2">Privacidad</a>
                    &middot;
                    <a href="#" class="text-decoration-none text-secondary ms-2">Términos</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS Local -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    <!-- Script AJAX -->
    <script>
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let typingTimer;
        const doneTypingInterval = 400;

        $('#inputEmail').on('input', function () {
            clearTimeout(typingTimer);
            var email = $(this).val();

            if (email.length > 3) {
                typingTimer = setTimeout(function() {
                    $('#loadingTiendas').removeClass('d-none');
                    
                    $.ajax({
                        url: '/tiendas', 
                        method: 'POST',
                        data: { email: email },
                        success: function (data) {
                            $('#idTienda').empty();

                            if (data.length === 0) {
                                $('#idTienda').append(new Option('No se encontraron tiendas', ''));
                            } else {
                                $('#idTienda').append(new Option('Seleccione una sucursal...', ''));
                                $.each(data, function (index, tienda) {
                                    $('#idTienda').append(new Option(tienda.Nombre, tienda.idTienda || tienda.id));
                                });
                            }
                        },
                        error: function (xhr) {
                            console.error('Error en consulta de sucursales: ', xhr);
                        },
                        complete: function() {
                            $('#loadingTiendas').addClass('d-none');
                        }
                    });
                }, doneTypingInterval);
            } else {
                $('#idTienda').html('<option value="" disabled selected>Introduzca su correo primero...</option>');
            }
        });
    });
    </script>
</body>
</html>
