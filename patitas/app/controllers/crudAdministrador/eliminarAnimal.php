<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../config/checkSessionUsuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_animal']) || !is_numeric($_POST['id_animal'])) {
    $_SESSION['mensaje_error'] = "Solicitud inválida";
    header("Location: animales.php");
    exit();
}

$id_animal = (int)$_POST['id_animal'];

try {
    $conexion->begin_transaction();

    // Verificar si el animal existe y no está adoptado
    $animal = $conexion->query("
        SELECT estado FROM animales 
        WHERE id_animal = $id_animal AND activo = 1
    ")->fetch_assoc();

    if (!$animal) {
        $_SESSION['mensaje_error'] = "El animal no existe o ya fue eliminado";
        $conexion->rollback();
        header("Location: animales.php");
        exit();
    }

    if ($animal['estado'] === 'Adoptado') {
        $_SESSION['mensaje_error'] = "No se puede eliminar un animal que ha sido adoptado";
        $conexion->rollback();
        header("Location: animales.php");
        exit();
    }

    // Eliminar registros relacionados primero (importante para integridad referencial)
    $conexion->query("DELETE FROM animal_departamento WHERE id_animal = $id_animal");
    $conexion->query("DELETE FROM fotos_animales WHERE id_animal = $id_animal");
    $conexion->query("DELETE FROM solicitudes_adopcion WHERE id_animal = $id_animal");
    $conexion->query("DELETE FROM favoritos WHERE id_animal = $id_animal");

    // Eliminar físicamente las fotos del servidor (después de obtener las rutas)
    $fotos = $conexion->query("
        SELECT url_foto FROM fotos_animales 
        WHERE id_animal = $id_animal
    ");
    if ($fotos) {
        $fotos = $fotos->fetch_all(MYSQLI_ASSOC);
        foreach ($fotos as $foto) {
            $ruta_archivo = __DIR__ . '../../' . $foto['url_foto'];
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
        }
    }

    // Eliminar el animal de la base de datos (borrado físico)
    $conexion->query("DELETE FROM animales WHERE id_animal = $id_animal");

    $conexion->commit();

    $_SESSION['mensaje_exito'] = "Animal eliminado correctamente";
} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['mensaje_error'] = "Error al eliminar el animal: " . $e->getMessage();
}

header("Location: ../../views/viewAdministrador/animales.php");
exit();
