<?php
// Incluir helpers para usar la función url()
require_once __DIR__ . '/../config/helpers.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refugio de Mascotas - Panel de Adoptante</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #6a3093;
            --secondary: #8e44ad;
            --accent: #ff6b8b;
            --light: #f8f9fa;
            --dark: #343a40;
            --light-purple: #f3e5ff;
            --gradient: linear-gradient(135deg, #6a3093 0%, #8e44ad 100%);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        /* Navbar mejorado */
        .navbar-refugio {
            background-color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            transition: all 0.3s ease;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            height: 80px;
        }

        .logo-container {
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .logo-container:hover {
            transform: scale(1.02);
        }

        .logo-img {
            height: 50px;
            margin-right: 15px;
            transition: all 0.3s;
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: all 0.3s;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .nav-item {
            position: relative;
            margin-left: 10px;
        }

        .nav-link {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            padding: 12px 18px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            position: relative;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            color: var(--primary);
            background-color: var(--light-purple);
        }

        .nav-link.active {
            color: var(--primary);
            font-weight: 600;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: var(--accent);
            border-radius: 50%;
        }

        .nav-link i {
            margin-left: 8px;
            font-size: 0.8rem;
            transition: transform 0.3s;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            padding: 10px 0;
            min-width: 220px;
            z-index: 1000;
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            border: none;
        }

        .nav-item:hover .dropdown-menu {
            display: block;
            opacity: 1;
            transform: translateY(0);
            animation: fadeInUp 0.3s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            padding: 12px 20px;
            color: var(--dark);
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .dropdown-item i {
            margin-right: 10px;
            width: 18px;
            text-align: center;
            color: var(--primary);
        }

        .dropdown-item:hover {
            background-color: var(--light-purple);
            color: var(--primary);
            padding-left: 25px;
        }

        .dropdown-divider {
            border-top: 1px solid rgba(0, 0, 0, 0.08);
            margin: 8px 0;
        }

        .btn-login {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(106, 48, 147, 0.3);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #8e44ad 0%, #6a3093 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(106, 48, 147, 0.4);
            color: white;
        }

        .user-menu {
            display: flex;
            align-items: center;
            position: relative;
            cursor: pointer;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background-color: var(--light-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            overflow: hidden;
            border: 2px solid var(--light-purple);
            transition: all 0.3s;
        }

        .user-avatar:hover {
            border-color: var(--primary);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
            margin-right: 10px;
            transition: all 0.3s;
        }

        .user-menu:hover .user-name {
            color: var(--primary);
        }

        .notification-badge {
            background-color: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            margin-left: 5px;
            position: absolute;
            top: -5px;
            right: -5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Menú móvil mejorado */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary);
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .mobile-menu-btn:hover {
            color: var(--secondary);
            transform: rotate(90deg);
        }

        /* Responsive mejorado */
        @media (max-width: 1200px) {
            .navbar-container {
                padding: 0 20px;
            }

            .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 992px) {
            .navbar-container {
                height: 70px;
                padding: 0 15px;
            }

            .mobile-menu-btn {
                display: block;
            }

            .nav-menu {
                position: fixed;
                top: 70px;
                left: 0;
                width: 100%;
                background-color: white;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                flex-direction: column;
                align-items: flex-start;
                padding: 0;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.4s ease-out;
                z-index: 1020;
            }

            .nav-menu.active {
                max-height: 100vh;
                padding: 15px 0;
            }

            .nav-item {
                width: 100%;
                margin: 0;
            }

            .nav-link {
                width: 100%;
                padding: 15px 25px;
                border-radius: 0;
                justify-content: space-between;
            }

            .nav-link.active::after {
                left: 20px;
                transform: none;
            }

            .dropdown-menu {
                position: static;
                box-shadow: none;
                display: none;
                width: 100%;
                border-radius: 0;
                padding: 0;
                margin: 0;
                opacity: 1;
                transform: none;
                animation: none;
                border-left: 4px solid var(--primary);
            }

            .dropdown-item {
                padding-left: 35px;
            }

            .btn-login {
                margin: 15px 25px;
                width: calc(100% - 50px);
                text-align: center;
            }

            .user-menu {
                padding: 15px 25px;
                width: 100%;
            }
        }

        /* Modales mejorados */
        .modal-content {
            border-radius: 15px;
            overflow: hidden;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            background: var(--gradient);
            color: white;
            border-bottom: none;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-body ul {
            padding-left: 20px;
        }

        .modal-body ul li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 15px;
        }

        .modal-body ul li::before {
            content: '•';
            color: var(--primary);
            font-size: 1.2rem;
            position: absolute;
            left: 0;
            top: -2px;
        }

        .modal-footer {
            border-top: none;
            padding: 15px 25px;
            background-color: #f9f9f9;
        }

        .btn-secondary {
            background-color: #e0e0e0;
            border: none;
            color: #555;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background-color: #d0d0d0;
        }

        /* Efecto de scroll */
        .navbar-refugio.scrolled {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: 70px;
        }

        .navbar-refugio.scrolled .navbar-container {
            height: 70px;
        }

        .navbar-refugio.scrolled .logo-img {
            height: 40px;
        }
    </style>
</head>

<body>
    <nav class="navbar-refugio">
        <div class="navbar-container">
            <div class="logo-container">
                <img src="https://th.bing.com/th/id/R.5adcd43cb75136e72de3b8d1821bf43f?rik=7VhypfUJNEp2Zg&pid=ImgRaw&r=0" alt="Logo Refugio" class="logo-img">
                <h1 class="logo-text">REFUGIO GAMALIEL</h1>
            </div>

            <button class="mobile-menu-btn d-lg-none">
                <i class="fas fa-bars"></i>
            </button>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo url('/inicio'); ?>" class="nav-link active">Inicio</a>
                </li>

                <li class="nav-item">
                    <a href="javascript:void(0)" class="nav-link" data-bs-toggle="modal" data-bs-target="#modalAdoptar">Adoptar</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#modalChatBot"><i class="fas fa-robot me-1"></i> Chat Asistente</a>
                </li>
    <!-- Chatbot tipo Messenger flotante -->
    <style>
    .chat-messenger-widget { position:fixed; bottom:24px; left:24px; width:340px; max-width:95vw; height:440px; background:#fff; border-radius:16px 16px 8px 8px; box-shadow:0 5px 40px rgba(0,0,0,0.18); display:none; flex-direction:column; z-index:1050; }
    .chat-messenger-header { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; padding:14px 18px; border-radius:16px 16px 0 0; display:flex; justify-content:space-between; align-items:center; }
    .chat-messenger-header h6 { margin:0; font-weight:700; font-size:1.1rem; display:flex; align-items:center; gap:8px; }
    .chat-messenger-close { background:none; border:none; color:#fff; font-size:20px; cursor:pointer; }
    .chat-messenger-body { flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:8px; }
    .chat-messenger-msg { padding:8px 12px; border-radius:8px; max-width:85%; word-wrap:break-word; }
    .chat-messenger-msg.user { background:#667eea; color:#fff; align-self:flex-end; }
    .chat-messenger-msg.bot { background:#f0f0f0; color:#333; align-self:flex-start; }
    .chat-messenger-input { padding:12px; border-top:1px solid #eee; display:flex; gap:8px; }
    .chat-messenger-input input { flex:1; border:1px solid #ddd; border-radius:8px; padding:8px 12px; }
    .chat-messenger-input button { background:#667eea; color:#fff; border:none; border-radius:8px; padding:8px 16px; cursor:pointer; }
    @media(max-width:600px){ .chat-messenger-widget { left:0; bottom:0; width:100vw; height:100vh; border-radius:0; } }
    .chat-messenger-fab { position:fixed; bottom:24px; right:24px; width:60px; height:60px; background:#667eea; color:#fff; border:none; border-radius:50%; box-shadow:0 5px 20px rgba(0,0,0,0.15); display:flex; align-items:center; justify-content:center; z-index:1049; font-size:2rem; cursor:pointer; }
    </style>
        <!-- Botón flotante para abrir el chat bot en la esquina inferior derecha -->
        <button class="chat-messenger-fab" id="openMessengerChat" title="Abrir chat">
            <i class="fas fa-robot"></i>
        </button>
    <div class="chat-messenger-widget" id="messengerWidget">
        <div class="chat-messenger-header">
            <h6><i class="fas fa-robot"></i> Asistente Virtual</h6>
            <button class="chat-messenger-close" onclick="toggleMessengerChat()">&times;</button>
        </div>
        <div class="chat-messenger-body" id="messengerChatBody">
            <div class="chat-messenger-msg bot">¡Hola! Soy el asistente virtual. ¿En qué puedo ayudarte?</div>
        </div>
        <div class="chat-messenger-input">
            <input id="messengerChatInput" type="text" placeholder="Escribe tu pregunta..." />
            <button onclick="sendMessengerMessage()">Enviar</button>
        </div>
    </div>

                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#modalInformacion">Información</a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#modalDonaciones">Donaciones</a>
                </li>

                <?php if (isset($_SESSION['id_usuario'])): ?>
                    <li class="nav-item">
                        <a href="<?php echo url('/seguimientos'); ?>" class="nav-link">
                            Seguimientos
                            <span class="notification-badge">3</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <div class="user-menu">
                            <div class="user-avatar">
                                <?php if (isset($_SESSION['foto_perfil'])): ?>
                                    <img src="<?= $_SESSION['foto_perfil'] ?>" alt="Foto de perfil">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <span class="user-name"><?= $_SESSION['nombre_usuario'] ?></span>
                            <i class="fas fa-chevron-down ms-1"></i>

                            <ul class="dropdown-menu">
                                <li><a href="<?php echo url('/adoptante/mi-perfil'); ?>" class="dropdown-item"><i class="fas fa-user me-2"></i> Mi Perfil</a></li>
                                <li><a href="<?php echo url('/adoptante/configuracion'); ?>" class="dropdown-item"><i class="fas fa-cog me-2"></i> Configuración</a></li>
                                <li><a href="<?php echo url('/adoptante/mis-animales'); ?>" class="dropdown-item"><i class="fas fa-paw me-2"></i> Mis Mascotas</a></li>
                                <li class="dropdown-divider"></li>
                                <li><a href="<?php echo url('/logout'); ?>" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a href="<?php echo url('/login'); ?>" class="btn-login">Iniciar Sesión</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Modales mejorados -->
    <!-- Modal Adoptar -->
    <div class="modal fade" id="modalAdoptar" tabindex="-1" aria-labelledby="modalAdoptarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdoptarLabel"><i class="fas fa-heart me-2"></i>Adoptar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Encuentra tu nueva mascota y conoce el proceso de adopción responsable.</p>
                    <ul>
                        <li><b>Perros:</b> Conoce a nuestros amigos caninos listos para un hogar amoroso.</li>
                        <li><b>Gatos:</b> Descubre adorables felinos esperando por una familia.</li>
                        <li><b>Otros animales:</b> Conejos, aves y más buscando un hogar.</li>
                        <li><b>Proceso de adopción:</b> Aprende cómo adoptar paso a paso.</li>
                    </ul>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i> ¡Adoptar es un acto de amor! Si tienes dudas, contáctanos.
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo url('/login'); ?>" class="btn btn-primary me-2"><i class="fas fa-paw me-1"></i> Ver Mascotas</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Información -->
    <div class="modal fade" id="modalInformacion" tabindex="-1" aria-labelledby="modalInformacionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalInformacionLabel"><i class="fas fa-info-circle me-2"></i>Información</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Aprende sobre el cuidado de mascotas y resuelve tus dudas con nuestros recursos.</p>
                    <ul>
                        <li><b>Cuidados básicos:</b> Guías completas para mantener a tu mascota saludable.</li>
                        <li><b>Blog educativo:</b> Artículos escritos por expertos en bienestar animal.</li>
                        <li><b>Preguntas frecuentes:</b> Respuestas a las dudas más comunes sobre adopción.</li>
                        <li><b>Eventos:</b> Talleres y actividades para dueños de mascotas.</li>
                    </ul>
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-lightbulb me-2"></i> La información es poder: ¡Infórmate antes de adoptar!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Donaciones -->
    <div class="modal fade" id="modalDonaciones" tabindex="-1" aria-labelledby="modalDonacionesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDonacionesLabel"><i class="fas fa-hand-holding-heart me-2"></i>Donaciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>Tu ayuda es fundamental para seguir rescatando y cuidando mascotas necesitadas.</p>
                    <ul>
                        <li><b>Donaciones económicas:</b> Contribuye con cualquier cantidad para alimentos y veterinarios.</li>
                        <li><b>Alimento y medicinas:</b> Lista de insumos que siempre necesitamos.</li>
                        <li><b>Voluntariado:</b> Únete a nuestro equipo de voluntarios.</li>
                        <li><b>Apadrina:</b> Ayuda a costear los gastos de una mascota específica.</li>
                    </ul>
                    <div class="alert alert-success mt-3">
                        <i class="fas fa-heart me-2"></i> ¡Gracias por apoyar a nuestros peluditos!
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo url('/donaciones'); ?>" class="btn btn-success me-2"><i class="fas fa-donate me-1"></i> Donar Ahora</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Chat Messenger Widget
    function toggleMessengerChat(){
        const widget = document.getElementById('messengerWidget');
        const fab = document.getElementById('openMessengerChat');
        if(widget.style.display === 'none' || widget.style.display === ''){
            widget.style.display = 'flex';
            fab.style.display = 'none';
            setTimeout(()=>{
                document.getElementById('messengerChatInput').focus();
                // Si es adoptante, mostrar info del refugio automáticamente
                <?php if (isset($_SESSION['id_usuario'])): ?>
                showRefugioInfo();
                <?php endif; ?>
            }, 200);
        } else {
            widget.style.display = 'none';
            fab.style.display = 'flex';
        }
    }

    function showRefugioInfo() {
        const body = document.getElementById('messengerChatBody');
        // Evitar duplicados
        if (!body.querySelector('.chat-messenger-msg.info')) {
            const infoMsg = document.createElement('div');
            infoMsg.className = 'chat-messenger-msg bot info';
            infoMsg.innerHTML = `
                <b>Información del Refugio:</b><br>
                <b>Ubicación:</b> Calle Beijing y Dorbigni<br>
                <b>Horarios:</b> Lunes a Domingo de 9:00am a 8:00pm
                <b>recaudacion de comida:</b> fines de semana de 9:00am a 8:00pm

            `;
            body.appendChild(infoMsg);
            body.scrollTop = body.scrollHeight;
        }
    }

    function sendMessengerMessage() {
        const input = document.getElementById('messengerChatInput');
        const body = document.getElementById('messengerChatBody');
        const msg = input.value.trim();
        if (!msg) return;
        // Mostrar mensaje del usuario
        const userMsgEl = document.createElement('div');
        userMsgEl.className = 'chat-messenger-msg user';
        userMsgEl.textContent = msg;
        body.appendChild(userMsgEl);
        input.value = '';
        body.scrollTop = body.scrollHeight;

        // Respuesta local del bot
        let response = '';
        const msgLower = msg.toLowerCase();
        if (
            msgLower.includes('ubicación') ||
            msgLower.includes('direccion') ||
            msgLower.includes('dirección') ||
            msgLower.includes('dónde') ||
            msgLower.includes('donde')
        ) {
            response = 'Estamos en Calle Beijing y Dorbigni.';
        } else if (
            msgLower.includes('horario') ||
            msgLower.includes('hora') ||
            msgLower.includes('atención') ||
            msgLower.includes('abren') ||
            msgLower.includes('cierran')
        ) {
            response = 'Nuestro horario de atención es de lunes a viernes de 9:00am a 8:00pm.';
        } else if (
            msgLower.includes('hola') ||
            msgLower.includes('buenas') ||
            msgLower.includes('saludo')
        ) {
            response = '¡Hola! ¿En qué puedo ayudarte?';
        } else if (
            msgLower.includes('gracias') ||
            msgLower.includes('thank')
        ) {
            response = '¡De nada! Si tienes otra consulta, aquí estoy.';
        } else if (
            msgLower.includes('información') ||
            msgLower.includes('info') ||
            msgLower.includes('refugio')
        ) {
            response = 'Estamos en Calle Beijing y Dorbigni. Nuestro horario es de lunes a viernes de 9:00am a 8:00pm.';
        } else {
            response = 'Soy el asistente virtual del refugio. Puedes preguntarme por la ubicación o los horarios de atención.';
        }
        const botMsgEl = document.createElement('div');
        botMsgEl.className = 'chat-messenger-msg bot';
        botMsgEl.textContent = response;
        body.appendChild(botMsgEl);
        body.scrollTop = body.scrollHeight;
    }

    document.addEventListener('DOMContentLoaded', function(){
        const input = document.getElementById('messengerChatInput');
        if(input){
            input.addEventListener('keypress', (e) => {
                if(e.key === 'Enter') sendMessengerMessage();
            });
        }
        // Inicializar widget oculto y botón visible
        const widget = document.getElementById('messengerWidget');
        const fab = document.getElementById('openMessengerChat');
        if(widget) widget.style.display = 'none';
        if(fab) fab.style.display = 'flex';
        // Agregar event listener al botón flotante para abrir el chat
        if(fab) fab.addEventListener('click', toggleMessengerChat);
    });

        // Menú móvil mejorado
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            this.querySelector('i').classList.toggle('fa-bars');
            this.querySelector('i').classList.toggle('fa-times');
            document.querySelector('.nav-menu').classList.toggle('active');
            document.body.classList.toggle('no-scroll');
        });

        // Cerrar menú al hacer clic en un enlace en móvil
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    document.querySelector('.nav-menu').classList.remove('active');
                    document.querySelector('.mobile-menu-btn i').classList.add('fa-bars');
                    document.querySelector('.mobile-menu-btn i').classList.remove('fa-times');
                }
            });
        });

        // Manejo de dropdowns responsivos
        function handleDropdowns() {
            const isMobile = window.innerWidth < 992;

            document.querySelectorAll('.nav-item').forEach(item => {
                const link = item.querySelector('.nav-link');
                const dropdown = item.querySelector('.dropdown-menu');

                if (dropdown) {
                    if (isMobile) {
                        // En móvil: click para abrir/cerrar
                        link.addEventListener('click', function(e) {
                            if (dropdown.style.display === 'block') {
                                dropdown.style.display = 'none';
                            } else {
                                // Cerrar otros dropdowns
                                document.querySelectorAll('.dropdown-menu').forEach(d => {
                                    if (d !== dropdown) d.style.display = 'none';
                                });
                                dropdown.style.display = 'block';
                            }
                            e.preventDefault();
                            e.stopPropagation();
                        });
                    } else {
                        // En escritorio: hover para abrir
                        item.addEventListener('mouseenter', () => {
                            dropdown.style.display = 'block';
                        });

                        item.addEventListener('mouseleave', () => {
                            dropdown.style.display = 'none';
                        });
                    }
                }
            });
        }

        // Cerrar dropdowns al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 992) {
                const dropdowns = document.querySelectorAll('.dropdown-menu');
                dropdowns.forEach(dropdown => {
                    if (!dropdown.parentElement.contains(e.target)) {
                        dropdown.style.display = 'none';
                    }
                });
            }
        });

        // Efecto de scroll para el navbar
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-refugio');
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            handleDropdowns();
            window.addEventListener('resize', handleDropdowns);

            // Activar el tooltip de notificaciones
            const notificationBadge = document.querySelector('.notification-badge');
            if (notificationBadge) {
                new bootstrap.Tooltip(notificationBadge, {
                    title: 'Tienes 3 seguimientos pendientes',
                    placement: 'bottom'
                });
            }
        });
    </script>
</body>

</html>

