<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '../../../views/header.php';

$id_usuario = $_SESSION['id_usuario'];

// Obtener animales adoptados por el usuario
$query = "SELECT a.*, r.nombre_raza, 
                 DATE_FORMAT(sa.fecha_respuesta, '%d/%m/%Y') as fecha_adopcion,
                 (SELECT url_foto FROM fotos_animales WHERE id_animal = a.id_animal LIMIT 1) as foto_principal
          FROM animales a
          LEFT JOIN razas r ON a.id_raza = r.id_raza
          JOIN solicitudes_adopcion sa ON a.id_animal = sa.id_animal
          JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
          WHERE ad.id_usuario = $id_usuario AND sa.estado = 'Aprobada' AND a.estado = 'Adoptado'
          ORDER BY sa.fecha_respuesta DESC";
$animales = $conexion->query($query)->fetch_all(MYSQLI_ASSOC);
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../views/dashboardAdoptante.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mis Animales</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0"><i class="fas fa-paw me-2"></i> Mis Animales Adoptados</h1>
                <a href="../../views/dashboardAdoptante.php" class="btn btn-outline-primary">
                    <i class="fas fa-plus me-2"></i> Adoptar otro
                </a>
            </div>

            <?php if (empty($animales)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-paw fa-4x text-muted mb-4"></i>
                        <h3 class="h4 mb-3">Aún no tienes animales adoptados</h3>
                        <p class="text-muted mb-4">Cuando adoptes un animal, aparecerá en esta sección.</p>
                        <a href="../../views/dashboardAdoptante.php" class="btn btn-primary px-4">
                            <i class="fas fa-paw me-2"></i> Ver animales disponibles
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($animales as $animal): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm h-100">
                                <?php if (!empty($animal['foto_principal'])): ?>
                                    <img src="<?= htmlspecialchars($animal['foto_principal']) ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($animal['nombre']) ?>"
                                         style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                         style="height: 200px;">
                                        <i class="fas fa-paw fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h3 class="h5 mb-0"><?= htmlspecialchars($animal['nombre']) ?></h3>
                                        <span class="badge bg-success">Adoptado</span>
                                    </div>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-<?= strtolower($animal['especie']) == 'perro' ? 'dog' : 'cat' ?> me-2"></i>
                                        <?= $animal['especie'] ?> • <?= $animal['nombre_raza'] ?? 'Sin raza específica' ?>
                                    </p>
                                    <p class="text-muted mb-3">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        Adoptado el <?= $animal['fecha_adopcion'] ?>
                                    </p>
                                    
                                    <ul class="list-unstyled small mb-3">
                                        <li class="mb-1"><i class="fas fa-birthday-cake text-primary me-2"></i> <?= $animal['edad_anios'] ?> años</li>
                                        <li class="mb-1"><i class="fas fa-ruler-combined text-primary me-2"></i> <?= $animal['tamanio'] ?></li>
                                        <li><i class="fas fa-<?= strtolower($animal['sexo']) == 'macho' ? 'mars' : 'venus' ?> text-primary me-2"></i> <?= $animal['sexo'] ?></li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0">
                                    <div class="d-grid gap-2">
                                        <a href="ver_animal.php?id=<?= $animal['id_animal'] ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-2"></i> Ver detalles
                                        </a>
                                        <a href="seguimiento.php?id=<?= $animal['id_animal'] ?>" class="btn btn-outline-success">
                                            <i class="fas fa-heartbeat me-2"></i> Seguimiento
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
