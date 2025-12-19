<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../config/checkSessionUsuario.php';
require_once __DIR__ . '../../../views/headerA.php';
// Mostrar mensaje de éxito solo si viene por GET
$mensaje_exito = '';
if (isset($_GET['exito']) && $_GET['exito'] == 1) {
    $mensaje_exito = "¡Animal registrado exitosamente!";
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar los datos
    $nombre = trim($_POST['nombre']);
    $especie = $_POST['especie'];
    $id_raza = $_POST['id_raza'] ?? null;
    $edad_anios = $_POST['edad_anios'];
    $sexo = $_POST['sexo'];
    $tamanio = $_POST['tamanio'];
    $descripcion = trim($_POST['descripcion']);
    $fecha_ingreso = $_POST['fecha_ingreso'];
    $estado_salud = trim($_POST['estado_salud']);
    $esterilizado = isset($_POST['esterilizado']) ? 1 : 0;
    $vacunado = isset($_POST['vacunado']) ? 1 : 0;
    $estado = $_POST['estado'];
    $historia = trim($_POST['historia']);
    $id_departamento = $_POST['id_departamento'];
    $foto_url = trim($_POST['foto_url'] ?? '');

    // Validaciones básicas
    $errores = [];

    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }

    if (!is_numeric($edad_anios) || $edad_anios < 0 || $edad_anios > 30) {
        $errores[] = "La edad debe ser un número válido entre 0 y 30";
    }

    if (empty($fecha_ingreso)) {
        $errores[] = "La fecha de ingreso es obligatoria";
    }

    // Si no hay errores, proceder a insertar
    if (empty($errores)) {
        try {
            $conexion->begin_transaction();

            // Insertar el animal
            $stmt = $conexion->prepare("
                INSERT INTO animales (
                    nombre, especie, id_raza, edad_anios, sexo, tamanio, 
                    descripcion, fecha_ingreso, estado_salud, esterilizado, 
                    vacunado, estado, historia
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssiisssssiiss",
                $nombre,
                $especie,
                $id_raza,
                $edad_anios,
                $sexo,
                $tamanio,
                $descripcion,
                $fecha_ingreso,
                $estado_salud,
                $esterilizado,
                $vacunado,
                $estado,
                $historia
            );

            $stmt->execute();
            $id_animal = $conexion->insert_id;
            $stmt->close();

            // Asignar departamento
            if ($id_departamento) {
                $stmt = $conexion->prepare("
                    INSERT INTO animal_departamento (id_animal, id_departamento) 
                    VALUES (?, ?)
                ");
                $stmt->bind_param("ii", $id_animal, $id_departamento);
                $stmt->execute();
                $stmt->close();
            }

            // Procesar fotos si se subieron por archivo
            if (!empty($_FILES['fotos']['name'][0])) {
                $fotos_dir = __DIR__ . '/../../views/uploads/animales/';
                if (!is_dir($fotos_dir)) {
                    mkdir($fotos_dir, 0755, true);
                }

                foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                        $nombre_archivo = uniqid() . '_' . basename($_FILES['fotos']['name'][$key]);
                        $ruta_destino = $fotos_dir . $nombre_archivo;

                        if (move_uploaded_file($tmp_name, $ruta_destino)) {
                            // Cambia la ruta para que sea accesible desde el navegador
                            $url_foto = 'uploads/animales/' . $nombre_archivo;

                            $stmt = $conexion->prepare("
                                INSERT INTO fotos_animales (id_animal, url_foto)
                                VALUES (?, ?)
                            ");
                            $stmt->bind_param("is", $id_animal, $url_foto);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }

            // Procesar foto por URL si se proporcionó
            if (!empty($foto_url)) {
                // Validar que sea una URL válida y de imagen
                if (filter_var($foto_url, FILTER_VALIDATE_URL) && @getimagesize($foto_url)) {
                    $stmt = $conexion->prepare("
                        INSERT INTO fotos_animales (id_animal, url_foto)
                        VALUES (?, ?)
                    ");
                    $stmt->bind_param("is", $id_animal, $foto_url);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $conexion->commit();

            // Redirigir para evitar reenvío y limpiar formulario
            header("Location: nuevo_animal.php?exito=1");
            exit();
        } catch (Exception $e) {
            $conexion->rollback();
            $errores[] = "Error al registrar el animal: " . $e->getMessage();
        }
    }
}

// Obtener datos para los selects
$razas_perros = $conexion->query("SELECT id_raza, nombre_raza FROM razas WHERE especie = 'Perro'");
$razas_gatos = $conexion->query("SELECT id_raza, nombre_raza FROM razas WHERE especie = 'Gato'");
$departamentos = $conexion->query("SELECT id_departamento, nombre, provincia FROM departamentos");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Animal - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4B7BEC;
            --secondary: #6C757D;
            --success: #28A745;
            --info: #17A2B8;
            --warning: #FFC107;
            --danger: #DC3545;
            --light: #F8F9FA;
            --dark: #343A40;
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

        .preview-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 10px;
            margin-bottom: 10px;
            border: 2px solid #eee;
        }

        .file-upload {
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .file-upload input[type="file"] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            background: white;
            cursor: inherit;
            display: block;
        }

        .required-field::after {
            content: "*";
            color: var(--danger);
            margin-left: 4px;
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card card-form">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="form-title mb-0">
                                <i class="fas fa-paw me-2"></i> Registrar Nuevo Animal
                            </h3>
                            <div>
                                <a href="http://localhost/PATITAS/app/views/dashboardAdministrador.php" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-home me-2"></i> Inicio
                                </a>
                                <a href="http://localhost/PATITAS/app/views/viewAdministrador/animales.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-list me-2"></i> Volver a Todos los Animales
                                </a>
                            </div>
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
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong><?= htmlspecialchars($mensaje_exito) ?></strong>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                            </div>
                        <?php endif; ?>

                        <form action="nuevo_animal.php" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Información básica -->
                                <div class="col-md-6">
                                    <h5 class="mb-4 text-primary">Información Básica</h5>

                                    <div class="mb-3">
                                        <label for="nombre" class="form-label required-field">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="especie" class="form-label required-field">Especie</label>
                                        <select class="form-select" id="especie" name="especie" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="Perro" <?= (($_POST['especie'] ?? '') === 'Perro' ? 'selected' : '') ?>>Perro</option>
                                            <option value="Gato" <?= (($_POST['especie'] ?? '') === 'Gato' ? 'selected' : '') ?>>Gato</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="id_raza" class="form-label">Raza</label>
                                        <select class="form-select" id="id_raza" name="id_raza">
                                            <option value="">Seleccionar...</option>
                                            <optgroup label="Perros" id="razas-perros">
                                                <?php while ($raza = $razas_perros->fetch_assoc()): ?>
                                                    <option value="<?= $raza['id_raza'] ?>" <?= (($_POST['id_raza'] ?? '') == $raza['id_raza'] ? 'selected' : '') ?>>
                                                        <?= htmlspecialchars($raza['nombre_raza']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </optgroup>
                                            <optgroup label="Gatos" id="razas-gatos">
                                                <?php while ($raza = $razas_gatos->fetch_assoc()): ?>
                                                    <option value="<?= $raza['id_raza'] ?>" <?= (($_POST['id_raza'] ?? '') == $raza['id_raza'] ? 'selected' : '') ?>>
                                                        <?= htmlspecialchars($raza['nombre_raza']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </optgroup>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="edad_anios" class="form-label required-field">Edad (años)</label>
                                            <input type="number" class="form-control" id="edad_anios" name="edad_anios"
                                                min="0" max="30" value="<?= htmlspecialchars($_POST['edad_anios'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="sexo" class="form-label required-field">Sexo</label>
                                            <select class="form-select" id="sexo" name="sexo" required>
                                                <option value="">Seleccionar...</option>
                                                <option value="Macho" <?= (($_POST['sexo'] ?? '') === 'Macho' ? 'selected' : '') ?>>Macho</option>
                                                <option value="Hembra" <?= (($_POST['sexo'] ?? '') === 'Hembra' ? 'selected' : '') ?>>Hembra</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tamanio" class="form-label required-field">Tamaño</label>
                                        <select class="form-select" id="tamanio" name="tamanio" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="Pequeño" <?= (($_POST['tamanio'] ?? '') === 'Pequeño' ? 'selected' : '') ?>>Pequeño</option>
                                            <option value="Mediano" <?= (($_POST['tamanio'] ?? '') === 'Mediano' ? 'selected' : '') ?>>Mediano</option>
                                            <option value="Grande" <?= (($_POST['tamanio'] ?? '') === 'Grande' ? 'selected' : '') ?>>Grande</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Información adicional -->
                                <div class="col-md-6">
                                    <h5 class="mb-4 text-primary">Información Adicional</h5>

                                    <div class="mb-3">
                                        <label for="fecha_ingreso" class="form-label required-field">Fecha de Ingreso</label>
                                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso"
                                            value="<?= htmlspecialchars($_POST['fecha_ingreso'] ?? date('Y-m-d')) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="estado" class="form-label required-field">Estado</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="Disponible" <?= (($_POST['estado'] ?? '') === 'Disponible' ? 'selected' : '') ?>>Disponible</option>
                                            <option value="En adopción" <?= (($_POST['estado'] ?? '') === 'En adopción' ? 'selected' : '') ?>>En adopción</option>
                                            <option value="Adoptado" <?= (($_POST['estado'] ?? '') === 'Adoptado' ? 'selected' : '') ?>>Adoptado</option>
                                            <option value="Reservado" <?= (($_POST['estado'] ?? '') === 'Reservado' ? 'selected' : '') ?>>Reservado</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="id_departamento" class="form-label">Ubicación</label>
                                        <select class="form-select" id="id_departamento" name="id_departamento">
                                            <option value="">Seleccionar departamento...</option>
                                            <?php while ($dep = $departamentos->fetch_assoc()): ?>
                                                <option value="<?= $dep['id_departamento'] ?>"
                                                    <?= (($_POST['id_departamento'] ?? '') == $dep['id_departamento'] ? 'selected' : '') ?>>
                                                    <?= htmlspecialchars($dep['nombre'] . ' - ' . $dep['provincia']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="esterilizado" name="esterilizado"
                                                    <?= (($_POST['esterilizado'] ?? '') ? 'checked' : '') ?>>
                                                <label class="form-check-label" for="esterilizado">Esterilizado</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="vacunado" name="vacunado"
                                                    <?= (($_POST['vacunado'] ?? '') ? 'checked' : '') ?>>
                                                <label class="form-check-label" for="vacunado">Vacunado</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="estado_salud" class="form-label">Estado de salud</label>
                                        <input type="text" class="form-control" id="estado_salud" name="estado_salud"
                                            value="<?= htmlspecialchars($_POST['estado_salud'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="historia" class="form-label">Historia</label>
                                    <textarea class="form-control" id="historia" name="historia" rows="3"><?= htmlspecialchars($_POST['historia'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <!-- Subida de fotos -->
                            <div class="mb-4">
                                <h5 class="mb-3 text-primary">Fotos del Animal</h5>
                                <div class="mb-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tipo_foto" id="foto_archivo" value="archivo" checked>
                                        <label class="form-check-label" for="foto_archivo">Subir archivo</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tipo_foto" id="foto_url" value="url" <?= (isset($_POST['tipo_foto']) && $_POST['tipo_foto'] === 'url') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="foto_url">Por URL</label>
                                    </div>
                                </div>
                                <div id="foto-archivo-group">
                                    <div class="file-upload btn btn-outline-primary mb-3">
                                        <i class="fas fa-camera me-2"></i> Seleccionar Fotos
                                        <input type="file" id="fotos" name="fotos[]" multiple accept="image/*">
                                    </div>
                                    <small class="text-muted d-block">Puedes seleccionar múltiples fotos</small>
                                    <div id="preview-container" class="mt-3 d-flex flex-wrap"></div>
                                </div>
                                <div id="foto-url-group" style="display:none;">
                                    <label for="foto_url_input" class="form-label">URL de la imagen</label>
                                    <input type="url" class="form-control" id="foto_url_input" name="foto_url" placeholder="https://ejemplo.com/imagen.jpg" value="<?= htmlspecialchars($_POST['foto_url'] ?? '') ?>">
                                    <small class="text-muted d-block">Pega la URL de una imagen válida (jpg, png, etc.)</small>
                                    <div id="preview-url-container" class="mt-3"></div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="reset" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-undo me-2"></i> Limpiar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Registrar Animal
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
            // Mostrar/ocultar razas según especie seleccionada
            const especieSelect = document.getElementById('especie');
            const razasPerros = document.getElementById('razas-perros');
            const razasGatos = document.getElementById('razas-gatos');

            function actualizarVisibilidadRazas() {
                if (especieSelect.value === 'Perro') {
                    razasPerros.style.display = 'block';
                    razasGatos.style.display = 'none';
                } else if (especieSelect.value === 'Gato') {
                    razasPerros.style.display = 'none';
                    razasGatos.style.display = 'block';
                } else {
                    razasPerros.style.display = 'none';
                    razasGatos.style.display = 'none';
                }
            }

            especieSelect.addEventListener('change', actualizarVisibilidadRazas);
            actualizarVisibilidadRazas(); // Ejecutar al cargar la página

            // Mostrar/ocultar campos de foto según selección
            const radioArchivo = document.getElementById('foto_archivo');
            const radioUrl = document.getElementById('foto_url');
            const grupoArchivo = document.getElementById('foto-archivo-group');
            const grupoUrl = document.getElementById('foto-url-group');
            const urlInput = document.getElementById('foto_url_input');
            const previewUrlContainer = document.getElementById('preview-url-container');

            function actualizarTipoFoto() {
                if (radioArchivo.checked) {
                    grupoArchivo.style.display = 'block';
                    grupoUrl.style.display = 'none';
                } else {
                    grupoArchivo.style.display = 'none';
                    grupoUrl.style.display = 'block';
                }
            }
            radioArchivo.addEventListener('change', actualizarTipoFoto);
            radioUrl.addEventListener('change', actualizarTipoFoto);
            actualizarTipoFoto();

            // Previsualización de imágenes por archivo
            const fileInput = document.getElementById('fotos');
            const previewContainer = document.getElementById('preview-container');
            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    previewContainer.innerHTML = '';
                    if (this.files) {
                        Array.from(this.files).forEach(file => {
                            if (file.type.startsWith('image/')) {
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.classList.add('preview-image');
                                    previewContainer.appendChild(img);
                                }
                                reader.readAsDataURL(file);
                            }
                        });
                    }
                });
            }

            // Previsualización de imagen por URL
            if (urlInput) {
                urlInput.addEventListener('input', function() {
                    previewUrlContainer.innerHTML = '';
                    const url = urlInput.value.trim();
                    if (url && (url.endsWith('.jpg') || url.endsWith('.jpeg') || url.endsWith('.png') || url.endsWith('.gif') || url.endsWith('.webp'))) {
                        const img = document.createElement('img');
                        img.src = url;
                        img.classList.add('preview-image');
                        img.onerror = function() {
                            previewUrlContainer.innerHTML = '<span class="text-danger">No se pudo cargar la imagen.</span>';
                        }
                        previewUrlContainer.appendChild(img);
                    }
                });
                // Mostrar preview si ya hay valor (por recarga con error)
                if (urlInput.value) {
                    urlInput.dispatchEvent(new Event('input'));
                }
            }
        });
    </script>
</body>

</html>