<?php
require_once '../fecha.php';

if (!verificarPeriodo('seleccion_materias')) {
    $periodo = obtenerPeriodoActivo('seleccion_materias');
    $mensaje = "No es posible seleccionar materias en este momento.";

    if ($periodo) {
        $inicio = date('d/m/Y', strtotime($periodo['fecha_inicio']));
        $fin = date('d/m/Y', strtotime($periodo['fecha_fin']));
        $mensaje .= " El periodo de selección de materias es del $inicio al $fin.";
    }

    echo "<div class='container mt-5' style='margin-top: 3rem !important;'>
            <div class='alert alert-danger text-center' style='background-color: #f44336; color: #fff; padding: 1rem; border-radius: 0.25rem;'>
                <h4 style='margin-top: 0; margin-bottom: 0.5rem; color: #fff;'><i class='fas fa-exclamation-triangle'></i> Fuera del periodo</h4>
                <p style='margin-bottom: 1rem; color: #fff;'>$mensaje</p>
                <div class='mt-3' style='margin-top: 1rem !important;'>
                    <a href='servicios_estudiante.php' class='btn btn-outline-primary' style='display: inline-block; font-weight: 400; color: #3498db; text-align: center; vertical-align: middle; -webkit-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; background-color: transparent; border: 1px solid #3498db; padding: 0.375rem 0.75rem; font-size: 1rem; line-height: 1.5; border-radius: 0.25rem; transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;'>
                        <i class='fas fa-clipboard-check'></i> Ver proceso de reinscripción
                    </a>
                </div>
            </div>
        </div>";
    exit();
}
?>
<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

$alumno = $_SESSION['usuario'];
$error = '';
$success = '';

// Verificar estatus del alumno
$query_estatus = "SELECT estatus, Carrera, Semestre FROM alumno WHERE idAlumno = ?";
$stmt = $conexion->prepare($query_estatus);
$stmt->bind_param("s", $alumno['id']);
$stmt->execute();
$result = $stmt->get_result();
$datos_alumno = $result->fetch_assoc();

// Procesar selección de materias
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_materias'])) {
    if ($datos_alumno['estatus'] != 'activo') {
        $error = "Debes estar con estatus ACTIVO para seleccionar materias.";
    } else {
        $materias_seleccionadas = $_POST['materias'] ?? [];
        
        if (empty($materias_seleccionadas)) {
            $error = "Debes seleccionar al menos una materia.";
        } else {
            try {
                $conexion->begin_transaction();
                
                // Actualizar materias del alumno
                $materias_str = implode(',', $materias_seleccionadas);
                $query_update = "UPDATE alumno SET Cursos = ? WHERE idAlumno = ?";
                $stmt_update = $conexion->prepare($query_update);
                $stmt_update->bind_param("ss", $materias_str, $alumno['id']);
                $stmt_update->execute();
                
                $conexion->commit();
                $success = "Materias seleccionadas correctamente. Ahora estás inscrito en: " . implode(', ', $materias_seleccionadas);
                
                // Actualizar datos en sesión
                $_SESSION['usuario']['cursos'] = $materias_str;
            } catch (Exception $e) {
                $conexion->rollback();
                $error = "Error al guardar las materias: " . $e->getMessage();
            }
        }
    }
}

// Obtener materias disponibles para la carrera del alumno
$materias_disponibles = [];
if ($datos_alumno['estatus'] == 'activo') {
    $query_materias = "SELECT m.idMateria, m.Nombre, m.Descripcionmateria 
                      FROM carrera_materia cm
                      JOIN materia m ON cm.Materia = m.idMateria
                      WHERE cm.Carrera = ?
                      ORDER BY m.Nombre";
    $stmt_materias = $conexion->prepare($query_materias);
    $stmt_materias->bind_param("s", $datos_alumno['Carrera']);
    $stmt_materias->execute();
    $result_materias = $stmt_materias->get_result();
    
    while ($materia = $result_materias->fetch_assoc()) {
        $materias_disponibles[] = $materia;
    }
}

