<?php
require_once __DIR__ . '../../config/conexion.php';
require_once __DIR__ . '/menu.php';

// Arrays de ejemplo (mismos campos que en donaciones.php)
if (!isset($perros) || !is_array($perros)) {
	$perros = [
		['name'=>'Luna','age'=>'3 años','breed'=>'Mestiza','image'=>'https://media.tiempodesanjuan.com/p/0dc31e47d653e910aefbf77ce1e48bfc/adjuntos/331/imagenes/000/697/0000697847/790x0/smart/luna-perrita-callejerajpg.jpg','description'=>'Tratamiento veterinario y medicinas necesarias.','raised'=>3200,'target'=>10000,'city'=>'Ciudad'],
	    ['name'=>'Simba','age'=>'4 años','breed'=>'Mestiza','image'=>'https://tse2.mm.bing.net/th/id/OIP._DapDIXPIH0PLwLFNs0Y8AHaEc?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3','description'=>'Tratamiento veterinario y medicinas necesarias.','raised'=>3200,'target'=>10000,'city'=>'Ciudad'],

	];
}
if (!isset($gatos) || !is_array($gatos)) {
	$gatos = [
		['name'=>'Misu','age'=>'2 años','breed'=>'Europeo','image'=>'assets/img/pets/misu.jpg','description'=>'Rescatada de la calle, necesita tratamiento.','raised'=>300,'target'=>1500,'city'=>'Ciudad'],
        ['name'=>'Luna','age'=>'1 año','breed'=>'Siames','image'=>'assets/img/pets/luna.jpg','description'=>'Vacunación y desparasitación.','raised'=>600,'target'=>2000,'city'=>'Localidad'],

	];
}

// Leer parámetros
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'perro';
$name = isset($_GET['name']) ? urldecode($_GET['name']) : '';

// Buscar animal
$animal = null;
$list = ($tipo === 'gato') ? $gatos : $perros;
foreach ($list as $item) {
	if (strcasecmp($item['name'], $name) === 0) { $animal = $item; break; }
}
if (!$animal) { $animal = $list[0]; }

$percent = ($animal['target'] > 0) ? round(($animal['raised'] / $animal['target']) * 100) : 0;
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Detalle - <?php echo htmlspecialchars($animal['name']); ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		body{background:#f4f6f8}
		.pet-main-img{width:100%;height:360px;object-fit:cover;border-radius:8px}
		.thumb{height:72px;object-fit:cover;border-radius:6px;cursor:pointer}
		.donate-amount .btn{min-width:70px}
		.progress{height:10px;border-radius:8px}
		.hero-section{background:linear-gradient(135deg, rgba(0,0,0,0.5), rgba(75,123,236,0.5)), url('<?php echo htmlspecialchars($animal['image']); ?>') center/cover no-repeat;min-height:280px;color:#fff;display:flex;align-items:center;justify-content:center;border-radius:12px;margin-bottom:30px}
		.hero-section h1{text-shadow:0 2px 8px rgba(0,0,0,0.4);font-weight:700}
		/* Estilos del chatbot */
		.chat-widget { position:fixed; bottom:20px; right:20px; width:360px; height:500px; background:#fff; border-radius:12px; box-shadow:0 5px 40px rgba(0,0,0,0.16); display:flex; flex-direction:column; z-index:9999; }
		.chat-header { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:#fff; padding:16px; border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center; }
		.chat-body { flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:8px; }
		.chat-msg { padding:8px 12px; border-radius:8px; max-width:85%; word-wrap:break-word; }
		.chat-msg.user { background:#667eea; color:#fff; align-self:flex-end; }
		.chat-msg.bot { background:#f0f0f0; color:#333; align-self:flex-start; }
		.chat-input { padding:12px; border-top:1px solid #eee; display:flex; gap:8px; }
		.chat-input input { flex:1; border:1px solid #ddd; border-radius:8px; padding:8px 12px; }
		.chat-input button { background:#667eea; color:#fff; border:none; border-radius:8px; padding:8px 16px; cursor:pointer; }
		.chat-close { background:none; border:none; color:#fff; font-size:18px; cursor:pointer; }
		@media(max-width:600px){ .chat-widget { width:100%; height:100%; bottom:0; right:0; border-radius:0; } }
	</style>
</head>
<body>
	<div class="hero-section">
		<div class="text-center">
			<h1 class="display-5"><?php echo htmlspecialchars($animal['name']); ?></h1>
			<p class="lead">Ayuda a <?php echo htmlspecialchars($animal['name']); ?> a recuperarse</p>
		</div>
	</div>

	<div class="container my-4">
		<div class="row g-4">
			<div class="col-lg-8">
				<div class="card p-3">
					<img id="mainImg" src="<?php echo htmlspecialchars($animal['image']); ?>" class="pet-main-img mb-3" alt="">
					<div class="d-flex gap-2">
						<img src="<?php echo htmlspecialchars($animal['image']); ?>" class="thumb" onclick="document.getElementById('mainImg').src=this.src">
					</div>

					<div class="mt-3">
						<h2 class="mb-1"><?php echo htmlspecialchars($animal['name']); ?></h2>
						<small class="text-muted"><?php echo htmlspecialchars($animal['breed']); ?> • <?php echo htmlspecialchars($animal['age']); ?> • <?php echo htmlspecialchars($animal['city']); ?></small>
						<p class="mt-3"><?php echo nl2br(htmlspecialchars($animal['description'])); ?></p>

						<h5 class="mt-4">Mi historia</h5>
						<p class="text-muted">Aquí puedes describir la historia completa del animal...</p>
					</div>
				</div>
			</div>

			<div class="col-lg-4">
				<div class="card p-3 shadow-sm h-100">
					<div class="d-flex justify-content-between align-items-start mb-3">
						<div>
							<small class="text-muted">Apoya a</small>
							<h5 class="mb-0"><?php echo htmlspecialchars($animal['name']); ?></h5>
						</div>
						<span class="badge bg-secondary"><?php echo ($tipo === 'gato') ? 'Gato' : 'Perro'; ?></span>
					</div>

					<div class="mt-3">
						<div class="d-flex justify-content-between">
							<strong>$<?php echo number_format($animal['raised'],0,',','.'); ?></strong>
							<small class="text-muted"><?php echo $percent; ?>%</small>
						</div>
						<div class="progress mt-2">
							<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%"></div>
						</div>
						<small class="text-muted">Meta: $<?php echo number_format($animal['target'],0,',','.'); ?></small>
					</div>

					<div class="mt-3 donate-amount">
						<form id="donForm" action="recibo" method="post" onsubmit="return validateDonation();">
							<div class="mb-2 d-flex gap-2">
								<button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('donation').value='50'">$50</button>
								<button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('donation').value='100'">$100</button>
								<button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('donation').value='200'">$200</button>
							</div>
							<div class="mb-2">
								<input id="donation" name="amount" class="form-control" placeholder="Otro monto" inputmode="decimal" />
							</div>
							<input type="hidden" name="animal_name" value="<?php echo htmlspecialchars($animal['name']); ?>">
							<input type="hidden" name="animal_image" value="<?php echo htmlspecialchars($animal['image']); ?>">
							<input type="hidden" name="tipo" value="<?php echo ($tipo === 'gato') ? 'gato' : 'perro'; ?>">
							<div class="d-grid">
								<button type="submit" class="btn btn-success"><i class="fas fa-donate me-1"></i> Donar ahora</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	</script>
</body>
</html>
