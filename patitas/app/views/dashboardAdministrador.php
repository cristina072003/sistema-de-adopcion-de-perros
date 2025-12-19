<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/checkSessionUsuario.php';
require_once __DIR__ . '/headerA.php';
// Consultas para estadísticas principales
$total_animales = $conexion->query("SELECT COUNT(*) FROM animales WHERE activo = 1")->fetch_row()[0];
$animales_disponibles = $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Disponible' AND activo = 1")->fetch_row()[0];
$total_adopciones = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'Aprobada'")->fetch_row()[0];
$solicitudes_pendientes = $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'Pendiente'")->fetch_row()[0];
$total_usuarios = $conexion->query("SELECT COUNT(*) FROM usuarios")->fetch_row()[0];
$total_adoptantes = $conexion->query("SELECT COUNT(*) FROM adoptantes")->fetch_row()[0];

// Consultas para datos adicionales
$animales_por_especie = $conexion->query("SELECT especie, COUNT(*) as total FROM animales WHERE activo = 1 GROUP BY especie")->fetch_all(MYSQLI_ASSOC);
$ultimas_adopciones = $conexion->query("
    SELECT sa.*, a.nombre as nombre_animal, ad.nombre_completo as adoptante 
    FROM solicitudes_adopcion sa
    JOIN animales a ON sa.id_animal = a.id_animal
    JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
    WHERE sa.estado = 'Aprobada'
    ORDER BY sa.fecha_respuesta DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$solicitudes_recientes = $conexion->query("
    SELECT sa.*, a.nombre as nombre_animal, ad.nombre_completo as adoptante 
    FROM solicitudes_adopcion sa
    JOIN animales a ON sa.id_animal = a.id_animal
    JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
    WHERE sa.estado = 'Pendiente'
    ORDER BY sa.fecha_solicitud DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refugio de Mascotas - Panel de Administración</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="/patitas/public/css/Dashboard.css">
</head>

<body>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar py-3">
                <div class="admin-sidebar p-3">
                    <div class="text-center mb-4">
                        <img src="https://randomuser.me/api/portraits/women/43.jpg" alt="Usuario" class="rounded-circle mb-2" width="80">
                        <h5 class="mb-1"><?= $_SESSION['usuario']['nombre'] ?? 'Administrador' ?></h5>
                        <small class="text-muted">Administrador</small>
                    </div>

                    <h6 class="sidebar-title mb-3">MENÚ PRINCIPAL</h6>
                    <a href="<?php echo url('/dashboard-administrador'); ?>" class="sidebar-link active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="<?php echo url('/admin/animales'); ?>" class="sidebar-link">
                        <i class="fas fa-paw"></i> Animales
                    </a>
                    <a href="<?php echo url('/admin/adopciones'); ?>" class="sidebar-link">
                        <i class="fas fa-home"></i> Adopciones
                    </a>
                    <a href="<?php echo url('/admin/solicitudes'); ?>" class="sidebar-link">
                        <i class="fas fa-clipboard-list"></i> Solicitudes
                    </a>
                    <a href="<?php echo url('/admin/usuarios'); ?>" class="sidebar-link">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                    <a href="<?php echo url('/admin/reportes'); ?>" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>

                    <h6 class="sidebar-title mt-4 mb-3 text-black">CONFIGURACIÓN</h6>

                    <a href="<?php echo url('/admin/configuracion'); ?>" class="sidebar-link">
                        <i class="fas fa-cog"></i> Configuración
                    </a>
                    <a href="<?php echo url('/logout'); ?>" class="sidebar-link">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4 py-4">
                <!-- Header -->
                <div class="dashboard-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0"><i class="fas fa-tachometer-alt text-primary me-2"></i> Panel de Administración</h2>
                            <p class="text-muted mb-0">Resumen general del sistema</p>
                        </div>
                        <div>
                            <span class="badge bg-primary"><?= date('d/m/Y') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row">
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card stat-card-1 p-4">
                            <h5 class="text-uppercase text-white-50 mb-0">Animales</h5>
                            <h2 class="mb-3"><?= $total_animales ?></h2>
                            <p class="mb-0 text-white-50">En el refugio</p>
                            <i class="fas fa-paw stat-card-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card stat-card-2 p-4">
                            <h5 class="text-uppercase text-white-50 mb-0">Disponibles</h5>
                            <h2 class="mb-3"><?= $animales_disponibles ?></h2>
                            <p class="mb-0 text-white-50">Para adopción</p>
                            <i class="fas fa-heart stat-card-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card stat-card-3 p-4">
                            <h5 class="text-uppercase text-white-50 mb-0">Adopciones</h5>
                            <h2 class="mb-3"><?= $total_adopciones ?></h2>
                            <p class="mb-0 text-white-50">Realizadas</p>
                            <i class="fas fa-home stat-card-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card stat-card-4 p-4">
                            <h5 class="text-uppercase text-white-50 mb-0">Pendientes</h5>
                            <h2 class="mb-3"><?= $solicitudes_pendientes ?></h2>
                            <p class="mb-0 text-white-50">Solicitudes</p>
                            <i class="fas fa-clock stat-card-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card stat-card-5 p-4">
                            <h5 class="text-uppercase text-white-50 mb-0">Usuarios</h5>
                            <h2 class="mb-3"><?= $total_usuarios ?></h2>
                            <p class="mb-0 text-white-50">Registrados</p>
                            <i class="fas fa-users stat-card-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card stat-card-6 p-4">
                            <h5 class="text-uppercase text-white-50 mb-0">Adoptantes</h5>
                            <h2 class="mb-3"><?= $total_adoptantes ?></h2>
                            <p class="mb-0 text-white-50">En sistema</p>
                            <i class="fas fa-user-friends stat-card-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Contenido principal -->
                <div class="row mt-4">
                    <!-- Gráficos y estadísticas -->
                    <div class="col-lg-8">
                        <div class="card-admin">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#estadisticas">Estadísticas</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#adopciones">Últimas Adopciones</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#solicitudes">Solicitudes Recientes</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="estadisticas">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5 class="title-section"><i class="fas fa-chart-pie me-2"></i> Distribución por Especie</h5>
                                                <canvas id="speciesChart" height="200"></canvas>
                                            </div>
                                            <div class="col-md-6">
                                                <h5 class="title-section"><i class="fas fa-chart-bar me-2"></i> Estado de Animales</h5>
                                                <canvas id="statusChart" height="200"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="adopciones">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Animal</th>
                                                        <th>Adoptante</th>
                                                        <th>Fecha</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ultimas_adopciones as $adopcion): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($adopcion['nombre_animal']) ?></td>
                                                            <td><?= htmlspecialchars($adopcion['adoptante']) ?></td>
                                                            <td><?= date('d/m/Y', strtotime($adopcion['fecha_respuesta'])) ?></td>
                                                            <td><span class="badge bg-success">Aprobada</span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="solicitudes">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Animal</th>
                                                        <th>Adoptante</th>
                                                        <th>Fecha</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($solicitudes_recientes as $solicitud): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($solicitud['nombre_animal']) ?></td>
                                                            <td><?= htmlspecialchars($solicitud['adoptante']) ?></td>
                                                            <td><?= date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) ?></td>
                                                            <td>
                                                                <a href="solicitud_detalle.php?id=<?= $solicitud['id_solicitud'] ?>" class="btn btn-sm btn-primary">Revisar</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Últimos ingresos y acciones rápidas -->
                    <div class="col-lg-4">
                        <div class="card-admin mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i> Últimos Ingresos</h5>
                                <a href="<?php echo url('/admin/animales'); ?>" class="btn btn-sm btn-primary">Ver todos</a>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <?php
                                    $animales_recientes = $conexion->query("
                                        SELECT a.*, r.nombre_raza, f.url_foto 
                                        FROM animales a 
                                        LEFT JOIN razas r ON a.id_raza = r.id_raza 
                                        LEFT JOIN fotos_animales f ON a.id_animal = f.id_animal AND f.id_foto = (
                                            SELECT MIN(id_foto) FROM fotos_animales WHERE id_animal = a.id_animal
                                        )
                                        WHERE a.activo = 1 
                                        ORDER BY a.fecha_ingreso DESC 
                                        LIMIT 5
                                    ");

                                    while ($animal = $animales_recientes->fetch_assoc()):
                                        $badge_class = $animal['estado'] == 'Disponible' ? 'badge-available' : ($animal['estado'] == 'Adoptado' ? 'badge-adopted' : 'badge-pending');
                                    ?>
                                        <div class="list-group-item border-0 py-3 px-0">
                                            <div class="d-flex align-items-center">
                                                <?php
                                                // Mostrar foto por URL absoluta si existe (http/https), si no, buscar archivo local en /views/uploads/animales
                                                if (!empty($animal['url_foto'])):
                                                    if (preg_match('/^https?:\/\//', $animal['url_foto'])): ?>
                                                        <img src="<?= htmlspecialchars($animal['url_foto']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>" class="animal-avatar me-3">
                                                    <?php else: ?>
                                                        <img src="views/<?= htmlspecialchars($animal['url_foto']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>" class="animal-avatar me-3">
                                                    <?php endif;
                                                // Si no hay url_foto, buscar si existe un archivo de foto (por ejemplo, campo 'foto_archivo')
                                                elseif (!empty($animal['foto_archivo']) && file_exists(__DIR__ . "/uploads" . $animal['foto_archivo'])): ?>
                                                    <img src="uploads/animales/<?= htmlspecialchars($animal['foto_archivo']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>" class="animal-avatar me-3">
                                                <?php else: ?>
                                                    <div class="animal-avatar bg-light d-flex align-items-center justify-content-center me-3">
                                                        <i class="fas fa-paw text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($animal['nombre']) ?></h6>
                                                    <small class="text-muted"><?= $animal['especie'] ?> • <?= $animal['edad_anios'] ?> años</small>
                                                </div>
                                                <span class="badge-status <?= $badge_class ?>"><?= $animal['estado'] ?></span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-admin">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="<?php echo url('/admin/nuevo-animal'); ?>" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i> Registrar Animal
                                    </a>
                                    <a href="<?php echo url('/admin/crear-usuario'); ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-user-plus me-2"></i> Crear Usuario
                                    </a>
                                    <a href="<?php echo url('/admin/generar-reporte'); ?>" class="btn btn-outline-success">
                                        <i class="fas fa-file-excel me-2"></i> Generar Reporte
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de especies
            const speciesCtx = document.getElementById('speciesChart').getContext('2d');
            const speciesChart = new Chart(speciesCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Perros', 'Gatos'],
                    datasets: [{
                        data: [
                            <?= $conexion->query("SELECT COUNT(*) FROM animales WHERE especie = 'Perro' AND activo = 1")->fetch_row()[0] ?>,
                            <?= $conexion->query("SELECT COUNT(*) FROM animales WHERE especie = 'Gato' AND activo = 1")->fetch_row()[0] ?>
                        ],
                        backgroundColor: ['#4B7BEC', '#FF6B8B'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        }
                    }
                }
            });

            // Gráfico de estados de animales
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'bar',
                data: {
                    labels: ['Disponible', 'En adopción', 'Adoptado', 'Reservado'],
                    datasets: [{
                        label: 'Estado de Animales',
                        data: [
                            <?= $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Disponible' AND activo = 1")->fetch_row()[0] ?>,
                            <?= $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'En adopción' AND activo = 1")->fetch_row()[0] ?>,
                            <?= $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Adoptado' AND activo = 1")->fetch_row()[0] ?>,
                            <?= $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Reservado' AND activo = 1")->fetch_row()[0] ?>
                        ],
                        backgroundColor: [
                            '#28A745',
                            '#FFC107',
                            '#DC3545',
                            '#17A2B8'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Inicializar DataTables
            $('.table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                pageLength: 5
            });
        });
    </script>
</body>

</html>