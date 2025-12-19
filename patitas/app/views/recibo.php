<?php
require_once __DIR__ . '../../config/conexion.php';
require_once __DIR__ . '/recibo.php'; 

// Obtener y sanitizar datos (POST preferido)
$animal_name = isset($_POST['animal_name']) ? trim($_POST['animal_name']) : (isset($_GET['animal_name']) ? trim($_GET['animal_name']) : 'Amigo');
$animal_image = isset($_POST['animal_image']) ? trim($_POST['animal_image']) : (isset($_GET['animal_image']) ? trim($_GET['animal_image']) : 'assets/img/pets/default.jpg');

$amount_raw = '';
if (isset($_POST['amount'])) $amount_raw = trim($_POST['amount']);
elseif (isset($_GET['amount'])) $amount_raw = trim($_GET['amount']);

// Normalizar y parsear monto
// quitar todo lo que no sea dígito, punto o coma o signo
$amt = preg_replace('/[^\d\.\,\-]/u', '', $amount_raw);
$amt = str_replace(' ', '', $amt);

// Si contiene ambos separadores, asumimos: puntos = miles (eliminamos) y coma = decimal
if (strpos($amt, '.') !== false && strpos($amt, ',') !== false) {
	$amt = str_replace('.', '', $amt);
	$amt = str_replace(',', '.', $amt);
} else {
	// si sólo coma -> tratar coma como decimal
	$amt = str_replace(',', '.', $amt);
}

// Finalmente convertir a float
$amount = is_numeric($amt) ? floatval($amt) : 0.0;
$formatted = '$' . number_format($amount, 2, ',', '.');

$tx_id = strtoupper(substr(sha1(uniqid('', true)), 0, 10));
$date = date('d/m/Y H:i');
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Gracias por tu donación</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		body{background:#f4f6f8;font-family:Arial,Helvetica,sans-serif}
		.container-card{max-width:840px;margin:36px auto}
		.card-confirm{border-radius:12px;padding:30px}
		.round-img{width:96px;height:96px;object-fit:cover;border-radius:999px;border:6px solid #fff;margin:-48px auto 8px;display:block;box-shadow:0 6px 24px rgba(0,0,0,0.08)}
		.badge-ok{width:72px;height:72px;border-radius:50%;background:#e6fbf0;color:#0f9d58;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 12px}
		.small-muted{color:#6c757d;font-size:0.95rem}
		.box{background:#fff;border-radius:10px;padding:18px}
		.detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f1f1}
	</style>
</head>
<body>
	<div class="container container-card">
		<div class="card box card-confirm shadow-sm">
			<div class="text-center">
				<div class="badge-ok">✓</div>
				<h3 class="fw-bold">¡Gracias por tu donación!</h3>
				<p class="small-muted mb-4">Tu generosidad hace una gran diferencia. Hemos enviado un comprobante a tu correo (si corresponde).</p>

				<img src="<?php echo htmlspecialchars($animal_image); ?>" alt="<?php echo htmlspecialchars($animal_name); ?>" class="round-img">
				<h5 class="mt-2 mb-0"><?php echo htmlspecialchars($animal_name); ?></h5>
				<p class="small-muted mb-3">Tu donación de <strong><?php echo $formatted; ?></strong> ayudará directamente.</p>

				<div class="mx-auto" style="max-width:680px">
					<div class="box mt-3">
						<div class="detail-row">
							<span class="small-muted">Concepto</span>
							<span><?php echo htmlspecialchars($animal_name); ?></span>
						</div>
						<div class="detail-row">
							<span class="small-muted">Monto</span>
							<span><?php echo $formatted; ?></span>
						</div>
						<div class="detail-row">
							<span class="small-muted">Fecha</span>
							<span><?php echo $date; ?></span>
						</div>
						<div class="detail-row">
							<span class="small-muted">ID transacción</span>
							<span><?php echo $tx_id; ?></span>
						</div>
						<div class="pt-3 text-center">
							<a href="donaciones" class="btn btn-primary">Volver al inicio</a>
						</div>
					</div>
				</div>

			</div>
		</div>

		<div class="text-center mt-4 small-muted">
			¿Quieres seguir ayudando? Comparte esta campaña con amigos.
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
