<?php
// Definir la ruta base del proyecto
define('BASE_PATH', __DIR__);

// Incluir funciones auxiliares
require_once BASE_PATH . '/app/config/helpers.php';

$routes = [
    // Rutas públicas
    '/' => 'app/views/inicio.php',
    '/inicio' => 'app/views/inicio.php',
    '/login' => 'app/views/loginViews/loginUsuario.php',
    '/registro' => 'app/views/loginViews/registro.php',
    '/donaciones' => 'app/views/donaciones.php',
    '/detalle' => 'app/views/detalle.php',
    '/recibo' => 'app/views/recibo.php',


    // Dashboards
    '/dashboard-administrador' => 'app/views/dashboardAdministrador.php',
    '/dashboard-adoptante' => 'app/views/dashboardAdoptante.php',

    // Vistas administrador
    '/admin/usuarios' => 'app/views/viewAdministrador/usuarios.php',
    '/admin/solicitudes' => 'app/views/viewAdministrador/solicitudes.php',
    '/admin/reportes' => 'app/views/viewAdministrador/reportes.php',
    '/admin/configuracion' => 'app/views/viewAdministrador/configuracion.php',
    '/admin/animales' => 'app/views/viewAdministrador/animales.php',
    '/admin/adopciones' => 'app/views/viewAdministrador/adopciones.php',

    // Controladores administrador
    '/admin/crear-usuario' => 'app/controllers/crudAdministrador/crearUsuario.php',
    '/admin/detalle-adopcion' => 'app/controllers/crudAdministrador/detalleAdopcion.php',
    '/admin/detalle-animal' => 'app/controllers/crudAdministrador/detalleAnimal.php',
    '/admin/detalle-usuario' => 'app/controllers/crudAdministrador/detalleUsuario.php',
    '/admin/editar-animal' => 'app/controllers/crudAdministrador/editarAnimal.php',
    '/admin/eliminar-animal' => 'app/controllers/crudAdministrador/eliminarAnimal.php',
    '/admin/generar-reporte' => 'app/controllers/crudAdministrador/generarReporte.php',
    '/admin/nuevo-animal' => 'app/controllers/crudAdministrador/nuevo_animal.php',
    '/admin/procesar-adopcion' => 'app/controllers/crudAdministrador/procesarAdopcion.php',

    // Controladores adoptante
    '/adoptante/cancelar-solicitud' => 'app/controllers/crudAdoptante/cancelar_solicitud.php',
    '/adoptante/configuracion' => 'app/controllers/crudAdoptante/configuracion.php',
    '/adoptante/confirmacion-adopcion' => 'app/controllers/crudAdoptante/confirmacion_adopcion.php',
    '/adoptante/detalle-animal' => 'app/controllers/crudAdoptante/detalle_animal.php',
    '/adoptante/mis-animales' => 'app/controllers/crudAdoptante/mis_animales.php',
    '/adoptante/mis-solicitudes' => 'app/controllers/crudAdoptante/mis_solicitudes.php',
    '/adoptante/mi-perfil' => 'app/controllers/crudAdoptante/mi_perfil.php',
    '/adoptante/solicitar-adopcion' => 'app/controllers/crudAdoptante/solicitar_adopcion.php',
    '/adoptante/ver-animal' => 'app/controllers/crudAdoptante/ver_animal.php',
    '/adoptante/ver-solicitud' => 'app/controllers/crudAdoptante/ver_solicitud.php',

    // Controladores login
    '/login/action' => 'app/controllers/loginControllers/loginAction.php',
    '/login/controller' => 'app/controllers/loginControllers/loginController.php',
    '/logout' => 'app/controllers/loginControllers/logout.php',
];

// Definir BASE_URL solo si no está definida aún
// (helpers.php ya la define, pero por si acaso se accede directamente al router)
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $basePath = str_replace('\\', '/', dirname($script));
    if ($basePath === '/') {
        $basePath = '';
    }
    define('BASE_URL', $protocol . '://' . $host . $basePath);
}

// Obtener la ruta solicitada
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = dirname($_SERVER['SCRIPT_NAME']);

// Normalizar la ruta base del script
if ($script_name !== '/') {
    $script_name = str_replace('\\', '/', $script_name);
    if (strpos($request_uri, $script_name) === 0) {
        $request_uri = substr($request_uri, strlen($script_name));
    }
}

// Asegurar que siempre empiece con /
if (empty($request_uri) || $request_uri === false) {
    $request_uri = '/';
} elseif ($request_uri[0] !== '/') {
    $request_uri = '/' . $request_uri;
}

// Buscar la ruta en el array
if (array_key_exists($request_uri, $routes)) {
    $file_path = BASE_PATH . '/' . $routes[$request_uri];

    // Verificar que el archivo existe
    if (file_exists($file_path)) {
        require $file_path;
        exit();
    } else {
        // Error 500 - El archivo de la ruta no existe
        http_response_code(500);
        echo '<h1>500 - Error interno del servidor</h1>';
        echo '<p>El archivo asociado a esta ruta no existe: ' . htmlspecialchars($routes[$request_uri]) . '</p>';
        error_log("Router: Archivo no encontrado para la ruta '$request_uri': $file_path");
        exit();
    }
} else {
    // 404 - Ruta no encontrada
    http_response_code(404);
    echo '<h1>404 - Página no encontrada</h1>';
    echo '<p>La ruta solicitada no existe: ' . htmlspecialchars($request_uri) . '</p>';
    echo '<p><a href="' . BASE_URL . '/">Volver al inicio</a></p>';
    exit();
}

