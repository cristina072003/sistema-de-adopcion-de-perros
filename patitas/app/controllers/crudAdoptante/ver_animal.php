<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../views/header.php';

// Obtener ID del animal desde la URL
$id_animal = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_animal <= 0) {
    redirect('/dashboard-adoptante');
}

// Consulta para obtener información del animal
$query = "SELECT a.*, r.nombre_raza 
          FROM animales a
          LEFT JOIN razas r ON a.id_raza = r.id_raza
          WHERE a.id_animal = $id_animal AND a.activo = 1";
$animal = $conexion->query($query)->fetch_assoc();

if (!$animal) {
    redirect('/dashboard-adoptante');
}

// Consulta para obtener fotos del animal
$fotos = $conexion->query("SELECT * FROM fotos_animales WHERE id_animal = $id_animal")->fetch_all(MYSQLI_ASSOC);

// Consulta para verificar si el animal está en favoritos del usuario
$en_favoritos = false;
if (isset($_SESSION['id_usuario'])) {
    $id_usuario = intval($_SESSION['id_usuario']);
    // Verificar existencia de la tabla antes de consultar
    $res_table = $conexion->query("SHOW TABLES LIKE 'favoritos'");
    if ($res_table && $res_table->num_rows > 0) {
        // Usar prepared statement para mayor seguridad
        if ($stmt = $conexion->prepare("SELECT COUNT(*) FROM favoritos WHERE id_usuario = ? AND id_animal = ?")) {
            $stmt->bind_param('ii', $id_usuario, $id_animal);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $en_favoritos = ($count > 0);
            $stmt->close();
        } else {
            // Si falla la preparación, evitar el fatal y marcar como no favorito
            // error_log("prepare failed favoritos: " . $conexion->error);
            $en_favoritos = false;
        }
    } else {
        // Tabla no existe; evitar consulta que provoca el fatal
        $en_favoritos = false;
    }
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../views/dashboardAdoptante.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item"><a href="../../views/dashboardAdoptante.php">Adoptar</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($animal['nombre']) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-4">
        <!-- Galería de fotos -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <?php if (!empty($fotos)): ?>
                        <div id="animalCarousel" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner rounded-3">
                                <?php foreach ($fotos as $index => $foto): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="<?= htmlspecialchars($foto['url_foto']) ?>"
                                            class="d-block w-100"
                                            alt="<?= htmlspecialchars($animal['nombre']) ?>"
                                            style="height: 500px; object-fit: cover;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#animalCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#animalCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                            <div class="carousel-indicators">
                                <?php foreach ($fotos as $index => $foto): ?>
                                    <button type="button" data-bs-target="#animalCarousel"
                                        data-bs-slide-to="<?= $index ?>"
                                        class="<?= $index === 0 ? 'active' : '' ?>"
                                        aria-current="<?= $index === 0 ? 'true' : 'false' ?>"
                                        style="background-image: url('<?= htmlspecialchars($foto['url_foto']) ?>'); background-size: cover;">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-image d-flex align-items-center justify-content-center" style="height: 500px;">
                            <i class="fas fa-paw fa-5x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Información del animal -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h1 class="h2 mb-0"><?= htmlspecialchars($animal['nombre']) ?></h1>
                        <!-- Se eliminó el botón de favoritos -->
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-primary">
                            <i class="fas fa-<?= strtolower($animal['especie']) == 'perro' ? 'dog' : 'cat' ?> me-1"></i>
                            <?= $animal['especie'] ?>
                        </span>
                        <span class="badge bg-secondary">
                            <i class="fas fa-<?= strtolower($animal['sexo']) == 'macho' ? 'mars' : 'venus' ?> me-1"></i>
                            <?= $animal['sexo'] ?>
                        </span>
                        <span class="badge bg-info">
                            <i class="fas fa-birthday-cake me-1"></i>
                            <?= $animal['edad_anios'] ?> años
                        </span>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-ruler-combined me-1"></i>
                            <?= $animal['tamanio'] ?>
                        </span>
                        <?php if ($animal['esterilizado']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Esterilizado
                            </span>
                        <?php endif; ?>
                        <?php if ($animal['vacunado']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-syringe me-1"></i>
                                Vacunado
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <h3 class="h5 mb-3">Sobre <?= htmlspecialchars($animal['nombre']) ?></h3>
                        <p class="mb-0"><?= !empty($animal['descripcion']) ? nl2br(htmlspecialchars($animal['descripcion'])) : 'Este adorable compañero está buscando un hogar lleno de amor.' ?></p>
                    </div>

                    <div class="mb-4">
                        <h3 class="h5 mb-3">Historia</h3>
                        <p class="mb-0"><?= !empty($animal['historia']) ? nl2br(htmlspecialchars($animal['historia'])) : 'No tenemos mucha información sobre la historia de este animalito, pero estamos seguros que merece una segunda oportunidad.' ?></p>
                    </div>

                    <div class="mb-4">
                        <h3 class="h5 mb-3">Estado de salud</h3>
                        <p class="mb-0"><?= !empty($animal['estado_salud']) ? nl2br(htmlspecialchars($animal['estado_salud'])) : 'Saludable' ?></p>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <a href="/patitas/adoptante/solicitar-adopcion?id=<?= $id_animal ?>" class="btn btn-primary btn-lg py-3">
                        <i class="fas fa-heart me-2"></i> Adoptar a <?= htmlspecialchars($animal['nombre']) ?>
                        </a>
                        <a href="/patitas/dashboard-adoptante" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Volver a la lista
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
