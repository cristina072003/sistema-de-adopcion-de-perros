CREATE DATABASE refugio_mascotas;
USE refugio_mascotas;

-- Tabla departamentos
CREATE TABLE departamentos (
	id_departamento INT AUTO_INCREMENT PRIMARY KEY,
	nombre VARCHAR(50) NOT NULL,
	provincia VARCHAR(45) NOT NULL,
	ubicacion_gps VARCHAR(100)
);

-- Tabla usuarios
CREATE TABLE usuarios (
	id_usuario INT AUTO_INCREMENT PRIMARY KEY,
	correo VARCHAR(150) NOT NULL UNIQUE,
	contrasena VARCHAR(255) NOT NULL,
	rol ENUM('administrador', 'adoptante') NOT NULL,
	activo TINYINT(1) DEFAULT 1,
	fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla adoptantes
CREATE TABLE adoptantes (
	id_adoptante INT AUTO_INCREMENT PRIMARY KEY,
	id_usuario INT NOT NULL,
	nombre_completo VARCHAR(150) NOT NULL,
	telefono VARCHAR(20) NOT NULL,
	direccion TEXT NOT NULL,
	documento_identidad VARCHAR(50) NOT NULL UNIQUE,
	fecha_nacimiento DATE NOT NULL,
	ocupacion VARCHAR(100),
	experiencia_con_animales TINYINT(1),
	descripcion_vivienda TEXT,
	otros_animales TEXT,
	tiempo_en_casa VARCHAR(100),
	referencia_personal VARCHAR(100),
	FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Tabla razas
CREATE TABLE razas (
	id_raza INT AUTO_INCREMENT PRIMARY KEY,
	especie ENUM('Perro', 'Gato') NOT NULL,
	nombre_raza VARCHAR(100) NOT NULL
);

-- Tabla animales
CREATE TABLE animales (
	id_animal INT AUTO_INCREMENT PRIMARY KEY,
	nombre VARCHAR(50) NOT NULL,
	especie ENUM('Perro', 'Gato') NOT NULL,
	id_raza INT,
	edad_anios INT,
	sexo ENUM('Macho', 'Hembra') NOT NULL,
	tamanio ENUM('Pequeño', 'Mediano', 'Grande') NOT NULL,
	descripcion TEXT,
	fecha_ingreso DATE NOT NULL,
	estado_salud VARCHAR(100),
	esterilizado TINYINT(1) DEFAULT 0,
	vacunado TINYINT(1) DEFAULT 0,
	estado ENUM('Disponible', 'En adopción', 'Adoptado', 'Reservado') DEFAULT 'Disponible',
	historia TEXT,
	activo TINYINT(1) DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_raza) REFERENCES razas(id_raza)
);

-- Tabla fotos_animales
CREATE TABLE fotos_animales (
	id_foto INT AUTO_INCREMENT PRIMARY KEY,
	id_animal INT NOT NULL,
	url_foto VARCHAR(255) NOT NULL,
	descripcion TEXT,
	FOREIGN KEY (id_animal) REFERENCES animales(id_animal) ON DELETE CASCADE
);

-- Tabla animal_departamento
CREATE TABLE animal_departamento (
	id_animal INT NOT NULL,
	id_departamento INT NOT NULL,
	PRIMARY KEY (id_animal, id_departamento),
	FOREIGN KEY (id_animal) REFERENCES animales(id_animal) ON DELETE CASCADE,
	FOREIGN KEY (id_departamento) REFERENCES departamentos(id_departamento) ON DELETE CASCADE
);

-- Tabla solicitudes_adopcion
CREATE TABLE solicitudes_adopcion (
	id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
	id_adoptante INT NOT NULL,
	id_animal INT NOT NULL,
	fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	estado ENUM('Pendiente', 'Aprobada', 'Rechazada', 'En revisión') DEFAULT 'Pendiente',
	motivo_adopcion TEXT NOT NULL,
	experiencia_mascotas TEXT,
	tipo_vivienda ENUM('Casa', 'Departamento', 'Otro') NOT NULL,
	tiene_otros_animales TINYINT(1),
	tiempo_solo_horas_por_dia INT,
	fecha_respuesta TIMESTAMP NULL,
	notas_admin TEXT,
	FOREIGN KEY (id_adoptante) REFERENCES adoptantes(id_adoptante) ON DELETE CASCADE,
	FOREIGN KEY (id_animal) REFERENCES animales(id_animal) ON DELETE CASCADE
);

-- Tabla seguimientos
CREATE TABLE seguimientos (
	id_seguimiento INT AUTO_INCREMENT PRIMARY KEY,
	id_solicitud INT NOT NULL,
	fecha_seguimiento DATE NOT NULL,
	notas TEXT,
	estado_animal TEXT,
	fotos_actuales TEXT,
	satisfaccion ENUM('Excelente', 'Bueno', 'Regular', 'Malo'),
	FOREIGN KEY (id_solicitud) REFERENCES solicitudes_adopcion(id_solicitud) ON DELETE CASCADE
);

-- Usuarios (1 admin + 5 adoptantes)
-- Contraseña admin: Cr12345678
-- Contraseña adoptantes: 12345678
INSERT INTO usuarios (correo, contrasena, rol, activo) VALUES
('admin@refugio.com', '12345678', 'administrador', 1),
('maria.garcia@example.com', '12345678', 'adoptante', 1),
('juan.perez@example.com', '12345678', 'adoptante', 1),
('laura.martinez@example.com', '12345678', 'adoptante', 1),
('carlos.rodriguez@example.com', '12345678', 'adoptante', 0),
('sofia.lopez@example.com', '12345678', 'adoptante', 1);


-- Insertar en departamentos
INSERT INTO departamentos (nombre, provincia, ubicacion_gps) VALUES
('La Paz', 'Pedro Domingo Murillo', '-16.4897,-68.1193'),
('Santa Cruz', 'Andrés Ibáñez', '-17.7892,-63.1972'),
('Cochabamba', 'Cercado', '-17.3841,-66.1667'),
('Oruro', 'Cercado', '-17.9833,-67.1500'),
('Potosí', 'Tomas Frías', '-19.5833,-65.7500'),
('Tarija', 'Cercado', '-21.5333,-64.7333'),
('Chuquisaca', 'Oropeza', '-19.0500,-65.2500'),
('Beni', 'Cercado', '-14.8333,-64.9000'),
('Pando', 'Nicolás Suárez', '-11.0333,-68.7333'),
('El Alto', 'Municipio', '-16.5047,-68.1632'),
('Quillacollo', 'Cercado', '-17.4000,-66.2833'),
('Sacaba', 'Cercado', '-17.4042,-66.0408'),
('Montero', 'Obispo Santistevan', '-17.3333,-63.2500'),
('Warnes', 'Ichilo', '-17.5167,-63.1667'),
('Villa Tunari', 'Chapare', '-16.9667,-65.4167');

-- Insertar en adoptantes
INSERT INTO adoptantes (
    id_usuario, nombre_completo, telefono, direccion, documento_identidad, fecha_nacimiento,
    ocupacion, experiencia_con_animales, descripcion_vivienda, otros_animales, tiempo_en_casa, referencia_personal
) VALUES
(2, 'Juan Pérez Mamani', '70123456', 'Av. Arce 1234, La Paz', '1234567LP', '1990-05-15', 'Ingeniero', 1, 'Casa con patio', '1 perro', '8 horas', 'Carlos Fernández'),
(3, 'María Gómez Quispe', '68754321', 'Calle Jordán 456, Santa Cruz', '7654321SC', '1988-07-22', 'Médico', 0, 'Departamento', 'Ninguno', '6 horas', 'Ana Rodríguez'),
(4, 'Carlos López Fernández', '71234567', 'Av. Heroínas 789, Cochabamba', '9876543CB', '1992-11-30', 'Abogado', 1, 'Casa pequeña', '1 gato', '5 horas', 'Laura Méndez'),
(5, 'Ana Martínez Vargas', '69987654', 'Calle Sucre 321, Oruro', '5432167OR', '1985-09-18', 'Profesora', 1, 'Casa con jardín', '2 perros', '7 horas', 'Pedro Sánchez'),
(6, 'Luis Torres Rojas', '72456789', 'Av. Potosí 654, Potosí', '3216547PT', '1995-02-10', 'Estudiante', 0, 'Departamento pequeño', 'Ninguno', '4 horas', 'Sofía Castro');

-- Insertar en razas
INSERT INTO razas (especie, nombre_raza) VALUES
('Perro', 'Criollo boliviano'),
('Perro', 'Pastor Alemán'),
('Gato', 'Criollo'),
('Gato', 'Siamés'),
('Perro', 'Labrador Retriever'),
('Perro', 'Bulldog Francés'),
('Perro', 'Chihuahua'),
('Perro', 'Poodle'),
('Perro', 'Beagle'),
('Perro', 'Boxer'),
('Gato', 'Persa'),
('Gato', 'Bengalí'),
('Gato', 'Maine Coon'),
('Gato', 'Azul Ruso'),
('Gato', 'Esfinge');

-- Insertar en animales
INSERT INTO animales (
    nombre, especie, id_raza, edad_anios, sexo, tamanio, descripcion, fecha_ingreso,
    estado_salud, esterilizado, vacunado, estado, historia
) VALUES
('Canela', 'Perro', 1, 2, 'Hembra', 'Mediano', 'Perro mestizo muy cariñoso', '2023-09-10', 'Saludable', 1, 1, 'Disponible', 'Rescatada de la calle en El Alto'),
('Toby', 'Perro', 5, 3, 'Macho', 'Grande', 'Labrador juguetón', '2023-08-15', 'Saludable', 1, 1, 'En adopción', 'Dueño anterior se mudó al exterior'),
('Michi', 'Gato', 3, 1, 'Macho', 'Pequeño', 'Gato tímido pero cariñoso', '2023-10-05', 'Buena', 0, 1, 'Disponible', 'Encontrado en parque'),
('Luna', 'Gato', 4, 2, 'Hembra', 'Mediano', 'Gata vocal y activa', '2023-07-20', 'Excelente', 1, 1, 'Reservado', 'Abandonada en clínica veterinaria'),
('Rex', 'Perro', 2, 4, 'Macho', 'Grande', 'Perro guardián bien entrenado', '2023-06-01', 'Saludable', 1, 1, 'Disponible', 'Donado por familia militar'),
('Bobby', 'Perro', 6, 2, 'Macho', 'Pequeño', 'Bulldog francés juguetón', '2023-05-12', 'Buena', 1, 1, 'Adoptado', 'Rescatado de criadero ilegal'),
('Pepita', 'Gato', 11, 3, 'Hembra', 'Mediano', 'Gata persa tranquila', '2023-04-18', 'Excelente', 1, 1, 'Disponible', 'Dueña falleció'),
('Rocky', 'Perro', 7, 1, 'Macho', 'Pequeño', 'Chihuahua valiente', '2023-03-22', 'Saludable', 0, 1, 'Disponible', 'Encontrado en mercado'),
('Nala', 'Gato', 12, 2, 'Hembra', 'Mediano', 'Gata bengalí activa', '2023-02-15', 'Buena', 1, 1, 'En adopción', 'Donada por familia'),
('Thor', 'Perro', 9, 5, 'Macho', 'Mediano', 'Beagle olfateador', '2023-01-10', 'Regular', 1, 1, 'Disponible', 'Rescatado de abandono'),
('Mimi', 'Gato', 13, 4, 'Hembra', 'Grande', 'Maine Coon majestuoso', '2022-12-05', 'Excelente', 1, 1, 'Disponible', 'Encontrado en parque'),
('Max', 'Perro', 8, 2, 'Macho', 'Mediano', 'Poodle inteligente', '2022-11-20', 'Saludable', 1, 1, 'Adoptado', 'Entrenado como perro de terapia'),
('Cleo', 'Gato', 15, 1, 'Hembra', 'Mediano', 'Gata esfinge cariñosa', '2022-10-15', 'Buena', 0, 1, 'Disponible', 'Necesita cuidados especiales'),
('Zeus', 'Perro', 10, 3, 'Macho', 'Grande', 'Boxer energético', '2022-09-01', 'Saludable', 1, 1, 'Disponible', 'Entrenado para agility'),
('Lucky', 'Perro', 1, 7, 'Macho', 'Mediano', 'Perro criollo anciano', '2022-08-15', 'Regular', 1, 1, 'Disponible', 'Buscando hogar para sus últimos años');
-- Insertar en fotos_animales
INSERT INTO fotos_animales (id_animal, url_foto, descripcion) VALUES
(1, 'https://www.excelsior.com.mx/media/inside-the-note/pictures/2023/08/17/perro_criollo_1.jpg', 'Canela en el jardín'),
(1, 'https://th.bing.com/th/id/OIP.mt3N3Q2K0Xj30EorMS80oQHaE8?cb=iwp2&rs=1&pid=ImgDetMain', 'Canela jugando'),
(2, 'https://th.bing.com/th/id/OIP.GuVDlpZLRGL88uKgHqJPLAHaE8?cb=iwp2&rs=1&pid=ImgDetMain', 'Toby con pelota'),
(3, 'https://th.bing.com/th/id/OIP.4Indvh3wGUCskoJf2GPZWwHaEs?cb=iwp2&rs=1&pid=ImgDetMain', 'Michi durmiendo'),
(4, 'https://th.bing.com/th/id/R.6b2026c15d2cc7b6c6453118b8758eb8?rik=p5hZWemaKDXfVg&riu=http%3a%2f%2fwww.razasdeperros.com%2fwp-content%2fuploads%2f2013%2f10%2fDepositphotos_8405161_m.jpg&ehk=076xT4EInAZGdk61vPke7foOGNpYlmbQT2JXi%2fSiMIw%3d&risl=&pid=ImgRaw&r=0', 'Luna en el árbol'),
(5, 'https://th.bing.com/th/id/OIP.5aWV0cwbE9qiUQVRRU-qMwHaE8?cb=iwp2&rs=1&pid=ImgDetMain', 'Rex entrenando'),
(6, 'https://th.bing.com/th/id/OIP.OuT64BMb_72PBbDLQEvKQQHaE8?cb=iwp2&rs=1&pid=ImgDetMain', 'Bobby sonriendo'),
(7, 'https://cdn.britannica.com/39/233239-050-50C0C3C5/standard-poodle-dog.jpg', 'Pepita en cojín'),
(8, 'https://cdn.britannica.com/80/29280-050-A3A13277/Beagles-pets.jpg', 'Rocky en brazos'),
(9, 'https://cdn.britannica.com/46/233846-050-8D30A43B/Boxer-dog.jpg', 'Nala explorando'),
(10, 'https://static.zoomalia.com/blogz/3223/tout-savoir-sur-le-persan.jpeg', 'Thor olfateando'),
(11, 'https://th.bing.com/th/id/R.12c531aa915f00a5c8ad600c4719cf1a?rik=1Jaz1AZWVPj3XA&pid=ImgRaw&r=0', 'Mimi majestuosa'),
(12, 'https://th.bing.com/th/id/OIP.9AZcAWfMAdID94FLYBZRjgHaFj?cb=iwp2&rs=1&pid=ImgDetMain', 'Max en terapia'),
(13, 'https://faunalogia.com/wp-content/uploads/gato-azul-ruso-1536x1023.jpg', 'Cleo con suéter'),
(14, 'https://th.bing.com/th/id/OIP.gkkacIUL3UOSjOBiKVIgkQHaFA?cb=iwp2&rs=1&pid=ImgDetMain', 'Zeus corriendo');

-- Insertar en animal_departamento
INSERT INTO animal_departamento (id_animal, id_departamento) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5),
(6, 6), (7, 7), (8, 8), (9, 9), (10, 10),
(11, 11), (12, 12), (13, 13), (14, 14), (15, 15);

-- Insertar en solicitudes_adopcion
INSERT INTO solicitudes_adopcion (
	id_adoptante, id_animal, motivo_adopcion, experiencia_mascotas, tipo_vivienda,
	tiene_otros_animales, tiempo_solo_horas_por_dia
) VALUES (
	1, 1, 'Quiero darle un hogar amoroso', 'He tenido perros antes', 'Casa', 1, 4
);

-- Insertar en seguimientos
INSERT INTO seguimientos (
	id_solicitud, fecha_seguimiento, notas, estado_animal, fotos_actuales, satisfaccion
) VALUES (
	1, '2024-01-20', 'El perro se adapta bien al hogar.', 'Activo y saludable',
	'https://ejemplo.com/foto_actual_firulais.jpg', 'Excelente'
);
