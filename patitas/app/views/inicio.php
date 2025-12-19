<?php
require_once __DIR__ . '../../config/conexion.php';
require_once __DIR__ . '/menu.php';

// Obtener filtros
$filtro_especie = $_GET['especie'] ?? '';
$filtro_raza = $_GET['raza'] ?? '';
$filtro_tamanio = $_GET['tamanio'] ?? '';
$filtro_edad = $_GET['edad'] ?? '';
$filtro_sexo = $_GET['sexo'] ?? '';
$filtro_busqueda = $_GET['busqueda'] ?? '';

// Consulta para animales disponibles
$sql = "
    SELECT 
        a.*, 
        r.nombre_raza,
        (SELECT url_foto FROM fotos_animales WHERE id_animal = a.id_animal LIMIT 1) as foto_principal
    FROM animales a
    LEFT JOIN razas r ON a.id_raza = r.id_raza
    WHERE a.estado = 'Disponible' AND a.activo = 1
";

// Aplicar filtros
if (!empty($filtro_especie)) {
    $sql .= " AND a.especie = '" . $conexion->real_escape_string($filtro_especie) . "'";
}

if (!empty($filtro_raza)) {
    $sql .= " AND r.id_raza = " . (int)$filtro_raza;
}

if (!empty($filtro_tamanio)) {
    $sql .= " AND a.tamanio = '" . $conexion->real_escape_string($filtro_tamanio) . "'";
}

if (!empty($filtro_edad)) {
    if ($filtro_edad === 'cachorro') {
        $sql .= " AND a.edad_anios <= 2";
    } elseif ($filtro_edad === 'adulto') {
        $sql .= " AND a.edad_anios > 2 AND a.edad_anios <= 8";
    } elseif ($filtro_edad === 'senior') {
        $sql .= " AND a.edad_anios > 8";
    }
}

if (!empty($filtro_sexo)) {
    $sql .= " AND a.sexo = '" . $conexion->real_escape_string($filtro_sexo) . "'";
}

if (!empty($filtro_busqueda)) {
    $sql .= " AND (a.nombre LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%' 
              OR a.descripcion LIKE '%" . $conexion->real_escape_string($filtro_busqueda) . "%')";
}

$sql .= " GROUP BY a.id_animal ORDER BY a.fecha_ingreso DESC";

$animales = $conexion->query($sql);

// Obtener razas para filtros
$razas_perros = $conexion->query("SELECT * FROM razas WHERE especie = 'Perro'")->fetch_all(MYSQLI_ASSOC);
$razas_gatos = $conexion->query("SELECT * FROM razas WHERE especie = 'Gato'")->fetch_all(MYSQLI_ASSOC);

