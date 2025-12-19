<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../headerA.php';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aprobar_solicitud'])) {
        procesarSolicitud($conexion, $_POST['id_solicitud'], 'Aprobada');
    } elseif (isset($_POST['rechazar_solicitud'])) {
        procesarSolicitud($conexion, $_POST['id_solicitud'], 'Rechazada');
    } elseif (isset($_POST['en_revision_solicitud'])) {
        procesarSolicitud($conexion, $_POST['id_solicitud'], 'En revisión');
    }
}

function procesarSolicitud($conexion, $id_solicitud, $estado)
{
    $id_solicitud = (int)$id_solicitud;

    try {
        $conexion->begin_transaction();

        // Obtener información actual de la solicitud
        $solicitud = $conexion->query("
            SELECT sa.*, a.id_animal, a.estado as estado_animal
            FROM solicitudes_adopcion sa
            JOIN animales a ON sa.id_animal = a.id_animal
            WHERE sa.id_solicitud = $id_solicitud
        ")->fetch_assoc();

        if (!$solicitud) {
            throw new Exception("Solicitud no encontrada");
        }

        // Actualizar estado de la solicitud
        $conexion->query("
            UPDATE solicitudes_adopcion 
            SET estado = '$estado', 
                fecha_respuesta = NOW(),
                notas_admin = '" . ($_POST['notas_admin'] ?? '') . "'
            WHERE id_solicitud = $id_solicitud
        ");

        // Si se aprueba, actualizar estado del animal y rechazar otras solicitudes
        if ($estado === 'Aprobada') {
            $conexion->query("
                UPDATE animales 
                SET estado = 'Adoptado' 
                WHERE id_animal = {$solicitud['id_animal']}
            ");

            $conexion->query("
                UPDATE solicitudes_adopcion 
                SET estado = 'Rechazada', 
                    fecha_respuesta = NOW(),
                    notas_admin = CONCAT('Rechazada automáticamente porque el animal fue adoptado (Solicitud #$id_solicitud)')
                WHERE id_animal = {$solicitud['id_animal']} 
                AND estado IN ('Pendiente', 'En revisión')
                AND id_solicitud != $id_solicitud
            ");
        }

        // Si se rechaza y el animal estaba en proceso de adopción, volver a disponible
        if ($estado === 'Rechazada' && $solicitud['estado_animal'] === 'En adopción') {
            $conexion->query("
                UPDATE animales 
                SET estado = 'Disponible' 
                WHERE id_animal = {$solicitud['id_animal']}
            ");
        }

        $conexion->commit();
        $_SESSION['mensaje_exito'] = "Solicitud actualizada correctamente";
    } catch (Exception $e) {
        $conexion->rollback();
        $_SESSION['mensaje_error'] = "Error al procesar la solicitud: " . $e->getMessage();
    }

    header("Location: solicitudes.php");
    exit();
}

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_especie = $_GET['especie'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';

// Consulta base con joins para obtener datos relacionados
$sql = "
    SELECT 
        sa.*, 
        a.nombre as nombre_animal, a.especie, a.id_animal, a.estado as estado_animal,
        ad.nombre_completo as adoptante, ad.telefono,
        (SELECT url_foto FROM fotos_animales WHERE id_animal = a.id_animal LIMIT 1) as foto_animal
    FROM solicitudes_adopcion sa
    JOIN animales a ON sa.id_animal = a.id_animal
    JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
    WHERE 1=1
";

// Aplicar filtros
if (!empty($filtro_estado)) {
    $sql .= " AND sa.estado = '" . $conexion->real_escape_string($filtro_estado) . "'";
}

if (!empty($filtro_especie)) {
    $sql .= " AND a.especie = '" . $conexion->real_escape_string($filtro_especie) . "'";
}

if (!empty($filtro_busqueda)) {
    $sql .= " AND (a.nombre LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%' 
              OR ad.nombre_completo LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%')";
}

if (!empty($filtro_fecha_desde)) {
    $sql .= " AND sa.fecha_solicitud >= '" . $conexion->real_escape_string($filtro_fecha_desde) . "'";
}

if (!empty($filtro_fecha_hasta)) {
    $sql .= " AND sa.fecha_solicitud <= '" . $conexion->real_escape_string($filtro_fecha_hasta) . " 23:59:59'";
}

// Ordenación
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_solicitud';
$direccion = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';
$ordenes_permitidos = ['nombre_animal', 'adoptante', 'estado', 'fecha_solicitud', 'fecha_respuesta'];
$direcciones_permitidas = ['ASC', 'DESC'];

if (in_array($orden, $ordenes_permitidos) && in_array($direccion, $direcciones_permitidas)) {
    $sql .= " ORDER BY $orden $direccion";
} else {
    $sql .= " ORDER BY sa.fecha_solicitud DESC";
}

// Consulta para paginación
$sql_total = $sql;
$sql .= " LIMIT $inicio, $por_pagina";

$solicitudes = $conexion->query($sql);
$total_solicitudes = $conexion->query($sql_total)->num_rows;
$total_paginas = ceil($total_solicitudes / $por_pagina);

// Obtener conteos por filtros para los badges
$total_pendientes = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'Pendiente'")->fetch_row()[0];
$total_aprobadas = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'Aprobada'")->fetch_row()[0];
$total_rechazadas = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'Rechazada'")->fetch_row()[0];
$total_revision = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'En revisión'")->fetch_row()[0];
$total_perros = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion sa JOIN animales a ON sa.id_animal = a.id_animal WHERE a.especie = 'Perro'")->fetch_row()[0];
$total_gatos = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion sa JOIN animales a ON sa.id_animal = a.id_animal WHERE a.especie = 'Gato'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Solicitudes - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary: #4B7BEC;
            --secondary: #6C757D;
            --success: #28A745;
            --info: #17A2B8;
            --warning: #FFC107;
            --danger: #DC3545;
            --pink: #FF6B8B;
            --teal: #20C997;
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

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
        }

        .animal-avatar {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
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

        .filter-badge {
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .pagination .page-link {
            color: var(--primary);
        }

        .sortable {
            cursor: pointer;
            position: relative;
            padding-right: 20px !important;
        }

        .sortable:after {
            content: "↓";
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            opacity: 0.5;
        }

        .sortable.asc:after {
            content: "↑";
        }

        .table-hover tbody tr:hover {
            background-color: rgba(75, 123, 236, 0.05);
        }

        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 0.875rem;
        }

        .filter-section {
            background-color: rgba(75, 123, 236, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .form-control-date {
            max-width: 200px;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card card-main">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-clipboard-list me-2"></i> Gestión de Solicitudes
                    </h3>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['mensaje_exito'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['mensaje_exito'] ?>
                        <?php unset($_SESSION['mensaje_exito']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['mensaje_error'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['mensaje_error'] ?>
                        <?php unset($_SESSION['mensaje_error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Filtros avanzados -->
                <div class="filter-section">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="Pendiente" <?= $filtro_estado == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="En revisión" <?= $filtro_estado == 'En revisión' ? 'selected' : '' ?>>En revisión</option>
                                <option value="Aprobada" <?= $filtro_estado == 'Aprobada' ? 'selected' : '' ?>>Aprobada</option>
                                <option value="Rechazada" <?= $filtro_estado == 'Rechazada' ? 'selected' : '' ?>>Rechazada</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="especie" class="form-label">Especie</label>
                            <select class="form-select" id="especie" name="especie">
                                <option value="">Todas las especies</option>
                                <option value="Perro" <?= $filtro_especie == 'Perro' ? 'selected' : '' ?>>Perro</option>
                                <option value="Gato" <?= $filtro_especie == 'Gato' ? 'selected' : '' ?>>Gato</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_desde" class="form-label">Fecha desde</label>
                            <input type="text" class="form-control form-control-date" id="fecha_desde" name="fecha_desde"
                                placeholder="Seleccionar fecha" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                            <input type="text" class="form-control form-control-date" id="fecha_hasta" name="fecha_hasta"
                                placeholder="Seleccionar fecha" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                        </div>
                        <div class="col-md-8">
                            <label for="busqueda" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="busqueda" name="busqueda"
                                placeholder="Buscar por animal o adoptante..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-2"></i> Filtrar
                            </button>
                            <a href="solicitudes.php" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Badges de filtros rápidos -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="filter-badge badge bg-primary" onclick="window.location.href='solicitudes.php'">
                        Todas <span class="badge bg-white text-primary"><?= $total_solicitudes ?></span>
                    </span>
                    <span class="filter-badge badge bg-warning" onclick="window.location.href='solicitudes.php?estado=Pendiente'">
                        Pendientes <span class="badge bg-white text-warning"><?= $total_pendientes ?></span>
                    </span>
                    <span class="filter-badge badge bg-info" onclick="window.location.href='solicitudes.php?estado=En revisión'">
                        En revisión <span class="badge bg-white text-info"><?= $total_revision ?></span>
                    </span>
                    <span class="filter-badge badge bg-success" onclick="window.location.href='solicitudes.php?estado=Aprobada'">
                        Aprobadas <span class="badge bg-white text-success"><?= $total_aprobadas ?></span>
                    </span>
                    <span class="filter-badge badge bg-danger" onclick="window.location.href='solicitudes.php?estado=Rechazada'">
                        Rechazadas <span class="badge bg-white text-danger"><?= $total_rechazadas ?></span>
                    </span>
                    <span class="filter-badge badge bg-pink" style="background-color: var(--pink);"
                        onclick="window.location.href='solicitudes.php?especie=Gato'">
                        Gatos <span class="badge bg-white" style="color: var(--pink);"><?= $total_gatos ?></span>
                    </span>
                    <span class="filter-badge badge bg-teal" style="background-color: var(--teal);"
                        onclick="window.location.href='solicitudes.php?especie=Perro'">
                        Perros <span class="badge bg-white" style="color: var(--teal);"><?= $total_perros ?></span>
                    </span>
                </div>

                <!-- Tabla de solicitudes -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Animal</th>
                                <th class="sortable <?= $orden == 'nombre_animal' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('nombre_animal', '<?= $orden == 'nombre_animal' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Nombre
                                </th>
                                <th class="sortable <?= $orden == 'adoptante' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('adoptante', '<?= $orden == 'adoptante' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Adoptante
                                </th>
                                <th class="sortable <?= $orden == 'fecha_solicitud' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('fecha_solicitud', '<?= $orden == 'fecha_solicitud' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Fecha Solicitud
                                </th>
                                <th class="sortable <?= $orden == 'estado' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('estado', '<?= $orden == 'estado' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Estado
                                </th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($solicitudes->num_rows > 0): ?>
                                <?php while ($solicitud = $solicitudes->fetch_assoc()):
                                    $badge_class = $solicitud['estado'] == 'Aprobada' ? 'badge-approved' : ($solicitud['estado'] == 'Rechazada' ? 'badge-rejected' : ($solicitud['estado'] == 'En revisión' ? 'badge-review' : 'badge-pending'));
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($solicitud['foto_animal']): ?>
                                                <img src="<?= htmlspecialchars($solicitud['foto_animal']) ?>"
                                                    alt="<?= htmlspecialchars($solicitud['nombre_animal']) ?>"
                                                    class="animal-avatar">
                                            <?php else: ?>
                                                <div class="animal-avatar bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-paw text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($solicitud['nombre_animal']) ?></strong>
                                            <div class="text-muted small"><?= $solicitud['especie'] ?></div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($solicitud['adoptante']) ?></strong>
                                            <div class="text-muted small"><?= $solicitud['telefono'] ?></div>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) ?>
                                            <?php if ($solicitud['fecha_respuesta']): ?>
                                                <div class="text-muted small">
                                                    Respuesta: <?= date('d/m/Y', strtotime($solicitud['fecha_respuesta'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $badge_class ?>"><?= $solicitud['estado'] ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 action-buttons">
                                                <a href="detalleSolicitud.php?id=<?= $solicitud['id_solicitud'] ?>"
                                                    class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <?php if ($solicitud['estado'] === 'Pendiente' || $solicitud['estado'] === 'En revisión'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                        title="Aprobar" data-bs-toggle="modal"
                                                        data-bs-target="#aprobarModal"
                                                        data-id="<?= $solicitud['id_solicitud'] ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>

                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        title="Rechazar" data-bs-toggle="modal"
                                                        data-bs-target="#rechazarModal"
                                                        data-id="<?= $solicitud['id_solicitud'] ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>

                                                    <button type="button" class="btn btn-sm btn-outline-info"
                                                        title="Marcar en revisión" data-bs-toggle="modal"
                                                        data-bs-target="#revisionModal"
                                                        data-id="<?= $solicitud['id_solicitud'] ?>">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-clipboard-list fa-2x mb-3"></i>
                                            <h5>No se encontraron solicitudes</h5>
                                            <p>Intenta con otros filtros de búsqueda</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($pagina > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($pagina < $total_paginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Aprobar Solicitud -->
    <div class="modal fade" id="aprobarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Aprobar Solicitud</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_solicitud" id="aprobar_id_solicitud">
                        <p>¿Estás seguro que deseas aprobar esta solicitud de adopción?</p>
                        <div class="mb-3">
                            <label for="notas_aprobar" class="form-label">Notas adicionales (opcional)</label>
                            <textarea class="form-control" id="notas_aprobar" name="notas_admin" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="aprobar_solicitud" class="btn btn-success">Aprobar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Rechazar Solicitud -->
    <div class="modal fade" id="rechazarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Rechazar Solicitud</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_solicitud" id="rechazar_id_solicitud">
                        <p>¿Estás seguro que deseas rechazar esta solicitud de adopción?</p>
                        <div class="mb-3">
                            <label for="notas_rechazar" class="form-label">Motivo del rechazo</label>
                            <textarea class="form-control" id="notas_rechazar" name="notas_admin" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="rechazar_solicitud" class="btn btn-danger">Rechazar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Marcar en Revisión -->
    <div class="modal fade" id="revisionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Marcar en Revisión</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_solicitud" id="revision_id_solicitud">
                        <p>¿Estás seguro que deseas marcar esta solicitud como "En revisión"?</p>
                        <div class="mb-3">
                            <label for="notas_revision" class="form-label">Notas adicionales (opcional)</label>
                            <textarea class="form-control" id="notas_revision" name="notas_admin" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="en_revision_solicitud" class="btn btn-info">Marcar en revisión</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script>
        // Inicializar datepickers
        flatpickr("#fecha_desde", {
            dateFormat: "Y-m-d",
            locale: "es",
            maxDate: "today"
        });

        flatpickr("#fecha_hasta", {
            dateFormat: "Y-m-d",
            locale: "es",
            maxDate: "today"
        });

        // Configurar modales
        const aprobarModal = document.getElementById('aprobarModal');
        if (aprobarModal) {
            aprobarModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const idSolicitud = button.getAttribute('data-id');
                document.getElementById('aprobar_id_solicitud').value = idSolicitud;
            });
        }

        const rechazarModal = document.getElementById('rechazarModal');
        if (rechazarModal) {
            rechazarModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const idSolicitud = button.getAttribute('data-id');
                document.getElementById('rechazar_id_solicitud').value = idSolicitud;
            });
        }

        const revisionModal = document.getElementById('revisionModal');
        if (revisionModal) {
            revisionModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const idSolicitud = button.getAttribute('data-id');
                document.getElementById('revision_id_solicitud').value = idSolicitud;
            });
        }

        function ordenar(columna, direccion) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('orden', columna);
            urlParams.set('dir', direccion);
            window.location.href = 'solicitudes.php?' + urlParams.toString();
        }
    </script>
</body>

</html>