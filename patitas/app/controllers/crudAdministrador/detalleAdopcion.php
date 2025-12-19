<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../config/checkSessionUsuario.php';
require_once __DIR__ . '../../../views/headerA.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: adopciones.php");
    exit();
}

$id_solicitud = (int)$_GET['id'];

// Obtener información de la solicitud
$solicitud = $conexion->query("
    SELECT 
        sa.*, 
        a.nombre as nombre_animal, a.especie, a.id_animal, a.estado as estado_animal,
        a.descripcion as descripcion_animal, a.edad_anios, a.sexo, a.tamanio,
        ad.*,
        (SELECT url_foto FROM fotos_animales WHERE id_animal = a.id_animal LIMIT 1) as foto_animal
    FROM solicitudes_adopcion sa
    JOIN animales a ON sa.id_animal = a.id_animal
    JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
    WHERE sa.id_solicitud = $id_solicitud
")->fetch_assoc();

if (!$solicitud) {
    header("Location: adopciones.php");
    exit();
}

// Obtener seguimientos si la adopción fue aprobada
$seguimientos = [];
if ($solicitud['estado'] === 'Aprobada') {
    $seguimientos = $conexion->query("
        SELECT * FROM seguimientos
        WHERE id_solicitud = $id_solicitud
        ORDER BY fecha_seguimiento DESC
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Adopción - Refugio de Mascotas</title>
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

        .badge-pending { background-color: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .badge-approved { background-color: rgba(40, 167, 69, 0.1); color: var(--success); }
        .badge-rejected { background-color: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .badge-review { background-color: rgba(23, 162, 184, 0.1); color: var(--info); }

        .info-label {
            font-weight: 500;
            color: var(--secondary);
        }

        .animal-avatar {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-home me-2"></i> Detalle de Solicitud de Adopción
            </h2>
            <a href="adopciones.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Volver
            </a>
        </div>

        <div class="row">
            <!-- Información del animal -->
            <div class="col-lg-6">
                <div class="card card-detail mb-4">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="animal-name mb-0">
                                <?= htmlspecialchars($solicitud['nombre_animal']) ?>
                                <span class="badge-status <?= 
                                    $solicitud['estado_animal'] == 'Disponible' ? 'badge-available' : 
                                    ($solicitud['estado_animal'] == 'Adoptado' ? 'badge-adopted' : 
                                    ($solicitud['estado_animal'] == 'Reservado' ? 'badge-reserved' : 'badge-pending')) 
                                ?>">
                                    <?= $solicitud['estado_animal'] ?>
                                </span>
                            </h3>
                            <a href="detalleAnimal.php?id=<?= $solicitud['id_animal'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-paw me-2"></i> Ver Animal
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if ($solicitud['foto_animal']): ?>
                                <img src="<?= htmlspecialchars($solicitud['foto_animal']) ?>" 
                                     alt="<?= htmlspecialchars($solicitud['nombre_animal']) ?>" 
                                     class="animal-avatar mb-3">
                            <?php else: ?>
                                <div class="animal-avatar bg-light d-flex align-items-center justify-content-center mx-auto mb-3">
                                    <i class="fas fa-paw fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <p><?= htmlspecialchars($solicitud['descripcion_animal']) ?></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <span class="info-label">Especie:</span>
                                    <p><?= htmlspecialchars($solicitud['especie']) ?></p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Edad:</span>
                                    <p><?= $solicitud['edad_anios'] ?> años</p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Sexo:</span>
                                    <p><?= $solicitud['sexo'] ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <span class="info-label">Tamaño:</span>
                                    <p><?= $solicitud['tamanio'] ?></p>
                                </div>
                                <div class="mb-3">
                                    <span class="info-label">Estado de salud:</span>
                                    <p><?= $solicitud['estado_salud'] ?? 'No especificado' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información de la solicitud y adoptante -->
            <div class="col-lg-6">
                <div class="card card-detail mb-4">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0">Información de la Solicitud</h3>
                            <span class="badge-status <?= 
                                $solicitud['estado'] == 'Aprobada' ? 'badge-approved' : 
                                ($solicitud['estado'] == 'Rechazada' ? 'badge-rejected' : 
                                ($solicitud['estado'] == 'En revisión' ? 'badge-review' : 'badge-pending')) 
                            ?>">
                                <?= $solicitud['estado'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="info-label">Fecha de solicitud:</span>
                            <p><?= date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])) ?></p>
                        </div>
                        
                        <?php if ($solicitud['fecha_respuesta']): ?>
                        <div class="mb-3">
                            <span class="info-label">Fecha de respuesta:</span>
                            <p><?= date('d/m/Y H:i', strtotime($solicitud['fecha_respuesta'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <span class="info-label">Motivo de adopción:</span>
                            <p><?= nl2br(htmlspecialchars($solicitud['motivo_adopcion'])) ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <span class="info-label">Tipo de vivienda:</span>
                            <p><?= $solicitud['tipo_vivienda'] ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <span class="info-label">Tiempo que el animal estará solo:</span>
                            <p><?= $solicitud['tiempo_solo_horas_por_dia'] ?> horas por día</p>
                        </div>
                        
                        <?php if ($solicitud['notas_admin']): ?>
                        <div class="mb-3">
                            <span class="info-label">Notas del administrador:</span>
                            <p><?= nl2br(htmlspecialchars($solicitud['notas_admin'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card card-detail mb-4">
                    <div class="card-header py-3">
                        <h3 class="mb-0">Información del Adoptante</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="info-label">Nombre completo:</span>
                            <p><?= htmlspecialchars($solicitud['nombre_completo']) ?></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Teléfono:</span>
                                <p><?= htmlspecialchars($solicitud['telefono']) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Documento de identidad:</span>
                                <p><?= htmlspecialchars($solicitud['documento_identidad']) ?></p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <span class="info-label">Dirección:</span>
                            <p><?= nl2br(htmlspecialchars($solicitud['direccion'])) ?></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Fecha de nacimiento:</span>
                                <p><?= date('d/m/Y', strtotime($solicitud['fecha_nacimiento'])) ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label">Ocupación:</span>
                                <p><?= htmlspecialchars($solicitud['ocupacion']) ?></p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <span class="info-label">Experiencia con animales:</span>
                            <p><?= $solicitud['experiencia_con_animales'] ? 'Sí' : 'No' ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <span class="info-label">Descripción de la vivienda:</span>
                            <p><?= nl2br(htmlspecialchars($solicitud['descripcion_vivienda'])) ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <span class="info-label">Otros animales:</span>
                            <p><?= $solicitud['otros_animales'] ? htmlspecialchars($solicitud['otros_animales']) : 'Ninguno' ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <span class="info-label">Tiempo en casa:</span>
                            <p><?= htmlspecialchars($solicitud['tiempo_en_casa']) ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <span class="info-label">Referencia personal:</span>
                            <p><?= htmlspecialchars($solicitud['referencia_personal']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Seguimientos post-adopción -->
        <?php if (!empty($seguimientos)): ?>
        <div class="card card-detail mb-4">
            <div class="card-header py-3">
                <h3 class="mb-0"><i class="fas fa-clipboard-check me-2"></i> Seguimientos Post-Adopción</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($seguimientos as $seg): ?>
                    <div class="timeline-item mb-3">
                        <div class="timeline-dot"></div>
                        <div class="card p-3">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">Seguimiento #<?= $seg['id_seguimiento'] ?></h6>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($seg['fecha_seguimiento'])) ?></small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-<?= 
                                    $seg['satisfaccion'] == 'Excelente' ? 'success' : 
                                    ($seg['satisfaccion'] == 'Bueno' ? 'primary' : 
                                    ($seg['satisfaccion'] == 'Regular' ? 'warning' : 'danger'))
                                ?>">
                                    <?= $seg['satisfaccion'] ?>
                                </span>
                            </div>
                            <p class="mb-1"><?= nl2br(htmlspecialchars($seg['notas'])) ?></p>
                            <?php if ($seg['fotos_actuales']): ?>
                                <small class="text-muted">Fotos adjuntas: <?= $seg['fotos_actuales'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Acciones -->
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="generarReporte.php?tipo=adopcion&id=<?= $id_solicitud ?>" class="btn btn-primary">
                <i class="fas fa-file-pdf me-2"></i> Generar PDF
            </a>
            
            <?php if ($solicitud['estado'] === 'Pendiente' || $solicitud['estado'] === 'En revisión'): ?>
                <a href="procesarAdopcion.php?id=<?= $id_solicitud ?>&accion=aprobar" 
                   class="btn btn-success"
                   onclick="return confirm('¿Estás seguro de aprobar esta solicitud?')">
                    <i class="fas fa-check me-2"></i> Aprobar
                </a>
                <a href="procesarAdopcion.php?id=<?= $id_solicitud ?>&accion=rechazar" 
                   class="btn btn-danger"
                   onclick="return confirm('¿Estás seguro de rechazar esta solicitud?')">
                    <i class="fas fa-times me-2"></i> Rechazar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>