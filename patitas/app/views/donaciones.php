<?php
require_once __DIR__ . '../../config/conexion.php';
require_once __DIR__ . '/menu.php';

// Si el controlador no pasa datos, usamos ejemplos de perros
if (!isset($perros) || !is_array($perros)) {
	$perros = [
		[
			'name' => 'Luna',
			'age' => '3 años',
			'breed' => 'Mestiza',
			'image' => 'https://media.tiempodesanjuan.com/p/0dc31e47d653e910aefbf77ce1e48bfc/adjuntos/331/imagenes/000/697/0000697847/790x0/smart/luna-perrita-callejerajpg.jpg',
			'description' => 'Tratamiento veterinario y medicinas necesarias.',
			'raised' => 3200,
			'target' => 10000,
			'city' => 'Ciudad',
		],
		[
			'name' => 'Simba',
			'age' => '4 años',
			'breed' => 'Labrador',
			'image' => 'https://tse2.mm.bing.net/th/id/OIP._DapDIXPIH0PLwLFNs0Y8AHaEc?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3',
			'description' => 'Cirugía urgente y recuperación.',
			'raised' => 8500,
			'target' => 25000,
			'city' => 'Localidad',
		],
		[
			'name' => 'Coco',
			'age' => '2 años',
			'breed' => 'mestizo',
			'image' => 'https://mx.web.img3.acsta.net/r_1280_720/newsv7/22/08/10/01/39/2326906.jpg',
			'description' => 'Cuidados postoperatorios y medicinas.',
			'raised' => 14000,
			'target' => 40000,
			'city' => 'Ciudad',
		],
		[
			'name' => 'Abuela Dita ',
			'age' => '11 años',
			'breed' => 'Mixta',
			'image' => 'https://www.zoorprendente.com/wp-content/uploads/2018/04/viejito.jpg',
			'description' => 'Una perrita de 11 años lleva en el refugio 3 años , se necesita como  primordial es analisis de sangre  ya q  se  le encontró bultos con sospecha de tumores cancerígenos  por ello se solicita dicho estudio .
			también Necesitara  rx , ecografias para definir tratamiento.',
			'raised' => 1200,
			'target' => 5000,
			'city' => 'Ciudad',
		],

		[
			'name' => 'Killa',
			'age' => '12 años',
			'breed' => 'Mestizo',
			'image' => 'https://estaticos-cdn.prensaiberica.es/clip/29a7a30e-cd8e-4a5c-903d-27e483a19fcd_16-9-aspect-ratio_default_0_x470y454.jpg',
			'description' => 'fue  utilizada para procrear y comercializar crías ya que dicha perrita era de raza pura al ya no servirles la votaron  
			a las  calles  ciega y desnutrida  a causa  de ello   tiene aún secuelas q necesita  gotitas q de llaman SOFTALE para ayudar a su seguirá 
			para que disminua por lo menos un 50 % y ayude a su visibilidad.',
			'raised' => 150,
			'target' => 1000,
			'city' => 'Ciudad',
		],
		[
			'name' => 'Mama Soltera',
			'age' => '5 años',
			'breed' => 'Cruza',
			'image' => 'https://i.ytimg.com/vi/99WOAbL9nkM/hqdefault.jpg',
			'description' => 'Llega con 5 años al refugio con el pasar de  los días se noto dificultad al caminar   y al llevar al veterinario  se descubre problemas de columna  q le impidieron  
			mover las patas traseras por ello necesita  aun fioterapia para fortalecer  a los músculos   y eso debe recibir como mínimo 3 veces por semana .',
			'raised' => 900,
			'target' => 5000,
			'city' => 'Localidad',
		],
		[
			'name' => 'Mami',
			'age' => '2 años',
			'breed' => 'Mixta pequeña',
			'image' => 'https://www.bunko.pet/__export/1686782337671/sites/debate/img/2023/06/14/perra_mestiza.jpg_673822677.jpg',
			'description' => 'Llego a sus 2 añitos al refugio con el tiempo se descubrió q tenia seguera temporal  para ello necesita gotitas SOFTAL E debe aplicarse hasta q
			ue disminuya la catarata q se le forma en sus ojitos  para aliviar  su visibilidad.',
			'raised' => 600,
			'target' => 3000,
			'city' => 'Ciudad',
		],
		[
			'name' => 'Abuelo ximeno',
			'age' => '10 años',
			'breed' => 'Senior',
			'image' => 'https://media.ambito.com/p/0057380d81ca3862cbbdfedd2594c687/adjuntos/239/imagenes/041/677/0041677027/730x0/smart/pais-sin-perros-callejerosjpg.jpg',
			'description' => 'Llego con parálisis de sua patas traseras, fue curado  con  inyectable que debe recibir cada 2 meses',
			'raised' => 500,
			'target' => 2000,
			'city' => 'Localidad',
		],
		
		[
			'name' => 'Za',
			'age' => '2 años',
			'breed' => 'Bulldog',
			'image' => 'https://tse2.mm.bing.net/th/id/OIP.8vVa2zkyTMDpChUK44SB_wHaEK?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3',
			'description' => ' Perrita  abandonada en las puertas del refugio junto a sus 4 hermanas  ella es un caso  especial ya q tiene la mandíbula inferior   
			retraída,  ella  tiene problemas de piel para lo cual  debe tomar medicamentos  todos los días sin falta durante toda su vida y baños medicinales  2 veces al mes',
			'raised' => 0,
			'target' => 3000,
			'city' => 'Localidad',
		],
		[
			'name' => 'Choco',
			'age' => '16 años',
			'breed' => 'mestizo',
			'image' => 'https://imagenes.elpais.com/resizer/7whXDOLPNPR0pLH67LkE8U1u3Dk=/414x0/arc-anglerfish-eu-central-1-prod-prisa.s3.amazonaws.com/public/22HGBYGUVG3PEGGOM7EHMXGVFI.jpg',
			'description' => 'Tiene problemas articulares  q le dificultan  el  movimiento y por ello  necesita un tratamiento de inyectables cada  mes ( Artrosan )
            Rescatado con problemas  cutáneos  y lleva en el refugio más de 5 años   y necesario con hurgencia un hogar ya q por el problema  
			que tiene la umedad no le ayuda aliviar sus dolores articulares',
			'raised' => 700,
			'target' => 3000,
			'city' => 'Ciudad',
		],
	];
}
if (!isset($gatos) || !is_array($gatos)) {
	$gatos = [
		[
			'name' => 'Misu',
			'age' => '2 años',
			'breed' => 'Europeo',
			'image' => 'https://tse4.mm.bing.net/th/id/OIP.4zaBVyOADjRr5LP7Wyc_VQHaHa?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3',
			'description' => 'Rescatada de la calle, necesita tratamiento para infecciones y esterilización.',
			'raised' => 300,
			'target' => 1500,
			'city' => 'Ciudad',
		],
		[
			'name' => 'Nube',
			'age' => '4 meses',
			'breed' => 'Criolla',
			'image' => 'https://tse2.mm.bing.net/th/id/OIP.y8a-Nea-rBZ1w4bQkRNHMAHaE7?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3',
			'description' => 'Cachorra con desnutrición y desparasitación pendiente.',
			'raised' => 50,
			'target' => 800,
			'city' => 'Localidad',
		],
		[
			'name' => 'Luna (gato)',
			'age' => '6 años',
			'breed' => 'Siames',
			'image' => 'https://tse1.mm.bing.net/th/id/OIP.e4ZfjhXJ8ItJJ5s7bbeqQQHaFj?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3',
			'description' => 'Problemas dentales y examen veterinario completo requerido.',
			'raised' => 120,
			'target' => 1000,
			'city' => 'Ciudad',
		],
		[
			'name' => 'Pepper',
			'age' => '1 año',
			'breed' => 'Bengalí',
			'image' => 'https://blog.felinus.cl/wp-content/uploads/2023/03/gato-bengali-5.png',
			'description' => 'Necesita vacunas y seguimiento por infecciones respiratorias.',
			'raised' => 0,
			'target' => 600,
			'city' => 'Localidad',
		],
	];
}
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Donaciones para perros - Gamaliel</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		body { background:#f4f6f8; font-family: 'Poppins', sans-serif; }
		.hero { background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(75,123,236,0.7)), url('https://tse1.mm.bing.net/th/id/OIP.ITiPTLaMGqOuRXdMTN901QHaEl?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3') center/cover no-repeat; color:#fff; padding:80px 0; margin-bottom:20px; border-radius:8px; min-height:320px; display:flex; align-items:center; }
		.card-pet img { height:160px; object-fit:cover; }
		.progress { height:8px; border-radius:8px; }
		.tag { font-size:0.8rem; padding:4px 8px; border-radius:999px; background:#eef6ff; color:#2a6df4; font-weight:600 }
	</style>
</head>
<body>
	<div class="container mt-4">
		<section class="hero text-center">
			<div class="container">
				<h1 class="display-5 fw-bold" style="text-shadow: 0 2px 10px rgba(0,0,0,0.5);">Donaciones para perros</h1>
				<p class="lead mb-0" style="text-shadow: 0 1px 6px rgba(0,0,0,0.5);">Apoya a perros necesitados con donaciones directas: tratamientos, cirugías y cuidados.</p>
			</div>
		</section>
		<section id="lista-perros" class="py-3">
			<h4 class="mb-4">Perros que necesitan ayuda</h4>
			<div class="row g-4">
				<?php foreach ($perros as $p):
					$percent = ($p['target'] > 0) ? round(($p['raised'] / $p['target']) * 100) : 0;
				?>
				<div class="col-sm-6 col-md-4 col-lg-3">
					<div class="card card-pet h-100 shadow-sm">
						<img src="<?php echo htmlspecialchars($p['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($p['name']); ?>">
						<div class="card-body d-flex flex-column">
							<div class="d-flex justify-content-between align-items-start mb-1">
								<h5 class="card-title mb-0"><?php echo htmlspecialchars($p['name']); ?></h5>
								<span class="tag"><?php echo htmlspecialchars($p['breed']); ?></span>
							</div>
							<small class="text-muted"><?php echo htmlspecialchars($p['age']); ?> — <?php echo htmlspecialchars($p['city']); ?></small>
							<p class="card-text small text-muted mt-2 mb-2"><?php echo htmlspecialchars($p['description']); ?></p>

							<div class="mb-2">
								<div class="d-flex justify-content-between small">
									<span class="text-primary fw-bold">$<?php echo number_format($p['raised'],0,',','.'); ?></span>
									<span class="text-muted"><?php echo $percent; ?>%</span>
								</div>
								<div class="progress mt-1">
									<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%;" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
								</div>
							</div>

							<div class="mt-auto">
								<div class="d-grid gap-2">
									<a href="<?php echo url('/detalle'); ?>" class="btn btn-success me-2"><i class="fas fa-donate me-1"></i> Donar a</a>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</section>

		<!-- Nueva sección: Gatos -->
		<section id="lista-gatos" class="py-4">
			<h4 class="mb-4">Gatos que necesitan ayuda</h4>
			<div class="row g-4">
				<?php foreach ($gatos as $g):
					$percent = ($g['target'] > 0) ? round(($g['raised'] / $g['target']) * 100) : 0;
				?>
				<div class="col-sm-6 col-md-4 col-lg-3">
					<div class="card card-pet h-100 shadow-sm">
						<img src="<?php echo htmlspecialchars($g['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($g['name']); ?>">
						<div class="card-body d-flex flex-column">
							<div class="d-flex justify-content-between align-items-start mb-1">
								<h5 class="card-title mb-0"><?php echo htmlspecialchars($g['name']); ?></h5>
								<span class="tag"><?php echo htmlspecialchars($g['breed']); ?></span>
							</div>
							<small class="text-muted"><?php echo htmlspecialchars($g['age']); ?> — <?php echo htmlspecialchars($g['city']); ?></small>
							<p class="card-text small text-muted mt-2 mb-2"><?php echo htmlspecialchars($g['description']); ?></p>

							<div class="mb-2">
								<div class="d-flex justify-content-between small">
									<span class="text-primary fw-bold">$<?php echo number_format($g['raised'],0,',','.'); ?></span>
									<span class="text-muted"><?php echo $percent; ?>%</span>
								</div>
								<div class="progress mt-1">
									<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%;" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
								</div>
							</div>

							<div class="mt-auto">
								<div class="d-grid gap-2">
									<a href="<?php echo url('/detalle'); ?>" class="btn btn-success me-2"><i class="fas fa-donate me-1"></i> Donar a</a>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</section>

		<footer class="py-4 bg-white border-top mt-5">
			<div class="container text-center text-muted small">
				&copy; <?php echo date('Y'); ?> Gamaliel - Donaciones para perros
			</div>
		</footer>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
	function filterCards() {
		const searchInput = document.getElementById('search').value.toLowerCase().trim();
		const allCards = document.querySelectorAll('.card-pet');

		allCards.forEach(card => {
			const name = card.querySelector('.card-title') ? card.querySelector('.card-title').textContent.toLowerCase() : '';
			const breed = card.querySelector('.tag') ? card.querySelector('.tag').textContent.toLowerCase() : '';
			const description = card.querySelector('.card-text') ? card.querySelector('.card-text').textContent.toLowerCase() : '';
			const city = card.parentElement.querySelector('small') ? card.parentElement.querySelector('small').textContent.toLowerCase() : '';

			// Buscar coincidencias en cualquiera de estos campos
			const matches = name.includes(searchInput) || breed.includes(searchInput) || description.includes(searchInput) || city.includes(searchInput);

			// Mostrar u ocultar la tarjeta
			if (searchInput === '' || matches) {
				card.closest('.col-sm-6').style.display = 'block';
			} else {
				card.closest('.col-sm-6').style.display = 'none';
			}
		});
	}
	</script>
</body>
</html>