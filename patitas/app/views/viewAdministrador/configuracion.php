<?php
// Solución: Iniciar sesión antes de usar $_SESSION y validar conexión antes de consultas
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../headerA.php';

// Validar conexión
if (!$conexion) {
    die("Error de conexión a la base de datos.");
}

// Obtener información del usuario actual
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    die("Usuario no autenticado.");
}
$usuario_result = $conexion->query("
    SELECT u.*, a.* 
    FROM usuarios u 
    LEFT JOIN adoptantes a ON u.id_usuario = a.id_usuario 
    WHERE u.id_usuario = $id_usuario
");
if (!$usuario_result) {
    die("Error en la consulta de usuario: " . $conexion->error);
}
$usuario = $usuario_result->fetch_assoc();

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar_perfil'])) {
        try {
            $conexion->begin_transaction();

            // Actualizar datos básicos del usuario
            $correo = $conexion->real_escape_string($_POST['correo']);
            $conexion->query("UPDATE usuarios SET correo = '$correo' WHERE id_usuario = $id_usuario");

            // Si es adoptante, actualizar información adicional
            if ($usuario['rol'] === 'adoptante') {
                $nombre = $conexion->real_escape_string($_POST['nombre']);
                $telefono = $conexion->real_escape_string($_POST['telefono']);
                $direccion = $conexion->real_escape_string($_POST['direccion']);
                $documento = $conexion->real_escape_string($_POST['documento']);
                $fecha_nacimiento = $conexion->real_escape_string($_POST['fecha_nacimiento']);
                $ocupacion = $conexion->real_escape_string($_POST['ocupacion'] ?? '');
                $experiencia = isset($_POST['experiencia']) ? 1 : 0;
                $vivienda = $conexion->real_escape_string($_POST['vivienda'] ?? '');
                $otros_animales = $conexion->real_escape_string($_POST['otros_animales'] ?? '');
                $tiempo_casa = $conexion->real_escape_string($_POST['tiempo_casa'] ?? '');
                $referencia = $conexion->real_escape_string($_POST['referencia'] ?? '');

                $conexion->query("
                    UPDATE adoptantes SET
                        nombre_completo = '$nombre',
                        telefono = '$telefono',
                        direccion = '$direccion',
                        documento_identidad = '$documento',
                        fecha_nacimiento = '$fecha_nacimiento',
                        ocupacion = '$ocupacion',
                        experiencia_con_animales = $experiencia,
                        descripcion_vivienda = '$vivienda',
                        otros_animales = '$otros_animales',
                        tiempo_en_casa = '$tiempo_casa',
                        referencia_personal = '$referencia'
                    WHERE id_usuario = $id_usuario
                ");
            }

            // Procesar cambio de contraseña si se proporcionó
            if (!empty($_POST['nueva_contrasena'])) {
                if ($_POST['nueva_contrasena'] !== $_POST['confirmar_contrasena']) {
                    throw new Exception("Las contraseñas no coinciden");
                }

                $nueva_contrasena = password_hash($_POST['nueva_contrasena'], PASSWORD_DEFAULT);
                $conexion->query("UPDATE usuarios SET contrasena = '$nueva_contrasena' WHERE id_usuario = $id_usuario");
            }

            $conexion->commit();
            $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente";
            header("Location: configuracion.php");
            exit();
        } catch (Exception $e) {
            $conexion->rollback();
            $_SESSION['mensaje_error'] = "Error al actualizar el perfil: " . $e->getMessage();
        }
    }
}

