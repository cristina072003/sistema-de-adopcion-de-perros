</main> <!-- Cierre del main abierto en el header -->

<!-- Pie de página -->
<footer class="bg-dark text-white pt-5 pb-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="text-uppercase mb-4">Refugio Patitas</h5>
                <p>Dedicados a encontrar hogares amorosos para animales necesitados desde 2010.</p>
                <div class="mt-4">
                    <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Enlaces</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="dashboard_adoptante.php" class="text-white">Adoptar</a></li>
                    <li class="mb-2"><a href="blog.php" class="text-white">Blog</a></li>
                    <li class="mb-2"><a href="eventos.php" class="text-white">Eventos</a></li>
                    <li class="mb-2"><a href="testimonios.php" class="text-white">Testimonios</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Contacto</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Av. Principal 123, Lima</li>
                    <li class="mb-2"><i class="fas fa-phone me-2"></i> (01) 987 654 321</li>
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@patitas.org</li>
                    <li><i class="fas fa-clock me-2"></i> Lun-Vie: 9am - 6pm</li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="text-uppercase mb-4">Boletín</h5>
                <p>Suscríbete para recibir novedades y oportunidades de adopción.</p>
                <form class="mb-3" onsubmit="mostrarDonar(event)">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Tu correo">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
                <button class="btn btn-outline-light w-100" onclick="mostrarDonar(event)">
                    <i class="fas fa-hand-holding-heart me-2"></i> Donar
                </button>
            </div>
        </div>

        <hr class="my-4 bg-light">

        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; 2023 Patitas Felices. Todos los derechos reservados.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="politica-privacidad.php" class="text-white me-3">Política de privacidad</a>
                <a href="terminos-condiciones.php" class="text-white">Términos y condiciones</a>
            </div>
        </div>
    </div>
</footer>

<!-- Modal Donar -->
<div class="modal fade" id="modalDonar" tabindex="-1" aria-labelledby="modalDonarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-white">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDonarLabel"><i class="fas fa-hand-holding-heart me-2 text-danger"></i> ¡Gracias por apoyar a Patitas Felices!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Tu donación ayuda a que más animales encuentren un hogar y reciban los cuidados que merecen.</p>
                <ul>
                    <li>Yape/Plin: <strong>987654321</strong></li>
                    <li>Cuenta BCP: <strong>123-4567890-0-12</strong></li>
                    <li>Cuenta Interbank: <strong>123-4567890123</strong></li>
                </ul>
                <p class="mb-0">¡Gracias por tu generosidad!</p>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
    // Activar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Cambiar clase del navbar al hacer scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-patitas');
            if (navbar && window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else if (navbar) {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    });

    // Mostrar modal de donación
    function mostrarDonar(e) {
        if (e) e.preventDefault();
        var modal = new bootstrap.Modal(document.getElementById('modalDonar'));
        modal.show();
    }
</script>
</body>

</html>