// Obtener materias ya seleccionadas por el alumno
$materias_actuales = [];
if (!empty($alumno['cursos'])) {
    $materias_actuales = explode(',', $alumno['cursos']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Selección de Materias</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .selection-container {
            max-width: 800px;
            margin: 30px auto;
            background-color: var(--secondary-color);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .selection-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .selection-header h2 {
            color: var(--accent-color);
            margin-bottom: 10px;
        }
        
        .student-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color:--seconadry-color;
            border-radius: 4px;
        }
        
        .student-info p {
            margin: 5px 0;
        }
        
        .materias-list {
            margin-bottom: 30px;
        }
        
        .materia-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .materia-item:hover {
            border-color: var(--accent-color);
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .materia-checkbox {
            margin-right: 15px;
        }
        
        .materia-info {
            flex-grow: 1;
        }
        
        .materia-info h4 {
            margin: 0 0 5px 0;
            color: var(--text-color);
        }
        
        .materia-info p {
            margin: 0;
            color: var(--accent-color);
            font-size: 0.9em;
        }
        
        .btn-save {
            width: 100%;
            padding: 15px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-save:hover {
            background-color: #0056b3;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 6px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 6px solid #c3e6cb;
        }
        
        .status-info {
            background-color: #e2e3e5;
            color: #383d41;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
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
        <h1>Selección de Materias</h1>
        <p>Inscripción a materias para el semestre actual</p>
    </div>
    
    <div class="main-content">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="selection-container">
            <div class="selection-header">
                <h2><i class="fas fa-book-open"></i> Materias Disponibles</h2>
                <p>Selecciona las materias que deseas cursar este semestre</p>
            </div>
            
            <div class="student-info">
                <p><strong>Alumno:</strong> <?php echo htmlspecialchars($alumno['nombre']); ?></p>
                <p><strong>Carrera:</strong> <?php echo htmlspecialchars($datos_alumno['Carrera']); ?></p>
                <p><strong>Semestre:</strong> <?php echo htmlspecialchars($datos_alumno['Semestre']); ?></p>
                <p><strong>Estatus:</strong> <?php echo htmlspecialchars($datos_alumno['estatus']); ?></p>
            </div>
            
            <?php if ($datos_alumno['estatus'] != 'activo'): ?>
                <div class="status-info">
                    <h3><i class="fas fa-info-circle"></i> No puedes seleccionar materias</h3>
                    <p>Tu estatus actual es: <strong><?php echo htmlspecialchars($datos_alumno['estatus']); ?></strong></p>
                    <p>Debes estar con estatus <strong>ACTIVO</strong> para poder seleccionar materias.</p>
                    <p>Si acabas de reinscribirte, recarga la página o cierra sesión y vuelve a ingresar.</p>
                </div>
            <?php elseif (empty($materias_disponibles)): ?>
                <div class="status-info">
                    <h3><i class="fas fa-info-circle"></i> No hay materias disponibles</h3>
                    <p>No se encontraron materias disponibles para tu carrera en este momento.</p>
                    <p>Por favor, contacta a Servicios Escolares para más información.</p>
                </div>
            <?php else: ?>
                <form method="POST">
                    <div class="materias-list">
                        <?php foreach ($materias_disponibles as $materia): ?>
                            <div class="materia-item">
                                <div class="materia-checkbox">
                                    <input type="checkbox" 
                                           id="materia_<?php echo htmlspecialchars($materia['idMateria']); ?>" 
                                           name="materias[]" 
                                           value="<?php echo htmlspecialchars($materia['idMateria']); ?>"
                                           <?php echo in_array($materia['idMateria'], $materias_actuales) ? 'checked' : ''; ?>>
                                </div>
                                <div class="materia-info">
                                    <h4><?php echo htmlspecialchars($materia['Nombre']); ?> (<?php echo htmlspecialchars($materia['idMateria']); ?>)</h4>
                                    <p><?php echo htmlspecialchars($materia['Descripcionmateria']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" name="guardar_materias" class="btn-save">
                        <i class="fas fa-save"></i> Guardar Selección
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="/scripts.js"></script>
</body>
</html>