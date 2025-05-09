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
$cursos = [];

// Consulta para obtener los datos del alumno
$query = "SELECT Cursos FROM alumno WHERE idAlumno = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $idAlumno);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $alumno = $row;
    // Obtener los IDs de los cursos del alumno (separados por comas)
    $idsMaterias = !empty($alumno['Cursos']) ? explode(',', $alumno['Cursos']) : [];
    
    // Obtener información de cada materia
    if (!empty($idsMaterias)) {
        // Crear placeholders para la consulta IN
        $placeholders = implode(',', array_fill(0, count($idsMaterias), '?'));
        
        // Consulta para obtener las materias
        $query = "SELECT m.idMateria, m.Nombre, m.Descripcionmateria 
                  FROM materia m 
                  WHERE m.idMateria IN ($placeholders)";
        $stmt = $conexion->prepare($query);
        
        // Vincular parámetros dinámicamente
        $types = str_repeat('s', count($idsMaterias));
        $stmt->bind_param($types, ...$idsMaterias);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Buscar profesor para esta materia
            $queryProfesor = "SELECT d.Nombre 
                             FROM docente d 
                             WHERE FIND_IN_SET(?, d.Cursos) 
                             LIMIT 1";
            $stmtProfesor = $conexion->prepare($queryProfesor);
            $stmtProfesor->bind_param("s", $row['idMateria']);
            $stmtProfesor->execute();
            $resultProfesor = $stmtProfesor->get_result();
            
            $profesor = 'Sin asignar';
            if ($profesorRow = $resultProfesor->fetch_assoc()) {
                $profesor = $profesorRow['Nombre'];
            }
            
            $cursos[] = [
                'id' => $row['idMateria'],
                'nombre' => $row['Nombre'],
                'descripcion' => $row['Descripcionmateria'],
                'profesor' => $profesor
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Cursos</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
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
        <h1>Cursos</h1>
        <p>Gestiona tu carga académica</p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Mis Cursos Actuales</h3>
            <?php if (count($cursos) > 0): ?>
                <div class="course-list">
                    <?php foreach ($cursos as $curso): ?>
                        <div class="course-item">
                            <h4><?php echo htmlspecialchars($curso['nombre']); ?></h4>
                            <p class="course-description"><?php echo htmlspecialchars($curso['descripcion']); ?></p>
                            <p><strong>Profesor:</strong> <?php echo htmlspecialchars($curso['profesor']); ?></p>
                            <p><strong>Código:</strong> <?php echo htmlspecialchars($curso['id']); ?></p>
                            <a href="inicio_curso.php?id=<?php echo $curso['id']; ?>" class="btn-course">Acceder al curso</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No estás inscrito en ningún curso actualmente.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="/scripts.js"></script>
</body>
</html>