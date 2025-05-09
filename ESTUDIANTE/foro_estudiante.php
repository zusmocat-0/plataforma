<?php
session_start();

// Verificar sesión de estudiante según la estructura que me indicaste
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

$alumno = $_SESSION['usuario'];

require_once '../conexion.php';

// Obtener el ID del alumno desde la sesión (asumiendo que está en $_SESSION['usuario']['id'])
$idAlumno = $alumno['id'];

// Obtener las materias del estudiante
$queryMaterias = "SELECT Cursos FROM alumno WHERE idAlumno = ?";
$stmtMaterias = $conexion->prepare($queryMaterias);
$stmtMaterias->bind_param("s", $idAlumno);
$stmtMaterias->execute();
$resultMaterias = $stmtMaterias->get_result();

$materiasEstudiante = [];
$forosDisponibles = [];

if ($rowMaterias = $resultMaterias->fetch_assoc()) {
    // Obtener los IDs de las materias del estudiante (separados por comas)
    $idsMaterias = !empty($rowMaterias['Cursos']) ? explode(',', $rowMaterias['Cursos']) : [];
    
    // Filtrar valores vacíos
    $idsMaterias = array_filter($idsMaterias, function($value) {
        return !empty($value) && $value !== 'Array';
    });
    
    // Obtener información de las materias y sus foros
    if (!empty($idsMaterias)) {
        // Crear placeholders para la consulta IN
        $placeholders = implode(',', array_fill(0, count($idsMaterias), '?'));
        
        // Consulta para obtener las materias del estudiante con información básica
        $query = "SELECT idMateria, Nombre, Descripcionmateria, NumeroUnidades 
                 FROM materia 
                 WHERE idMateria IN ($placeholders)";
        $stmt = $conexion->prepare($query);
        
        // Vincular parámetros dinámicamente
        $types = str_repeat('s', count($idsMaterias));
        $stmt->bind_param($types, ...$idsMaterias);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $materiasEstudiante[] = [
                'id' => $row['idMateria'],
                'nombre' => $row['Nombre'],
                'descripcion' => $row['Descripcionmateria'],
                'unidades' => $row['NumeroUnidades']
            ];
        }
        
        // Consulta para obtener los foros disponibles agrupados por materia
        $queryForos = "SELECT f.idForo, f.Tituloforo, f.Contenido, f.Fecha, f.Unidad, 
                              m.idMateria, m.Nombre AS nombreMateria,
                              d.Nombre AS nombreDocente,
                              COUNT(c.idForo) AS numComentarios
                       FROM foro f
                       JOIN materia m ON f.idMateria = m.idMateria
                       JOIN docente d ON f.idAutor = d.`idDocente(RFC)`
                       LEFT JOIN comentario c ON f.idForo = c.idForo
                       WHERE f.idMateria IN ($placeholders)
                       GROUP BY f.idForo
                       ORDER BY f.Fecha DESC";
        $stmtForos = $conexion->prepare($queryForos);
        $stmtForos->bind_param($types, ...$idsMaterias);
        $stmtForos->execute();
        $resultForos = $stmtForos->get_result();
        
        while ($rowForo = $resultForos->fetch_assoc()) {
            $forosDisponibles[] = $rowForo;
        }
        
        // Agrupar foros por materia
        $forosPorMateria = [];
        foreach ($forosDisponibles as $foro) {
            $materiaId = $foro['idMateria'];
            if (!isset($forosPorMateria[$materiaId])) {
                // Buscar la información completa de la materia
                $materiaInfo = null;
                foreach ($materiasEstudiante as $materia) {
                    if ($materia['id'] === $materiaId) {
                        $materiaInfo = $materia;
                        break;
                    }
                }
                
                $forosPorMateria[$materiaId] = [
                    'info' => $materiaInfo,
                    'foros' => []
                ];
            }
            $forosPorMateria[$materiaId]['foros'][] = $foro;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Foro Estudiante</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

        
        

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
        <h1>Foro</h1>
        <p>Participa en los foros de tus materias</p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Mis Materias con Foros Disponibles</h3>
            
            <?php if (!empty($forosPorMateria)): ?>
                <div class="course-list">
                    <?php foreach ($forosPorMateria as $materiaId => $materiaData): ?>
                        <?php if ($materiaData['info']): ?>
                            <div class="course-item">
                                <h4><?php echo htmlspecialchars($materiaData['info']['nombre']); ?></h4>
                                <p class="course-description"><?php echo htmlspecialchars($materiaData['info']['descripcion']); ?></p>
                                <div class="course-meta">
                                    <span><strong>Código:</strong> <?php echo htmlspecialchars($materiaData['info']['id']); ?></span>
                                    <span><strong>Unidades:</strong> <?php echo htmlspecialchars($materiaData['info']['unidades']); ?></span>
                                </div>
                                
                                <?php if (!empty($materiaData['foros'])): ?>
                                    <div class="forum-list">
                                        <?php foreach ($materiaData['foros'] as $foro): ?>
                                            <div class="forum-item">
                                                <div class="forum-meta">
                                                    <span>Unidad <?php echo htmlspecialchars($foro['Unidad']); ?></span>
                                                    <span><?php echo date('d/m/Y H:i', strtotime($foro['Fecha'])); ?></span>
                                                </div>
                                                <h5><?php echo htmlspecialchars($foro['Tituloforo']); ?></h5>
                                                <p class="forum-content"><?php echo htmlspecialchars($foro['Contenido']); ?></p>
                                                <div class="forum-meta">
                                                    <span>Por: <?php echo htmlspecialchars($foro['nombreDocente']); ?></span>
                                                    <span><?php echo htmlspecialchars($foro['numComentarios']); ?> comentarios</span>
                                                </div>
                                                <a href="foro_detalle.php?id=<?php echo $foro['idForo']; ?>" class="btn-foro">
                                                    <i class="fas fa-comment-dots"></i> Participar
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="no-foros">No hay foros disponibles en esta materia.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-foros">
                    <p>No tienes foros disponibles en tus materias actuales.</p>
                    <p>Consulta con tus profesores si deberían haber foros disponibles.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/scripts.js"></script>
</body>
</html>