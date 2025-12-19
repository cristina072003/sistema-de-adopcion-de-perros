<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/checkSessionUsuario.php'; // Verifica sesión de adoptante
require_once __DIR__ . '/header.php';

// Obtener filtros de búsqueda
$especie = $_GET['especie'] ?? '';
$tamanio = $_GET['tamanio'] ?? '';
$sexo = $_GET['sexo'] ?? '';
$edad = $_GET['edad'] ?? '';
$raza = $_GET['raza'] ?? '';

// Construir consulta con filtros
$query = "SELECT a.*, r.nombre_raza, 
          (SELECT url_foto FROM fotos_animales WHERE id_animal = a.id_animal LIMIT 1) as foto_principal
          FROM animales a
          LEFT JOIN razas r ON a.id_raza = r.id_raza
          WHERE a.activo = 1 AND a.estado = 'Disponible'";

if (!empty($especie)) {
    $query .= " AND a.especie = '$especie'";
}
if (!empty($tamanio)) {
    $query .= " AND a.tamanio = '$tamanio'";
}
if (!empty($sexo)) {
    $query .= " AND a.sexo = '$sexo'";
}
if (!empty($edad)) {
    $query .= " AND a.edad_anios = $edad";
}
if (!empty($raza)) {
    $query .= " AND a.id_raza = $raza";
}

$animales = $conexion->query($query);

