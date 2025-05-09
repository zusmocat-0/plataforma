<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit();
}

// Verificar que se haya proporcionado un ID de materia
if (!isset($_GET['id'])) {
    header("Location: cursos_estudiante.php");
    exit();
}

require_once '../conexion.php';

$idMateria = $_GET['id'];
$idAlumno = $_SESSION['usuario']['id'];

// Obtener información de la materia
$queryMateria = "SELECT m.Nombre, m.Descripcionmateria, m.NumeroUnidades, m.Unidades
                    FROM materia m
                    WHERE m.idMateria = ?";
$stmtMateria = $conexion->prepare($queryMateria);
$stmtMateria->bind_param("s", $idMateria);
$stmtMateria->execute();
$resultMateria = $stmtMateria->get_result();

if ($resultMateria->num_rows === 0) {
    header("Location: cursos_estudiante.php");
    exit();
}

$materia = $resultMateria->fetch_assoc();
$unidades = json_decode($materia['Unidades'], true) ?? [];

// Obtener profesor que imparte la materia
$queryProfesor = "SELECT d.Nombre
                     FROM docente d
                     WHERE FIND_IN_SET(?, d.Cursos)
                     LIMIT 1";
$stmtProfesor = $conexion->prepare($queryProfesor);
$stmtProfesor->bind_param("s", $idMateria);
$stmtProfesor->execute();
$resultProfesor = $stmtProfesor->get_result();

$profesor = 'Sin asignar';
if ($profesorRow = $resultProfesor->fetch_assoc()) {
    $profesor = $profesorRow['Nombre'];
}

// Modificación: Consulta para obtener calificaciones de actividades
$queryActividades = "SELECT
                                    a.idActividades,
                                    a.TituloActividades,
                                    a.Descripcion,
                                    a.ArchivoAdjunto,
                                    a.fecha_inicio,
                                    a.fecha_fin,
                                    a.unidad,
                                    a.calificacion_maxima,
                                    a.calificacion,
                                    a.Entregas,
                                    a.archivo_entregas
                                FROM actividades a
                                WHERE a.idcurso = ?
                                ORDER BY a.unidad, a.fecha_inicio";
$stmtActividades = $conexion->prepare($queryActividades);
$stmtActividades->bind_param("s", $idMateria);
$stmtActividades->execute();
$resultActividades = $stmtActividades->get_result();

