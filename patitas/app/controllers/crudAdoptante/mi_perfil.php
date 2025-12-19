<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../config/conexion.php';

// Obtener información del adoptante
$id_usuario = $_SESSION['id_usuario'];
$query = "SELECT * FROM adoptantes WHERE id_usuario = $id_usuario";
$adoptante = $conexion->query($query)->fetch_assoc();

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_completo = $conexion->real_escape_string(trim($_POST['nombre_completo']));
    $telefono = $conexion->real_escape_string(trim($_POST['telefono']));
    $direccion = $conexion->real_escape_string(trim($_POST['direccion']));
    $ocupacion = $conexion->real_escape_string(trim($_POST['ocupacion']));
    $experiencia = isset($_POST['experiencia_con_animales']) ? 1 : 0;
    $descripcion_vivienda = $conexion->real_escape_string(trim($_POST['descripcion_vivienda']));
    $otros_animales = $conexion->real_escape_string(trim($_POST['otros_animales']));
    $tiempo_en_casa = $conexion->real_escape_string(trim($_POST['tiempo_en_casa']));
    $referencia_personal = $conexion->real_escape_string(trim($_POST['referencia_personal']));

    $query_update = "UPDATE adoptantes SET 
                    nombre_completo = '$nombre_completo',
                    telefono = '$telefono',
                    direccion = '$direccion',
                    ocupacion = '$ocupacion',
                    experiencia_con_animales = $experiencia,
                    descripcion_vivienda = '$descripcion_vivienda',
                    otros_animales = '$otros_animales',
                    tiempo_en_casa = '$tiempo_en_casa',
                    referencia_personal = '$referencia_personal'
                    WHERE id_usuario = $id_usuario";

    if ($conexion->query($query_update)) {
        $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente";
        $_SESSION['nombre_completo'] = $nombre_completo;
        header("Location: mi_perfil.php");
        exit();
    } else {
        $error = "Error al actualizar el perfil: " . $conexion->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Refugio Patitas Felices</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: none;
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, #4B7BEC 0%, #6A5ACD 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            margin-bottom: 1rem;
        }

        .section-title {
            color: #4B7BEC;
            border-bottom: 2px solid #4B7BEC;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 500;
            color: #6c757d;
        }

        .btn-edit {
            background-color: #FF6B8B;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
        }

        .btn-edit:hover {
            background-color: #e05575;
        }
    </style>
</head>

<body>
    <?php require_once __DIR__ . '/../../views/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard_adoptante.php"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Mi Perfil</li>
                    </ol>
                </nav>

                <?php if (isset($_SESSION['mensaje_exito'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['mensaje_exito'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_exito']); ?>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card profile-card mb-4">
                    <div class="profile-header">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($adoptante['nombre_completo']) ?>&background=4B7BEC&color=fff"
                            alt="Avatar" class="profile-avatar">
                        <h2><?= htmlspecialchars($adoptante['nombre_completo']) ?></h2>
                        <p class="mb-0">
                            Adoptante desde
                            <?= isset($adoptante['fecha_registro']) && $adoptante['fecha_registro'] ? date('d/m/Y', strtotime($adoptante['fecha_registro'])) : 'N/D' ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3 class="section-title"><i class="fas fa-user me-2"></i> Información Personal</h3>

                                    <div class="mb-3">
                                        <label for="nombre_completo" class="form-label info-label">Nombre completo</label>
                                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                                            value="<?= htmlspecialchars($adoptante['nombre_completo']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="telefono" class="form-label info-label">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono"
                                            value="<?= htmlspecialchars($adoptante['telefono']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="direccion" class="form-label info-label">Dirección</label>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="2" required><?= htmlspecialchars($adoptante['direccion']) ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="ocupacion" class="form-label info-label">Ocupación</label>
                                        <input type="text" class="form-control" id="ocupacion" name="ocupacion"
                                            value="<?= htmlspecialchars($adoptante['ocupacion']) ?>">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h3 class="section-title"><i class="fas fa-home me-2"></i> Sobre tu Hogar</h3>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="experiencia_con_animales"
                                            name="experiencia_con_animales" <?= $adoptante['experiencia_con_animales'] ? 'checked' : '' ?>>
                                        <label class="form-check-label info-label" for="experiencia_con_animales">¿Tienes experiencia con animales?</label>
                                    </div>

                                    <div class="mb-3">
                                        <label for="descripcion_vivienda" class="form-label info-label">Descripción de tu vivienda</label>
                                        <textarea class="form-control" id="descripcion_vivienda" name="descripcion_vivienda"
                                            rows="2"><?= htmlspecialchars($adoptante['descripcion_vivienda']) ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="otros_animales" class="form-label info-label">Otros animales en casa</label>
                                        <textarea class="form-control" id="otros_animales" name="otros_animales"
                                            rows="2"><?= htmlspecialchars($adoptante['otros_animales']) ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tiempo_en_casa" class="form-label info-label">Tiempo que pasas en casa</label>
                                        <input type="text" class="form-control" id="tiempo_en_casa" name="tiempo_en_casa"
                                            value="<?= htmlspecialchars($adoptante['tiempo_en_casa']) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="referencia_personal" class="form-label info-label">Referencia personal</label>
                                        <input type="text" class="form-control" id="referencia_personal" name="referencia_personal"
                                            value="<?= htmlspecialchars($adoptante['referencia_personal']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-edit">
                                    <i class="fas fa-save me-2"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>