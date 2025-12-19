<?php
require_once __DIR__ . '../../../config/conexion.php';
require_once __DIR__ . '../../../config/checkSessionUsuario.php';
require_once __DIR__ . '../../../views/headerA.php';
// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_reporte = $_POST['tipo_reporte'];
    $fecha_inicio = $_POST['fecha_inicio'] ?? '';
    $fecha_fin = $_POST['fecha_fin'] ?? '';
    $formato = $_POST['formato'] ?? 'pdf';

    // Validar fechas
    if (!empty($fecha_inicio) && !empty($fecha_fin) && strtotime($fecha_fin) < strtotime($fecha_inicio)) {
        $_SESSION['mensaje_error'] = "La fecha final no puede ser anterior a la fecha inicial";
        header("Location: generarReporte.php");
        exit();
    }

    // Generar el reporte según el tipo seleccionado
    switch ($tipo_reporte) {
        case 'animales':
            generarReporteAnimales($conexion, $fecha_inicio, $fecha_fin, $formato);
            break;
        case 'adopciones':
            generarReporteAdopciones($conexion, $fecha_inicio, $fecha_fin, $formato);
            break;
        case 'usuarios':
            generarReporteUsuarios($conexion, $fecha_inicio, $fecha_fin, $formato);
            break;
        default:
            $_SESSION['mensaje_error'] = "Tipo de reporte no válido";
            header("Location: generarReporte.php");
            exit();
    }
}

function generarReporteAnimales($conexion, $fecha_inicio, $fecha_fin, $formato)
{
    $sql = "SELECT 
                a.id_animal, a.nombre, a.especie, r.nombre_raza, 
                a.edad_anios, a.sexo, a.tamanio, a.estado,
                a.fecha_ingreso, a.esterilizado, a.vacunado,
                d.nombre as departamento, d.provincia
            FROM animales a
            LEFT JOIN razas r ON a.id_raza = r.id_raza
            LEFT JOIN animal_departamento ad ON a.id_animal = ad.id_animal
            LEFT JOIN departamentos d ON ad.id_departamento = d.id_departamento
            WHERE a.activo = 1";

    if (!empty($fecha_inicio)) {
        $sql .= " AND a.fecha_ingreso >= '$fecha_inicio'";
    }

    if (!empty($fecha_fin)) {
        $sql .= " AND a.fecha_ingreso <= '$fecha_fin'";
    }

    $sql .= " ORDER BY a.fecha_ingreso DESC";

    $animales = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

    if ($formato === 'pdf') {
        exportarAPDF('Reporte de Animales', $animales, [
            'ID',
            'Nombre',
            'Especie',
            'Raza',
            'Edad',
            'Sexo',
            'Tamaño',
            'Estado',
            'Fecha Ingreso',
            'Esterilizado',
            'Vacunado',
            'Departamento',
            'Provincia'
        ]);
    } else {
        exportarAExcel('Reporte de Animales', $animales, [
            'id_animal',
            'nombre',
            'especie',
            'nombre_raza',
            'edad_anios',
            'sexo',
            'tamanio',
            'estado',
            'fecha_ingreso',
            'esterilizado',
            'vacunado',
            'departamento',
            'provincia'
        ]);
    }
}

