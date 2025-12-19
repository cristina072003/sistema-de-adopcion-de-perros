<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/conexion.php';
$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanitizar datos
    $nombre_completo = $conexion->real_escape_string(trim($_POST['nombre_completo']));
    $correo = $conexion->real_escape_string(trim($_POST['correo']));
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $telefono = $conexion->real_escape_string(trim($_POST['telefono']));
    $direccion = $conexion->real_escape_string(trim($_POST['direccion']));
    $documento_identidad = $conexion->real_escape_string(trim($_POST['documento_identidad']));
    $fecha_nacimiento = $conexion->real_escape_string(trim($_POST['fecha_nacimiento']));
    $ocupacion = $conexion->real_escape_string(trim($_POST['ocupacion']));
    $experiencia_con_animales = isset($_POST['experiencia_con_animales']) ? 1 : 0;
    $descripcion_vivienda = $conexion->real_escape_string(trim($_POST['descripcion_vivienda']));
    $otros_animales = $conexion->real_escape_string(trim($_POST['otros_animales']));
    $tiempo_en_casa = $conexion->real_escape_string(trim($_POST['tiempo_en_casa']));
    $referencia_personal = $conexion->real_escape_string(trim($_POST['referencia_personal']));

    // Validaciones
    if (empty($nombre_completo)) {
        $errores[] = "El nombre completo es requerido";
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    } else {
        // Verificar si el correo ya existe
        $existe = $conexion->query("SELECT id_usuario FROM usuarios WHERE correo = '$correo'");
        if ($existe->num_rows > 0) {
            $errores[] = "Este correo electrónico ya está registrado";
        }
    }

    if (strlen($contrasena) < 8) {
        $errores[] = "La contraseña debe tener al menos 8 caracteres";
    }

    if ($contrasena !== $confirmar_contrasena) {
        $errores[] = "Las contraseñas no coinciden";
    }

    if (empty($documento_identidad)) {
        $errores[] = "El documento de identidad es requerido";
    } else {
        // Verificar si el documento ya existe
        $existe = $conexion->query("SELECT id_adoptante FROM adoptantes WHERE documento_identidad = '$documento_identidad'");
        if ($existe->num_rows > 0) {
            $errores[] = "Este documento de identidad ya está registrado";
        }
    }

    if (empty($fecha_nacimiento)) {
        $errores[] = "La fecha de nacimiento es requerida";
    } else {
        $fecha_nac = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nac)->y;

        if ($edad < 18) {
            $errores[] = "Debes ser mayor de 18 años para registrarte";
        }
    }

    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        $conexion->begin_transaction();

        try {
            // Guardar la contraseña sin cifrar
            $contrasena_hash = $contrasena;

            // Insertar en tabla usuarios
            $conexion->query("
                INSERT INTO usuarios (
                    correo, 
                    contrasena, 
                    rol, 
                    activo
                ) VALUES (
                    '$correo',
                    '$contrasena_hash',
                    'adoptante',
                    1
                )
            ");

            $id_usuario = $conexion->insert_id;

            // Insertar en tabla adoptantes
            $conexion->query("
                INSERT INTO adoptantes (
                    id_usuario,
                    nombre_completo,
                    telefono,
                    direccion,
                    documento_identidad,
                    fecha_nacimiento,
                    ocupacion,
                    experiencia_con_animales,
                    descripcion_vivienda,
                    otros_animales,
                    tiempo_en_casa,
                    referencia_personal
                ) VALUES (
                    $id_usuario,
                    '$nombre_completo',
                    '$telefono',
                    '$direccion',
                    '$documento_identidad',
                    '$fecha_nacimiento',
                    '$ocupacion',
                    $experiencia_con_animales,
                    '$descripcion_vivienda',
                    '$otros_animales',
                    '$tiempo_en_casa',
                    '$referencia_personal'
                )
            ");

            $conexion->commit();
            $exito = true;

            // Elimina la redirección por header aquí
            // header("Refresh: 3; url= loginUsuario.php");
            // exit;
        } catch (Exception $e) {
            $conexion->rollback();
            $errores[] = "Error al registrar: " . $e->getMessage();
        }
    }
}

