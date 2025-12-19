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
$filtro_especie = $_GET['especie'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Consulta base con joins para obtener datos relacionados
$sql = "
    SELECT a.*, r.nombre_raza, d.nombre as nombre_departamento, d.provincia,
           (SELECT url_foto FROM fotos_animales WHERE id_animal = a.id_animal LIMIT 1) as foto_principal
    FROM animales a
    LEFT JOIN razas r ON a.id_raza = r.id_raza
    LEFT JOIN animal_departamento ad ON a.id_animal = ad.id_animal
    LEFT JOIN departamentos d ON ad.id_departamento = d.id_departamento
    WHERE a.activo = 1
";

// Aplicar filtros
if (!empty($filtro_especie)) {
    $sql .= " AND a.especie = '" . $conexion->real_escape_string($filtro_especie) . "'";
}

if (!empty($filtro_estado)) {
    $sql .= " AND a.estado = '" . $conexion->real_escape_string($filtro_estado) . "'";
}

if (!empty($filtro_busqueda)) {
    $sql .= " AND (a.nombre LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%' 
              OR r.nombre_raza LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%')";
}

// Ordenación
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_ingreso';
$direccion = isset($_GET['dir']) ? $_GET['dir'] : 'DESC';
$ordenes_permitidos = ['nombre', 'especie', 'estado', 'fecha_ingreso', 'edad_anios'];
$direcciones_permitidas = ['ASC', 'DESC'];

if (in_array($orden, $ordenes_permitidos) && in_array($direccion, $direcciones_permitidas)) {
    $sql .= " ORDER BY a.$orden $direccion";
} else {
    $sql .= " ORDER BY a.fecha_ingreso DESC";
}

// Consulta para paginación
$sql_total = $sql;
$sql .= " LIMIT $inicio, $por_pagina";

$animales = $conexion->query($sql);
$total_animales = $conexion->query($sql_total)->num_rows;
$total_paginas = ceil($total_animales / $por_pagina);

// Obtener conteos por filtros para los badges
$total_perros = $conexion->query("SELECT COUNT(*) FROM animales WHERE especie = 'Perro' AND activo = 1")->fetch_row()[0];
$total_gatos = $conexion->query("SELECT COUNT(*) FROM animales WHERE especie = 'Gato' AND activo = 1")->fetch_row()[0];
$total_disponibles = $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Disponible' AND activo = 1")->fetch_row()[0];
$total_adoptados = $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Adoptado' AND activo = 1")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Animales - Refugio de Mascotas</title>
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
            --light: #F8F9FA;
            --dark: #343A40;
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
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card card-main">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-paw me-2"></i> Todos los Animales
                    </h3>
                    <div>
                        <a href="../dashboardAdministrador.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-home me-2"></i> Volver a Inicio
                        </a>
                        <a href="../../controllers//crudAdministrador/nuevo_animal.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Nuevo Animal
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="get" class="row g-2">
                            <div class="col-md-5">
                                <select class="form-select" name="especie" onchange="this.form.submit()">
                                    <option value="">Todas las especies</option>
                                    <option value="Perro" <?= $filtro_especie == 'Perro' ? 'selected' : '' ?>>Perros</option>
                                    <option value="Gato" <?= $filtro_especie == 'Gato' ? 'selected' : '' ?>>Gatos</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <select class="form-select" name="estado" onchange="this.form.submit()">
                                    <option value="">Todos los estados</option>
                                    <option value="Disponible" <?= $filtro_estado == 'Disponible' ? 'selected' : '' ?>>Disponibles</option>
                                    <option value="En adopción" <?= $filtro_estado == 'En adopción' ? 'selected' : '' ?>>En adopción</option>
                                    <option value="Adoptado" <?= $filtro_estado == 'Adoptado' ? 'selected' : '' ?>>Adoptados</option>
                                    <option value="Reservado" <?= $filtro_estado == 'Reservado' ? 'selected' : '' ?>>Reservados</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="reset" class="btn btn-outline-secondary w-100" onclick="window.location.href='animales.php'">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="get">
                            <div class="input-group">
                                <input type="text" class="form-control" name="busqueda" placeholder="Buscar por nombre o raza..."
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
                    <span class="filter-badge badge bg-primary" onclick="window.location.href='animales.php'">
                        Todos <span class="badge bg-white text-primary"><?= $total_animales ?></span>
                    </span>
                    <span class="filter-badge badge bg-success" onclick="window.location.href='animales.php?especie=Perro'">
                        Perros <span class="badge bg-white text-success"><?= $total_perros ?></span>
                    </span>
                    <span class="filter-badge badge bg-pink" style="background-color: #FF6B8B;"
                        onclick="window.location.href='animales.php?especie=Gato'">
                        Gatos <span class="badge bg-white" style="color: #FF6B8B;"><?= $total_gatos ?></span>
                    </span>
                    <span class="filter-badge badge bg-info" onclick="window.location.href='animales.php?estado=Disponible'">
                        Disponibles <span class="badge bg-white text-info"><?= $total_disponibles ?></span>
                    </span>
                    <span class="filter-badge badge bg-danger" onclick="window.location.href='animales.php?estado=Adoptado'">
                        Adoptados <span class="badge bg-white text-danger"><?= $total_adoptados ?></span>
                    </span>
                </div>

                <!-- Tabla de animales -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th class="sortable <?= $orden == 'nombre' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('nombre', '<?= $orden == 'nombre' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Nombre
                                </th>
                                <th class="sortable <?= $orden == 'especie' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('especie', '<?= $orden == 'especie' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Especie/Raza
                                </th>
                                <th>Edad</th>
                                <th class="sortable <?= $orden == 'estado' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('estado', '<?= $orden == 'estado' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Estado
                                </th>
                                <th class="sortable <?= $orden == 'fecha_ingreso' && $direccion == 'ASC' ? 'asc' : '' ?>"
                                    onclick="ordenar('fecha_ingreso', '<?= $orden == 'fecha_ingreso' && $direccion == 'ASC' ? 'DESC' : 'ASC' ?>')">
                                    Fecha Ingreso
                                </th>
                                <th>Ubicación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($animales->num_rows > 0): ?>
                                <?php while ($animal = $animales->fetch_assoc()):
                                    $badge_class = $animal['estado'] == 'Disponible' ? 'badge-available' : ($animal['estado'] == 'Adoptado' ? 'badge-adopted' : ($animal['estado'] == 'Reservado' ? 'badge-reserved' : 'badge-pending'));
                                ?>
                                    <tr>
                                        <td>
                                            <?php
                                            // Mostrar foto por URL absoluta si existe (http/https), si no, buscar archivo local en /uploads/animales/
                                            if (!empty($animal['foto_principal'])):
                                                if (preg_match('/^https?:\/\//', $animal['foto_principal'])): ?>
                                                    <img src="<?= htmlspecialchars($animal['foto_principal']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>" class="animal-avatar">
                                                <?php else: ?>
                                                    <img src="../../views/<?= htmlspecialchars($animal['foto_principal']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>" class="animal-avatar">
                                                <?php endif;
                                            // Si no hay url, buscar si existe un archivo de foto (por ejemplo, campo 'foto_archivo')
                                            elseif (!empty($animal['foto_archivo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/../" . $animal['foto_archivo'])): ?>
                                                <img src="../../views/uploads/animales/<?= htmlspecialchars($animal['foto_archivo']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>" class="animal-avatar">
                                            <?php else: ?>
                                                <div class="animal-avatar bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-paw text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($animal['nombre']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($animal['especie']) ?></strong>
                                            <?php if ($animal['nombre_raza']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($animal['nombre_raza']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $animal['edad_anios'] ?> años</td>
                                        <td>
                                            <span class="badge-status <?= $badge_class ?>"><?= $animal['estado'] ?></span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($animal['fecha_ingreso'])) ?></td>
                                        <td>
                                            <?php if ($animal['nombre_departamento']): ?>
                                                <?= htmlspecialchars($animal['nombre_departamento']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($animal['provincia']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">No asignada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="../../controllers/crudAdministrador/editarAnimal.php?id=<?= $animal['id_animal'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../../controllers/crudAdministrador/detalleAnimal.php?id=<?= $animal['id_animal'] ?>" class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="../../controllers/crudAdministrador/eliminarAnimal.php" method="post" class="d-inline">
                                                    <input type="hidden" name="id_animal" value="<?= $animal['id_animal'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar este animal?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-paw fa-2x mb-3"></i>
                                            <h5>No se encontraron animales</h5>
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
            window.location.href = 'animales.php?' + urlParams.toString();
        }
    </script>
</body>

</html>