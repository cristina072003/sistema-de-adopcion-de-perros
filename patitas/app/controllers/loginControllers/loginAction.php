<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/loginController.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
    $contrasena = filter_input(INPUT_POST, 'contrasena', FILTER_SANITIZE_STRING);

    if ($correo && $contrasena) {
        $controller = new LoginController();
        $user = $controller->login($correo, $contrasena);

        if ($user) {
            session_start();
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['id_usuario'] = $user['id_usuario'];

            // Redirección según rol
            if ($user['rol'] === 'administrador') {
                redirect('/dashboard-administrador');
            } else {
                redirect('/dashboard-adoptante');
            }
        } else {
            redirect('/login?error=credenciales');
        }
    } else {
        redirect('/login?error=datos_vacios');
    }
}