// Obtener configuración del sistema
$config_result = $conexion->query("SELECT * FROM configuracion_sistema LIMIT 1");
if ($config_result && $config_result->num_rows > 0) {
    $configuracion = $config_result->fetch_assoc();
} else {
    // Configuración por defecto si no existe o si hubo error en la consulta
    $configuracion = [
        'nombre_refugio' => 'Refugio de Mascotas',
        'logo' => 'assets/img/logo.png',
        'direccion' => '',
        'telefono' => '',
        'email_contacto' => '',
        'horario_atencion' => 'Lunes a Viernes 9:00 - 18:00',
        'terminos_condiciones' => '',
        'politica_privacidad' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

        .card-main {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 15px 15px 0 0 !important;
        }

        .page-title {
            color: var(--dark);
            font-weight: 600;
        }

        .nav-tabs .nav-link {
            border: none;
            padding: 12px 20px;
            color: #6c757d;
            font-weight: 500;
            border-radius: 8px 8px 0 0;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary);
            background-color: rgba(75, 123, 236, 0.1);
            border-bottom: 2px solid var(--primary);
        }

        .nav-tabs .nav-link:hover:not(.active) {
            color: var(--primary);
            background-color: rgba(75, 123, 236, 0.05);
        }

        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .form-section h5 {
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .logo-preview {
            max-width: 200px;
            max-height: 100px;
            margin-bottom: 15px;
            border: 1px dashed #ddd;
            padding: 10px;
            border-radius: 5px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #28a745;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(26px);
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="card card-main">
            <div class="card-header py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="page-title mb-0">
                        <i class="fas fa-cog me-2"></i> Configuración
                    </h3>
                </div>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['mensaje_exito'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['mensaje_exito'] ?>
                        <?php unset($_SESSION['mensaje_exito']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['mensaje_error'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['mensaje_error'] ?>
                        <?php unset($_SESSION['mensaje_error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Pestañas de configuración -->
                <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="perfil-tab" data-bs-toggle="tab" data-bs-target="#perfil" type="button" role="tab">
                            <i class="fas fa-user me-2"></i> Mi Perfil
                        </button>
                    </li>
                    <?php if ($_SESSION['rol'] === 'administrador'): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema" type="button" role="tab">
                                <i class="fas fa-sliders-h me-2"></i> Sistema
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="usuarios-tab" data-bs-toggle="tab" data-bs-target="#usuarios" type="button" role="tab">
                                <i class="fas fa-users-cog me-2"></i> Usuarios
                            </button>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad" type="button" role="tab">
                            <i class="fas fa-shield-alt me-2"></i> Seguridad
                        </button>
                    </li>
                </ul>

                <!-- Contenido de las pestañas -->
                <div class="tab-content" id="configTabsContent">
                    <!-- Pestaña de Perfil -->
                    <div class="tab-pane fade show active" id="perfil" role="tabpanel">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-section text-center">
                                        <div class="user-avatar mx-auto mb-3">
                                            <i class="fas fa-user fa-3x text-muted"></i>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-camera me-2"></i> Cambiar foto
                                        </button>
                                        <input type="file" id="fotoPerfil" class="d-none" accept="image/*">
                                    </div>

                                    <?php if ($usuario['rol'] === 'adoptante'): ?>
                                        <div class="form-section">
                                            <h5><i class="fas fa-id-card me-2"></i>Información de Adopción</h5>

                                            <div class="mb-3">
                                                <label for="experiencia" class="form-label">Experiencia con animales</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="experiencia" name="experiencia"
                                                        <?= $usuario['experiencia_con_animales'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="experiencia">
                                                        <?= $usuario['experiencia_con_animales'] ? 'Sí tengo experiencia' : 'No tengo experiencia' ?>
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="vivienda" class="form-label">Descripción de vivienda</label>
                                                <textarea class="form-control" id="vivienda" name="vivienda" rows="3"><?= htmlspecialchars($usuario['descripcion_vivienda'] ?? '') ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="otros_animales" class="form-label">Otros animales</label>
                                                <textarea class="form-control" id="otros_animales" name="otros_animales" rows="2"><?= htmlspecialchars($usuario['otros_animales'] ?? '') ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="tiempo_casa" class="form-label">Tiempo en casa</label>
                                                <input type="text" class="form-control" id="tiempo_casa" name="tiempo_casa"
                                                    value="<?= htmlspecialchars($usuario['tiempo_en_casa'] ?? '') ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="referencia" class="form-label">Referencia personal</label>
                                                <input type="text" class="form-control" id="referencia" name="referencia"
                                                    value="<?= htmlspecialchars($usuario['referencia_personal'] ?? '') ?>">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-8">
                                    <div class="form-section">
                                        <h5><i class="fas fa-info-circle me-2"></i>Información Básica</h5>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="correo" class="form-label">Correo electrónico</label>
                                                <input type="email" class="form-control" id="correo" name="correo"
                                                    value="<?= htmlspecialchars($usuario['correo']) ?>" required>
                                            </div>

                                            <?php if ($usuario['rol'] === 'adoptante'): ?>
                                                <div class="col-md-6 mb-3">
                                                    <label for="nombre" class="form-label">Nombre completo</label>
                                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                                        value="<?= htmlspecialchars($usuario['nombre_completo']) ?>" required>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="telefono" class="form-label">Teléfono</label>
                                                    <input type="tel" class="form-control" id="telefono" name="telefono"
                                                        value="<?= htmlspecialchars($usuario['telefono']) ?>" required>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="documento" class="form-label">Documento de identidad</label>
                                                    <input type="text" class="form-control" id="documento" name="documento"
                                                        value="<?= htmlspecialchars($usuario['documento_identidad']) ?>" required>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                                                    <input type="text" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                                        value="<?= htmlspecialchars($usuario['fecha_nacimiento']) ?>" required>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="ocupacion" class="form-label">Ocupación</label>
                                                    <input type="text" class="form-control" id="ocupacion" name="ocupacion"
                                                        value="<?= htmlspecialchars($usuario['ocupacion'] ?? '') ?>">
                                                </div>

                                                <div class="col-12 mb-3">
                                                    <label for="direccion" class="form-label">Dirección</label>
                                                    <textarea class="form-control" id="direccion" name="direccion" rows="2" required><?= htmlspecialchars($usuario['direccion']) ?></textarea>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h5><i class="fas fa-lock me-2"></i>Cambiar Contraseña</h5>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="nueva_contrasena" class="form-label">Nueva contraseña</label>
                                                <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena">
                                                <small class="text-muted">Dejar en blanco para no cambiar</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="confirmar_contrasena" class="form-label">Confirmar contraseña</label>
                                                <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i> Guardar Cambios
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Pestaña de Sistema (solo admin) -->
                    <?php if ($_SESSION['rol'] === 'administrador'): ?>
                        <div class="tab-pane fade" id="sistema" role="tabpanel">
                            <form method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h5><i class="fas fa-info-circle me-2"></i>Información del Refugio</h5>

                                            <div class="mb-3">
                                                <label for="nombre_refugio" class="form-label">Nombre del refugio</label>
                                                <input type="text" class="form-control" id="nombre_refugio" name="nombre_refugio"
                                                    value="<?= htmlspecialchars($configuracion['nombre_refugio']) ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label for="logo" class="form-label">Logo</label>
                                                <?php if (!empty($configuracion['logo'])): ?>
                                                    <img src="<?= htmlspecialchars($configuracion['logo']) ?>" class="logo-preview d-block mb-2" id="logoPreview">
                                                <?php else: ?>
                                                    <div class="logo-preview d-flex align-items-center justify-content-center mb-2" id="logoPreview">
                                                        <i class="fas fa-paw fa-2x text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                            </div>

                                            <div class="mb-3">
                                                <label for="direccion_refugio" class="form-label">Dirección</label>
                                                <textarea class="form-control" id="direccion_refugio" name="direccion_refugio" rows="2"><?= htmlspecialchars($configuracion['direccion'] ?? '') ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="telefono_refugio" class="form-label">Teléfono</label>
                                                <input type="tel" class="form-control" id="telefono_refugio" name="telefono_refugio"
                                                    value="<?= htmlspecialchars($configuracion['telefono'] ?? '') ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="email_contacto" class="form-label">Email de contacto</label>
                                                <input type="email" class="form-control" id="email_contacto" name="email_contacto"
                                                    value="<?= htmlspecialchars($configuracion['email_contacto'] ?? '') ?>">
                                            </div>

                                            <div class="mb-3">
                                                <label for="horario_atencion" class="form-label">Horario de atención</label>
                                                <input type="text" class="form-control" id="horario_atencion" name="horario_atencion"
                                                    value="<?= htmlspecialchars($configuracion['horario_atencion'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-section">
                                            <h5><i class="fas fa-file-alt me-2"></i>Documentos Legales</h5>

                                            <div class="mb-3">
                                                <label for="terminos_condiciones" class="form-label">Términos y Condiciones</label>
                                                <textarea class="form-control" id="terminos_condiciones" name="terminos_condiciones" rows="8"><?= htmlspecialchars($configuracion['terminos_condiciones'] ?? '') ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label for="politica_privacidad" class="form-label">Política de Privacidad</label>
                                                <textarea class="form-control" id="politica_privacidad" name="politica_privacidad" rows="8"><?= htmlspecialchars($configuracion['politica_privacidad'] ?? '') ?></textarea>
                                            </div>
                                        </div>

                                        <div class="text-end mt-3">
                                            <button type="submit" name="guardar_configuracion" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i> Guardar Configuración
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Pestaña de Usuarios (solo admin) -->
                        <div class="tab-pane fade" id="usuarios" role="tabpanel">
                            <div class="form-section">
                                <h5><i class="fas fa-users-cog me-2"></i>Configuración de Usuarios</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Permisos de registro</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="registro_abierto" checked>
                                                <label class="form-check-label" for="registro_abierto">
                                                    Permitir registro de nuevos usuarios
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tipos de registro permitidos</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="registro_adoptantes" checked>
                                                <label class="form-check-label" for="registro_adoptantes">
                                                    Adoptantes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="registro_voluntarios">
                                                <label class="form-check-label" for="registro_voluntarios">
                                                    Voluntarios
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="roles_default" class="form-label">Rol por defecto</label>
                                            <select class="form-select" id="roles_default">
                                                <option value="adoptante">Adoptante</option>
                                                <option value="voluntario">Voluntario</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Verificación requerida</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="verificacion_email" checked>
                                                <label class="form-check-label" for="verificacion_email">
                                                    Verificación por email
                                                </label>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="verificacion_documento">
                                                <label class="form-check-label" for="verificacion_documento">
                                                    Verificación de documentos
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="button" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Guardar Configuración
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Pestaña de Seguridad -->
                    <div class="tab-pane fade" id="seguridad" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-section">
                                    <h5><i class="fas fa-shield-alt me-2"></i>Configuración de Seguridad</h5>

                                    <div class="mb-3">
                                        <label class="form-label">Autenticación de dos factores</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="doble_factor"
                                                <?= $_SESSION['doble_factor'] ?? false ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="doble_factor">
                                                Habilitar autenticación de dos factores
                                            </label>
                                        </div>
                                        <small class="text-muted">Recibirás un código por email al iniciar sesión</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Sesiones activas</label>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Tienes 2 dispositivos conectados actualmente
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-sign-out-alt me-2"></i> Cerrar todas las sesiones
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-section">
                                    <h5><i class="fas fa-history me-2"></i>Actividad Reciente</h5>

                                    <div class="list-group">
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Inicio de sesión</small>
                                                <small class="text-muted">Hoy, 10:30 AM</small>
                                            </div>
                                            <div>Desde Chrome en Windows</div>
                                            <small class="text-muted">IP: 192.168.1.1</small>
                                        </div>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Cambio de contraseña</small>
                                                <small class="text-muted">Ayer, 3:45 PM</small>
                                            </div>
                                            <div>Solicitado desde el sistema</div>
                                        </div>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Inicio de sesión</small>
                                                <small class="text-muted">Ayer, 9:15 AM</small>
                                            </div>
                                            <div>Desde Safari en iPhone</div>
                                            <small class="text-muted">IP: 192.168.1.2</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <!-- CKEditor (para textareas enriquecidos) -->
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>

    <script>
        // Inicializar datepicker para fecha de nacimiento
        flatpickr("#fecha_nacimiento", {
            dateFormat: "Y-m-d",
            locale: "es",
            maxDate: "today"
        });

        // Inicializar CKEditor para textareas
        if (document.getElementById('terminos_condiciones')) {
            CKEDITOR.replace('terminos_condiciones');
            CKEDITOR.replace('politica_privacidad');
        }

        // Preview de imagen al seleccionar logo
        document.getElementById('logo')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('logoPreview').innerHTML =
                        `<img src="${event.target.result}" class="img-fluid">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Preview de imagen para foto de perfil
        document.getElementById('fotoPerfil')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.querySelector('.user-avatar').innerHTML =
                        `<img src="${event.target.result}" class="img-fluid rounded-circle">`;
                };
                reader.readAsDataURL(file);
            }
        });

        // Cambiar texto del switch de experiencia
        document.getElementById('experiencia')?.addEventListener('change', function() {
            const label = document.querySelector('label[for="experiencia"]');
            label.textContent = this.checked ? 'Sí tengo experiencia' : 'No tengo experiencia';
        });
    </script>
</body>

</html>