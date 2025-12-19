<?php
require_once __DIR__ . '../../../config/checkSessionUsuario.php';
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../views/header.php';

// Verificar ID del animal
$id_animal = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_animal <= 0) {
    header("Location: ../../views/dashboardAdoptante.php");
    exit();
}

// Obtener información del animal
$query_animal = "SELECT a.*, r.nombre_raza 
                FROM animales a
                LEFT JOIN razas r ON a.id_raza = r.id_raza
                WHERE a.id_animal = $id_animal";
$animal = $conexion->query($query_animal)->fetch_assoc();

if (!$animal) {
    header("Location: ../../views/dashboardAdoptante.php");
    exit();
}

// Obtener información de la última solicitud del usuario para este animal
$id_usuario = $_SESSION['id_usuario'];
$query_solicitud = "SELECT sa.* 
                   FROM solicitudes_adopcion sa
                   JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
                   WHERE ad.id_usuario = $id_usuario AND sa.id_animal = $id_animal
                   ORDER BY sa.fecha_solicitud DESC
                   LIMIT 1";
$solicitud = $conexion->query($query_solicitud)->fetch_assoc();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white py-3">
                    <h2 class="h4 mb-0"><i class="fas fa-check-circle me-2"></i> Solicitud de adopción enviada</h2>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                        <h3 class="h2 mb-3">¡Gracias por tu solicitud, <?= htmlspecialchars($_SESSION['nombre_completo'] ?? '') ?>!</h3>
                        <p class="lead">Tu solicitud para adoptar a <strong><?= htmlspecialchars($animal['nombre']) ?></strong> ha sido recibida correctamente.</p>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h4 class="h5 mb-3"><i class="fas fa-paw me-2 text-primary"></i> Sobre <?= htmlspecialchars($animal['nombre']) ?></h4>
                                    <div class="text-center mb-3">
                                        <?php 
                                        $foto_principal = $conexion->query("SELECT url_foto FROM fotos_animales WHERE id_animal = $id_animal LIMIT 1")->fetch_assoc();
                                        if ($foto_principal): ?>
                                            <img src="<?= htmlspecialchars($foto_principal['url_foto']) ?>" 
                                                 alt="<?= htmlspecialchars($animal['nombre']) ?>" 
                                                 class="img-fluid rounded" style="max-height: 200px;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                                <i class="fas fa-paw fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><strong>Especie:</strong> <?= $animal['especie'] ?></li>
                                        <li class="mb-2"><strong>Raza:</strong> <?= $animal['nombre_raza'] ?? 'Sin especificar' ?></li>
                                        <li class="mb-2"><strong>Edad:</strong> <?= $animal['edad_anios'] ?> años</li>
                                        <li><strong>Tamaño:</strong> <?= $animal['tamanio'] ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h4 class="h5 mb-3"><i class="fas fa-file-alt me-2 text-primary"></i> Detalles de tu solicitud</h4>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) ?></li>
                                        <li class="mb-2"><strong>Tipo de vivienda:</strong> <?= $solicitud['tipo_vivienda'] ?></li>
                                        <li class="mb-2"><strong>Otros animales:</strong> <?= $solicitud['tiene_otros_animales'] ? 'Sí' : 'No' ?></li>
                                        <li><strong>Horas solo al día:</strong> <?= $solicitud['tiempo_solo_horas_por_dia'] ?></li>
                                    </ul>
                                    
                                    <hr class="my-4">
                                    
                                    <h5 class="h6 mb-3">Proceso de adopción</h5>
                                    <div class="d-flex flex-column gap-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                1
                                            </div>
                                            <div class="ms-3">
                                                <small class="text-muted">Paso actual</small>
                                                <p class="mb-0"><strong>Solicitud enviada</strong></p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light text-muted rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                2
                                            </div>
                                            <div class="ms-3">
                                                <small class="text-muted">Próximo paso</small>
                                                <p class="mb-0">Revisión por el equipo</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light text-muted rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                                3
                                            </div>
                                            <div class="ms-3">
                                                <small class="text-muted">Finalización</small>
                                                <p class="mb-0">Confirmación de adopción</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info text-start">
                        <h4 class="h5 mb-3"><i class="fas fa-info-circle me-2"></i> ¿Qué sigue?</h4>
                        <ul class="mb-0">
                            <li class="mb-2">Nuestro equipo revisará tu solicitud en un plazo de 2-3 días hábiles.</li>
                            <li class="mb-2">Te contactaremos por correo electrónico o teléfono para programar una entrevista.</li>
                            <li>Puedes ver el estado de tu solicitud en la sección <a href="mis_solicitudes.php" class="alert-link">Mis solicitudes</a>.</li>
                        </ul>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <a href="dashboard_adoptante.php" class="btn btn-primary px-4">
                            <i class="fas fa-paw me-2"></i> Ver más animales
                        </a>
                        <a href="mis_solicitudes.php" class="btn btn-outline-primary px-4">
                            <i class="fas fa-clipboard-list me-2"></i> Ver mis solicitudes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