// Obtener razas para filtro
$razas = $conexion->query("SELECT id_raza, nombre_raza FROM razas")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refugio de Mascotas - Adopta un Amigo</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4B7BEC;
            --secondary-color: #FF6B8B;
            --dark-color: #2C3E50;
            --light-color: #F8F9FA;
            --success-color: #28A745;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: var(--primary-color);
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1477884213360-7e9d7dcc1e48?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 40px;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
        }

        .search-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .animal-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }

        .animal-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .animal-img {
            height: 250px;
            object-fit: cover;
            width: 100%;
        }

        .no-image {
            height: 250px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .animal-info {
            padding: 20px;
        }

        .animal-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .animal-meta {
            display: flex;
            margin-bottom: 15px;
        }

        .meta-item {
            margin-right: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .meta-item i {
            margin-right: 5px;
            color: var(--primary-color);
        }

        .animal-description {
            color: #666;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .btn-adopt {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-adopt:hover {
            background-color: #e05575;
            color: white;
            transform: translateY(-2px);
        }

        .sidebar {
            background-color: var(--dark-color);
            color: white;
            min-height: 100vh;
            padding: 20px 0;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-link {
            display: block;
            padding: 10px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            margin-bottom: 5px;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid var(--secondary-color);
        }

        .sidebar-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-available {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-adopted {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .filter-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
        }

        .form-check-label {
            cursor: pointer;
        }

        .empty-state {
            text-align: center;
            padding: 50px 0;
        }

        .empty-state i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar d-none d-md-flex flex-column align-items-center py-4" style="background: #fff; min-height: 100vh; box-shadow: 2px 0 12px rgba(75,123,236,0.06);">
                <div class="sidebar-header text-center mb-4 w-100">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['usuario']['nombre'] ?? 'Usuario') ?>&background=4B7BEC&color=fff"
                        alt="Usuario" class="user-avatar shadow mb-2" style="border: 3px solid #4B7BEC;">
                    <h5 class="mb-1" style="color:#4B7BEC;"><?= $_SESSION['usuario']['nombre'] ?? 'Adoptante' ?></h5>
                    <small class="text-muted">Adoptante</small>
                </div>
                <div class="sidebar-menu w-100">
                    <a href="<?php echo url('/adoptante/mis-animales'); ?>" class="sidebar-link active w-100 py-2 px-3 rounded-pill mb-2" style="color:#4B7BEC; font-weight:600; background:rgba(75,123,236,0.08);">
                        <i class="fas fa-paw me-2"></i> Animales Disponibles
                    </a>
                    <a href="<?php echo url('/adoptante/mis-solicitudes'); ?>" class="sidebar-link w-100 py-2 px-3 rounded-pill mb-2" style="color:#4B7BEC; font-weight:600;">
                        <i class="fas fa-clipboard-list me-2"></i> Mis Solicitudes
                    </a>
                    <a href="<?php echo url('/logout'); ?>" class="sidebar-link w-100 py-2 px-3 rounded-pill mb-2" style="color:#FF6B8B; font-weight:600;">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                    </a>
                </div>
            </nav>
            <!-- Sidebar móvil -->
            <nav class="d-md-none bg-white shadow-sm py-2 px-3 mb-3 rounded-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['usuario']['nombre'] ?? 'Usuario') ?>&background=4B7BEC&color=fff"
                            alt="Usuario" class="user-avatar" style="width:40px;height:40px; border:2px solid #4B7BEC;">
                        <span class="ms-2 fw-semibold" style="color:#4B7BEC;"><?= $_SESSION['usuario']['nombre'] ?? 'Adoptante' ?></span>
                    </div>
                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMobile" aria-expanded="false" aria-controls="sidebarMobile">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="collapse mt-2" id="sidebarMobile">
                    <a href="<?php echo url('/adoptante/mis-animales'); ?>" class="d-block py-2 px-2 text-primary fw-semibold"><i class="fas fa-paw me-2"></i> Animales Disponibles</a>
                    <a href="<?php echo url('/adoptante/mis-solicitudes'); ?>" class="d-block py-2 px-2 text-primary fw-semibold"><i class="fas fa-clipboard-list me-2"></i> Mis Solicitudes</a>
                    <a href="<?php echo url('/logout'); ?>" class="d-block py-2 px-2 text-danger fw-semibold"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a>
                </div>
            </nav>
            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
                <!-- Hero Section -->
                <div class="hero-section rounded-3 mb-4">
                    <h1 class="hero-title">Encuentra a tu compañero perfecto</h1>
                    <p class="hero-subtitle">Ellos están esperando por un hogar lleno de amor</p>
                </div>

                <!-- Search Filters -->
                <div class="search-card mb-4">
                    <h4 class="mb-4"><i class="fas fa-filter me-2"></i> Filtros de Búsqueda</h4>
                    <form method="get" action="">
                        <div class="row">
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label">Especie</label>
                                <select class="form-select" name="especie">
                                    <option value="">Todas</option>
                                    <option value="Perro" <?= $especie == 'Perro' ? 'selected' : '' ?>>Perros</option>
                                    <option value="Gato" <?= $especie == 'Gato' ? 'selected' : '' ?>>Gatos</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label">Tamaño</label>
                                <select class="form-select" name="tamanio">
                                    <option value="">Todos</option>
                                    <option value="Pequeño" <?= $tamanio == 'Pequeño' ? 'selected' : '' ?>>Pequeño</option>
                                    <option value="Mediano" <?= $tamanio == 'Mediano' ? 'selected' : '' ?>>Mediano</option>
                                    <option value="Grande" <?= $tamanio == 'Grande' ? 'selected' : '' ?>>Grande</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label">Sexo</label>
                                <select class="form-select" name="sexo">
                                    <option value="">Todos</option>
                                    <option value="Macho" <?= $sexo == 'Macho' ? 'selected' : '' ?>>Macho</option>
                                    <option value="Hembra" <?= $sexo == 'Hembra' ? 'selected' : '' ?>>Hembra</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label">Edad (años)</label>
                                <select class="form-select" name="edad">
                                    <option value="">Todas</option>
                                    <option value="1" <?= $edad == '1' ? 'selected' : '' ?>>1 año</option>
                                    <option value="2" <?= $edad == '2' ? 'selected' : '' ?>>2 años</option>
                                    <option value="3" <?= $edad == '3' ? 'selected' : '' ?>>3 años</option>
                                    <option value="4" <?= $edad == '4' ? 'selected' : '' ?>>4 años</option>
                                    <option value="5" <?= $edad == '5' ? 'selected' : '' ?>>5+ años</option>
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-3">
                                <label class="form-label">Raza</label>
                                <select class="form-select" name="raza">
                                    <option value="">Todas</option>
                                    <?php foreach ($razas as $raza_item): ?>
                                        <option value="<?= $raza_item['id_raza'] ?>" <?= $raza == $raza_item['id_raza'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($raza_item['nombre_raza']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i> Buscar
                                </button>
                                <a href="?" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Animales Disponibles -->
                <div class="row">
                    <?php if ($animales->num_rows > 0): ?>
                        <?php while ($animal = $animales->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="animal-card">
                                    <?php if (!empty($animal['foto_principal'])): ?>
                                        <img src="<?= htmlspecialchars($animal['foto_principal']) ?>"
                                            alt="<?= htmlspecialchars($animal['nombre']) ?>"
                                            class="animal-img">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-paw fa-3x"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="animal-info">
                                        <h3 class="animal-name"><?= htmlspecialchars($animal['nombre']) ?></h3>

                                        <div class="animal-meta">
                                            <span class="meta-item">
                                                <i class="fas fa-dog"></i> <?= $animal['especie'] ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-<?= strtolower($animal['sexo']) == 'macho' ? 'mars' : 'venus' ?>"></i> <?= $animal['sexo'] ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-birthday-cake"></i> <?= $animal['edad_anios'] ?> años
                                            </span>
                                        </div>

                                        <p class="animal-description">
                                            <?= !empty($animal['descripcion']) ? htmlspecialchars($animal['descripcion']) : 'Este adorable compañero está buscando un hogar lleno de amor.' ?>
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge-status badge-available">Disponible</span>
                                            <div>
                                                <a href="<?php echo url('/adoptante/ver-animal?id=' . $animal['id_animal']); ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                <a href="<?php echo url('/adoptante/solicitar-adopcion?id=' . $animal['id_animal']); ?>" class="btn btn-sm btn-adopt">
                                                    <i class="fas fa-heart"></i> Adoptar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="fas fa-paw"></i>
                                <h3>No encontramos animales con esos criterios</h3>
                                <p>Intenta con otros filtros o <a href="?" class="text-primary">ver todos los animales</a></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>