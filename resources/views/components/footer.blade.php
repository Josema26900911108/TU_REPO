<footer class="py-4 bg-light mt-auto">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between small">
            <div class="text-muted">Copyright &copy; CODE ERP {{ date('Y') }}</div>
            <div>
                    <!-- Reemplaza los enlaces viejos del footer por estos -->
                    <a href="{{ route('legal.privacidad') }}" target="_blank" class="text-decoration-none text-secondary me-2">Privacidad</a>
                    &middot;
                    <a href="{{ route('legal.terminos') }}" target="_blank" class="text-decoration-none text-secondary ms-2">Términos y Condiciones</a>

            </div>
        </div>
    </div>
</footer>
