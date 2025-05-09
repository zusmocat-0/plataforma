<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Obtener información del alumno
$idAlumno = $_SESSION['usuario']['id'];
$alumno = [];
$horarios = [];

// Consulta para obtener los cursos del alumno
$query = "SELECT Cursos FROM alumno WHERE idAlumno = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $idAlumno);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $alumno = $row;
    // Obtener los IDs de los cursos del alumno (separados por comas)
    $idsMaterias = !empty($alumno['Cursos']) ? explode(',', $alumno['Cursos']) : [];
    
    // Obtener horarios de cada materia
    if (!empty($idsMaterias)) {
        // Crear placeholders para la consulta IN
        $placeholders = implode(',', array_fill(0, count($idsMaterias), '?'));
        
        // Consulta corregida para evitar duplicados
        $query = "SELECT DISTINCT
                    h.id_materia, 
                    m.Nombre AS nombre_materia,
                    h.dia_semana, 
                    h.hora_inicio, 
                    h.hora_fin, 
                    h.aula,
                    h.grupo,
                    (SELECT d.Nombre FROM docente d WHERE FIND_IN_SET(h.id_materia, d.Cursos) LIMIT 1) AS nombre_profesor
                  FROM horarios h
                  JOIN materia m ON h.id_materia = m.idMateria
                  WHERE h.id_materia IN ($placeholders)
                  ORDER BY 
                    FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'),
                    h.hora_inicio";
        
        $stmt = $conexion->prepare($query);
        
        // Vincular parámetros dinámicamente
        $types = str_repeat('s', count($idsMaterias));
        $stmt->bind_param($types, ...$idsMaterias);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $horarios[] = $row;
        }
    }
}

// Organizar horarios por día
$horarioPorDia = [
    'Lunes' => [],
    'Martes' => [],
    'Miércoles' => [],
    'Jueves' => [],
    'Viernes' => [],
    'Sábado' => []
];

foreach ($horarios as $horario) {
    $dia = $horario['dia_semana'];
    $horarioPorDia[$dia][] = $horario;
}

// Función para formatear la hora
function formatHora($hora) {
    return date("g:i a", strtotime($hora));
}
?>
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Calendario</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
.schedule-container {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            min-width: 800px;
        }

        .schedule-day {
            background-color: var(--secondary-color); /* Usando el color secundario para un tono más oscuro */
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: var(--text-color); /* Texto en color claro para contrastar */
        }

        .day-header {
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid var(--light-gray); /* Línea divisoria clara */
            color: var(--text-color);
        }

        .schedule-item {
            background-color: var(--primary-color); /* Fondo primario para los elementos */
            border-left: 4px solid var(--accent-color); /* Acento en el borde izquierdo */
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            color: var(--text-color);
        }

        .course-time {
            font-size: 0.9em;
            color: var(--light-gray); /* Texto un poco más claro */
            margin-bottom: 5px;
        }

        .course-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .course-details {
            font-size: 0.8em;
            color: var(--dark-gray); /* Detalles en gris oscuro */
        }

        .empty-day {
            text-align: center;
            color: var(--light-gray);
            font-style: italic;
            padding: 20px 0;
        }

        @media (max-width: 768px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }
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
            <li class="active"><a href="calendario_estudiante.php"><i class="fas fa-calendar-alt"></i> <span>Calendario</span></a></li>
            <li><a href="avance_reticular.php"><i class="fas fa-tasks"></i> <span>Avance Reticular</span></a></li>
            <li><a href="foro_estudiante.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="servicios_estudiante.php"><i class="fas fa-school"></i> <span>Servicios Escolares</span></a></li>
            <li><a href="info_estudiante.php"><i class="fas fa-user"></i> <span>Información personal</span></a></li>
            <li><a href="\logout.php"><i class="fas fa-right-from-bracket"></i> <span>Cerrar sesión</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Mi Horario</h1>
        <p>Visualiza tus clases organizadas por día</p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <?php if (count($horarios) > 0): ?>
                <div class="schedule-container">
                    <div class="schedule-grid">
                        <?php foreach ($horarioPorDia as $dia => $clases): ?>
                            <div class="schedule-day">
                                <div class="day-header"><?php echo $dia; ?></div>
                                <?php if (count($clases) > 0): ?>
                                    <?php foreach ($clases as $clase): ?>
                                        <div class="schedule-item">
                                            <div class="course-time">
                                                <?php echo formatHora($clase['hora_inicio']); ?> - <?php echo formatHora($clase['hora_fin']); ?>
                                            </div>
                                            <div class="course-name">
                                                <?php echo htmlspecialchars($clase['nombre_materia']); ?>
                                            </div>
                                            <div class="course-details">
                                                <div><i class="fas fa-user"></i> <?php echo htmlspecialchars($clase['nombre_profesor'] ?? 'Sin asignar'); ?></div>
                                                <div><i class="fas fa-door-open"></i> Aula: <?php echo htmlspecialchars($clase['aula']); ?></div>
                                                <div><i class="fas fa-users"></i> Grupo: <?php echo htmlspecialchars($clase['grupo']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-day">No hay clases programadas</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <p>No tienes horarios asignados para tus cursos actuales.</p>
                <p>Si crees que esto es un error, por favor contacta a servicios escolares.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="/scripts.js"></script>
    <script>
        // Cambiar tema
        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const icon = document.querySelector('.theme-toggle i');
            if (document.body.classList.contains('dark-theme')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }

        // Verificar tema al cargar
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-theme');
                const icon = document.querySelector('.theme-toggle i');
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        });
    </script>
</body>
</html>