<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../config/checkSessionUsuario.php';
require_once __DIR__ . '../../../views/headerA.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}

$id_usuario = (int)$_GET['id'];

// Obtener información del usuario
$usuario = $conexion->query("   
    SELECT 
        u.*,
        a.*,
        (SELECT COUNT(*) FROM solicitudes_adopcion WHERE id_adoptante = a.id_adoptante) as total_solicitudes,
        (SELECT COUNT(*) FROM solicitudes_adopcion WHERE id_adoptante = a.id_adoptante AND estado = 'Aprobada') as solicitudes_aprobadas
    FROM usuarios u
    LEFT JOIN adoptantes a ON u.id_usuario = a.id_usuario
    WHERE u.id_usuario = $id_usuario
")->fetch_assoc();

if (!$usuario) {
    $_SESSION['mensaje_error'] = "Usuario no encontrado";
    header("Location: usuarios.php");
    exit();
}

// Obtener solicitudes del adoptante (si es adoptante)
$solicitudes = [];
if ($usuario['rol'] === 'adoptante' && isset($usuario['id_adoptante'])) {
    $solicitudes = $conexion->query("
        SELECT sa.*, a.nombre as nombre_animal, a.especie
        FROM solicitudes_adopcion sa
        JOIN animales a ON sa.id_animal = a.id_animal
        WHERE sa.id_adoptante = {$usuario['id_adoptante']}
        ORDER BY sa.fecha_solicitud DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Usuario - Refugio de Mascotas</title>
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

        .card-main {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 15px 15px 0 0 !important;
        }

        .page-title {
            color: var(--dark);
            font-weight: 600;
        }

        .user-avatar-lg {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .badge-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .badge-inactive {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .badge-admin {
            background-color: rgba(75, 123, 236, 0.1);
            color: var(--primary);
        }

        .badge-adoptante {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }

        .info-card {
            border-radius: 10px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px;
            margin-bottom: 15px;
            background-color: white;
        }

        .info-label {
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .animal-avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .solicitud-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .badge-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .badge-approved {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .badge-rejected {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .badge-review {
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info);
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card card-main">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-user me-2"></i> Detalle de Usuario
                    </h3>
                    <div>
                        <a href="usuarios.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i> Volver
                        </a>
                        <?php if ($usuario['rol'] == 'adoptante' && isset($usuario['id_adoptante'])): ?>
                            <a href="generarReporte.php?tipo=adoptante&id=<?= $usuario['id_usuario'] ?>" class="btn btn-primary">
                                <i class="fas fa-file-pdf me-2"></i> Generar PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex flex-column align-items-center mb-4">
                            <div class="user-avatar-lg mb-3">
                                <i class="fas fa-user fa-3x text-muted"></i>
                            </div>
                            <h4><?= htmlspecialchars($usuario['nombre_completo'] ?? 'Usuario') ?></h4>
                            <span class="badge-status <?= $usuario['rol'] == 'administrador' ? 'badge-admin' : 'badge-adoptante' ?>">
                                <?= ucfirst($usuario['rol']) ?>
                            </span>
                            <span class="badge-status <?= $usuario['activo'] ? 'badge-active' : 'badge-inactive' ?> mt-2">
                                <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>

                        <div class="info-card">
                            <h5 class="mb-4"><i class="fas fa-info-circle me-2"></i>Información Básica</h5>

                            <div class="info-label">Correo electrónico</div>
                            <div class="info-value"><?= htmlspecialchars($usuario['correo']) ?></div>

                            <div class="info-label">Fecha de registro</div>
                            <div class="info-value"><?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?></div>

                            <?php if ($usuario['rol'] == 'adoptante' && isset($usuario['nombre_completo'])): ?>
                                <div class="info-label">Teléfono</div>
                                <div class="info-value"><?= htmlspecialchars($usuario['telefono']) ?></div>

                                <div class="info-label">Documento de identidad</div>
                                <div class="info-value"><?= htmlspecialchars($usuario['documento_identidad']) ?></div>

                                <div class="info-label">Fecha de nacimiento</div>
                                <div class="info-value"><?= date('d/m/Y', strtotime($usuario['fecha_nacimiento'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <?php if ($usuario['rol'] == 'adoptante' && isset($usuario['id_adoptante'])): ?>
                            <div class="info-card">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0"><i class="fas fa-home me-2"></i>Información de Adopción</h5>
                                    <div>
                                        <span class="badge bg-primary"><?= $usuario['total_solicitudes'] ?> solicitudes</span>
                                        <span class="badge bg-success ms-2"><?= $usuario['solicitudes_aprobadas'] ?> aprobadas</span>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Dirección</div>
                                        <div class="info-value"><?= htmlspecialchars($usuario['direccion']) ?></div>

                                        <div class="info-label">Ocupación</div>
                                        <div class="info-value"><?= htmlspecialchars($usuario['ocupacion'] ?? 'No especificado') ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Tipo de vivienda</div>
                                        <div class="info-value"><?= htmlspecialchars($usuario['descripcion_vivienda'] ?? 'No especificado') ?></div>

                                        <div class="info-label">Tiempo en casa</div>
                                        <div class="info-value"><?= htmlspecialchars($usuario['tiempo_en_casa'] ?? 'No especificado') ?></div>
                                    </div>
                                </div>

                                <div class="info-label">Experiencia con animales</div>
                                <div class="info-value">
                                    <?= $usuario['experiencia_con_animales'] ? 'Sí tiene experiencia' : 'No tiene experiencia' ?>
                                </div>

                                <div class="info-label">Otros animales</div>
                                <div class="info-value"><?= htmlspecialchars($usuario['otros_animales'] ?? 'No tiene otros animales') ?></div>

                                <div class="info-label">Referencia personal</div>
                                <div class="info-value"><?= htmlspecialchars($usuario['referencia_personal'] ?? 'No especificada') ?></div>
                            </div>

                            <?php if (!empty($solicitudes)): ?>
                                <div class="info-card">
                                    <h5 class="mb-4"><i class="fas fa-clipboard-list me-2"></i>Últimas solicitudes</h5>

                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Animal</th>
                                                    <th>Fecha</th>
                                                    <th>Estado</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($solicitudes as $solicitud):
                                                    $badge_class = $solicitud['estado'] == 'Aprobada' ? 'badge-approved' : ($solicitud['estado'] == 'Rechazada' ? 'badge-rejected' : ($solicitud['estado'] == 'En revisión' ? 'badge-review' : 'badge-pending'));
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-2">
                                                                    <i class="fas fa-<?= strtolower($solicitud['especie']) == 'perro' ? 'dog' : 'cat' ?>"></i>
                                                                </div>
                                                                <div>
                                                                    <div><?= htmlspecialchars($solicitud['nombre_animal']) ?></div>
                                                                    <div class="small text-muted"><?= $solicitud['especie'] ?></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?= date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) ?></td>
                                                        <td>
                                                            <span class="solicitud-badge <?= $badge_class ?>">
                                                                <?= $solicitud['estado'] ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="detalleSolicitud.php?id=<?= $solicitud['id_solicitud'] ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-2">
                                        <a href="../../views/viewAdministrador/solicitudes.php?adoptante=<?= $usuario['id_adoptante'] ?>" class="btn btn-sm btn-link">
                                            Ver todas las solicitudes
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="info-card">
                                <h5 class="mb-4"><i class="fas fa-user-shield me-2"></i>Administrador</h5>
                                <p>Este usuario tiene privilegios de administrador en el sistema.</p>
                                <p>Los administradores pueden gestionar animales, solicitudes, usuarios y configuraciones del sistema.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>