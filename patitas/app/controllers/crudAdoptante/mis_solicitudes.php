<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../views/header.php';

$id_usuario = $_SESSION['id_usuario'];

// Obtener todas las solicitudes del usuario
$query = "SELECT sa.*, a.nombre as nombre_animal, a.especie, 
                 DATE_FORMAT(sa.fecha_solicitud, '%d/%m/%Y %H:%i') as fecha_formateada
          FROM solicitudes_adopcion sa
          JOIN animales a ON sa.id_animal = a.id_animal
          JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
          WHERE ad.id_usuario = $id_usuario
          ORDER BY sa.fecha_solicitud DESC";
$result = $conexion->query($query);
if ($result) {
    $solicitudes = $result->fetch_all(MYSQLI_ASSOC);
} else {
    die("Error en la consulta: " . $conexion->error);
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../views/dashboardAdoptante.php"><i class="fas fa-home"></i> Inicio</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mis Solicitudes</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0"><i class="fas fa-clipboard-list me-2"></i> Mis Solicitudes</h1>
                <a href="../../views/dashboardAdoptante.php" class="btn btn-outline-primary">
                    <i class="fas fa-paw me-2"></i> Ver animales disponibles
                </a>
            </div>

            <?php if (empty($solicitudes)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clipboard-list fa-4x text-muted mb-4"></i>
                        <h3 class="h4 mb-3">No tienes solicitudes de adopción</h3>
                        <p class="text-muted mb-4">Parece que aún no has aplicado para adoptar a ningún animal.</p>
                        <a href="../../views/dashboardAdoptante.php" class="btn btn-primary px-4">
                            <i class="fas fa-paw me-2"></i> Ver animales disponibles
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Animal</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($solicitudes as $solicitud): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($solicitud['foto_principal'])): ?>
                                                        <img src="<?= htmlspecialchars($solicitud['foto_principal']) ?>"
                                                            alt="<?= htmlspecialchars($solicitud['nombre_animal']) ?>"
                                                            class="rounded me-3" width="50" height="50" style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded d-flex align-items-center justify-content-center me-3"
                                                            style="width: 50px; height: 50px;">
                                                            <i class="fas fa-paw text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($solicitud['nombre_animal']) ?></h6>
                                                        <small class="text-muted"><?= $solicitud['especie'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= $solicitud['fecha_formateada'] ?></td>
                                            <td>
                                                <?php
                                                $badge_class = [
                                                    'Pendiente' => 'bg-warning text-dark',
                                                    'Aprobada' => 'bg-success',
                                                    'Rechazada' => 'bg-danger',
                                                    'En revisión' => 'bg-info'
                                                ][$solicitud['estado']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?= $badge_class ?>"><?= $solicitud['estado'] ?></span>
                                            </td>
                                            <td>
                                                <a href="ver_solicitud.php?id=<?= $solicitud['id_solicitud'] ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                    title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($solicitud['estado'] == 'Pendiente'): ?>
                                                    <a href="cancelar_solicitud.php?id=<?= $solicitud['id_solicitud'] ?>"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Cancelar solicitud"
                                                        onclick="return confirm('¿Estás seguro de cancelar esta solicitud?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