// Organizar actividades por unidad
$actividadesPorUnidad = [];
while ($actividad = $resultActividades->fetch_assoc()) {
    $unidad = $actividad['unidad'];
    if (!isset($actividadesPorUnidad[$unidad])) {
        $actividadesPorUnidad[$unidad] = [];
    }

    // Modificación: Obtener calificación del alumno para esta actividad
    $calificacionAlumno = 'No calificado';

    // Verificar si el alumno entregó esta actividad
    $entregas = $actividad['Entregas'] ? explode(",", $actividad['Entregas']) : [];
    $calificaciones = $actividad['calificacion'] ? explode(",", $actividad['calificacion']) : [];

    $indice = array_search($idAlumno, $entregas);
    if ($indice !== false && isset($calificaciones[$indice])) {
        // Si la calificación es una fecha (formato antiguo), mostrar "Entregado"
        if (DateTime::createFromFormat('Y-m-d H:i:s', $calificaciones[$indice]) !== false) {
            $calificacionAlumno = 'Entregado';
        } else {
            $calificacionAlumno = $calificaciones[$indice] . '/' . $actividad['calificacion_maxima'];
        }
    }

    // Agregar calificación al array de actividad
    $actividad['calificacion_alumno'] = $calificacionAlumno;
    $actividadesPorUnidad[$unidad][] = $actividad;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - <?php echo htmlspecialchars($materia['Nombre']); ?></title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .unit-card {
            margin-bottom: 15px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            background-color: var(--card-bg);
        }

        .unit-header {
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .unit-content {
            padding: 15px;
            background-color: var(--card-bg);
        }

        .activities-list {
            margin-top: 15px;
        }

        .activity-item {
            padding: 15px;
            margin-bottom: 10px;
            background-color: var(--light-bg);
            border-radius: 5px;
            border-left: 4px solid var(--accent-color);
        }

        .activity-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .activity-meta {
            font-size: 0.9em;
            color: var(--text-muted);
            margin-bottom: 5px;
        }

        .activity-actions {
            margin-top: 10px;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-expired {
            background-color: #dc3545;
            color: #fff;
        }
        .calificacion-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
            color: white;
        }
        .calificacion-aprobatoria {
            background-color: #28a745;
        }
        .calificacion-reprobatoria {
            background-color: #dc3545;
        }
        .calificacion-neutral {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
        <i class="fas fa-moon"></i>
    </button>

    <div class="sidebar">
        <ul class="sidebar-menu">
        <li><a href="inicio_estudiante.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="cursos_estudiante.php"><i class="fas fa-book"></i> <span>Cursos</span></a></li>
            <li><a href="calendario_estudiante.php"><i class="fas fa-calendar-alt"></i> <span>Calendario</span></a></li>
            <li><a href="avance_reticular.php"><i class="fas fa-tasks"></i> <span>Avance Reticular</span></a></li>
            <li><a href="foro_estudiante.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="servicios_estudiante.php"><i class="fas fa-school"></i> <span>Servicios Escolares</span></a></li>
            <li><a href="info_estudiante.php"><i class="fas fa-user"></i> <span>Información personal</span></a></li>
            <li><a href="\logout.php"><i class="fas fa-right-from-bracket"></i> <span>Cerrar sesión</span></a></li>
        </ul>
    </div>

    <div class="header">
        <h1><?php echo htmlspecialchars($materia['Nombre']); ?></h1>
        <p><?php echo htmlspecialchars($materia['Descripcionmateria']); ?></p>
        <p><strong>Profesor:</strong> <?php echo htmlspecialchars($profesor); ?></p>
    </div>

    <div class="main-content">
        <div class="dashboard-card">
            <h3>Unidades del Curso</h3>

            <?php if (!empty($unidades)): ?>
                <div class="units-container">
                    <?php foreach ($unidades as $index => $unidad): ?>
                        <?php
                        $unidadNumero = $index + 1;
                        $actividadesUnidad = $actividadesPorUnidad[$unidadNumero] ?? [];
                        ?>
                        <div class="unit-card">
                            <div class="unit-header">
                                <h4>Unidad <?php echo $unidadNumero; ?>: <?php echo htmlspecialchars($unidad['nombre']); ?></h4>
                            </div>
                            <div class="unit-content">
                                <div class="activities-list">
                                    <?php if (empty($actividadesUnidad)): ?>
                                        <div class="empty-message">
                                            <p>No hay actividades asignadas para esta unidad.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($actividadesUnidad as $actividad): ?>
                                            <?php
                                            // DEBUG: Mostrar fechas originales
                                            // echo "Fecha inicio (DB): ".$actividad['fecha_inicio']."<br>";
                                            // echo "Fecha fin (DB): ".$actividad['fecha_fin']."<br>";

                                            // Convertir fechas de la base de datos a formato DateTime
                                            try {
                                                $fechaInicio = DateTime::createFromFormat('Y-m-d H:i:s', $actividad['fecha_inicio']);
                                                $fechaFin = DateTime::createFromFormat('Y-m-d H:i:s', $actividad['fecha_fin']);

                                                if (!$fechaInicio || !$fechaFin) {
                                                    throw new Exception("Formato de fecha inválido");
                                                }

                                                $hoy = new DateTime();

                                                // Determinar el estado
                                                if ($hoy < $fechaInicio) {
                                                    $estado = '<span class="status-badge status-upcoming">Próxima</span>';
                                                    $mostrarBoton = false;
                                                } elseif ($hoy >= $fechaInicio && $hoy <= $fechaFin) {
                                                    $estado = '<span class="status-badge status-active">Activa</span>';
                                                    $mostrarBoton = true;
                                                } else {
                                                    $estado = '<span class="status-badge status-expired">Vencida</span>';
                                                    $mostrarBoton = false;
                                                }
                                            } catch (Exception $e) {
                                                // En caso de error en el formato de fecha
                                                $estado = '<span class="status-badge status-expired">Error en fecha</span>';
                                                $mostrarBoton = false;
                                                $fechaInicio = new DateTime($actividad['fecha_inicio']);
                                                $fechaFin = new DateTime($actividad['fecha_fin']);
                                            }
                                            ?>
                                            <div class="activity-item">
                                                <div class="activity-title">
                                                    <?php echo htmlspecialchars($actividad['TituloActividades']); ?>
                                                    <?php echo $estado; ?>

                                                    <?php if ($actividad['calificacion_alumno'] !== 'No calificado'): ?>
                                                        <?php
                                                        $claseCalificacion = 'calificacion-neutral';
                                                        if ($actividad['calificacion_alumno'] !== 'Entregado') {
                                                            list($puntos, $max) = explode('/', $actividad['calificacion_alumno']);
                                                            $porcentaje = ($puntos / $max) * 100;
                                                            $claseCalificacion = ($porcentaje >= 60) ? 'calificacion-aprobatoria' : 'calificacion-reprobatoria';
                                                        } elseif ($actividad['calificacion_alumno'] === 'Entregado') {
                                                            $claseCalificacion = 'calificacion-neutral'; // Opcional: otra clase para "Entregado"
                                                        }
                                                        ?>
                                                        <span class="calificacion-badge <?php echo $claseCalificacion; ?>">
                                                            <?php echo htmlspecialchars($actividad['calificacion_alumno']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="activity-description">
                                                    <?php echo nl2br(htmlspecialchars($actividad['Descripcion'])); ?>
                                                </div>

                                                <div class="activity-meta">
                                                    <span><i class="far fa-calendar-alt"></i> Inicio: <?php echo $fechaInicio->format('d/m/Y H:i'); ?></span>
                                                    <span><i class="far fa-calendar-check"></i> Fin: <?php echo $fechaFin->format('d/m/Y H:i'); ?></span>
                                                    <span><i class="fas fa-star"></i> Máx: <?php echo $actividad['calificacion_maxima']; ?> pts</span>
                                                </div>

                                                <?php if ($actividad['ArchivoAdjunto']): ?>
                                                    <div class="activity-attachment">
                                                        <i class="fas fa-paperclip"></i>
                                                        <a href="<?php echo $actividad['ArchivoAdjunto']; ?>" target="_blank">Material de apoyo</a>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="activity-actions">
                                                    <?php if ($mostrarBoton): ?>
                                                        <a href="entregar_actividad.php?id=<?php echo $actividad['idActividades']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-upload"></i> Entregar
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Este curso no tiene unidades definidas.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>