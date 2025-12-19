<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../headerA.php';

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_especie = $_GET['especie'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Consulta base con joins para obtener datos relacionados
$sql = "
    SELECT 
        sa.*, 
        a.nombre as nombre_animal, a.especie, a.estado as estado_animal,
        ad.nombre_completo as adoptante, ad.telefono, ad.documento_identidad,
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
$total_perros = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion sa JOIN animales a ON sa.id_animal = a.id_animal WHERE a.especie = 'Perro'")->fetch_row()[0];
$total_gatos = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion sa JOIN animales a ON sa.id_animal = a.id_animal WHERE a.especie = 'Gato'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Adopciones - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card card-main">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-home me-2"></i> Gestión de Adopciones
                    </h3>
                    <div class="btn-group">
                        <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-export me-2"></i> Generar Reporte
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="generarReporteGlobal('pdf'); return false;"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                            <li><a class="dropdown-item" href="#" onclick="generarReporteGlobal('excel'); return false;"><i class="fas fa-file-excel me-2"></i>Excel</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="get" class="row g-2">
                            <div class="col-md-5">
                                <select class="form-select" name="estado" onchange="this.form.submit()">
                                    <option value="">Todos los estados</option>
                                    <option value="Pendiente" <?= $filtro_estado == 'Pendiente' ? 'selected' : '' ?>>Pendientes</option>
                                    <option value="Aprobada" <?= $filtro_estado == 'Aprobada' ? 'selected' : '' ?>>Aprobadas</option>
                                    <option value="Rechazada" <?= $filtro_estado == 'Rechazada' ? 'selected' : '' ?>>Rechazadas</option>
                                    <option value="En revisión" <?= $filtro_estado == 'En revisión' ? 'selected' : '' ?>>En revisión</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <select class="form-select" name="especie" onchange="this.form.submit()">
                                    <option value="">Todas las especies</option>
                                    <option value="Perro" <?= $filtro_especie == 'Perro' ? 'selected' : '' ?>>Perros</option>
                                    <option value="Gato" <?= $filtro_especie == 'Gato' ? 'selected' : '' ?>>Gatos</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="reset" class="btn btn-outline-secondary w-100" onclick="window.location.href='adopciones.php'">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="get">
                            <div class="input-group">
                                <input type="text" class="form-control" name="busqueda" placeholder="Buscar por animal o adoptante..."
                                    value="<?= htmlspecialchars($filtro_busqueda) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Badges de filtros rápidos -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="filter-badge badge bg-primary" onclick="window.location.href='adopciones.php'">
                        Todas <span class="badge bg-white text-primary"><?= $total_solicitudes ?></span>
                    </span>
                    <span class="filter-badge badge bg-warning" onclick="window.location.href='adopciones.php?estado=Pendiente'">
                        Pendientes <span class="badge bg-white text-warning"><?= $total_pendientes ?></span>
                    </span>
                    <span class="filter-badge badge bg-success" onclick="window.location.href='adopciones.php?estado=Aprobada'">
                        Aprobadas <span class="badge bg-white text-success"><?= $total_aprobadas ?></span>
                    </span>
                    <span class="filter-badge badge bg-danger" onclick="window.location.href='adopciones.php?estado=Rechazada'">
                        Rechazadas <span class="badge bg-white text-danger"><?= $total_rechazadas ?></span>
                    </span>
                    <span class="filter-badge badge bg-pink" style="background-color: var(--pink);"
                        onclick="window.location.href='adopciones.php?especie=Gato'">
                        Gatos <span class="badge bg-white" style="color: var(--pink);"><?= $total_gatos ?></span>
                    </span>
                    <span class="filter-badge badge bg-teal" style="background-color: var(--teal);"
                        onclick="window.location.href='adopciones.php?especie=Perro'">
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
                                            <?php if ($solicitud['estado_animal']): ?>
                                                <div class="text-muted small mt-1">
                                                    Animal: <?= $solicitud['estado_animal'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 action-buttons">
                                                <a href="../../controllers/crudAdministrador/detalleAdopcion.php?id=<?= $solicitud['id_solicitud'] ?>"
                                                    class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($solicitud['estado'] === 'Pendiente' || $solicitud['estado'] === 'En revisión'): ?>
                                                    <a href="../../controllers/crudAdministrador/procesarAdopcion.php?id=<?= $solicitud['id_solicitud'] ?>&accion=aprobar"
                                                        class="btn btn-sm btn-outline-success" title="Aprobar"
                                                        onclick="return confirm('¿Estás seguro de aprobar esta solicitud?')">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="../../controllers/crudAdministrador/procesarAdopcion.php?id=<?= $solicitud['id_solicitud'] ?>&accion=rechazar"
                                                        class="btn btn-sm btn-outline-danger" title="Rechazar"
                                                        onclick="return confirm('¿Estás seguro de rechazar esta solicitud?')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="../../controllers/crudAdministrador/generarReporte.php?tipo=adopcion&id=<?= $solicitud['id_solicitud'] ?>"
                                                    class="btn btn-sm btn-outline-info" title="Generar PDF" target="_blank">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-home fa-2x mb-3"></i>
                                            <h5>No se encontraron solicitudes de adopción</h5>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        function generarReporteGlobal(formato) {
            const params = new URLSearchParams(window.location.search);
            // asegurar tipo y formato
            params.set('tipo', 'adopciones');
            params.set('formato', formato);
            // abrir en nueva pestaña para no perder la vista actual
            window.open('../../controllers/crudAdministrador/generarReporte.php?' + params.toString(), '_blank');
        }

        function ordenar(columna, direccion) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('orden', columna);
            urlParams.set('dir', direccion);
            window.location.href = 'adopciones.php?' + urlParams.toString();
        }
    </script>
</body>

</html>