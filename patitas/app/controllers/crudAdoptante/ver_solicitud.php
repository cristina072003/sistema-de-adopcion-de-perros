<?php
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../views/header.php';

// Verificar ID de solicitud
$id_solicitud = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_solicitud <= 0) {
    header("Location: mis_solicitudes.php");
    exit();
}

// Obtener información de la solicitud
$query = "SELECT sa.*, a.*, ad.*, 
                 DATE_FORMAT(sa.fecha_solicitud, '%d/%m/%Y a las %H:%i') as fecha_formateada,
                 DATE_FORMAT(sa.fecha_respuesta, '%d/%m/%Y a las %H:%i') as fecha_respuesta_formateada,
                 (SELECT url_foto FROM fotos_animales WHERE id_animal = a.id_animal LIMIT 1) as foto_animal
          FROM solicitudes_adopcion sa
          JOIN animales a ON sa.id_animal = a.id_animal
          JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
          WHERE sa.id_solicitud = $id_solicitud AND ad.id_usuario = {$_SESSION['id_usuario']}";

$result = $conexion->query($query);

if (!$result || $result->num_rows === 0) {
    header("Location: mis_solicitudes.php");
    exit();
}

$solicitud = $result->fetch_assoc();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard_adoptante.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="mis_solicitudes.php">Mis Solicitudes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Solicitud #<?= $id_solicitud ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">
                    <i class="fas fa-file-alt me-2"></i> Solicitud de adopción
                </h1>
                <span class="badge <?= [
                    'Pendiente' => 'bg-warning text-dark',
                    'Aprobada' => 'bg-success',
                    'Rechazada' => 'bg-danger',
                    'En revisión' => 'bg-info'
                ][$solicitud['estado']] ?? 'bg-secondary' ?>">
                    <?= $solicitud['estado'] ?>
                </span>
            </div>

            <div class="row g-4">
                <!-- Información del Animal -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h2 class="h5 mb-0"><i class="fas fa-paw me-2"></i> Animal solicitado</h2>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <?php if (!empty($solicitud['foto_animal'])): ?>
                                    <img src="<?= htmlspecialchars($solicitud['foto_animal']) ?>" 
                                         alt="<?= htmlspecialchars($solicitud['nombre']) ?>" 
                                         class="img-fluid rounded" style="max-height: 200px;">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="fas fa-paw fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h3 class="h4"><?= htmlspecialchars($solicitud['nombre']) ?></h3>
                            <ul class="list-unstyled">
                                <li class="mb-2"><strong>Especie:</strong> <?= $solicitud['especie'] ?></li>
                                <li class="mb-2"><strong>Edad:</strong> <?= $solicitud['edad_anios'] ?> años</li>
                                <li class="mb-2"><strong>Tamaño:</strong> <?= $solicitud['tamanio'] ?></li>
                                <li><strong>Sexo:</strong> <?= $solicitud['sexo'] ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Detalles de la Solicitud -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-light">
                            <h2 class="h5 mb-0"><i class="fas fa-info-circle me-2"></i> Detalles de la solicitud</h2>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <strong><i class="fas fa-calendar me-2"></i> Fecha de solicitud:</strong><br>
                                    <?= $solicitud['fecha_formateada'] ?>
                                </li>
                                <?php if (!empty($solicitud['fecha_respuesta_formateada'])): ?>
                                    <li class="mb-3">
                                        <strong><i class="fas fa-calendar-check me-2"></i> Fecha de respuesta:</strong><br>
                                        <?= $solicitud['fecha_respuesta_formateada'] ?>
                                    </li>
                                <?php endif; ?>
                                <li class="mb-3">
                                    <strong><i class="fas fa-home me-2"></i> Tipo de vivienda:</strong><br>
                                    <?= $solicitud['tipo_vivienda'] ?>
                                </li>
                                <li class="mb-3">
                                    <strong><i class="fas fa-paw me-2"></i> ¿Tiene otros animales?</strong><br>
                                    <?= $solicitud['tiene_otros_animales'] ? 'Sí' : 'No' ?>
                                </li>
                                <li class="mb-3">
                                    <strong><i class="fas fa-clock me-2"></i> Horas solo al día:</strong><br>
                                    <?= $solicitud['tiempo_solo_horas_por_dia'] ?> horas
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Motivos y Observaciones -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h2 class="h5 mb-0"><i class="fas fa-comment me-2"></i> Motivos y observaciones</h2>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h3 class="h6"><i class="fas fa-heart me-2 text-primary"></i> Motivo de adopción</h3>
                                <p><?= nl2br(htmlspecialchars($solicitud['motivo_adopcion'])) ?></p>
                            </div>
                            <div class="mb-4">
                                <h3 class="h6"><i class="fas fa-history me-2 text-primary"></i> Experiencia con mascotas</h3>
                                <p><?= nl2br(htmlspecialchars($solicitud['experiencia_mascotas'])) ?></p>
                            </div>
                            <?php if (!empty($solicitud['otros_detalles'])): ?>
                                <div class="mb-4">
                                    <h3 class="h6"><i class="fas fa-ellipsis-h me-2 text-primary"></i> Otros detalles</h3>
                                    <p><?= nl2br(htmlspecialchars($solicitud['otros_detalles'])) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($solicitud['notas_admin'])): ?>
                                <div class="mb-4">
                                    <h3 class="h6"><i class="fas fa-sticky-note me-2 text-primary"></i> Notas del administrador</h3>
                                    <div class="alert alert-info">
                                        <?= nl2br(htmlspecialchars($solicitud['notas_admin'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                <a href="mis_solicitudes.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Volver a mis solicitudes
                </a>
                <?php if ($solicitud['estado'] == 'Pendiente'): ?>
                    <a href="cancelar_solicitud.php?id=<?= $id_solicitud ?>" 
                       class="btn btn-outline-danger ms-md-2"
                       onclick="return confirm('¿Estás seguro de cancelar esta solicitud?');">
                        <i class="fas fa-times me-2"></i> Cancelar solicitud
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>