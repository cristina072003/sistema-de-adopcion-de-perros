<?php
require_once __DIR__ . '/../../config/conexion.php';

class LoginModel
{
    private $conn;

    public function __construct()
    {
        global $conexion;
        $this->conn = $conexion;
    }

    public function login($correo, $contrasena)
    {
        $sql = "SELECT id_usuario, correo, rol FROM usuarios WHERE correo = ? AND contrasena = ? AND activo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $correo, $contrasena);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    }
}