<?php
// Incluir helpers para usar funciones de enrutamiento
require_once __DIR__ . '/../config/helpers.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Refugio Patitas Felices</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4B7BEC;
            --secondary-color: #FF6B8B;
            --dark-color: #2C3E50;
            --light-color: #F8F9FA;
            --sidebar-width: 240px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
        }

        .admin-navbar {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
            padding: 10px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand,
        .navbar-brand span {
            color: var(--primary-color) !important;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .navbar-brand img {
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
            box-shadow: 0 2px 8px rgba(75, 123, 236, 0.15);
        }

        .nav-link {
            color: var(--primary-color) !important;
            font-size: 1.1rem;
            font-weight: 500;
            margin: 0 8px;
            border-radius: 20px;
            transition: background 0.2s, color 0.2s;
            padding: 8px 18px;
        }

        .nav-link.active,
        .nav-link:hover {
            background: rgba(75, 123, 236, 0.10);
            color: var(--secondary-color) !important;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-profile img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(75, 123, 236, 0.08);
            border-radius: 12px;
            padding: 10px 0;
        }

        .dropdown-item {
            padding: 10px 24px;
            font-size: 1rem;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: var(--primary-color);
        }

        .btn-danger {
            border-radius: 50px;
            font-weight: 500;
            font-size: 1rem;
            transition: background 0.2s, box-shadow 0.2s;
        }

        .btn-danger:hover {
            background: #e05575;
            box-shadow: 0 4px 12px rgba(255, 107, 139, 0.15);
        }

        .btn-back {
            background: transparent;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-right: 15px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-back:hover {
            transform: translateX(-3px);
            color: var(--secondary-color);
        }

        @media (max-width: 575px) {
            .navbar-brand img {
                height: 36px;
            }

            .navbar-brand span {
                font-size: 1rem;
            }

            .btn-back {
                font-size: 1.2rem;
                margin-right: 10px;
            }
        }
    </style>
</head>

<body>
    <!-- Barra de navegación superior -->
    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container-fluid">

            <a class="navbar-brand d-flex align-items-center" href="http://localhost/PATITAS/app/views/dashboardAdministrador.php#">
                <img src="https://i.pinimg.com/736x/c6/c4/c8/c6c4c8b0dae58645d13cc463b8dd0866.jpg" alt="Patitas Felices">
                <span class="ms-2">Patitas Felices</span>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                    <button onclick="window.history.back();" class="btn-back" title="Volver atrás">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == '' ? 'active' : '' ?>" href="<?php echo url('/dashboard-administrador');?>">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'animales.php' ? 'active' : '' ?>" href="<?php echo url('/admin/animales'); ?>"><i class="fas fa-paw me-1"></i> animales</a>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'adopciones.php' ? 'active' : '' ?>" href="<?php echo url('/admin/adopciones'); ?>"><i class="fas fa-paw me-1"></i> adopciones</a>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'solicitudes.php' ? 'active' : '' ?>" href="<?php echo url('/admin/solicitudes'); ?>"><i class="fas fa-paw me-1"></i>solicitudes</a>
                        </a>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : '' ?>" href="<?php echo url('/admin/usuarios'); ?>"><i class="fas fa-paw me-1"></i>usuarios</a>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : '' ?>" href="<?php echo url('/admin/reportes'); ?>"><i class="fas fa-paw me-1"></i>reportes</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?php echo url('/logout'); ?>">
                            <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <!-- ...continúa tu contenido principal aquí...