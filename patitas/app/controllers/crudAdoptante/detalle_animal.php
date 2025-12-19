<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../views/menu.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../../views/dashboardAdoptante.php");
    exit();
}

$id_animal = (int)$_GET['id'];

// Obtener información del animal
$animal = $conexion->query("
    SELECT 
        a.*, 
        r.nombre_raza,
        d.nombre as nombre_departamento, 
        d.provincia,
        GROUP_CONCAT(f.url_foto SEPARATOR '||') as fotos
    FROM animales a
    LEFT JOIN razas r ON a.id_raza = r.id_raza
    LEFT JOIN animal_departamento ad ON a.id_animal = ad.id_animal
    LEFT JOIN departamentos d ON ad.id_departamento = d.id_departamento
    LEFT JOIN fotos_animales f ON a.id_animal = f.id_animal
    WHERE a.id_animal = $id_animal AND a.estado = 'Disponible' AND a.activo = 1
    GROUP BY a.id_animal
")->fetch_assoc();

if (!$animal) {
    $_SESSION['mensaje_error'] = "Animal no encontrado o no disponible para adopción";
    header("Location: adoptante.php");
    exit();
}

// Separar las fotos
$fotos_animal = !empty($animal['fotos']) ? explode('||', $animal['fotos']) : [];
array_unshift($fotos_animal, $animal['foto_principal'] ?? '');

// Obtener características del animal
$caracteristicas = [
    'Especie' => $animal['especie'],
    'Raza' => $animal['nombre_raza'] ?: 'Sin raza definida',
    'Edad' => $animal['edad_anios'] . ' años',
    'Sexo' => $animal['sexo'],
    'Tamaño' => $animal['tamanio'],
    'Esterilizado' => $animal['esterilizado'] ? 'Sí' : 'No',
    'Vacunado' => $animal['vacunado'] ? 'Sí' : 'No',
    'Ubicación' => $animal['nombre_departamento'] . ', ' . $animal['provincia'],
    'Fecha de ingreso' => date('d/m/Y', strtotime($animal['fecha_ingreso']))
];

// Procesar solicitud de adopción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_solicitud'])) {
    $id_adoptante = $_SESSION['id_adoptante'];
    $motivo = $conexion->real_escape_string($_POST['motivo']);
    $experiencia = $conexion->real_escape_string($_POST['experiencia']);
    $tipo_vivienda = $conexion->real_escape_string($_POST['tipo_vivienda']);
    $otros_animales = isset($_POST['otros_animales']) ? 1 : 0;
    $tiempo_solo = (int)$_POST['tiempo_solo'];

    try {
        $conexion->query("
            INSERT INTO solicitudes_adopcion (
                id_adoptante, 
                id_animal, 
                motivo_adopcion, 
                experiencia_mascotas, 
                tipo_vivienda, 
                tiene_otros_animales, 
                tiempo_solo_horas_por_dia,
                fecha_solicitud,
                estado
            ) VALUES (
                $id_adoptante,
                $id_animal,
                '$motivo',
                '$experiencia',
                '$tipo_vivienda',
                $otros_animales,
                $tiempo_solo,
                NOW(),
                'Pendiente'
            )
        ");

        // Cambiar estado del animal a "En adopción"
        $conexion->query("UPDATE animales SET estado = 'En adopción' WHERE id_animal = $id_animal");

        $_SESSION['mensaje_exito'] = "¡Solicitud enviada con éxito! Revisaremos tu información y nos pondremos en contacto contigo.";
        header("Location: mis_solicitudes.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = "Error al enviar la solicitud: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($animal['nombre']) ?> - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6a3093;
            --secondary: #8e44ad;
            --accent: #ff6b8b;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .animal-header {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?= $fotos_animal[0] ?>');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            margin-bottom: 40px;
            text-align: center;
        }

        .animal-name {
            font-size: 3rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 10px;
        }

        .animal-meta {
            font-size: 1.2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            margin-bottom: 20px;
        }

        .badge-especie {
            background-color: var(--accent);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1rem;
        }

        .section-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--accent);
        }

        .gallery-thumbnail {
            height: 80px;
            width: 100%;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .gallery-thumbnail:hover {
            transform: scale(1.05);
            border-color: var(--accent);
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background-color: rgba(106, 48, 147, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary);
            font-size: 1.2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .feature-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0;
        }

        .feature-value {
            color: var(--secondary);
            font-weight: 500;
        }

        .btn-adoptar {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .btn-adoptar:hover {
            background-color: #ff4d6d;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 107, 139, 0.3);
            color: white;
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: white;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #7d3c98;
            color: white;
        }

        .animal-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border: none;
        }

        .card-body {
            padding: 30px;
        }

        .tab-content {
            padding: 20px 0;
        }

        .nav-tabs {
            overflow-x: auto;
            flex-wrap: nowrap;
        }

        .nav-tabs .nav-link {
            color: var(--dark);
            font-weight: 600;
            border: none;
            padding: 12px 20px;
            margin-right: 5px;
            white-space: nowrap;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            background-color: rgba(106, 48, 147, 0.1);
            border-bottom: 3px solid var(--primary);
        }

        .nav-tabs .nav-link:hover:not(.active) {
            color: var(--primary);
            background-color: rgba(106, 48, 147, 0.05);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background-color: var(--primary);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(106, 48, 147, 0.25);
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .map-container {
            height: 300px;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 20px;
        }

        /* Mejoras de responsividad */
        @media (max-width: 1200px) {
            .card-body {
                padding: 20px;
            }
        }

        @media (max-width: 992px) {
            .animal-header {
                padding: 50px 0;
            }

            .animal-name {
                font-size: 2.2rem;
            }

            .card-body {
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .animal-header {
                padding: 30px 0;
                margin-bottom: 20px;
            }

            .animal-name {
                font-size: 1.5rem;
            }

            .animal-meta {
                font-size: 0.95rem;
            }

            .section-title {
                font-size: 1.2rem;
                margin-bottom: 10px;
            }

            .gallery-thumbnail {
                height: 60px;
            }

            .animal-card {
                margin-bottom: 18px;
            }

            .tab-content {
                padding: 10px 0;
            }

            .modal-content {
                border-radius: 10px;
            }

            .modal-header {
                border-radius: 10px 10px 0 0 !important;
                padding: 10px 15px;
            }

            .modal-body,
            .modal-footer {
                padding: 10px 15px;
            }

            .map-container {
                height: 180px;
            }
        }

        @media (max-width: 576px) {
            .animal-header {
                padding: 18px 0;
            }

            .animal-name {
                font-size: 1.1rem;
            }

            .gallery-thumbnail {
                height: 45px;
            }

            .feature-icon {
                width: 28px;
                height: 28px;
                font-size: 0.9rem;
                margin-right: 8px;
            }

            .btn-adoptar,
            .btn-secondary {
                padding: 8px 15px;
                font-size: 0.95rem;
            }

            .modal-content {
                border-radius: 7px;
            }

            .modal-header {
                border-radius: 7px 7px 0 0 !important;
            }
        }
    </style>
</head>

<body>
    <!-- Encabezado con imagen principal -->
    <div class="animal-header">
        <h1 class="animal-name"><?= htmlspecialchars($animal['nombre']) ?></h1>
        <p class="animal-meta">
            <?= $animal['especie'] ?> • <?= $animal['sexo'] === 'Macho' ? '♂' : '♀' ?> • <?= $animal['edad_anios'] ?> años
        </p>
        <span class="badge-especie">
            <i class="fas fa-<?= strtolower($animal['especie']) === 'perro' ? 'dog' : 'cat' ?> me-2"></i>
            <?= $animal['estado'] ?>
        </span>
    </div>

    <div class="container mb-5">
        <div class="row">
            <!-- Columna izquierda - Galería y características -->
            <div class="col-lg-4 mb-4">
                <!-- Galería de fotos -->
                <div class="animal-card">
                    <div class="card-body">
                        <h3 class="section-title">Galería</h3>
                        <div class="row g-2">
                            <?php foreach ($fotos_animal as $index => $foto): ?>
                                <?php if (!empty($foto)): ?>
                                    <div class="col-4 col-sm-3 col-md-4 mb-2">
                                        <a href="<?= htmlspecialchars($foto) ?>" data-lightbox="animal-gallery" data-title="<?= htmlspecialchars($animal['nombre']) ?>">
                                            <img src="<?= htmlspecialchars($foto) ?>" class="gallery-thumbnail" alt="Foto <?= $index + 1 ?> de <?= htmlspecialchars($animal['nombre']) ?>">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Características -->
                <div class="animal-card">
                    <div class="card-body">
                        <h3 class="section-title">Características</h3>
                        <?php foreach ($caracteristicas as $label => $value): ?>
                            <div class="feature-item">
                                <div class="feature-icon">
                                    <i class="fas fa-<?=
                                                        $label === 'Especie' ? 'paw' : ($label === 'Raza' ? 'dna' : ($label === 'Edad' ? 'birthday-cake' : ($label === 'Sexo' ? 'venus-mars' : ($label === 'Tamaño' ? 'ruler-combined' : ($label === 'Esterilizado' ? 'clinic-medical' : ($label === 'Vacunado' ? 'syringe' : ($label === 'Ubicación' ? 'map-marker-alt' : 'calendar-alt'))))))) ?>"></i>
                                </div>
                                <div>
                                    <h5 class="feature-label"><?= $label ?></h5>
                                    <p class="feature-value mb-0"><?= $value ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Ubicación -->
                <div class="animal-card">
                    <div class="card-body">
                        <h3 class="section-title">Ubicación</h3>
                        <p><i class="fas fa-map-marker-alt me-2"></i> <?= $animal['nombre_departamento'] ?>, <?= $animal['provincia'] ?></p>
                        <div class="map-container w-100">
                            <img src="https://maps.googleapis.com/maps/api/staticmap?center=<?= urlencode($animal['nombre_departamento'] . ',' . $animal['provincia']) ?>&zoom=12&size=600x300&maptype=roadmap&key=TU_API_KEY"
                                alt="Mapa de <?= htmlspecialchars($animal['nombre_departamento']) ?>" class="img-fluid w-100 h-100" style="object-fit:cover;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna derecha - Información detallada -->
            <div class="col-lg-8">
                <div class="animal-card">
                    <div class="card-body">
                        <!-- Pestañas de información -->
                        <ul class="nav nav-tabs mb-3" id="animalTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="descripcion-tab" data-bs-toggle="tab" data-bs-target="#descripcion" type="button" role="tab">
                                    <i class="fas fa-info-circle me-2"></i>Descripción
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="historia-tab" data-bs-toggle="tab" data-bs-target="#historia" type="button" role="tab">
                                    <i class="fas fa-book me-2"></i>Historia
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="cuidados-tab" data-bs-toggle="tab" data-bs-target="#cuidados" type="button" role="tab">
                                    <i class="fas fa-heart me-2"></i>Cuidados
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="animalTabsContent">
                            <!-- Pestaña de Descripción -->
                            <div class="tab-pane fade show active" id="descripcion" role="tabpanel">
                                <div class="mt-4">
                                    <h4 class="mb-3">Sobre <?= htmlspecialchars($animal['nombre']) ?></h4>
                                    <p><?= nl2br(htmlspecialchars($animal['descripcion'])) ?></p>

                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <h5>Personalidad</h5>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check-circle text-success me-2"></i> <?= $animal['sexo'] === 'Macho' ? 'Amigable' : 'Cariñosa' ?></li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Juguetón</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i> Bueno con niños</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Necesidades especiales</h5>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-times-circle text-danger me-2"></i> No requiere cuidados especiales</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña de Historia -->
                            <div class="tab-pane fade" id="historia" role="tabpanel">
                                <div class="mt-4">
                                    <h4 class="mb-3">Historia de <?= htmlspecialchars($animal['nombre']) ?></h4>
                                    <p><?= nl2br(htmlspecialchars($animal['historia'] ?: 'Este animal no tiene una historia registrada aún.')) ?></p>

                                    <div class="alert alert-info mt-4">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <?= htmlspecialchars($animal['nombre']) ?> llegó al refugio el <?= date('d/m/Y', strtotime($animal['fecha_ingreso'])) ?> y está buscando un hogar amoroso.
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña de Cuidados -->
                            <div class="tab-pane fade" id="cuidados" role="tabpanel">
                                <div class="mt-4">
                                    <h4 class="mb-3">Cuidados recomendados</h4>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <h5><i class="fas fa-utensils me-2"></i> Alimentación</h5>
                                                <p><?= $animal['especie'] === 'Perro' ?
                                                        'Recomendamos comida premium para perros, 2 veces al día. Porciones adecuadas para su tamaño.' :
                                                        'Comida para gatos de alta calidad, disponible todo el día. Agua fresca siempre disponible.' ?>
                                                </p>
                                            </div>

                                            <div class="mb-4">
                                                <h5><i class="fas fa-home me-2"></i> Espacio</h5>
                                                <p><?= $animal['tamanio'] === 'Pequeño' ?
                                                        'Ideal para departamentos o casas pequeñas.' : ($animal['tamanio'] === 'Mediano' ?
                                                            'Necesita espacio para moverse, ideal casa con patio.' :
                                                            'Requiere mucho espacio y ejercicio diario.') ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <h5><i class="fas fa-medkit me-2"></i> Salud</h5>
                                                <p>
                                                    <?= $animal['vacunado'] ? 'Tiene todas sus vacunas al día.' : 'Requiere completar su esquema de vacunación.' ?>
                                                    <?= $animal['esterilizado'] ? 'Ya está esterilizado/a.' : 'Necesita ser esterilizado/a.' ?>
                                                </p>
                                            </div>

                                            <div class="mb-4">
                                                <h5><i class="fas fa-bone me-2"></i> Ejercicio</h5>
                                                <p><?= $animal['especie'] === 'Perro' ?
                                                        ($animal['tamanio'] === 'Grande' ?
                                                            'Necesita al menos 2 paseos diarios y tiempo de juego.' :
                                                            '1-2 paseos diarios y sesiones de juego.') :
                                                        'Juguetes interactivos y rascador. No requiere paseos.' ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de adopción -->
                        <div class="text-center mt-5">
                            <?php if (isset($_SESSION['id_adoptante'])): ?>
                                <button type="button" class="btn btn-adoptar" data-bs-toggle="modal" data-bs-target="#solicitudModal">
                                    <i class="fas fa-heart me-2"></i> ¡Quiero adoptar!
                                </button>
                            <?php else: ?>
                                <form action="../../views/loginViews/loginUsuario.php" method="get" style="display:inline;">
                                    <input type="hidden" name="redirect" value="<?php echo url('/adoptante/detalle-animal?id=' . $animal['id_animal']); ?>">
                                    <button type="submit" class="btn btn-adoptar">
                                        <i class="fas fa-heart me-2"></i> Inicia sesión para adoptar
                                    </button>
                                </form>
                                <p class="text-muted mt-2">¿No tienes cuenta? <a href="../../views/loginViews/registro.php">Regístrate aquí</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de solicitud de adopción -->
    <div class="modal fade" id="solicitudModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Solicitud de Adopción</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Estás solicitando adoptar a <strong><?= htmlspecialchars($animal['nombre']) ?></strong>. Por favor completa el siguiente formulario.
                        </div>

                        <div class="mb-3">
                            <label for="motivo" class="form-label">¿Por qué quieres adoptar a <?= htmlspecialchars($animal['nombre']) ?>?</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="experiencia" class="form-label">Describe tu experiencia previa con mascotas</label>
                            <textarea class="form-control" id="experiencia" name="experiencia" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo_vivienda" class="form-label">Tipo de vivienda</label>
                                <select class="form-select" id="tipo_vivienda" name="tipo_vivienda" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Casa">Casa</option>
                                    <option value="Departamento">Departamento</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tiempo_solo" class="form-label">Horas al día que estaría solo</label>
                                <select class="form-select" id="tiempo_solo" name="tiempo_solo" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="1">Menos de 2 horas</option>
                                    <option value="2">2-4 horas</option>
                                    <option value="4">4-6 horas</option>
                                    <option value="6">6-8 horas</option>
                                    <option value="8">Más de 8 horas</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="otros_animales" name="otros_animales">
                            <label class="form-check-label" for="otros_animales">¿Tienes otros animales en casa?</label>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            El proceso de adopción puede tomar de 3 a 5 días hábiles. Nos pondremos en contacto contigo.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="enviar_solicitud" class="btn btn-adoptar">Enviar solicitud</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        // Inicializar lightbox
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': 'Imagen %1 de %2'
        });

        // Cambiar pestaña si hay hash en la URL
        if (window.location.hash) {
            const tabTrigger = document.querySelector(`[data-bs-target="${window.location.hash}"]`);
            if (tabTrigger) {
                new bootstrap.Tab(tabTrigger).show();
            }
        }
    </script>
</body>

</html>