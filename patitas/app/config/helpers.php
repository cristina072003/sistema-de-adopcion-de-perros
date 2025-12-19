<?php
/**
 * Archivo de funciones auxiliares para el enrutamiento
 * Incluir este archivo en todas las vistas y controladores que necesiten generar URLs
 */

// Cargar constantes del router si no están definidas
if (!defined('BASE_URL')) {
    // Obtener la URL base del proyecto
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $basePath = str_replace('\\', '/', dirname($script));
    if ($basePath === '/') {
        $basePath = '';
    }
    define('BASE_URL', $protocol . '://' . $host . $basePath);
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__DIR__)));
}

/**
 * Genera una URL completa basada en una ruta
 * @param string $route Ruta relativa (ej: '/login', '/admin/usuarios')
 * @return string URL completa
 */
function url($route = '') {
    // Asegurar que la ruta empiece con /
    if (!empty($route) && $route[0] !== '/') {
        $route = '/' . $route;
    }
    return BASE_URL . $route;
}

/**
 * Redirige a una ruta específica
 * @param string $route Ruta a la que redirigir (ej: '/login', '/dashboard-administrador')
 */
function redirect($route) {
    header("Location: " . url($route));
    exit();
}

/**
 * Redirige a una ruta con un mensaje de sesión
 * @param string $route Ruta a la que redirigir
 * @param string $message Mensaje a mostrar
 * @param string $type Tipo de mensaje (success, error, warning, info)
 */
function redirectWithMessage($route, $message, $type = 'info') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['mensaje'] = $message;
    $_SESSION['mensaje_tipo'] = $type;
    redirect($route);
}

/**
 * Obtiene y limpia un mensaje de sesión
 * @return array|null Array con 'texto' y 'tipo' o null si no hay mensaje
 */
function getMessage() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['mensaje'])) {
        $mensaje = [
            'texto' => $_SESSION['mensaje'],
            'tipo' => $_SESSION['mensaje_tipo'] ?? 'info'
        ];
        unset($_SESSION['mensaje']);
        unset($_SESSION['mensaje_tipo']);
        return $mensaje;
    }

    return null;
}

/**
 * Genera una ruta a un recurso estático (CSS, JS, imágenes)
 * @param string $path Ruta relativa desde la carpeta public
 * @return string URL completa al recurso
 */
function asset($path) {
    // Eliminar la barra inicial si existe
    $path = ltrim($path, '/');
    return BASE_URL . '/public/' . $path;
}

