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
$filtro_rol = $_GET['rol'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Consulta base con joins para obtener datos relacionados
$sql = "
    SELECT 
        u.*,
        a.nombre_completo as nombre_adoptante,
        a.telefono,
        a.documento_identidad
    FROM usuarios u
    LEFT JOIN adoptantes a ON u.id_usuario = a.id_usuario
    WHERE 1=1
";

// Aplicar filtros
if (!empty($filtro_rol)) {
    $sql .= " AND u.rol = '" . $conexion->real_escape_string($filtro_rol) . "'";
}

if (!empty($filtro_estado)) {
    $sql .= " AND u.activo = " . (int)$filtro_estado;
}

if (!empty($filtro_busqueda)) {
    $sql .= " AND (u.correo LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%' 
              OR a.nombre_completo LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%'
              OR a.documento_identidad LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%')";
}

// Ordenación
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_registro';
$direccion = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';
$ordenes_permitidos = ['correo', 'rol', 'activo', 'fecha_registro', 'nombre_adoptante'];
$direcciones_permitidas = ['ASC', 'DESC'];

if (in_array($orden, $ordenes_permitidos) && in_array($direccion, $direcciones_permitidas)) {
    $sql .= " ORDER BY $orden $direccion";
} else {
    $sql .= " ORDER BY u.fecha_registro DESC";
}

// Consulta para paginación
$sql_total = $sql;
$sql .= " LIMIT $inicio, $por_pagina";

$usuarios = $conexion->query($sql);
$total_usuarios = $conexion->query($sql_total)->num_rows;
$total_paginas = ceil($total_usuarios / $por_pagina);

// Obtener conteos por filtros para los badges
$total_activos = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1")->fetch_row()[0];
$total_inactivos = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE activo = 0")->fetch_row()[0];
$total_administradores = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador'")->fetch_row()[0];
$total_adoptantes = $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'adoptante'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Refugio de Mascotas</title>
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

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
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

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #28a745;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(26px);
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card card-main">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-users me-2"></i> Gestión de Usuarios
                    </h3>
                    <a href="../../controllers/crudAdministrador/generarReporte.php?tipo=usuarios" class="btn btn-primary">
                        <i class="fas fa-file-export me-2"></i> Generar Reporte
                    </a>
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
                        <div class="col-md-4">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" id="rol" name="rol">
                                <option value="">Todos los roles</option>
                                <option value="administrador" <?= $filtro_rol == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                <option value="adoptante" <?= $filtro_rol == 'adoptante' ? 'selected' : '' ?>>Adoptante</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="1" <?= $filtro_estado === '1' ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= $filtro_estado === '0' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="busqueda" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="busqueda" name="busqueda"
                                placeholder="Buscar por correo, nombre o documento..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter me-2"></i> Filtrar
                            </button>
                            <a href="usuarios.php" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Badges de filtros rápidos -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="filter-badge badge bg-primary" onclick="window.location.href='usuarios.php'">
                        Todos <span class="badge bg-white text-primary"><?= $total_usuarios ?></span>
                    </span>
                    <span class="filter-badge badge bg-success" onclick="window.location.href='usuarios.php?estado=1'">
                        Activos <span class="badge bg-white text-success"><?= $total_activos ?></span>
                    </span>
                    <span class="filter-badge badge bg-danger" onclick="window.location.href='usuarios.php?estado=0'">
                        Inactivos <span class="badge bg-white text-danger"><?= $total_inactivos ?></span>
                    </span>
                    <span class="filter-badge badge bg-admin" style="background-color: var(--primary);"
                        onclick="window.location.href='usuarios.php?rol=administrador'">
                        Administradores <span class="badge bg-white" style="color: var(--primary);"><?= $total_administradores ?></span>
                    </span>
                    <span class="filter-badge badge bg-adoptante" style="background-color: var(--info);"
                        onclick="window.location.href='usuarios.php?rol=adoptante'">
                        Adoptantes <span class="badge bg-white" style="color: var(--info);"><?= $total_adoptantes ?></span>
                    </span>
                </div>

                <!-- Tabla de usuarios -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="sortable <?= $orden == 'correo' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('correo', '<?= $orden == 'correo' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Correo
                                </th>
                                <th class="sortable <?= $orden == 'nombre_adoptante' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('nombre_adoptante', '<?= $orden == 'nombre_adoptante' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Nombre
                                </th>
                                <th class="sortable <?= $orden == 'rol' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('rol', '<?= $orden == 'rol' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Rol
                                </th>
                                <th class="sortable <?= $orden == 'activo' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('activo', '<?= $orden == 'activo' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Estado
                                </th>
                                <th class="sortable <?= $orden == 'fecha_registro' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('fecha_registro', '<?= $orden == 'fecha_registro' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Fecha Registro
                                </th>
                                <!-- Quitar columna Acciones -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($usuarios->num_rows > 0): ?>
                                <?php while ($usuario = $usuarios->fetch_assoc()):
                                    $badge_rol_class = $usuario['rol'] == 'administrador' ? 'badge-admin' : 'badge-adoptante';
                                    $badge_estado_class = $usuario['activo'] ? 'badge-active' : 'badge-inactive';
                                ?>
                                    <tr>
                                        <td>
                                            <div class="user-avatar">
                                                <i class="fas fa-user text-muted"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($usuario['correo']) ?></strong>
                                        </td>
                                        <td>
                                            <?= $usuario['nombre_adoptante'] ? htmlspecialchars($usuario['nombre_adoptante']) : '<span class="text-muted">N/A</span>' ?>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $badge_rol_class ?>">
                                                <?= ucfirst($usuario['rol']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge-status <?= $badge_estado_class ?>">
                                                <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?>
                                        </td>
                                        <!-- Quitar celda de acciones -->
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-users fa-2x mb-3"></i>
                                            <h5>No se encontraron usuarios</h5>
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
        function ordenar(columna, direccion) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('orden', columna);
            urlParams.set('dir', direccion);
            window.location.href = 'usuarios.php?' + urlParams.toString();
        }
    </script>
</body>

</html>