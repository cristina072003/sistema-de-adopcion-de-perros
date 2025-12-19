<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../menu.php'; // ajustar según tu menú/header

// Intentar obtener solicitudes reales; si falla, usar ejemplos
$solicitudes = [];
$query = "SELECT ad.id_adopcion, a.nombre AS animal, a.especie, u.nombre AS adoptante, u.telefono, ad.fecha_solicitud, ad.estado
          FROM adopciones ad
          LEFT JOIN animales a ON ad.id_animal = a.id_animal
          LEFT JOIN usuarios u ON ad.id_usuario = u.id_usuario
          ORDER BY ad.fecha_solicitud DESC
          LIMIT 200";
$res = $conexion->query($query);
if ($res) {
    while ($r = $res->fetch_assoc()) $solicitudes[] = $r;
} else {
    // ejemplos
    $solicitudes = [
        ['id_adopcion'=>1,'animal'=>'Canela','especie'=>'Perro','adoptante'=>'Juan Pérez Mamani','telefono'=>'70123456','fecha_solicitud'=>'2025-12-19','estado'=>'Pendiente'],
    ];
}
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Gestión de Adopciones</h3>
        <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">↻</button>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <select id="filterEstado" class="form-select" onchange="filterTable()">
                <option value="">Todos los estados</option>
                <option value="Pendiente">Pendiente</option>
                <option value="Aprobado">Aprobado</option>
                <option value="Rechazado">Rechazado</option>
            </select>
        </div>
        <div class="col-md-4">
            <input id="searchInput" type="text" class="form-control" placeholder="Buscar por animal o adoptante..." onkeyup="filterTable()">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover" id="tablaSolicitudes">
            <thead>
                <tr>
                    <th>Animal</th>
                    <th>Nombre</th>
                    <th>Adoptante</th>
                    <th>Fecha Solicitud</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitudes as $s): ?>
                <tr data-estado="<?= htmlspecialchars($s['estado']) ?>">
                    <td style="width:80px">
                        <div class="d-flex align-items-center">
                            <img src="<?= isset($s['animal_img']) ? htmlspecialchars($s['animal_img']) : 'assets/img/pets/default.jpg' ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;margin-right:8px">
                            <div>
                                <strong><?= htmlspecialchars($s['animal']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($s['especie'] ?? '') ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($s['animal']) ?></td>
                    <td>
                        <?= htmlspecialchars($s['adoptante']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($s['telefono'] ?? '') ?></small>
                    </td>
                    <td><?= htmlspecialchars($s['fecha_solicitud']) ?></td>
                    <td class="estado-cell">
                        <?php if (strtolower($s['estado']) == 'pendiente'): ?>
                            <span class="badge bg-warning text-dark">Pendiente</span>
                        <?php elseif (strtolower($s['estado']) == 'aprobado'): ?>
                            <span class="badge bg-success">Aprobado</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Rechazado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-info" onclick="verDetalle(<?= (int)$s['id_adopcion'] ?>)">Ver</button>
                            <button class="btn btn-sm btn-success" onclick="accionSolicitud(<?= (int)$s['id_adopcion'] ?>,'aprobar', this)">✓</button>
                            <button class="btn btn-sm btn-danger" onclick="accionSolicitud(<?= (int)$s['id_adopcion'] ?>,'rechazar', this)">✕</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="generarPDF(<?= (int)$s['id_adopcion'] ?>)">PDF</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal simple para mostrar detalles -->
<div class="modal fade" id="detalleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle de solicitud</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="detalleBody">Cargando...</div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function filterTable(){
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    const estado = document.getElementById('filterEstado').value;
    document.querySelectorAll('#tablaSolicitudes tbody tr').forEach(tr=>{
        const txt = tr.textContent.toLowerCase();
        const est = tr.getAttribute('data-estado') || '';
        const match = (q === '' || txt.includes(q)) && (estado === '' || est === estado);
        tr.style.display = match ? '' : 'none';
    });
}

function verDetalle(id){
    // Simple: abrir modal y cargar via fetch endpoint opcional
    const modalBody = document.getElementById('detalleBody');
    modalBody.innerHTML = 'Cargando...';
    // intentar cargar detalle real
    fetch('../../controllers/admin/detalle_solicitud.php?id=' + encodeURIComponent(id))
    .then(r=>r.ok? r.text() : Promise.reject('error'))
    .then(html=>{ modalBody.innerHTML = html; var m=new bootstrap.Modal(document.getElementById('detalleModal')); m.show(); })
    .catch(()=>{ modalBody.innerHTML = 'No se pudo cargar el detalle.'; var m=new bootstrap.Modal(document.getElementById('detalleModal')); m.show(); });
}

async function accionSolicitud(id, accion, btn){
    if(!confirm((accion === 'aprobar' ? 'Confirmar aprobación?' : 'Confirmar rechazo?'))) return;
    let motivo = '';
    if(accion === 'rechazar'){
        motivo = prompt('Motivo del rechazo (opcional):','');
        if(motivo === null) return;
    }
    btn.disabled = true;
    try {
        const form = new FormData();
        form.append('id', id);
        form.append('accion', accion);
        form.append('motivo', motivo);
        const res = await fetch('../../controllers/admin/action_adopcion.php', { method:'POST', body: form });
        const json = await res.json();
        if(json.success){
            // actualizar fila
            const tr = btn.closest('tr');
            const cell = tr.querySelector('.estado-cell');
            if(json.estado === 'Aprobado') cell.innerHTML = '<span class="badge bg-success">Aprobado</span>';
            else cell.innerHTML = '<span class="badge bg-danger">Rechazado</span>';
            tr.setAttribute('data-estado', json.estado);
            alert('Operación exitosa');
        } else {
            alert('Error: ' + (json.message || 'no se pudo procesar'));
        }
    } catch(e){
        alert('Error en la petición');
    } finally { btn.disabled = false; }
}

function generarPDF(id){
    window.open('../../controllers/admin/print_solicitud.php?id=' + encodeURIComponent(id), '_blank');
}
</script>
