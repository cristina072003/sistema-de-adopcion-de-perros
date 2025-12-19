<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../config/checkSessionUsuario.php';
require_once __DIR__ . '../../../views/headerA.php';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar los datos
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $rol = $_POST['rol'];

    // Datos específicos para adoptantes
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $documento_identidad = trim($_POST['documento_identidad'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $ocupacion = trim($_POST['ocupacion'] ?? '');
    $experiencia_con_animales = isset($_POST['experiencia_con_animales']) ? 1 : 0;
    $descripcion_vivienda = trim($_POST['descripcion_vivienda'] ?? '');
    $otros_animales = trim($_POST['otros_animales'] ?? '');
    $tiempo_en_casa = trim($_POST['tiempo_en_casa'] ?? '');
    $referencia_personal = trim($_POST['referencia_personal'] ?? '');

    // Validaciones básicas
    $errores = [];

    if (empty($correo)) {
        $errores[] = "El correo electrónico es obligatorio";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }

    if (empty($contrasena)) {
        $errores[] = "La contraseña es obligatoria";
    } elseif (strlen($contrasena) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    }

    if ($contrasena !== $confirmar_contrasena) {
        $errores[] = "Las contraseñas no coinciden";
    }

    if ($rol === 'adoptante') {
        if (empty($nombre_completo)) {
            $errores[] = "El nombre completo es obligatorio para adoptantes";
        }

        if (empty($telefono)) {
            $errores[] = "El teléfono es obligatorio para adoptantes";
        }

        if (empty($direccion)) {
            $errores[] = "La dirección es obligatoria para adoptantes";
        }

        if (empty($documento_identidad)) {
            $errores[] = "El documento de identidad es obligatorio para adoptantes";
        }

        if (empty($fecha_nacimiento)) {
            $errores[] = "La fecha de nacimiento es obligatoria para adoptantes";
        } elseif (strtotime($fecha_nacimiento) > strtotime('-18 years')) {
            $errores[] = "Debes ser mayor de 18 años para registrarte como adoptante";
        }
    }

    // Si no hay errores, proceder a insertar
    if (empty($errores)) {
        try {
            $conexion->begin_transaction();

            // Verificar si el correo ya existe
            $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
            $stmt->bind_param("s", $correo);
            $stmt->execute();

            if ($stmt->get_result()->num_rows > 0) {
                $errores[] = "El correo electrónico ya está registrado";
                $stmt->close();
            } else {
                $stmt->close();

                // Guardar la contraseña sin cifrar (NO RECOMENDADO para producción)
                $contrasena_guardar = $contrasena;

                // Insertar usuario
                $stmt = $conexion->prepare("
                    INSERT INTO usuarios (correo, contrasena, rol)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("sss", $correo, $contrasena_guardar, $rol);
                $stmt->execute();
                $id_usuario = $conexion->insert_id;
                $stmt->close();

                // Si es adoptante, insertar datos adicionales
                if ($rol === 'adoptante') {
                    $stmt = $conexion->prepare("
                        INSERT INTO adoptantes (
                            id_usuario, nombre_completo, telefono, direccion, documento_identidad,
                            fecha_nacimiento, ocupacion, experiencia_con_animales, descripcion_vivienda,
                            otros_animales, tiempo_en_casa, referencia_personal
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->bind_param(
                        "issssssissss",
                        $id_usuario,
                        $nombre_completo,
                        $telefono,
                        $direccion,
                        $documento_identidad,
                        $fecha_nacimiento,
                        $ocupacion,
                        $experiencia_con_animales,
                        $descripcion_vivienda,
                        $otros_animales,
                        $tiempo_en_casa,
                        $referencia_personal
                    );
                    $stmt->execute();
                    $stmt->close();
                }

                $conexion->commit();

                // Mostrar mensaje de éxito en la misma página
                $mensaje_exito = "¡Usuario registrado exitosamente!";
                // No redirigir
            }
        } catch (Exception $e) {
            $conexion->rollback();
            $errores[] = "Error al registrar el usuario: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4B7BEC;
            --secondary: #6C757D;
            --success: #28A745;
            --info: #17A2B8;
            --warning: #FFC107;
            --danger: #DC3545;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }

        .card-form {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 15px 15px 0 0 !important;
        }

        .form-title {
            color: var(--primary);
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #3A6BD9;
            border-color: #3A6BD9;
        }

        .required-field::after {
            content: "*";
            color: var(--danger);
            margin-left: 4px;
        }

        .adoptante-fields {
            transition: all 0.3s;
            overflow: hidden;
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-form">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="form-title mb-0">
                                <i class="fas fa-user-plus me-2"></i> Registrar Nuevo Usuario
                            </h3>
                            <a href="http://localhost/PATITAS/app/views/dashboardAdministrador.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-home me-2"></i> Inicio
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errores)): ?>
                            <div class="alert alert-danger">
                                <strong>Errores encontrados:</strong>
                                <ul>
                                    <?php foreach ($errores as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($mensaje_exito)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= $mensaje_exito ?>
                            </div>
                        <?php endif; ?>

                        <form action="crearUsuario.php" method="post">
                            <!-- Información básica del usuario -->
                            <h5 class="mb-4 text-primary">Información de Acceso</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="correo" class="form-label required-field">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="correo" name="correo"
                                        value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="rol" class="form-label required-field">Rol</label>
                                    <select class="form-select" id="rol" name="rol" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="administrador" <?= ($_POST['rol'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                        <option value="adoptante" <?= ($_POST['rol'] ?? '') === 'adoptante' ? 'selected' : '' ?>>Adoptante</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="contrasena" class="form-label required-field">Contraseña</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('contrasena')"></i>
                                    </div>
                                    <small class="text-muted">Mínimo 8 caracteres</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="confirmar_contrasena" class="form-label required-field">Confirmar Contraseña</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('confirmar_contrasena')"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Campos específicos para adoptantes -->
                            <div id="adoptanteFields" class="adoptante-fields" style="<?= ($_POST['rol'] ?? '') === 'adoptante' ? 'max-height: 3000px;' : 'max-height: 0;' ?>">
                                <hr>
                                <h5 class="mb-4 text-primary">Información del Adoptante</h5>

                                <div class="mb-3">
                                    <label for="nombre_completo" class="form-label required-field">Nombre Completo</label>
                                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                                        value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>">
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono" class="form-label required-field">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono"
                                            value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="documento_identidad" class="form-label required-field">Documento de Identidad</label>
                                        <input type="text" class="form-control" id="documento_identidad" name="documento_identidad"
                                            value="<?= htmlspecialchars($_POST['documento_identidad'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="direccion" class="form-label required-field">Dirección</label>
                                    <textarea class="form-control" id="direccion" name="direccion" rows="2"><?= htmlspecialchars($_POST['direccion'] ?? '') ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="fecha_nacimiento" class="form-label required-field">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                            max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                                            value="<?= htmlspecialchars($_POST['fecha_nacimiento'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="ocupacion" class="form-label">Ocupación</label>
                                        <input type="text" class="form-control" id="ocupacion" name="ocupacion"
                                            value="<?= htmlspecialchars($_POST['ocupacion'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="experiencia_con_animales" name="experiencia_con_animales"
                                            <?= isset($_POST['experiencia_con_animales']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="experiencia_con_animales">
                                            ¿Tiene experiencia previa con animales?
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="descripcion_vivienda" class="form-label">Descripción de la Vivienda</label>
                                    <textarea class="form-control" id="descripcion_vivienda" name="descripcion_vivienda" rows="2"><?= htmlspecialchars($_POST['descripcion_vivienda'] ?? '') ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="otros_animales" class="form-label">Otros Animales en Casa</label>
                                        <input type="text" class="form-control" id="otros_animales" name="otros_animales"
                                            value="<?= htmlspecialchars($_POST['otros_animales'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="tiempo_en_casa" class="form-label">Tiempo que pasa en casa</label>
                                        <input type="text" class="form-control" id="tiempo_en_casa" name="tiempo_en_casa"
                                            value="<?= htmlspecialchars($_POST['tiempo_en_casa'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="referencia_personal" class="form-label">Referencia Personal</label>
                                    <input type="text" class="form-control" id="referencia_personal" name="referencia_personal"
                                        value="<?= htmlspecialchars($_POST['referencia_personal'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="reset" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-undo me-2"></i> Limpiar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Registrar Usuario
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar/ocultar campos de adoptante según el rol seleccionado
            const rolSelect = document.getElementById('rol');
            const adoptanteFields = document.getElementById('adoptanteFields');

            function toggleAdoptanteFields() {
                if (rolSelect.value === 'adoptante') {
                    adoptanteFields.style.maxHeight = adoptanteFields.scrollHeight + 'px';

                    // Hacer campos obligatorios
                    document.querySelectorAll('#adoptanteFields [required]').forEach(el => {
                        el.required = true;
                    });
                } else {
                    adoptanteFields.style.maxHeight = '0';

                    // Quitar requeridos si no es adoptante
                    document.querySelectorAll('#adoptanteFields [required]').forEach(el => {
                        el.required = false;
                    });
                }
            }

            rolSelect.addEventListener('change', toggleAdoptanteFields);

            // Ejecutar al cargar la página por si hay valores previamente seleccionados
            toggleAdoptanteFields();
        });

        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>