function generarReporteAdopciones($conexion, $fecha_inicio, $fecha_fin, $formato)
{
    $sql = "SELECT 
                sa.id_solicitud, a.nombre as animal, a.especie,
                ad.nombre_completo as adoptante, ad.telefono,
                sa.fecha_solicitud, sa.fecha_respuesta, sa.estado,
                sa.motivo_adopcion, sa.tipo_vivienda
            FROM solicitudes_adopcion sa
            JOIN animales a ON sa.id_animal = a.id_animal
            JOIN adoptantes ad ON sa.id_adoptante = ad.id_adoptante";

    $where = [];
    if (!empty($fecha_inicio)) {
        $where[] = "sa.fecha_solicitud >= '$fecha_inicio'";
    }

    if (!empty($fecha_fin)) {
        $where[] = "sa.fecha_solicitud <= '$fecha_fin'";
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY sa.fecha_solicitud DESC";

    $adopciones = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

    if ($formato === 'pdf') {
        exportarAPDF('Reporte de Adopciones', $adopciones, [
            'ID',
            'Animal',
            'Especie',
            'Adoptante',
            'Teléfono',
            'Fecha Solicitud',
            'Fecha Respuesta',
            'Estado',
            'Motivo Adopción',
            'Tipo Vivienda'
        ]);
    } else {
        exportarAExcel('Reporte de Adopciones', $adopciones, [
            'id_solicitud',
            'animal',
            'especie',
            'adoptante',
            'telefono',
            'fecha_solicitud',
            'fecha_respuesta',
            'estado',
            'motivo_adopcion',
            'tipo_vivienda'
        ]);
    }
}

function generarReporteUsuarios($conexion, $fecha_inicio, $fecha_fin, $formato)
{
    $sql = "SELECT 
                u.id_usuario, u.correo, u.rol, u.fecha_registro,
                a.nombre_completo, a.telefono, a.documento_identidad
            FROM usuarios u
            LEFT JOIN adoptantes a ON u.id_usuario = a.id_usuario
            WHERE 1=1";

    if (!empty($fecha_inicio)) {
        $sql .= " AND u.fecha_registro >= '$fecha_inicio'";
    }

    if (!empty($fecha_fin)) {
        $sql .= " AND u.fecha_registro <= '$fecha_fin'";
    }

    $sql .= " ORDER BY u.fecha_registro DESC";

    $usuarios = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

    if ($formato === 'pdf') {
        exportarAPDF('Reporte de Usuarios', $usuarios, [
            'ID',
            'Correo',
            'Rol',
            'Fecha Registro',
            'Nombre Completo',
            'Teléfono',
            'Documento'
        ]);
    } else {
        exportarAExcel('Reporte de Usuarios', $usuarios, [
            'id_usuario',
            'correo',
            'rol',
            'fecha_registro',
            'nombre_completo',
            'telefono',
            'documento_identidad'
        ]);
    }
}

function exportarAPDF($titulo, $datos, $cabeceras)
{
    // Simulación de PDF: realmente es HTML, pero con cabecera para navegador
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="reporte.html"');
    echo "<h1>$titulo</h1>";
    echo "<table border='1'><tr>";
    foreach ($cabeceras as $cabecera) {
        echo "<th>$cabecera</th>";
    }
    echo "</tr>";
    foreach ($datos as $fila) {
        echo "<tr>";
        foreach ($fila as $valor) {
            echo "<td>" . htmlspecialchars($valor) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    exit();
}

function exportarAExcel($titulo, $datos, $campos)
{
    // Exportar como CSV para máxima compatibilidad con Excel
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte.csv"');
    $output = fopen('php://output', 'w');
    // Escribir título
    fputcsv($output, [$titulo]);
    // Escribir cabeceras
    $cabeceras = array_map(function ($campo) {
        return ucfirst(str_replace('_', ' ', $campo));
    }, $campos);
    fputcsv($output, $cabeceras);
    // Escribir datos
    foreach ($datos as $fila) {
        $row = [];
        foreach ($campos as $campo) {
            $row[] = $fila[$campo] ?? '';
        }
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Reporte - Refugio de Mascotas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4B7BEC;
            --secondary: #6C757D;
            --success: #28A745;
            --info: #17A2B8;
            --warning: #FFC107;
            --danger: #DC3545;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }

        .card-reporte {
            border: none;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 15px 15px 0 0 !important;
        }

        .form-title {
            color: var(--primary);
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #3A6BD9;
            border-color: #3A6BD9;
        }

        .required-field::after {
            content: "*";
            color: var(--danger);
            margin-left: 4px;
        }

        .report-type-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            border-radius: 10px;
        }

        .report-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .report-type-card.selected {
            border-color: var(--primary);
            background-color: rgba(75, 123, 236, 0.05);
        }

        .report-type-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--primary);
        }
    </style>
</head>

<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-reporte">
                    <div class="card-header py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="form-title mb-0">
                                <i class="fas fa-file-alt me-2"></i> Generar Reporte
                            </h3>
                            <a href="http://localhost/PATITAS/app/views/dashboardAdministrador.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-home me-2"></i> Inicio
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['mensaje_error'])): ?>
                            <div class="alert alert-danger">
                                <?= $_SESSION['mensaje_error'] ?>
                                <?php unset($_SESSION['mensaje_error']); ?>
                            </div>
                        <?php endif; ?>

                        <form action="generarReporte.php" method="post">
                            <!-- Selección del tipo de reporte -->
                            <div class="mb-4">
                                <h5 class="mb-3 text-primary">Tipo de Reporte</h5>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="report-type-card p-3 text-center" onclick="selectReportType('animales')" id="cardAnimales">
                                            <i class="fas fa-paw"></i>
                                            <h6>Animales</h6>
                                            <small class="text-muted">Listado de animales registrados</small>
                                            <input type="radio" name="tipo_reporte" value="animales" id="animales" class="d-none" checked>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="report-type-card p-3 text-center" onclick="selectReportType('adopciones')" id="cardAdopciones">
                                            <i class="fas fa-home"></i>
                                            <h6>Adopciones</h6>
                                            <small class="text-muted">Solicitudes y adopciones</small>
                                            <input type="radio" name="tipo_reporte" value="adopciones" id="adopciones" class="d-none">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="report-type-card p-3 text-center" onclick="selectReportType('usuarios')" id="cardUsuarios">
                                            <i class="fas fa-users"></i>
                                            <h6>Usuarios</h6>
                                            <small class="text-muted">Usuarios del sistema</small>
                                            <input type="radio" name="tipo_reporte" value="usuarios" id="usuarios" class="d-none">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtros por fecha -->
                            <div class="mb-4">
                                <h5 class="mb-3 text-primary">Filtrar por Fecha</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="fecha_inicio" class="form-label">Fecha Inicial</label>
                                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fecha_fin" class="form-label">Fecha Final</label>
                                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                                    </div>
                                </div>
                            </div>

                            <!-- Formato del reporte -->
                            <div class="mb-4">
                                <h5 class="mb-3 text-primary">Formato del Reporte</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="formato" id="formatoPdf" value="pdf" checked>
                                            <label class="form-check-label" for="formatoPdf">
                                                <i class="fas fa-file-pdf text-danger me-2"></i> PDF
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="formato" id="formatoExcel" value="excel">
                                            <label class="form-check-label" for="formatoExcel">
                                                <i class="fas fa-file-excel text-success me-2"></i> Excel
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <button type="reset" class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-undo me-2"></i> Limpiar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i> Generar Reporte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectReportType(type) {
            // Deseleccionar todas las tarjetas
            document.querySelectorAll('.report-type-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Seleccionar la tarjeta clickeada
            document.getElementById(`card${type.charAt(0).toUpperCase() + type.slice(1)}`).classList.add('selected');

            // Marcar el radio button correspondiente
            document.getElementById(type).checked = true;
        }

        // Seleccionar el primer tipo por defecto
        document.addEventListener('DOMContentLoaded', function() {
            selectReportType('animales');

            // Establecer fecha máxima como hoy para fecha_fin
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('fecha_fin').max = today;

            // Cuando se selecciona fecha_inicio, establecer min de fecha_fin
            document.getElementById('fecha_inicio').addEventListener('change', function() {
                document.getElementById('fecha_fin').min = this.value;
            });
        });
    </script>
</body>

</html>