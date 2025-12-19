<?php
// Incluir helpers para usar funciones de enrutamiento
require_once __DIR__ . '/../config/helpers.php';

// Verificar si la sesión está activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refugio Patitas - Adopta un amigo</title>
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
        }

        .navbar-patitas {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
            padding: 10px 0;
        }

        .navbar-brand,
        .navbar-brand span {
            color: #4B7BEC !important;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .navbar-brand img {
            height: 45px;
            border-radius: 50%;
            border: 2px solid #4B7BEC;
            box-shadow: 0 2px 8px rgba(75, 123, 236, 0.15);
        }

        .nav-link {
            color: #4B7BEC !important;
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
            color: #FF6B8B !important;
        }

        .user-dropdown img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4B7BEC;
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
            color: #4B7BEC;
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

        .mobile-menu-btn {
            display: none;
            border: none;
            background: none;
            font-size: 1.5rem;
            color: var(--dark-color);
        }

        .btn-back {
            background: transparent;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-right: 15px;
            cursor: pointer;
            transition: transform 0.2s;
            padding: 8px;
        }

        .btn-back:hover {
            transform: translateX(-3px);
            color: var(--secondary-color);
        }

        @media (max-width: 991px) {
            .mobile-menu-btn {
                display: block;
            }

            .navbar-collapse {
                background: #fff;
                border-radius: 12px;
                margin-top: 10px;
                box-shadow: 0 5px 15px rgba(75, 123, 236, 0.08);
            }

            .nav-link {
                margin: 6px 0;
                padding: 10px 15px;
                border-radius: 8px;
            }

            .user-dropdown span {
                display: none;
            }
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
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-patitas sticky-top">
        <div class="container">
            <!-- Botón de retroceso -->
            <button onclick="window.history.back();" class="btn-back" title="Volver atrás">
                <i class="fas fa-arrow-left"></i>
            </button>

            <a class="navbar-brand d-flex align-items-center" href="http://localhost/PATITAS/app/views/dashboardAdoptante.php">
                <img src="https://i.pinimg.com/736x/c6/c4/c8/c6c4c8b0dae58645d13cc463b8dd0866.jpg" alt="Patitas Felices">
                <span class="ms-2">Patitas Felices</span>
            </a>

            <button class="navbar-toggler mobile-menu-btn" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo url('/dashboard-adoptante'); ?>"><i class="fas fa-paw me-1"></i> Adoptar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/adoptante/mis-solicitudes'); ?>"><i class="fas fa-clipboard-list me-1"></i> Mis Solicitudes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/adoptante/mi-perfil');?>"><i class="fas fa-user me-1"></i> Mi Perfil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('/adoptante/configuracion');?>"><i class="fas fa-cog me-1"></i> Configuración</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center user-dropdown px-2 py-1" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nombre_completo'] ?? 'Usuario') ?>&background=4B7BEC&color=fff" alt="Usuario">
                            <span class="d-none d-md-inline ms-2"><?= $_SESSION['nombre_completo'] ?? 'Usuario' ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo url('/logout'); ?>">
                                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main class="container-fluid"></main>