// Obtener animales destacados para el carrusel
$destacados = $conexion->query("
    SELECT a.*, f.url_foto 
    FROM animales a
    JOIN fotos_animales f ON a.id_animal = f.id_animal
    WHERE a.estado = 'Disponible' AND a.activo = 1
    GROUP BY a.id_animal
    ORDER BY RAND()
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adopta una Mascota - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4B7BEC;
            --secondary: #6C757D;
            --pink: #FF6B8B;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        /* Carrusel mejorado */
        #animalesCarousel {
            max-width: 1300px;
            /* antes 1400px, ahora menos ancho */
            margin: 0 auto 30px auto;
            padding: 0 15px;
        }

        .carousel-inner {
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .carousel-item {
            height: 600px;
            /* antes 420px, ahora más alto */
        }

        .carousel-img {
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        .carousel-caption {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 12px;
            padding: 15px;
            bottom: 20px;
            left: 20px;
            right: auto;
            width: auto;
            max-width: 80%;
        }

        .carousel-title {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .carousel-text {
            font-size: 1rem;
        }

        .btn-adoptar {
            background: var(--pink);
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        /* Controles del carrusel */
        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            opacity: 0.8;
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            background-size: 60%;
        }

        .card-animal {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            margin: 0 0 10px 0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .animal-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .card-body {
            padding: 12px;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .badge-especie {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .badge-perro {
            background: var(--primary);
            color: #fff;
        }

        .badge-gato {
            background: var(--pink);
            color: #fff;
        }

        .animal-name {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 2px;
            color: #333;
        }

        .animal-raza {
            color: var(--secondary);
            font-size: 0.9rem;
            margin-bottom: 6px;
        }

        .animal-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .animal-info-item {
            text-align: center;
        }

        .animal-info-label {
            font-size: 0.75rem;
            color: var(--secondary);
        }

        .animal-info-value {
            font-weight: 600;
            color: #333;
        }

        .btn-ver-detalle {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 6px 0;
            font-weight: 500;
            width: 100%;
            margin-top: 6px;
            font-size: 0.95rem;
        }

        .btn-ver-detalle:hover {
            background: #3a6bd8;
        }

        .section-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            font-size: 1.2rem;
            position: relative;
            display: inline-block;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--pink);
        }

        .no-results {
            text-align: center;
            padding: 30px 10px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .no-results-icon {
            font-size: 2.5rem;
            color: var(--secondary);
            margin-bottom: 8px;
            opacity: 0.5;
        }

        .floating-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--pink);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 3px 12px rgba(255, 107, 139, 0.18);
            z-index: 100;
        }

        .floating-btn:hover {
            background: #ff4d6d;
            color: #fff;
        }

        /* Mejoras en los filtros */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .filter-title {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
        }

        /* Mejoras en la cuadrícula de animales */
        .animales-lista {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        /* Ajustes responsivos */
        @media (max-width: 991.98px) {
            .carousel-item {
                height: 400px;
                /* ajusta también en responsive */
            }

            .carousel-caption {
                bottom: 10px;
                padding: 10px;
            }

            .carousel-title {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 767.98px) {
            .carousel-item {
                height: 260px;
                /* ajusta también en responsive */
            }

            .carousel-caption {
                position: static;
                background: transparent;
                color: #333;
                padding: 10px 0;
                max-width: 100%;
            }

            .animales-lista {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .carousel-item {
                height: 180px;
            }
        }
    </style>
</head>

<body>
    <!-- Carrusel de animales destacados -->
    <div id="animalesCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3500">
        <div class="carousel-indicators">
            <?php foreach ($destacados as $index => $animal): ?>
                <button type="button" data-bs-target="#animalesCarousel" data-bs-slide-to="<?= $index ?>"
                    <?= $index === 0 ? 'class="active" aria-current="true"' : '' ?>
                    aria-label="Slide <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($destacados as $index => $animal): ?>
                <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>">
                    <img src="<?= htmlspecialchars($animal['url_foto']) ?>"
                        class="d-block w-100 carousel-img"
                        alt="<?= htmlspecialchars($animal['nombre']) ?>">
                    <div class="carousel-caption text-start">
                        <h3 class="carousel-title"><?= htmlspecialchars($animal['nombre']) ?></h3>
                        <p class="carousel-text"><?= $animal['especie'] ?> <?= $animal['sexo'] === 'Macho' ? '♂' : '♀' ?> • <?= $animal['edad_anios'] ?> años</p>
                        <a href="#animal-<?= $animal['id_animal'] ?>" class="btn btn-adoptar">
                            <i class="fas fa-heart me-2"></i> ¡Conóceme!
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#animalesCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#animalesCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <div class="container mb-5">
        <!-- Filtros de búsqueda -->
        <div class="filter-section">
            <h4 class="filter-title"><i class="fas fa-filter me-2"></i>Filtrar Mascotas</h4>
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="especie" class="form-label">Especie</label>
                    <select class="form-select" id="especie" name="especie">
                        <option value="">Todas las especies</option>
                        <option value="Perro" <?= $filtro_especie === 'Perro' ? 'selected' : '' ?>>Perros</option>
                        <option value="Gato" <?= $filtro_especie === 'Gato' ? 'selected' : '' ?>>Gatos</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="raza" class="form-label">Raza</label>
                    <select class="form-select" id="raza" name="raza">
                        <option value="">Todas las razas</option>
                        <?php if ($filtro_especie === 'Perro' || empty($filtro_especie)): ?>
                            <?php foreach ($razas_perros as $raza): ?>
                                <option value="<?= $raza['id_raza'] ?>" <?= $filtro_raza == $raza['id_raza'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($raza['nombre_raza']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ($filtro_especie === 'Gato' || empty($filtro_especie)): ?>
                            <?php foreach ($razas_gatos as $raza): ?>
                                <option value="<?= $raza['id_raza'] ?>" <?= $filtro_raza == $raza['id_raza'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($raza['nombre_raza']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="tamanio" class="form-label">Tamaño</label>
                    <select class="form-select" id="tamanio" name="tamanio">
                        <option value="">Cualquier tamaño</option>
                        <option value="Pequeño" <?= $filtro_tamanio === 'Pequeño' ? 'selected' : '' ?>>Pequeño</option>
                        <option value="Mediano" <?= $filtro_tamanio === 'Mediano' ? 'selected' : '' ?>>Mediano</option>
                        <option value="Grande" <?= $filtro_tamanio === 'Grande' ? 'selected' : '' ?>>Grande</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="edad" class="form-label">Edad</label>
                    <select class="form-select" id="edad" name="edad">
                        <option value="">Cualquier edad</option>
                        <option value="cachorro" <?= $filtro_edad === 'cachorro' ? 'selected' : '' ?>>Cachorro (0-2 años)</option>
                        <option value="adulto" <?= $filtro_edad === 'adulto' ? 'selected' : '' ?>>Adulto (3-8 años)</option>
                        <option value="senior" <?= $filtro_edad === 'senior' ? 'selected' : '' ?>>Senior (9+ años)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="sexo" class="form-label">Sexo</label>
                    <select class="form-select" id="sexo" name="sexo">
                        <option value="">Cualquier sexo</option>
                        <option value="Macho" <?= $filtro_sexo === 'Macho' ? 'selected' : '' ?>>Macho</option>
                        <option value="Hembra" <?= $filtro_sexo === 'Hembra' ? 'selected' : '' ?>>Hembra</option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label for="busqueda" class="form-label">Buscar por nombre o descripción</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda"
                        placeholder="Ej: 'Labrador', 'juguetón', 'pequeño'..." value="<?= htmlspecialchars($filtro_busqueda) ?>">
                </div>

                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-search me-2"></i> Buscar
                    </button>
                    <a href="inicio.php" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Listado de animales -->
        <h2 class="section-title"><i class="fas fa-paw me-2"></i>Nuestros Animales Buscan Hogar</h2>
        <?php if ($animales->num_rows > 0): ?>
            <div class="row animales-lista">
                <?php while ($animal = $animales->fetch_assoc()): ?>
                    <div class="col-animal" id="animal-<?= $animal['id_animal'] ?>">
                        <div class="card-animal w-100 position-relative">
                            <img src="<?= htmlspecialchars($animal['foto_principal']) ?>" class="animal-img" alt="<?= htmlspecialchars($animal['nombre']) ?>">
                            <div class="card-body">
                                <div class="badge-especie <?= $animal['especie'] === 'Perro' ? 'badge-perro' : 'badge-gato' ?>">
                                    <i class="fas fa-<?= strtolower($animal['especie']) === 'perro' ? 'dog' : 'cat' ?>"></i>
                                </div>
                                <h3 class="animal-name"><?= htmlspecialchars($animal['nombre']) ?></h3>
                                <p class="animal-raza">
                                    <?= $animal['nombre_raza'] ? htmlspecialchars($animal['nombre_raza']) : 'Sin raza definida' ?>
                                    • <?= $animal['sexo'] === 'Macho' ? '♂' : '♀' ?>
                                </p>
                                <div class="animal-info">
                                    <div class="animal-info-item">
                                        <div class="animal-info-value"><?= $animal['edad_anios'] ?> años</div>
                                        <div class="animal-info-label">Edad</div>
                                    </div>
                                    <div class="animal-info-item">
                                        <div class="animal-info-value"><?= $animal['tamanio'] ?></div>
                                        <div class="animal-info-label">Tamaño</div>
                                    </div>
                                    <div class="animal-info-item">
                                        <div class="animal-info-value">
                                            <?= $animal['esterilizado'] ? 'Sí' : 'No' ?>
                                        </div>
                                        <div class="animal-info-label">Esterilizado</div>
                                    </div>
                                </div>
                                <p class="card-text"><?= substr(htmlspecialchars($animal['descripcion']), 0, 100) ?>...</p>
                                <a href="<?php echo url('/adoptante/detalle-animal?id=' . $animal['id_animal']); ?>" class="btn btn-ver-detalle">
                                    <i class="fas fa-eye me-2"></i> Ver detalles
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-paw no-results-icon"></i>
                <h3>No encontramos mascotas con esos criterios</h3>
                <p class="text-muted">Intenta con otros filtros de búsqueda o visita más tarde</p>
                <a href="adoptante.php" class="btn btn-primary mt-3">
                    <i class="fas fa-undo me-2"></i> Ver todos los animales
                </a>
            </div>
        <?php endif; ?>
    </div>



    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtro de razas dinámico
        document.getElementById('especie').addEventListener('change', function() {
            const especie = this.value;
            const razaSelect = document.getElementById('raza');
            razaSelect.innerHTML = '<option value="">Todas las razas</option>';
            if (especie === 'Perro' || especie === '') {
                <?php foreach ($razas_perros as $raza): ?>
                    razaSelect.innerHTML += `<option value="<?= $raza['id_raza'] ?>"><?= addslashes($raza['nombre_raza']) ?></option>`;
                <?php endforeach; ?>
            }
            if (especie === 'Gato' || especie === '') {
                <?php foreach ($razas_gatos as $raza): ?>
                    razaSelect.innerHTML += `<option value="<?= $raza['id_raza'] ?>"><?= addslashes($raza['nombre_raza']) ?></option>`;
                <?php endforeach; ?>
            }
        });

        // Forzar reinicio y autoplay del carrusel principal
        document.addEventListener('DOMContentLoaded', function() {
            var mainCarousel = document.getElementById('animalesCarousel');
            if (mainCarousel) {
                // Eliminar cualquier instancia previa
                if (mainCarousel.carouselInstance) {
                    mainCarousel.carouselInstance.dispose();
                }
                // Reiniciar y asegurar autoplay
                mainCarousel.carouselInstance = new bootstrap.Carousel(mainCarousel, {
                    interval: 3500,
                    pause: 'hover',
                    ride: 'carousel',
                    wrap: true
                });
            }
        });
    </script>
</body>

</html>