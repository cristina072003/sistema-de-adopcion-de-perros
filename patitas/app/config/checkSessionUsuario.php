<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['correo'])) {
    header("Location: ../../views/loginViews/loginUsuario.php"); // Cambiado a loginUsuario.php
    exit();
}

// Verificar que la información completa del usuario esté presente
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['rol'])) {
    echo "Error: La sesión no contiene todos los datos necesarios del usuario.";
    exit();
}

// Opcional: Verificar que el rol sea válido según tu ENUM
$roles_permitidos = ['administrador', 'adoptante'];
if (!in_array($_SESSION['rol'], $roles_permitidos)) {
    echo "Error: Rol de usuario no válido.";
    exit();
}