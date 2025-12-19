<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../headerA.php';

// Obtener estadísticas generales
$estadisticas = [
    'animales' => $conexion->query("SELECT COUNT(*) FROM animales")->fetch_row()[0],
    'animales_disponibles' => $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Disponible'")->fetch_row()[0],
    'animales_adoptados' => $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Adoptado'")->fetch_row()[0],
    'perros' => $conexion->query("SELECT COUNT(*) FROM animales WHERE especie = 'Perro'")->fetch_row()[0],
    'gatos' => $conexion->query("SELECT COUNT(*) FROM animales WHERE especie = 'Gato'")->fetch_row()[0],
    'usuarios' => $conexion->query("SELECT COUNT(*) FROM usuarios")->fetch_row()[0],
    'adoptantes' => $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'adoptante'")->fetch_row()[0],
    'administradores' => $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador'")->fetch_row()[0],
    'solicitudes' => $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion")->fetch_row()[0],
    'solicitudes_aprobadas' => $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'Aprobada'")->fetch_row()[0],
    'seguimientos' => $conexion->query("SELECT COUNT(*) FROM seguimientos")->fetch_row()[0],
];

// Obtener razas más comunes
$razas_comunes = $conexion->query("
    SELECT r.nombre_raza, r.especie, COUNT(a.id_animal) as total
    FROM razas r
    JOIN animales a ON r.id_raza = a.id_raza
    GROUP BY r.id_raza
    ORDER BY total DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Obtener últimos animales ingresados
$ultimos_animales = $conexion->query("
    SELECT a.*, r.nombre_raza, 
           (SELECT url_foto FROM fotos_animales WHERE id_animal = a.id_animal LIMIT 1) as foto
    FROM animales a
    LEFT JOIN razas r ON a.id_raza = r.id_raza
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Obtener últimos seguimientos
$ultimos_seguimientos = $conexion->query("
    SELECT s.*, a.nombre as nombre_animal, ad.nombre_completo as adoptante
    FROM seguimientos s
    JOIN solicitudes_adopcion sa ON s.id_solicitud = sa.id_solicitud
    JOIN animales a ON sa.id_animal = a.id_animal
    JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
    ORDER BY s.fecha_seguimiento DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --indigo: #6610f2;
            --purple: #6f42c1;
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

        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .animal-avatar {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .badge-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .badge-disponible {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .badge-adoptado {
            background-color: rgba(75, 123, 236, 0.1);
            color: var(--primary);
        }

        .badge-reservado {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .report-section {
            margin-bottom: 30px;
        }

        .report-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .report-btn {
            border-radius: 8px;
            padding: 8px 15px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card card-main">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i> Reportes del Sistema
                    </h3>
                </div>
            </div>
            <div class="card-body">
                <!-- Estadísticas generales -->
                <div class="report-section">
                    <h4 class="mb-4"><i class="fas fa-chart-pie me-2"></i>Estadísticas Generales</h4>
                    
                    <div class="row">
                        <!-- Tarjeta de Animales -->
                        <div class="col-md-3 col-sm-6">
                            <div class="stat-card bg-white">
                                <div class="stat-icon text-primary">
                                    <i class="fas fa-paw"></i>
                                </div>
                                <div class="stat-value text-primary"><?= $estadisticas['animales'] ?></div>
                                <div class="stat-label">Animales registrados</div>
                            </div>
                        </div>
                        
                        <!-- Tarjeta de Usuarios -->
                        <div class="col-md-3 col-sm-6">
                            <div class="stat-card bg-white">
                                <div class="stat-icon text-info">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-value text-info"><?= $estadisticas['usuarios'] ?></div>
                                <div class="stat-label">Usuarios registrados</div>
                            </div>
                        </div>
                        
                        <!-- Tarjeta de Solicitudes -->
                        <div class="col-md-3 col-sm-6">
                            <div class="stat-card bg-white">
                                <div class="stat-icon text-warning">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="stat-value text-warning"><?= $estadisticas['solicitudes'] ?></div>
                                <div class="stat-label">Solicitudes de adopción</div>
                            </div>
                        </div>
                        
                        <!-- Tarjeta de Seguimientos -->
                        <div class="col-md-3 col-sm-6">
                            <div class="stat-card bg-white">
                                <div class="stat-icon text-success">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div class="stat-value text-success"><?= $estadisticas['seguimientos'] ?></div>
                                <div class="stat-label">Seguimientos realizados</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="report-section">
                    <h4 class="mb-4"><i class="fas fa-chart-line me-2"></i>Visualizaciones</h4>
                    
                    <div class="row">
                        <!-- Gráfico de animales por especie -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Animales por Especie</h5>
                                    <div class="chart-container">
                                        <canvas id="especiesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gráfico de estado de animales -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Estado de Animales</h5>
                                    <div class="chart-container">
                                        <canvas id="estadoAnimalesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Gráfico de solicitudes por estado -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Solicitudes por Estado</h5>
                                    <div class="chart-container">
                                        <canvas id="estadoSolicitudesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gráfico de usuarios por rol -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Usuarios por Rol</h5>
                                    <div class="chart-container">
                                        <canvas id="rolesUsuariosChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reportes descargables -->
                <div class="report-section">
                    <h4 class="mb-4"><i class="fas fa-file-export me-2"></i>Reportes Descargables</h4>
                    
                    <div class="report-actions">
                        <a href="../../controllers/crudAdministrador/generarReporte.php?tipo=animales" class="btn btn-primary report-btn">
                            <i class="fas fa-paw me-1"></i> Reporte de Animales
                        </a>
                        <a href="../../controllers/crudAdministrador/generarReporte.php?tipo=adoptantes" class="btn btn-success report-btn">
                            <i class="fas fa-users me-1"></i> Reporte de Adoptantes
                        </a>
                        <a href="../../controllers/crudAdministrador/generarReporte.php?tipo=solicitudes" class="btn btn-warning report-btn">
                            <i class="fas fa-clipboard-list me-1"></i> Reporte de Solicitudes
                        </a>
                        <a href="../../controllers/crudAdministrador/generarReporte.php?tipo=seguimientos" class="btn btn-info report-btn">
                            <i class="fas fa-clipboard-check me-1"></i> Reporte de Seguimientos
                        </a>
                        <a href="../../controllers/crudAdministrador/generarReporte.php?tipo=completo" class="btn btn-danger report-btn">
                            <i class="fas fa-file-pdf me-1"></i> Reporte Completo
                        </a>
                    </div>
                </div>

                <!-- Razas más comunes -->
                <div class="report-section">
                    <h4 class="mb-4"><i class="fas fa-dna me-2"></i>Razas Más Comunes</h4>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Raza</th>
                                    <th>Especie</th>
                                    <th>Total Animales</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($razas_comunes as $index => $raza): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($raza['nombre_raza']) ?></td>
                                        <td><?= $raza['especie'] ?></td>
                                        <td><?= $raza['total'] ?></td>
                                        <td><?= round(($raza['total'] / $estadisticas['animales']) * 100, 2) ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Últimos animales ingresados -->
                <div class="report-section">
                    <h4 class="mb-4"><i class="fas fa-clock me-2"></i>Últimos Animales Ingresados</h4>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Animal</th>
                                    <th>Nombre</th>
                                    <th>Especie</th>
                                    <th>Raza</th>
                                    <th>Estado</th>
                                    <th>Fecha Ingreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimos_animales as $animal): 
                                    $badge_class = $animal['estado'] == 'Disponible' ? 'badge-disponible' : 
                                                  ($animal['estado'] == 'Adoptado' ? 'badge-adoptado' : 'badge-reservado');
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($animal['foto']): ?>
                                                <img src="<?= htmlspecialchars($animal['foto']) ?>" class="animal-avatar">
                                            <?php else: ?>
                                                <div class="animal-avatar bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-<?= strtolower($animal['especie']) == 'perro' ? 'dog' : 'cat' ?>"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($animal['nombre']) ?></td>
                                        <td><?= $animal['especie'] ?></td>
                                        <td><?= $animal['nombre_raza'] ? htmlspecialchars($animal['nombre_raza']) : 'Sin raza definida' ?></td>
                                        <td>
                                            <span class="badge-status <?= $badge_class ?>">
                                                <?= $animal['estado'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($animal['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Últimos seguimientos -->
                <div class="report-section">
                    <h4 class="mb-4"><i class="fas fa-heart me-2"></i>Últimos Seguimientos</h4>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Animal</th>
                                    <th>Adoptante</th>
                                    <th>Satisfacción</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimos_seguimientos as $seguimiento): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($seguimiento['fecha_seguimiento'])) ?></td>
                                        <td><?= htmlspecialchars($seguimiento['nombre_animal']) ?></td>
                                        <td><?= htmlspecialchars($seguimiento['adoptante']) ?></td>
                                        <td>
                                            <?php if ($seguimiento['satisfaccion']): ?>
                                                <?php 
                                                    $satisfaccion_class = [
                                                        'Excelente' => 'text-success',
                                                        'Bueno' => 'text-primary',
                                                        'Regular' => 'text-warning',
                                                        'Malo' => 'text-danger'
                                                    ][$seguimiento['satisfaccion']] ?? '';
                                                ?>
                                                <span class="<?= $satisfaccion_class ?>">
                                                    <i class="fas fa-smile"></i> <?= $seguimiento['satisfaccion'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">No evaluado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $seguimiento['estado_animal'] ? htmlspecialchars(substr($seguimiento['estado_animal'], 0, 30)) . '...' : 'Sin detalles' ?>
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

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para gráficos -->
    <script>
        // Gráfico de animales por especie
        const especiesCtx = document.getElementById('especiesChart').getContext('2d');
        const especiesChart = new Chart(especiesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Perros', 'Gatos'],
                datasets: [{
                    data: [<?= $estadisticas['perros'] ?>, <?= $estadisticas['gatos'] ?>],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de estado de animales
        const estadoAnimalesCtx = document.getElementById('estadoAnimalesChart').getContext('2d');
        const estadoAnimalesChart = new Chart(estadoAnimalesCtx, {
            type: 'bar',
            data: {
                labels: ['Disponibles', 'Adoptados', 'En adopción', 'Reservados'],
                datasets: [{
                    label: 'Cantidad',
                    data: [
                        <?= $estadisticas['animales_disponibles'] ?>,
                        <?= $estadisticas['animales_adoptados'] ?>,
                        <?= $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'En adopción'")->fetch_row()[0] ?>,
                        <?= $conexion->query("SELECT COUNT(*) FROM animales WHERE estado = 'Reservado'")->fetch_row()[0] ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(75, 123, 236, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(75, 123, 236, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Gráfico de estado de solicitudes
        const estadoSolicitudesCtx = document.getElementById('estadoSolicitudesChart').getContext('2d');
        const estadoSolicitudesChart = new Chart(estadoSolicitudesCtx, {
            type: 'pie',
            data: {
                labels: ['Aprobadas', 'Rechazadas', 'Pendientes', 'En revisión'],
                datasets: [{
                    data: [
                        <?= $estadisticas['solicitudes_aprobadas'] ?>,
                        <?= $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'Rechazada'")->fetch_row()[0] ?>,
                        <?= $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'Pendiente'")->fetch_row()[0] ?>,
                        <?= $conexion->query("SELECT COUNT(*) FROM solicitudes_adopcion WHERE estado = 'En revisión'")->fetch_row()[0] ?>
                    ],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(23, 162, 184, 0.7)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(23, 162, 184, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Gráfico de usuarios por rol
        const rolesUsuariosCtx = document.getElementById('rolesUsuariosChart').getContext('2d');
        const rolesUsuariosChart = new Chart(rolesUsuariosCtx, {
            type: 'polarArea',
            data: {
                labels: ['Administradores', 'Adoptantes'],
                datasets: [{
                    data: [<?= $estadisticas['administradores'] ?>, <?= $estadisticas['adoptantes'] ?>],
                    backgroundColor: [
                        'rgba(75, 123, 236, 0.7)',
                        'rgba(23, 162, 184, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 123, 236, 1)',
                        'rgba(23, 162, 184, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>

</html>