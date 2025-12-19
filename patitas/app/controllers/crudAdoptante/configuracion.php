<?php
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../config/conexion.php';

// Obtener información del usuario de forma segura
$id_usuario = intval($_SESSION['id_usuario']);
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = ? AND activo = 1");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Volver a obtener la contraseña actual (sin hash)
    $stmt = $conexion->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ? AND activo = 1");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($contrasena_actual);
    $stmt->fetch();
    $stmt->close();

    if (!$contrasena_actual) {
        $error_password = "Usuario no encontrado o inactivo";
    } elseif ($current_password !== $contrasena_actual) {
        $error_password = "La contraseña actual es incorrecta";
    } elseif ($new_password !== $confirm_password) {
        $error_password = "Las nuevas contraseñas no coinciden";
    } elseif (strlen($new_password) < 8) {
        $error_password = "La contraseña debe tener al menos 8 caracteres";
    } else {
        // Actualizar contraseña (sin hash)
        $stmt = $conexion->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $new_password, $id_usuario);
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Contraseña actualizada correctamente";
            $stmt->close();
            header("Location: configuracion.php");
            exit();
        } else {
            $error_password = "Error al actualizar la contraseña: " . $conexion->error;
            $stmt->close();
        }
    }
}

// Procesar cambio de email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_email'])) {
    $new_email = $conexion->real_escape_string(trim($_POST['new_email']));
    $current_password = trim($_POST['password_email']);

    // Volver a obtener la contraseña actual (sin hash)
    $stmt = $conexion->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ? AND activo = 1");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($contrasena_actual);
    $stmt->fetch();
    $stmt->close();

    if (!$contrasena_actual) {
        $error_email = "Usuario no encontrado o inactivo";
    } elseif ($current_password !== $contrasena_actual) {
        $error_email = "Contraseña incorrecta";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error_email = "Ingresa un email válido";
    } else {
        // Verificar si el email ya existe
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE correo = ? AND id_usuario != ?");
        $stmt->bind_param("si", $new_email, $id_usuario);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error_email = "Este email ya está en uso";
            $stmt->close();
        } else {
            $stmt->close();
            // Actualizar email
            $stmt = $conexion->prepare("UPDATE usuarios SET correo = ? WHERE id_usuario = ?");
            $stmt->bind_param("si", $new_email, $id_usuario);
            if ($stmt->execute()) {
                $_SESSION['correo'] = $new_email;
                $_SESSION['mensaje_exito'] = "Email actualizado correctamente";
                $stmt->close();
                header("Location: configuracion.php");
                exit();
            } else {
                $error_email = "Error al actualizar el email: " . $conexion->error;
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Refugio Patitas Felices</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4B7BEC;
            --secondary-color: #FF6B8B;
            --dark-color: #2C3E50;
            --light-color: #F8F9FA;
        }

        .settings-card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: none;
            overflow: hidden;
        }

        .settings-section {
            border-bottom: 1px solid #eee;
            padding: 2rem;
        }

        .settings-section:last-child {
            border-bottom: none;
        }

        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            color: white;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            background-color: #3a6bd8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(75, 123, 236, 0.2);
            color: white;
        }

        .form-password {
            max-width: 500px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(75, 123, 236, 0.25);
        }

        .alert {
            border-radius: 8px;
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
                        <li class="breadcrumb-item active" aria-current="page">Configuración</li>
                    </ol>
                </nav>

                <?php if (isset($_SESSION['mensaje_exito'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= $_SESSION['mensaje_exito'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_exito']); ?>
                <?php endif; ?>

                <div class="card settings-card mb-4">
                    <div class="settings-section">
                        <h2 class="section-title"><i class="fas fa-envelope me-2"></i> Cambiar Email</h2>

                        <?php if (isset($error_email)): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $error_email ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="form-password">
                            <input type="hidden" name="cambiar_email" value="1">

                            <div class="mb-3">
                                <label for="current_email" class="form-label">Email actual</label>
                                <input type="email" class="form-control" id="current_email"
                                    value="<?= htmlspecialchars($usuario['correo']) ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="new_email" class="form-label">Nuevo email</label>
                                <input type="email" class="form-control" id="new_email" name="new_email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password_email" class="form-label">Confirma tu contraseña</label>
                                <input type="password" class="form-control" id="password_email" name="password_email" required>
                            </div>

                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i> Cambiar Email
                            </button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h2 class="section-title"><i class="fas fa-lock me-2"></i> Cambiar Contraseña</h2>

                        <?php if (isset($error_password)): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $error_password ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="form-password">
                            <input type="hidden" name="cambiar_password" value="1">

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Contraseña actual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nueva contraseña</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">Mínimo 8 caracteres</small>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar nueva contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i> Cambiar Contraseña
                            </button>
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
    <script>
        // Validación básica del formulario
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Validar campos requeridos
                this.querySelectorAll('[required]').forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                // Validación adicional para contraseña
                if (this.querySelector('#new_password') && this.querySelector('#confirm_password')) {
                    const newPass = this.querySelector('#new_password').value;
                    const confirmPass = this.querySelector('#confirm_password').value;

                    if (newPass !== confirmPass) {
                        this.querySelector('#confirm_password').classList.add('is-invalid');
                        isValid = false;
                    }

                    if (newPass.length < 8) {
                        this.querySelector('#new_password').classList.add('is-invalid');
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    // Mostrar mensaje de error general
                    if (!this.querySelector('.alert-danger')) {
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-danger mt-3';
                        alertDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Por favor completa todos los campos correctamente';
                        this.appendChild(alertDiv);
                    }
                }
            });
        });
    </script>
</body>

</html>