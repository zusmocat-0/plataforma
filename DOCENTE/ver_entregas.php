<?php
session_start();

// Verificar sesión de docente
if (!isset($_SESSION['idDocente'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Obtener ID de la actividad desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: docente_grupos.php");
    exit();
}

$idActividad = $_GET['id'];

// Obtener información de la actividad
$queryActividad = "SELECT a.*, m.Nombre as nombre_materia 
                   FROM actividades a
                   JOIN materia m ON a.idcurso = m.idMateria
                   WHERE a.idActividades = ?";
$stmtActividad = $conexion->prepare($queryActividad);
$stmtActividad->bind_param("i", $idActividad);
$stmtActividad->execute();
$resultActividad = $stmtActividad->get_result();

if ($resultActividad->num_rows === 0) {
    header("Location: docente_grupos.php?error=Actividad no encontrada");
    exit();
}

$actividad = $resultActividad->fetch_assoc();

// Procesar calificación si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calificar'])) {
    $idAlumno = $_POST['id_alumno'];
    $calificacion = floatval($_POST['calificacion']);
    
    // Validar que la calificación no supere el máximo
    if ($calificacion > $actividad['calificacion_maxima']) {
        $error = "La calificación no puede ser mayor a " . $actividad['calificacion_maxima'];
    } else {
        // Actualizar la calificación en la tabla actividades
        // Primero obtenemos los datos actuales
        $entregas = $actividad['Entregas'] ? explode(",", $actividad['Entregas']) : [];
        $calificaciones = $actividad['calificacion'] ? explode(",", $actividad['calificacion']) : array_fill(0, count($entregas), '');
        
        $indice = array_search($idAlumno, $entregas);
        if ($indice !== false) {
            $calificaciones[$indice] = $calificacion;
            $calificacionesStr = implode(",", $calificaciones);
            
            $queryActualizar = "UPDATE actividades 
                              SET calificacion = ?
                              WHERE idActividades = ?";
            $stmtActualizar = $conexion->prepare($queryActualizar);
            $stmtActualizar->bind_param("si", $calificacionesStr, $idActividad);
            
            if ($stmtActualizar->execute()) {
                $success = "Calificación actualizada correctamente";
                // Actualizar la actividad para reflejar los cambios
                $actividad['calificacion'] = $calificacionesStr;
            } else {
                $error = "Error al actualizar la calificación: " . $conexion->error;
            }
        } else {
            $error = "No se encontró la entrega del alumno";
        }
    }
}

// Obtener todas las entregas para esta actividad
$entregas = $actividad['Entregas'] ? explode(",", $actividad['Entregas']) : [];
$archivos = $actividad['archivo_entregas'] ? explode(",", $actividad['archivo_entregas']) : [];
$calificaciones = $actividad['calificacion'] ? explode(",", $actividad['calificacion']) : array_fill(0, count($entregas), '');

// Obtener información de los alumnos que han entregado
$alumnosEntregas = [];
if (!empty($entregas)) {
    // Crear placeholders para la consulta IN
    $placeholders = implode(',', array_fill(0, count($entregas), '?'));
    
    // Consulta para obtener los alumnos
    $queryAlumnos = "SELECT idAlumno, Nombre 
                     FROM alumno 
                     WHERE idAlumno IN ($placeholders)";
    $stmtAlumnos = $conexion->prepare($queryAlumnos);
    
    // Vincular parámetros dinámicamente
    $types = str_repeat('s', count($entregas));
    $stmtAlumnos->bind_param($types, ...$entregas);
    $stmtAlumnos->execute();
    $resultAlumnos = $stmtAlumnos->get_result();
    
    while ($alumno = $resultAlumnos->fetch_assoc()) {
        $indice = array_search($alumno['idAlumno'], $entregas);
        $alumnosEntregas[] = [
            'id' => $alumno['idAlumno'],
            'nombre' => $alumno['Nombre'],
            'archivo' => $archivos[$indice] ?? null,
            'calificacion' => $calificaciones[$indice] ?? null
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Ver Entregas</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .deliveries-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .delivery-item {
            padding: 15px;
            margin-bottom: 15px;
            background-color: var(--bg-color);
            border-radius: 5px;
            border-left: 4px solid var(--accent-color);
        }
        
        .delivery-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .delivery-title {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .delivery-meta {
            font-size: 0.9em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        
        .delivery-actions {
            margin-top: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 10px;
            font-size: 0.9em;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .grade-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .grade-input {
            width: 80px;
            padding: 5px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .max-grade {
            color: var(--text-muted);
            font-size: 0.9em;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
        <i class="fas fa-moon"></i>
    </button>

    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="docente_inicio.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="docente_foro.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="docente_anuncios.php"><i class="fas fa-bullhorn"></i> <span>Anuncios</span></a></li>
            <li class="active"><a href="docente_grupos.php"><i class="fas fa-user-group"></i> <span>Grupos</span></a></li>
            <li><a href="/logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Entregas: <?php echo htmlspecialchars($actividad['TituloActividades']); ?></h1>
        <p><?php echo htmlspecialchars($actividad['nombre_materia']); ?></p>
    </div>
    
    <div class="main-content">
        <div class="deliveries-container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="delivery-meta">
                <span><i class="fas fa-star"></i> Calificación máxima: <?php echo $actividad['calificacion_maxima']; ?></span>
                <span><i class="fas fa-users"></i> Entregas recibidas: <?php echo count($alumnosEntregas); ?></span>
            </div>
            
            <?php if (!empty($alumnosEntregas)): ?>
                <?php foreach ($alumnosEntregas as $entrega): ?>
                    <div class="delivery-item">
                        <div class="delivery-header">
                            <div class="delivery-title">
                                <?php echo htmlspecialchars($entrega['nombre']); ?>
                            </div>
                            <div>
                                <?php if (!is_null($entrega['calificacion']) && $entrega['calificacion'] !== ''): ?>
                                    <span style="font-weight: bold; color: var(--accent-color);">
                                        Calificación: <?php echo $entrega['calificacion']; ?>/<?php echo $actividad['calificacion_maxima']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">Sin calificar</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($entrega['archivo']): ?>
                            <div class="delivery-meta">
                                <span><i class="fas fa-file"></i> Archivo entregado: <?php echo htmlspecialchars(basename($entrega['archivo'])); ?></span>
                            </div>
                            
                            <div class="delivery-actions">
                                <a href="../entregas/<?php echo htmlspecialchars($entrega['archivo']); ?>" class="btn btn-secondary" download>
                                    <i class="fas fa-download"></i> Descargar entrega
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="grade-form">
                            <form method="POST" action="">
                                <input type="hidden" name="id_alumno" value="<?php echo htmlspecialchars($entrega['id']); ?>">
                                <label for="calificacion_<?php echo $entrega['id']; ?>">Calificación:</label>
                                <input type="number" 
                                       id="calificacion_<?php echo $entrega['id']; ?>" 
                                       name="calificacion" 
                                       class="grade-input" 
                                       min="0" 
                                       max="<?php echo $actividad['calificacion_maxima']; ?>" 
                                       step="0.1"
                                       value="<?php echo !is_null($entrega['calificacion']) && $entrega['calificacion'] !== '' ? htmlspecialchars($entrega['calificacion']) : ''; ?>"
                                       required>
                                <span class="max-grade">/ <?php echo $actividad['calificacion_maxima']; ?></span>
                                <button type="submit" name="calificar" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay entregas para esta actividad todavía.</p>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="docente_grupos.php?id=<?php echo htmlspecialchars($actividad['idcurso']); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a actividades
                </a>
            </div>
        </div>
    </div>

    <script src="/scripts.js"></script>
    <script>
        // Validar que las calificaciones no superen el máximo permitido
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseFloat(this.getAttribute('max'));
                const value = parseFloat(this.value);
                
                if (value > max) {
                    alert('La calificación no puede ser mayor a ' + max);
                    this.value = max;
                }
            });
        });
    </script>
</body>
</html>