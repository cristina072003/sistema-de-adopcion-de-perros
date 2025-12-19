<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../config/checkSessionUsuario.php';
require_once __DIR__ . '../../../views/headerA.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['accion'])) {
    $_SESSION['mensaje_error'] = "Solicitud inválida";
    header("Location: adopciones.php");
    exit();
}

$id_solicitud = (int)$_GET['id'];
$accion = $_GET['accion'];

// Validar acción
if (!in_array($accion, ['aprobar', 'rechazar'])) {
    $_SESSION['mensaje_error'] = "Acción no válida";
    header("Location: adopciones.php");
    exit();
}

try {
    $conexion->begin_transaction();

    // Obtener información de la solicitud
    $solicitud = $conexion->query("
        SELECT sa.*, a.id_animal, a.estado as estado_animal
        FROM solicitudes_adopcion sa
        JOIN animales a ON sa.id_animal = a.id_animal
        WHERE sa.id_solicitud = $id_solicitud
    ")->fetch_assoc();

    if (!$solicitud) {
        throw new Exception("La solicitud no existe");
    }

    // Verificar que la solicitud esté pendiente o en revisión
    if (!in_array($solicitud['estado'], ['Pendiente', 'En revisión'])) {
        throw new Exception("No se puede modificar una solicitud ya procesada");
    }

    // Procesar según la acción
    if ($accion === 'aprobar') {
        // Actualizar estado de la solicitud
        $conexion->query("
            UPDATE solicitudes_adopcion 
            SET estado = 'Aprobada', 
                fecha_respuesta = NOW() 
            WHERE id_solicitud = $id_solicitud
        ");

        // Actualizar estado del animal
        $conexion->query("
            UPDATE animales 
            SET estado = 'Adoptado' 
            WHERE id_animal = {$solicitud['id_animal']}
        ");

        // Rechazar automáticamente otras solicitudes pendientes para este animal
        $conexion->query("
            UPDATE solicitudes_adopcion 
            SET estado = 'Rechazada', 
                fecha_respuesta = NOW(),
                notas_admin = CONCAT('Rechazada automáticamente porque el animal fue adoptado (Solicitud #$id_solicitud)')
            WHERE id_animal = {$solicitud['id_animal']} 
            AND estado IN ('Pendiente', 'En revisión')
            AND id_solicitud != $id_solicitud
        ");

        $_SESSION['mensaje_exito'] = "Solicitud aprobada correctamente. El animal ha sido marcado como adoptado.";
    } else {
        // Rechazar la solicitud
        $conexion->query("
            UPDATE solicitudes_adopcion 
            SET estado = 'Rechazada', 
                fecha_respuesta = NOW() 
            WHERE id_solicitud = $id_solicitud
        ");

        // Si el animal estaba "En adopción", volver a "Disponible"
        if ($solicitud['estado_animal'] === 'En adopción') {
            $conexion->query("
                UPDATE animales 
                SET estado = 'Disponible' 
                WHERE id_animal = {$solicitud['id_animal']}
            ");
        }

        $_SESSION['mensaje_exito'] = "Solicitud rechazada correctamente";
    }

    $conexion->commit();
} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['mensaje_error'] = "Error al procesar la solicitud: " . $e->getMessage();
}

header("Location: ../../views/viewAdministrador/adopciones.php");
exit();
