<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../views/headerA.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('/admin/animales');
}

$id_animal = (int)$_GET['id'];

// Obtener información del animal
$animal = $conexion->query("
    SELECT a.*, r.nombre_raza, d.nombre as nombre_departamento, d.provincia, d.ubicacion_gps
    FROM animales a
    LEFT JOIN razas r ON a.id_raza = r.id_raza
    LEFT JOIN animal_departamento ad ON a.id_animal = ad.id_animal
    LEFT JOIN departamentos d ON ad.id_departamento = d.id_departamento
    WHERE a.id_animal = $id_animal AND a.activo = 1
")->fetch_assoc();

if (!$animal) {
    redirect('/admin/animales');
}

// Obtener fotos del animal
$fotos = $conexion->query("
    SELECT * FROM fotos_animales 
    WHERE id_animal = $id_animal
    ORDER BY id_foto
")->fetch_all(MYSQLI_ASSOC);

// Obtener historial de adopciones
$adopciones = $conexion->query("
    SELECT sa.*, ad.nombre_completo as adoptante, ad.telefono
    FROM solicitudes_adopcion sa
    JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
    WHERE sa.id_animal = $id_animal
    ORDER BY sa.fecha_solicitud DESC
")->fetch_all(MYSQLI_ASSOC);

// Obtener seguimientos post-adopción
$seguimientos = $conexion->query("
    SELECT s.*, ad.nombre_completo as adoptante
    FROM seguimientos s
    JOIN solicitudes_adopcion sa ON s.id_solicitud = sa.id_solicitud
    JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
    WHERE sa.id_animal = $id_animal
    ORDER BY s.fecha_seguimiento DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($animal['nombre']) ?> - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4B7BEC;
            --secondary: #6C757D;
            --success: #28A745;
            --info: #17A2B8;
            --warning: #FFC107;
            --danger: #DC3545;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }

        .card-detail {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 15px 15px 0 0 !important;
        }

        .animal-name {
            color: var(--primary);
            font-weight: 600;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .badge-available {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .badge-adopted {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .badge-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .badge-reserved {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .info-label {
            font-weight: 500;
            color: var(--secondary);
        }

        .gallery-image {
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .gallery-image:hover {
            transform: scale(1.03);
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline:before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #e9ecef;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -30px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--primary);
            border: 4px solid white;
        }

        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-paw me-2"></i> Detalles del Animal
            </h2>
            <a href="<?php echo url('/admin/animales'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>

        <div class="row">
            <!-- Información principal -->
            <div class="col-lg-8">
                <div class="card card-detail mb-4">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="animal-name mb-0">
                                <?= htmlspecialchars($animal['nombre']) ?>
                                <span class="badge-status <?=
                                                            $animal['estado'] == 'Disponible' ? 'badge-available' : ($animal['estado'] == 'Adoptado' ? 'badge-adopted' : ($animal['estado'] == 'Reservado' ? 'badge-reserved' : 'badge-pending'))
                                                            ?>">
                                    <?= $animal['estado'] ?>
                                </span>
                            </h3>
                            <div>
                                <a href="<?php echo url('/admin/editar-animal?id=' . $id_animal); ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit me-2"></i> Editar
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <span class="info-label">Especie:</span>
                                    <p><?= htmlspecialchars($animal['especie']) ?></p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Raza:</span>
                                    <p><?= $animal['nombre_raza'] ? htmlspecialchars($animal['nombre_raza']) : 'Sin especificar' ?></p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Edad:</span>
                                    <p><?= $animal['edad_anios'] ?> años</p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Sexo:</span>
                                    <p><?= $animal['sexo'] ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <span class="info-label">Tamaño:</span>
                                    <p><?= $animal['tamanio'] ?></p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Fecha de ingreso:</span>
                                    <p><?= date('d/m/Y', strtotime($animal['fecha_ingreso'])) ?></p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Estado de salud:</span>
                                    <p><?= $animal['estado_salud'] ? htmlspecialchars($animal['estado_salud']) : 'Sin especificar' ?></p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Vacunado/Esterilizado:</span>
                                    <p>
                                        <?= $animal['vacunado'] ? '<span class="badge bg-success me-2">Vacunado</span>' : '<span class="badge bg-secondary me-2">No vacunado</span>' ?>
                                        <?= $animal['esterilizado'] ? '<span class="badge bg-success">Esterilizado</span>' : '<span class="badge bg-secondary">No esterilizado</span>' ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="info-label">Descripción:</span>
                            <p><?= $animal['descripcion'] ? nl2br(htmlspecialchars($animal['descripcion'])) : 'Sin descripción' ?></p>
                        </div>

                        <div class="mb-3">
                            <span class="info-label">Historia:</span>
                            <p><?= $animal['historia'] ? nl2br(htmlspecialchars($animal['historia'])) : 'Sin historia registrada' ?></p>
                        </div>
                    </div>
                </div>

                <!-- Galería de fotos -->
                <?php if (!empty($fotos)): ?>
                    <div class="card card-detail mb-4">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-images me-2"></i> Galería de Fotos</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($fotos as $foto): ?>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <img src="../../views/<?= htmlspecialchars($foto['url_foto']) ?>"
                                            alt="<?= htmlspecialchars($animal['nombre']) ?>"
                                            class="img-fluid gallery-image w-100"
                                            data-bs-toggle="modal"
                                            data-bs-target="#imageModal"
                                            data-bs-img="../../views/<?= htmlspecialchars($foto['url_foto']) ?>"
                                            data-bs-desc="<?= htmlspecialchars($foto['descripcion'] ?? '') ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Ubicación -->
                <?php if ($animal['nombre_departamento']): ?>
                    <div class="card card-detail mb-4">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i> Ubicación</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <p><strong>Departamento:</strong> <?= htmlspecialchars($animal['nombre_departamento']) ?></p>
                                    <p><strong>Provincia:</strong> <?= htmlspecialchars($animal['provincia']) ?></p>
                                    <?php if ($animal['ubicacion_gps']): ?>
                                        <p><strong>Coordenadas GPS:</strong> <?= htmlspecialchars($animal['ubicacion_gps']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <div class="map-container bg-light">
                                        <!-- Aquí iría un mapa con las coordenadas GPS -->
                                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                            <i class="fas fa-map fa-3x me-3"></i>
                                            <span>Mapa de ubicación</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Información secundaria -->
            <div class="col-lg-4">
                <!-- Historial de adopciones -->
                <div class="card card-detail mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-home me-2"></i> Historial de Adopciones</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($adopciones)): ?>
                            <div class="timeline">
                                <?php foreach ($adopciones as $adopcion): ?>
                                    <div class="timeline-item mb-3">
                                        <div class="timeline-dot"></div>
                                        <div class="card p-3">
                                            <h6 class="mb-1"><?= htmlspecialchars($adopcion['adoptante']) ?></h6>
                                            <small class="text-muted d-block mb-1">
                                                <?= date('d/m/Y', strtotime($adopcion['fecha_solicitud'])) ?>
                                                <span class="badge bg-<?=
                                                                        $adopcion['estado'] == 'Aprobada' ? 'success' : ($adopcion['estado'] == 'Rechazada' ? 'danger' : 'warning')
                                                                        ?> ms-2">
                                                    <?= $adopcion['estado'] ?>
                                                </span>
                                            </small>
                                            <p class="mb-1"><small><?= htmlspecialchars($adopcion['motivo_adopcion']) ?></small></p>
                                            <?php if ($adopcion['telefono']): ?>
                                                <small class="d-block"><i class="fas fa-phone me-2"></i> <?= htmlspecialchars($adopcion['telefono']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3 text-muted">
                                <i class="fas fa-home fa-2x mb-3"></i>
                                <p>No hay historial de adopciones</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Seguimientos post-adopción -->
                <?php if (!empty($seguimientos)): ?>
                    <div class="card card-detail mb-4">
                        <div class="card-header py-3">
                            <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i> Seguimientos</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($seguimientos as $seg): ?>
                                    <div class="timeline-item mb-3">
                                        <div class="timeline-dot"></div>
                                        <div class="card p-3">
                                            <h6 class="mb-1"><?= htmlspecialchars($seg['adoptante']) ?></h6>
                                            <small class="text-muted d-block mb-1">
                                                <?= date('d/m/Y', strtotime($seg['fecha_seguimiento'])) ?>
                                                <span class="badge bg-<?=
                                                                        $seg['satisfaccion'] == 'Excelente' ? 'success' : ($seg['satisfaccion'] == 'Bueno' ? 'primary' : ($seg['satisfaccion'] == 'Regular' ? 'warning' : 'danger'))
                                                                        ?> ms-2">
                                                    <?= $seg['satisfaccion'] ?>
                                                </span>
                                            </small>
                                            <p class="mb-1"><small><?= htmlspecialchars($seg['notas']) ?></small></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para ver imagen en grande -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Foto de <?= htmlspecialchars($animal['nombre']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid rounded" alt="">
                    <p id="modalDescription" class="mt-3"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal para mostrar imágenes
        const imageModal = document.getElementById('imageModal');
        if (imageModal) {
            imageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const imgSrc = button.getAttribute('data-bs-img');
                const imgDesc = button.getAttribute('data-bs-desc');

                document.getElementById('modalImage').src = imgSrc;
                document.getElementById('modalDescription').textContent = imgDesc || '';
            });
        }
    </script>
</body>

</html>