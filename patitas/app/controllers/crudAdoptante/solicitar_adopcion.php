<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../config/conexion.php';

// Verificar si se proporcionó un ID de animal válido
$id_animal = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_animal <= 0) {
    redirect('/dashboard-adoptante');
}

// Verificar que el animal existe y está disponible
$query = "SELECT nombre, estado FROM animales WHERE id_animal = $id_animal AND activo = 1";
$animal = $conexion->query($query)->fetch_assoc();

if (!$animal || $animal['estado'] != 'Disponible') {
    redirect('/dashboard-adoptante');
}

// Obtener información del adoptante
$id_usuario = $_SESSION['id_usuario'];
$query_adoptante = "SELECT * FROM adoptantes WHERE id_usuario = $id_usuario";
$adoptante = $conexion->query($query_adoptante)->fetch_assoc();

// Procesar el formulario si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo_adopcion = $conexion->real_escape_string($_POST['motivo_adopcion']);
    $experiencia_mascotas = $conexion->real_escape_string($_POST['experiencia_mascotas']);
    $tipo_vivienda = $conexion->real_escape_string($_POST['tipo_vivienda']);
    $tiene_otros_animales = isset($_POST['tiene_otros_animales']) ? intval($_POST['tiene_otros_animales']) : 0;
    $tiempo_solo = intval($_POST['tiempo_solo_horas_por_dia']);

    // Insertar la solicitud
    $query_insert = "INSERT INTO solicitudes_adopcion (
        id_adoptante, 
        id_animal, 
        motivo_adopcion, 
        experiencia_mascotas, 
        tipo_vivienda, 
        tiene_otros_animales, 
        tiempo_solo_horas_por_dia
    ) VALUES (
        {$adoptante['id_adoptante']}, 
        $id_animal, 
        '$motivo_adopcion', 
        '$experiencia_mascotas', 
        '$tipo_vivienda', 
        $tiene_otros_animales, 
        $tiempo_solo
    )";

    // Depuración:
    // var_dump($adoptante, $_POST, $query_insert);
    // exit;
    if ($conexion->query($query_insert)) {
        // Actualizar estado del animal
        $conexion->query("UPDATE animales SET estado = 'En adopción' WHERE id_animal = $id_animal");

        // Redirigir a confirmación
        redirect("/adoptante/confirmacion-adopcion?id=$id_animal");
    } else {
        // Mostrar el error de MySQL para depuración
        $error = "Ocurrió un error al enviar la solicitud. Por favor intenta nuevamente.<br><small>" . $conexion->error . "</small>";
    }
}

// Incluir header después de toda la lógica de redirección
require_once __DIR__ . '/../../views/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../views/dashboardAdoptante.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="ver_animal.php?id=<?= $id_animal ?>"><?= htmlspecialchars($animal['nombre']) ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Solicitud de adopción</li>
                </ol>
            </nav>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <h2 class="h4 mb-0"><i class="fas fa-heart me-2"></i> Solicitud de adopción</h2>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <h3 class="h5 mb-3">Estás solicitando adoptar a <strong><?= htmlspecialchars($animal['nombre']) ?></strong></h3>
                        <p class="mb-0">Por favor completa el siguiente formulario con información veraz. Esto nos ayudará a asegurarnos que <?= htmlspecialchars($animal['nombre']) ?> tendrá el hogar perfecto.</p>
                    </div>

                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <h3 class="h5 mb-3 border-bottom pb-2">Información personal</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre completo</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($adoptante['nombre_completo']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($adoptante['telefono']) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Dirección</label>
                                    <textarea class="form-control" rows="2" readonly><?= htmlspecialchars($adoptante['direccion']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-text">
                                        Si necesitas actualizar esta información, por favor edita tu <a href="mi_perfil.php">perfil</a> primero.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h3 class="h5 mb-3 border-bottom pb-2">Sobre la adopción</h3>
                            <div class="mb-3">
                                <label for="motivo_adopcion" class="form-label">¿Por qué quieres adoptar a <?= htmlspecialchars($animal['nombre']) ?>? *</label>
                                <textarea class="form-control" id="motivo_adopcion" name="motivo_adopcion" rows="3" required></textarea>
                                <div class="invalid-feedback">
                                    Por favor explica tus motivos para la adopción.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="experiencia_mascotas" class="form-label">Describe tu experiencia previa con mascotas *</label>
                                <textarea class="form-control" id="experiencia_mascotas" name="experiencia_mascotas" rows="3" required></textarea>
                                <div class="invalid-feedback">
                                    Por favor describe tu experiencia con mascotas.
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h3 class="h5 mb-3 border-bottom pb-2">Sobre tu hogar</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="tipo_vivienda" class="form-label">Tipo de vivienda *</label>
                                    <select class="form-select" id="tipo_vivienda" name="tipo_vivienda" required>
                                        <option value="" selected disabled>Selecciona una opción</option>
                                        <option value="Casa">Casa</option>
                                        <option value="Departamento">Departamento</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor selecciona el tipo de vivienda.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">¿Tienes otros animales? *</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tiene_otros_animales" id="otros_animales_si" value="1">
                                        <label class="form-check-label" for="otros_animales_si">Sí</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tiene_otros_animales" id="otros_animales_no" value="0" checked>
                                        <label class="form-check-label" for="otros_animales_no">No</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="tiempo_solo_horas_por_dia" class="form-label">¿Cuántas horas al día estaría solo el animal? *</label>
                                    <input type="number" class="form-control" id="tiempo_solo_horas_por_dia" name="tiempo_solo_horas_por_dia" min="0" max="24" required>
                                    <div class="invalid-feedback">
                                        Por favor indica cuántas horas estaría solo el animal.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="acepto_terminos" required>
                            <label class="form-check-label" for="acepto_terminos">
                                Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#terminosModal">términos y condiciones</a> de adopción *
                            </label>
                            <div class="invalid-feedback">
                                Debes aceptar los términos y condiciones para continuar.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                <i class="fas fa-paper-plane me-2"></i> Enviar solicitud de adopción
                            </button>
                            <a href="ver_animal.php?id=<?= $id_animal ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Volver atrás
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de términos y condiciones -->
<div class="modal fade" id="terminosModal" tabindex="-1" aria-labelledby="terminosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="terminosModalLabel">Términos y condiciones de adopción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5>1. Compromiso de cuidado</h5>
                <p>El adoptante se compromete a proporcionar al animal alimento adecuado, agua fresca, refugio adecuado, atención veterinaria cuando sea necesario y mucho amor y cuidado.</p>

                <h5>2. Esterilización</h5>
                <p>Si el animal no está esterilizado al momento de la adopción, el adoptante se compromete a realizar este procedimiento cuando sea recomendado por el veterinario.</p>

                <h5>3. Identificación</h5>
                <p>El adoptante se compromete a mantener al animal con identificación adecuada (microchip o placa) en todo momento.</p>

                <h5>4. Seguimiento</h5>
                <p>El refugio puede realizar seguimientos periódicos para verificar el bienestar del animal. El adoptante se compromete a cooperar con estos seguimientos.</p>

                <h5>5. No abandono</h5>
                <p>Si por cualquier razón el adoptante no puede continuar con el cuidado del animal, se compromete a devolverlo al refugio en lugar de abandonarlo o darlo a terceros.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Validación del formulario
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            var forms = document.getElementsByClassName('needs-validation');
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
