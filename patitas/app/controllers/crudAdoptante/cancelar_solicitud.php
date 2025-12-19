<?php
require_once __DIR__ . '/../../config/checkSessionUsuario.php';
require_once __DIR__ . '/../../config/conexion.php';

// Verificar ID de solicitud
$id_solicitud = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_solicitud <= 0) {
    header("Location: mis_solicitudes.php");
    exit();
}

// Verificar que la solicitud pertenece al usuario y está pendiente
$query = "SELECT sa.id_solicitud, sa.id_animal 
          FROM solicitudes_adopcion sa
          JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante
          WHERE sa.id_solicitud = $id_solicitud 
          AND ad.id_usuario = {$_SESSION['id_usuario']}
          AND sa.estado = 'Pendiente'";

$result = $conexion->query($query);

if (!$result || $result->num_rows === 0) {
    header("Location: mis_solicitudes.php");
    exit();
}

$solicitud = $result->fetch_assoc();

// Procesar cancelación
$conexion->begin_transaction();

try {
    // 1. Actualizar estado de la solicitud
    $update_solicitud = "UPDATE solicitudes_adopcion 
                         SET estado = 'Rechazada', 
                             fecha_respuesta = NOW(),
                             notas_admin = CONCAT(IFNULL(notas_admin, ''), 'Cancelada por el adoptante')
                         WHERE id_solicitud = $id_solicitud";
    
    if (!$conexion->query($update_solicitud)) {
        throw new Exception("Error al actualizar solicitud");
    }

    // 2. Actualizar estado del animal si no hay otras solicitudes pendientes
    $check_solicitudes = "SELECT COUNT(*) as count 
                          FROM solicitudes_adopcion 
                          WHERE id_animal = {$solicitud['id_animal']} 
                          AND estado = 'Pendiente'";
    
    $count_result = $conexion->query($check_solicitudes);
    $count = $count_result->fetch_assoc()['count'];
    
    if ($count == 0) {
        $update_animal = "UPDATE animales 
                          SET estado = 'Disponible' 
                          WHERE id_animal = {$solicitud['id_animal']}";
        
        if (!$conexion->query($update_animal)) {
            throw new Exception("Error al actualizar estado del animal");
        }
    }

    $conexion->commit();
    $_SESSION['mensaje_exito'] = "Solicitud cancelada correctamente";
} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['mensaje_error'] = "Error al cancelar la solicitud: " . $e->getMessage();
}

header("Location: mis_solicitudes.php");
exit();
?>