// Incluye menu.php después de cualquier posible redirección
require_once __DIR__ . '/../menu.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .register-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h2 {
            color: var(--primary);
            font-weight: 700;
        }

        .register-header p {
            color: var(--dark);
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
        }

        .btn-register {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-register:hover {
            background-color: #ff4d6d;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(255, 107, 139, 0.3);
            color: white;
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

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: var(--primary);
            font-weight: 600;
        }

        .error-list {
            list-style-type: none;
            padding-left: 0;
        }

        .error-item {
            color: #dc3545;
            margin-bottom: 5px;
        }

        .success-message {
            color: #28a745;
            font-weight: 600;
            text-align: center;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h2><i class="fas fa-paw me-2"></i>Registro de Adoptante</h2>
                <p>Completa el formulario para registrarte y poder adoptar una mascota</p>
            </div>

            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul class="error-list">
                        <?php foreach ($errores as $error): ?>
                            <li class="error-item"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <div class="alert alert-success">
                    <p class="success-message">
                        <i class="fas fa-check-circle me-2"></i>
                        ¡Registro exitoso! Serás redirigido al inicio de sesión en unos segundos.
                    </p>
                </div>
                <script>
                    setTimeout(function() {
                        window.location.href = "loginUsuario.php";
                    }, 3000);
                </script>
            <?php else: ?>
                <form method="post">
                    <h3 class="section-title">Información de Cuenta</h3>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                                value="<?= isset($_POST['nombre_completo']) ? htmlspecialchars($_POST['nombre_completo']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="correo" class="form-label">Correo Electrónico *</label>
                            <input type="email" class="form-control" id="correo" name="correo"
                                value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="contrasena" class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                            <small class="text-muted">Mínimo 8 caracteres</small>
                        </div>
                        <div class="col-md-6">
                            <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                        </div>
                    </div>

                    <h3 class="section-title mt-4">Información Personal</h3>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="documento_identidad" class="form-label">Documento de Identidad *</label>
                            <input type="text" class="form-control" id="documento_identidad" name="documento_identidad"
                                value="<?= isset($_POST['documento_identidad']) ? htmlspecialchars($_POST['documento_identidad']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                value="<?= isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : '' ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="ocupacion" class="form-label">Ocupación</label>
                            <input type="text" class="form-control" id="ocupacion" name="ocupacion"
                                value="<?= isset($_POST['ocupacion']) ? htmlspecialchars($_POST['ocupacion']) : '' ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección Completa *</label>
                        <textarea class="form-control" id="direccion" name="direccion" rows="2" required><?= isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : '' ?></textarea>
                    </div>

                    <h3 class="section-title mt-4">Información para Adopción</h3>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="experiencia_con_animales" name="experiencia_con_animales"
                                <?= isset($_POST['experiencia_con_animales']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="experiencia_con_animales">
                                ¿Tienes experiencia previa con mascotas?
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion_vivienda" class="form-label">Descripción de tu vivienda *</label>
                        <textarea class="form-control" id="descripcion_vivienda" name="descripcion_vivienda" rows="2" required><?= isset($_POST['descripcion_vivienda']) ? htmlspecialchars($_POST['descripcion_vivienda']) : '' ?></textarea>
                        <small class="text-muted">Ej: Casa con patio, departamento pequeño, etc.</small>
                    </div>

                    <div class="mb-3">
                        <label for="otros_animales" class="form-label">¿Tienes otros animales? Describe</label>
                        <textarea class="form-control" id="otros_animales" name="otros_animales" rows="2"><?= isset($_POST['otros_animales']) ? htmlspecialchars($_POST['otros_animales']) : '' ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tiempo_en_casa" class="form-label">Tiempo que pasas en casa *</label>
                            <select class="form-select" id="tiempo_en_casa" name="tiempo_en_casa" required>
                                <option value="">Seleccionar...</option>
                                <option value="Todo el día" <?= (isset($_POST['tiempo_en_casa']) && $_POST['tiempo_en_casa'] == 'Todo el día') ? 'selected' : '' ?>>Todo el día</option>
                                <option value="Mayoría del día" <?= (isset($_POST['tiempo_en_casa']) && $_POST['tiempo_en_casa'] == 'Mayoría del día') ? 'selected' : '' ?>>Mayoría del día</option>
                                <option value="Medio día" <?= (isset($_POST['tiempo_en_casa']) && $_POST['tiempo_en_casa'] == 'Medio día') ? 'selected' : '' ?>>Medio día</option>
                                <option value="Poco tiempo" <?= (isset($_POST['tiempo_en_casa']) && $_POST['tiempo_en_casa'] == 'Poco tiempo') ? 'selected' : '' ?>>Poco tiempo</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="referencia_personal" class="form-label">Referencia Personal *</label>
                            <input type="text" class="form-control" id="referencia_personal" name="referencia_personal"
                                value="<?= isset($_POST['referencia_personal']) ? htmlspecialchars($_POST['referencia_personal']) : '' ?>" required>
                            <small class="text-muted">Nombre y teléfono de un contacto</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terminos" name="terminos" required>
                            <label class="form-check-label" for="terminos">
                                Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#terminosModal">términos y condiciones</a> *
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i> Registrarse
                    </button>

                    <div class="login-link">
                        <p>¿Ya tienes una cuenta? <a href="http://localhost/PATITAS/app/views/loginViews/loginUsuario.php">Inicia sesión aquí</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Términos y Condiciones -->
    <div class="modal fade" id="terminosModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Términos y Condiciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4>Política de Adopción</h4>
                    <p>El proceso de adopción está sujeto a verificación de información y aprobación por parte del refugio. Nos reservamos el derecho de rechazar solicitudes sin necesidad de explicación.</p>

                    <h4 class="mt-4">Compromisos del Adoptante</h4>
                    <ul>
                        <li>Proveer alimentación adecuada y atención veterinaria</li>
                        <li>Mantener al animal en condiciones de higiene y seguridad</li>
                        <li>No utilizar al animal para fines de reproducción o lucro</li>
                        <li>Permitir visitas de seguimiento por parte del refugio</li>
                        <li>Notificar al refugio en caso de no poder continuar con la adopción</li>
                    </ul>

                    <h4 class="mt-4">Privacidad de Datos</h4>
                    <p>Toda la información proporcionada será utilizada exclusivamente para el proceso de adopción y no será compartida con terceros sin consentimiento.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación de contraseña en tiempo real
        document.getElementById('contrasena').addEventListener('input', function() {
            const contrasena = this.value;
            const feedback = document.getElementById('feedback-contrasena');

            if (contrasena.length > 0 && contrasena.length < 8) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Validar que las contraseñas coincidan
        document.getElementById('confirmar_contrasena').addEventListener('input', function() {
            const contrasena = document.getElementById('contrasena').value;
            const confirmacion = this.value;

            if (confirmacion !== contrasena && confirmacion.length > 0) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Validar edad mínima
        document.getElementById('fecha_nacimiento').addEventListener('change', function() {
            const fechaNac = new Date(this.value);
            const hoy = new Date();
            let edad = hoy.getFullYear() - fechaNac.getFullYear();
            const mes = hoy.getMonth() - fechaNac.getMonth();

            if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
                edad--;
            }

            if (edad < 18) {
                this.classList.add('is-invalid');
                document.getElementById('error-edad').style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('error-edad').style.display = 'none';
            }
        });
    </script>
</body>

</html>