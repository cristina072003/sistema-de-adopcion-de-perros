<?php
require_once __DIR__ . '/../../models/loginModels/loginModelUsuario.php';

class LoginController
{
    private $model;

    public function __construct()
    {
        $this->model = new LoginModel();
    }

    public function login($correo, $contrasena)
    {
        return $this->model->login($correo, $contrasena);
    }
}