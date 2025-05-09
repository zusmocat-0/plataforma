<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['idDocente'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Obtener información del docente desde la sesión
$rfcDocente = $_SESSION['idDocente'];
$materias = [];

// Consulta para obtener los cursos del docente
$queryDocente = "SELECT Cursos FROM docente WHERE `idDocente(RFC)` = ?";
$stmtDocente = $conexion->prepare($queryDocente);
$stmtDocente->bind_param("s", $rfcDocente);
$stmtDocente->execute();
$resultDocente = $stmtDocente->get_result();

if ($rowDocente = $resultDocente->fetch_assoc()) {
    // Obtener los IDs de las materias del docente
    $idsMaterias = !empty($rowDocente['Cursos']) ? explode(',', $rowDocente['Cursos']) : [];
    
    // Filtrar valores vacíos o "Array"
    $idsMaterias = array_filter($idsMaterias, function($value) {
        return !empty($value) && $value !== 'Array';
    });
    
    // Obtener información de cada materia y sus foros
    if (!empty($idsMaterias)) {
        // Crear placeholders para la consulta IN
        $placeholders = implode(',', array_fill(0, count($idsMaterias), '?'));
        
        // Consulta para obtener las materias
        $query = "SELECT m.idMateria, m.Nombre, m.Descripcionmateria, m.NumeroUnidades 
                  FROM materia m 
                  WHERE m.idMateria IN ($placeholders)";
        $stmt = $conexion->prepare($query);
        
        // Vincular parámetros dinámicamente
        $types = str_repeat('s', count($idsMaterias));
        $stmt->bind_param($types, ...$idsMaterias);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Obtener foros de esta materia
            $queryForos = "SELECT idForo, Tituloforo, Fecha, Unidad 
                           FROM foro 
                           WHERE idMateria = ? 
                           ORDER BY Fecha DESC";
            $stmtForos = $conexion->prepare($queryForos);
            $stmtForos->bind_param("s", $row['idMateria']);
            $stmtForos->execute();
            $resultForos = $stmtForos->get_result();
            $foros = [];
            
            while ($foro = $resultForos->fetch_assoc()) {
                $foros[] = $foro;
            }
            
            $materias[] = [
                'id' => $row['idMateria'],
                'nombre' => $row['Nombre'],
                'descripcion' => $row['Descripcionmateria'],
                'unidades' => $row['NumeroUnidades'],
                'foros' => $foros
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
    <title>MindBox - Foro Docente</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        
        .course-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .course-item {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .course-item:hover {
            transform: translateY(-5px);
        }
        
        .course-item h4 {
            margin-top: 0;
            color: var(--accent-color);
        }
        
        .course-description {
            color: var(--text-muted);
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .course-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        
        .btn-foro {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn-foro:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .foros-container {
            margin-top: 15px;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }
        
        .foro-item {
            padding: 10px;
            margin-bottom: 10px;
            background-color: var(--light-bg);
            border-radius: 5px;
        }
        
        .foro-title {
            font-weight: bold;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }
        
        .foro-meta {
            font-size: 0.8em;
            color: var(--text-muted);
        }
        
        .toggle-foros {
            cursor: pointer;
            color: var(--accent-color);
            margin-top: 10px;
            display: inline-block;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="notification-icon">
        <i class="fas fa-bell" onclick="toggleNotifications()"></i>
    </div>
    
    <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
        <i class="fas fa-moon"></i>
    </button>
    
    <div id="notificationBox" class="notification-box">
    </div>
    
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="docente_inicio.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li class="active"><a href="docente_foro.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="docente_anuncios.php"><i class="fas fa-bullhorn"></i> <span>Anuncios</span></a></li>
            <li><a href="docente_grupos.php"><i class="fas fa-user-group"></i> <span>Grupos</span></a></li>
            <li><a href="/logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Foro</h1>
        <p>Gestiona los foros de discusión para tus materias</p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Mis Materias Asignadas</h3>
            <p>Seleccione una materia para crear un foro de discusión o ver los existentes.</p>
            
            <?php if (count($materias) > 0): ?>
                <div class="course-list">
                    <?php foreach ($materias as $materia): ?>
                        <div class="course-item">
                            <h4><?php echo htmlspecialchars($materia['nombre']); ?></h4>
                            <p class="course-description"><?php echo htmlspecialchars($materia['descripcion']); ?></p>
                            <div class="course-meta">
                                <span><strong>Código:</strong> <?php echo htmlspecialchars($materia['id']); ?></span>
                                <span><strong>Unidades:</strong> <?php echo htmlspecialchars($materia['unidades']); ?></span>
                            </div>
                            
                            <a href="foro_materia.php?id=<?php echo $materia['id']; ?>" class="btn-foro">
                                <i class="fas fa-plus"></i> Nuevo Foro
                            </a>
                            
                            <?php if (!empty($materia['foros'])): ?>
                                <div class="toggle-foros" onclick="toggleForos('<?php echo $materia['id']; ?>')">
                                    <i class="fas fa-chevron-down"></i> Ver foros existentes (<?php echo count($materia['foros']); ?>)
                                </div>
                                
                                <div id="foros-<?php echo $materia['id']; ?>" class="foros-container hidden">
                                    <?php foreach ($materia['foros'] as $foro): ?>
                                        <div class="foro-item">
                                            <div class="foro-title">
                                                <span><?php echo htmlspecialchars($foro['Tituloforo']); ?></span>
                                                <a href="detalle_foro_docente.php?id=<?php echo $foro['idForo']; ?>" class="btn-foro btn-secondary" style="padding: 3px 8px; font-size: 0.8em;">
                                                    <i class="fas fa-eye"></i> Entrar
                                                </a>
                                            </div>
                                            <div class="foro-meta">
                                                <span>Unidad <?php echo htmlspecialchars($foro['Unidad']); ?></span> | 
                                                <span><?php echo date('d/m/Y H:i', strtotime($foro['Fecha'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="font-size: 0.9em; color: var(--text-muted); margin-top: 10px;">
                                    No hay foros creados para esta materia.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No tienes materias asignadas actualmente.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleForos(materiaId) {
            const container = document.getElementById(`foros-${materiaId}`);
            const icon = document.querySelector(`#foros-${materiaId}`).previousElementSibling.querySelector('i');
            
            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                container.classList.add('hidden');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
    </script>
    <script src="/scripts.js"></script>
</body>
</html>