<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../config/checkSessionUsuario.php';
require_once __DIR__ . '../../../views/headerA.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: animales.php");
    exit();
}

$id_animal = (int)$_GET['id'];

// Obtener información del animal
$animal = $conexion->query("
    SELECT a.*, ad.id_departamento
    FROM animales a
    LEFT JOIN animal_departamento ad ON a.id_animal = ad.id_animal
    WHERE a.id_animal = $id_animal AND a.activo = 1
")->fetch_assoc();

if (!$animal) {
    header("Location: animales.php");
    exit();
}

// Obtener fotos del animal
$fotos = $conexion->query("
    SELECT * FROM fotos_animales 
    WHERE id_animal = $id_animal
    ORDER BY id_foto
")->fetch_all(MYSQLI_ASSOC);

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

    // Si no hay errores, proceder a actualizar
    if (empty($errores)) {
        try {
            $conexion->begin_transaction();

            // Actualizar el animal
            $stmt = $conexion->prepare("
                UPDATE animales SET
                    nombre = ?, especie = ?, id_raza = ?, edad_anios = ?, sexo = ?, tamanio = ?, 
                    descripcion = ?, fecha_ingreso = ?, estado_salud = ?, esterilizado = ?, 
                    vacunado = ?, estado = ?, historia = ?
                WHERE id_animal = ?
            ");

            $stmt->bind_param(
                "ssiisssssiissi",
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
                $historia,
                $id_animal
            );

            $stmt->execute();
            $stmt->close();

            // Actualizar departamento
            $conexion->query("DELETE FROM animal_departamento WHERE id_animal = $id_animal");

            if ($id_departamento) {
                $stmt = $conexion->prepare("
                    INSERT INTO animal_departamento (id_animal, id_departamento) 
                    VALUES (?, ?)
                ");
                $stmt->bind_param("ii", $id_animal, $id_departamento);
                $stmt->execute();
                $stmt->close();
            }

            // Procesar eliminación de fotos
            if (!empty($_POST['fotos_eliminar'])) {
                foreach ($_POST['fotos_eliminar'] as $id_foto) {
                    // Primero obtener la ruta para eliminar el archivo
                    $foto = $conexion->query("SELECT url_foto FROM fotos_animales WHERE id_foto = $id_foto")->fetch_assoc();

                    if ($foto) {
                        $ruta_archivo = __DIR__ . '../../' . $foto['url_foto'];
                        if (file_exists($ruta_archivo)) {
                            unlink($ruta_archivo);
                        }

                        $conexion->query("DELETE FROM fotos_animales WHERE id_foto = $id_foto");
                    }
                }
            }

            // Procesar nuevas fotos si se subieron
            if (!empty($_FILES['fotos']['name'][0])) {
                $fotos_dir = __DIR__ . '../../uploads/animales/';
                if (!is_dir($fotos_dir)) {
                    mkdir($fotos_dir, 0755, true);
                }

                foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                        $nombre_archivo = uniqid() . '_' . basename($_FILES['fotos']['name'][$key]);
                        $ruta_destino = $fotos_dir . $nombre_archivo;

                        if (move_uploaded_file($tmp_name, $ruta_destino)) {
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

            // Procesar nueva imagen principal si se subió archivo o se ingresó URL
            if (
                (!empty($_FILES['imagen_principal']['name']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK)
                || (!empty($_POST['imagen_principal_url']))
            ) {
                $url_foto = null;

                // Si se subió archivo
                if (!empty($_FILES['imagen_principal']['name']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
                    $fotos_dir = __DIR__ . '/../../views/uploads/animales/';
                    if (!is_dir($fotos_dir)) {
                        mkdir($fotos_dir, 0755, true);
                    }
                    $nombre_archivo = uniqid() . '_' . basename($_FILES['imagen_principal']['name']);
                    $ruta_destino = $fotos_dir . $nombre_archivo;
                    if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $ruta_destino)) {
                        $url_foto = 'uploads/animales/' . $nombre_archivo;
                    }
                }
                // Si se ingresó URL válida (y no se subió archivo)
                elseif (!empty($_POST['imagen_principal_url']) && filter_var($_POST['imagen_principal_url'], FILTER_VALIDATE_URL)) {
                    $url_foto = trim($_POST['imagen_principal_url']);
                }

                if ($url_foto) {
                    // Actualizar la foto principal (puedes definir cuál es la principal según tu lógica)
                    $foto_principal = $conexion->query("SELECT id_foto FROM fotos_animales WHERE id_animal = $id_animal ORDER BY id_foto ASC LIMIT 1")->fetch_assoc();
                    if ($foto_principal) {
                        // Eliminar archivo anterior si era local
                        $foto_ant = $conexion->query("SELECT url_foto FROM fotos_animales WHERE id_foto = {$foto_principal['id_foto']}")->fetch_assoc();
                        if ($foto_ant && !preg_match('/^https?:\/\//', $foto_ant['url_foto']) && file_exists(__DIR__ . '/../../views/' . $foto_ant['url_foto'])) {
                            unlink(__DIR__ . '/../../views/' . $foto_ant['url_foto']);
                        }
                        // Actualizar registro
                        $stmt = $conexion->prepare("UPDATE fotos_animales SET url_foto = ? WHERE id_foto = ?");
                        $stmt->bind_param("si", $url_foto, $foto_principal['id_foto']);
                        $stmt->execute();
                        $stmt->close();
                    } else {
                        // Insertar nueva si no hay ninguna
                        $stmt = $conexion->prepare("INSERT INTO fotos_animales (id_animal, url_foto) VALUES (?, ?)");
                        $stmt->bind_param("is", $id_animal, $url_foto);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            $conexion->commit();

            $_SESSION['mensaje_exito'] = "Animal actualizado exitosamente!";
            header("Location: detalleAnimal.php?id=$id_animal");
            exit();
        } catch (Exception $e) {
            $conexion->rollback();
            $errores[] = "Error al actualizar el animal: " . $e->getMessage();
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
    <title>Editar Animal - Refugio de Mascotas</title>
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

        .photo-container {
            position: relative;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .photo-checkbox {
            position: absolute;
            top: 5px;
            right: 5px;
            z-index: 1;
        }

        .photo-label {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(220, 53, 69, 0.3);
            border-radius: 8px;
            display: none;
        }

        .photo-container:hover .photo-label {
            display: block;
        }

        .photo-container input[type="checkbox"]:checked+.photo-label {
            display: block;
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
                                <i class="fas fa-edit me-2"></i> Editar Animal: <?= htmlspecialchars($animal['nombre']) ?>
                            </h3>
                            <a href="detalleAnimal.php?id=<?= $id_animal ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Cancelar
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

                        <form action="editarAnimal.php?id=<?= $id_animal ?>" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Información básica -->
                                <div class="col-md-6">
                                    <h5 class="mb-4 text-primary">Información Básica</h5>

                                    <div class="mb-3">
                                        <label for="nombre" class="form-label required-field">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            value="<?= htmlspecialchars($animal['nombre']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="especie" class="form-label required-field">Especie</label>
                                        <select class="form-select" id="especie" name="especie" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="Perro" <?= $animal['especie'] === 'Perro' ? 'selected' : '' ?>>Perro</option>
                                            <option value="Gato" <?= $animal['especie'] === 'Gato' ? 'selected' : '' ?>>Gato</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="id_raza" class="form-label">Raza</label>
                                        <select class="form-select" id="id_raza" name="id_raza">
                                            <option value="">Seleccionar...</option>
                                            <optgroup label="Perros" id="razas-perros">
                                                <?php while ($raza = $razas_perros->fetch_assoc()): ?>
                                                    <option value="<?= $raza['id_raza'] ?>"
                                                        <?= $animal['id_raza'] == $raza['id_raza'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($raza['nombre_raza']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </optgroup>
                                            <optgroup label="Gatos" id="razas-gatos">
                                                <?php while ($raza = $razas_gatos->fetch_assoc()): ?>
                                                    <option value="<?= $raza['id_raza'] ?>"
                                                        <?= $animal['id_raza'] == $raza['id_raza'] ? 'selected' : '' ?>>
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
                                                min="0" max="30" value="<?= htmlspecialchars($animal['edad_anios']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="sexo" class="form-label required-field">Sexo</label>
                                            <select class="form-select" id="sexo" name="sexo" required>
                                                <option value="">Seleccionar...</option>
                                                <option value="Macho" <?= $animal['sexo'] === 'Macho' ? 'selected' : '' ?>>Macho</option>
                                                <option value="Hembra" <?= $animal['sexo'] === 'Hembra' ? 'selected' : '' ?>>Hembra</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tamanio" class="form-label required-field">Tamaño</label>
                                        <select class="form-select" id="tamanio" name="tamanio" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="Pequeño" <?= $animal['tamanio'] === 'Pequeño' ? 'selected' : '' ?>>Pequeño</option>
                                            <option value="Mediano" <?= $animal['tamanio'] === 'Mediano' ? 'selected' : '' ?>>Mediano</option>
                                            <option value="Grande" <?= $animal['tamanio'] === 'Grande' ? 'selected' : '' ?>>Grande</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Información adicional -->
                                <div class="col-md-6">
                                    <h5 class="mb-4 text-primary">Información Adicional</h5>

                                    <div class="mb-3">
                                        <label for="fecha_ingreso" class="form-label required-field">Fecha de Ingreso</label>
                                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso"
                                            value="<?= htmlspecialchars($animal['fecha_ingreso']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="estado" class="form-label required-field">Estado</label>
                                        <select class="form-select" id="estado" name="estado" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="Adoptable" <?= $animal['estado'] === 'Adoptable' ? 'selected' : '' ?>>Adoptable</option>
                                            <option value="Adoptado" <?= $animal['estado'] === 'Adoptado' ? 'selected' : '' ?>>Adoptado</option>
                                            <option value="Fallecido" <?= $animal['estado'] === 'Fallecido' ? 'selected' : '' ?>>Fallecido</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="estado_salud" class="form-label">Estado de Salud</label>
                                        <input type="text" class="form-control" id="estado_salud" name="estado_salud"
                                            value="<?= htmlspecialchars($animal['estado_salud']) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Fotos del animal</label>
                                        <div class="d-flex flex-wrap">
                                            <?php foreach ($fotos as $foto): ?>
                                                <div class="photo-container">
                                                    <img src="../../<?= htmlspecialchars($foto['url_foto']) ?>" class="preview-image" alt="Foto del animal">
                                                    <input type="checkbox" name="fotos_eliminar[]" value="<?= $foto['id_foto'] ?>"
                                                        class="photo-checkbox" id="foto-<?= $foto['id_foto'] ?>">
                                                    <label for="foto-<?= $foto['id_foto'] ?>" class="photo-label"></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="form-text">
                                            Marca las fotos que deseas eliminar. También puedes subir nuevas fotos.
                                        </div>
                                    </div>

                                    <!-- Imagen principal -->
                                    <div class="mb-3">
                                        <label class="form-label">Imagen principal actual</label><br>
                                        <?php if (!empty($fotos)): ?>
                                            <img src="../../<?= htmlspecialchars($fotos[0]['url_foto']) ?>" class="preview-image" alt="Imagen principal">
                                        <?php else: ?>
                                            <span class="text-muted">Sin imagen</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label for="imagen_principal" class="form-label">Cambiar imagen principal</label>
                                        <input type="file" class="form-control mb-2" id="imagen_principal" name="imagen_principal" accept="image/*">
                                        <input type="url" class="form-control" name="imagen_principal_url" placeholder="O pega una URL de imagen">
                                        <div class="form-text">Selecciona una imagen para reemplazar la principal o ingresa una URL de imagen.</div>
                                    </div>
                                </div>
                            </div>


                            <div class="mb-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i> Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <!-- jQuery (opcional, pero recomendado) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Mostrar/ocultar razas según especie
            $('#especie').change(function() {
                var especie = $(this).val();
                $('#razas-perros, #razas-gatos').hide();
                if (especie === 'Perro') {
                    $('#razas-perros').show();
                } else if (especie === 'Gato') {
                    $('#razas-gatos').show();
                }
            });
        });
    </script>
</body>

</html>
</script>