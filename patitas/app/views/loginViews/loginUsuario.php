<?php
// Incluir helpers para poder usar las funciones url() y redirect()
require_once __DIR__ . '/../../config/helpers.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema</title>
    <!-- DaisyUI y Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.13/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: grid;
            place-content: center;
            background-image: url('https://3.bp.blogspot.com/-GOBGYypEns4/UQH_ac0u6dI/AAAAAAABj-w/ZW4_7ijrItE/s1600/perrito-y-gatito-cat-and-dog-friends-mascotas-adorables%2B%25281%2529.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .avatar-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .avatar-ring {
            border: 3px solid rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            padding: 0.25rem;
            background: linear-gradient(135deg, rgba(107, 115, 255, 0.8) 0%, rgba(0, 13, 255, 0.8) 100%);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .avatar-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .input-field {
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(203, 213, 225, 0.5);
        }

        .input-field:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            background-color: white;
        }

        .login-btn {
            background: linear-gradient(135deg, #6B73FF 0%, #000DFF 100%);
            color: white;
            border: none;
            transition: all 0.3s ease;
            padding: 0.75rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .error-message {
            animation: fadeIn 0.5s ease;
            background-color: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .login-container {
                padding: 1.25rem;
                max-width: 98vw;
                min-width: 0;
            }

            .avatar-img {
                width: 60px;
                height: 60px;
            }

            h2.text-2xl {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 400px) {
            .login-container {
                padding: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container mx-2 sm:mx-4">
        <!-- Avatar con el diseño de anillo original -->
        <div class="avatar-container">
            <div class="avatar-ring">
                <img src="https://img.freepik.com/fotos-premium/felices-amigos-perros-gatos-posando-juntos_691560-6871.jpg?w=2000"
                    alt="Avatar de usuario"
                    class="avatar-img">
            </div>
        </div>

        <!-- Título de bienvenida -->
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Bienvenido de vuelta</h2>
        <p class="text-center text-gray-600 mb-6">Ingresa a tu cuenta para continuar</p>

        <!-- Formulario de login -->
        <form action="<?php echo url('/login/action'); ?>" method="post" class="space-y-4">
            <!-- Campo de email -->
            <div class="form-control">
                <label class="input input-bordered flex items-center gap-2 input-field">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70">
                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.735 14c.618 0 1.093-.561.872-1.139a6.002 6.002 0 0 0-11.215 0c-.22.578.254 1.139.872 1.139h9.47Z" />
                    </svg>
                    <input type="email" id="correo" name="correo"
                        class="grow"
                        placeholder="Correo electrónico"
                        required />
                </label>
            </div>

            <!-- Campo de contraseña -->
            <div class="form-control">
                <label class="input input-bordered flex items-center gap-2 input-field">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 opacity-70">
                        <path fill-rule="evenodd" d="M14 6a4 4 0 0 1-4.899 3.899l-1.955 1.955a.5.5 0 0 1-.353.146H5v1.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-2.293a.5.5 0 0 1 .146-.353l3.955-3.955A4 4 0 1 1 14 6Zm-4-2a.75.75 0 0 0 0 1.5.5.5 0 0 1 .5.5.75.75 0 0 0 1.5 0 2 2 0 0 0-2-2Z" clip-rule="evenodd" />
                    </svg>
                    <input type="password" id="contrasena" name="contrasena"
                        class="grow"
                        placeholder="Contraseña"
                        required />
                </label>
            </div>

            <!-- Botón de login -->
            <button type="submit" class="btn login-btn w-full mt-4">
                <i class="fas fa-sign-in-alt mr-2"></i> Iniciar Sesión
            </button>

            <!-- Enlace de registro -->
            <div class="text-center mt-4">
                ¿No tienes cuenta?
                <a href="<?php echo url('/registro'); ?>" class="text-blue-700 font-semibold hover:underline">Regístrate</a>
            </div>

            <!-- Mensaje de error -->
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message alert mt-4">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>
                            <?php
                            if ($_GET['error'] == 'credenciales') {
                                echo 'Correo o contraseña incorrectos';
                            } elseif ($_GET['error'] == 'inactivo') {
                                echo 'Tu cuenta está desactivada';
                            } elseif ($_GET['error'] == 'db_error') {
                                echo 'Error en el sistema. Por favor, intente más tarde';
                            }
                            ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>

</html>