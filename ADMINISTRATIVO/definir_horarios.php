<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../conexion.php';

// Procesar formulario de horarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar_horario') {
    $id_materia = $conexion->real_escape_string($_POST['id_materia']);
    $dia_semana = $conexion->real_escape_string($_POST['dia_semana']);
    $hora_inicio = $conexion->real_escape_string($_POST['hora_inicio']);
    $hora_fin = $conexion->real_escape_string($_POST['hora_fin']);
    $aula = $conexion->real_escape_string($_POST['aula']);
    $grupo = $conexion->real_escape_string($_POST['grupo']);
    $periodo = $conexion->real_escape_string($_POST['periodo']);

    // 1. Verificar si la materia ya tiene horario asignado
    $query_verificar = "SELECT * FROM horarios WHERE id_materia = '$id_materia'";
    $result_verificar = $conexion->query($query_verificar);
    
    if ($result_verificar->num_rows > 0) {
        $_SESSION['error'] = "Esta materia ya tiene un horario asignado";
        header("Location: definir_horarios.php");
        exit();
    }

    // 2. Verificar conflictos de horario para la misma carrera
    // Primero obtenemos la carrera de la materia
    $query_carrera = "SELECT Carrera FROM carrera_materia WHERE Materia = '$id_materia'";
    $result_carrera = $conexion->query($query_carrera);
    
    if ($result_carrera->num_rows > 0) {
        $row_carrera = $result_carrera->fetch_assoc();
        $carrera = $conexion->real_escape_string($row_carrera['Carrera']);
        
        // Buscamos materias de la misma carrera con horarios que coincidan
        $query_conflicto = "SELECT h.*, m.Nombre 
                           FROM horarios h
                           JOIN carrera_materia cm ON h.id_materia = cm.Materia
                           JOIN materia m ON h.id_materia = m.idMateria
                           WHERE cm.Carrera = '$carrera'
                           AND h.dia_semana = '$dia_semana'
                           AND (
                               ('$hora_inicio' BETWEEN h.hora_inicio AND h.hora_fin) OR
                               ('$hora_fin' BETWEEN h.hora_inicio AND h.hora_fin) OR
                               (h.hora_inicio BETWEEN '$hora_inicio' AND '$hora_fin') OR
                               (h.hora_fin BETWEEN '$hora_inicio' AND '$hora_fin')
                           )";
        
        $result_conflicto = $conexion->query($query_conflicto);
        
        if ($result_conflicto->num_rows > 0) {
            $conflicto = $result_conflicto->fetch_assoc();
            $_SESSION['error'] = "Conflicto de horario con la materia: " . $conflicto['Nombre'] . 
                                " (Día: " . $conflicto['dia_semana'] . 
                                ", Hora: " . $conflicto['hora_inicio'] . " - " . $conflicto['hora_fin'] . ")";
            header("Location: definir_horarios.php");
            exit();
        }
    }

    // 3. Insertar el nuevo horario si no hay conflictos
    $query_insert = "INSERT INTO horarios (id_materia, dia_semana, hora_inicio, hora_fin, aula, grupo, periodo) 
                     VALUES ('$id_materia', '$dia_semana', '$hora_inicio', '$hora_fin', '$aula', '$grupo', '$periodo')";
    
    if ($conexion->query($query_insert)) {
        $_SESSION['success'] = "Horario asignado correctamente";
    } else {
        $_SESSION['error'] = "Error al asignar horario: " . $conexion->error;
    }
    
    header("Location: definir_horarios.php");
    exit();
}

// Obtener todas las carreras con sus materias
$carreras = [];
$query_carreras = "SELECT c.idCarrera, c.Nombre FROM carrera c ORDER BY c.Nombre";
$result_carreras = $conexion->query($query_carreras);

while ($row = $result_carreras->fetch_assoc()) {
    $carreras[$row['idCarrera']] = [
        'nombre' => $row['Nombre'],
        'materias' => []
    ];
}

// Obtener todas las materias con su información y horarios si los tienen
$query_materias = "SELECT 
    m.idMateria, 
    m.Nombre, 
    m.Descripcionmateria,
    cm.Carrera AS nombre_carrera,  -- Cambiado de id_carrera a nombre_carrera
    h.dia_semana,
    h.hora_inicio,
    h.hora_fin,
    h.aula,
    h.grupo,
    h.periodo
FROM materia m
LEFT JOIN carrera_materia cm ON m.idMateria = cm.Materia
LEFT JOIN horarios h ON m.idMateria = h.id_materia
ORDER BY cm.Carrera, m.Nombre";

$result_materias = $conexion->query($query_materias);

while ($row = $result_materias->fetch_assoc()) {
    $materia = [
        'id' => $row['idMateria'],
        'nombre' => $row['Nombre'],
        'descripcion' => $row['Descripcionmateria'],
        'horario' => null
    ];
    
    if ($row['dia_semana']) {
        $materia['horario'] = [
            'dia_semana' => $row['dia_semana'],
            'hora_inicio' => $row['hora_inicio'],
            'hora_fin' => $row['hora_fin'],
            'aula' => $row['aula'],
            'grupo' => $row['grupo'],
            'periodo' => $row['periodo']
        ];
    }
    
    // Si la materia tiene carrera asignada, la agregamos a esa carrera
    if (!empty($row['nombre_carrera'])) {
        // Buscamos la carrera por nombre en el array $carreras
        foreach ($carreras as $carreraId => $carreraData) {
            if ($carreraData['nombre'] == $row['nombre_carrera']) {
                $carreras[$carreraId]['materias'][] = $materia;
                break;
            }
        }
    } else {
        // Si no tiene carrera asignada, la ponemos en una sección especial
        if (!isset($carreras['sin_carrera'])) {
            $carreras['sin_carrera'] = [
                'nombre' => 'Materias sin carrera asignada',
                'materias' => []
            ];
        }
        $carreras['sin_carrera']['materias'][] = $materia;
    }
}
    
    // Solo agregar la materia si tiene una carrera asignada
    if (!empty($row['id_carrera']) && isset($carreras[$row['id_carrera']])) {
        $carreras[$row['id_carrera']]['materias'][] = $materia;
    }


// Mostrar mensajes de éxito/error
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Panel Administrativo</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

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
            <li><a href="/ADMINISTRATIVO/inicio_admin.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="administrador_gestionar usuarios.php"><i class="fas fa-user-plus"></i> <span>Gestionar Usuarios</span></a></li>
            <li><a href="administrarforo_administrador.php"><i class="fas fa-comments"></i> <span>Administrar Foros</span></a></li>
            <li><a href="administrador_documentos.php"><i class="fas fa-file-alt"></i> <span>Gestión Documental</span></a></li>
            <li><a href="admin_cursos.php"><i class="fas fa-book"></i> <span>Cursos</span></a></li>
            <li><a href="admin_anuncios.php"><i class="fas fa-comment-dots"></i> <span>Anuncios</span></a></li>
            <li class="active"><a href="definir_horarios.php"><i class="fas fa-calendar"></i> <span>Definir Horarios</span></a></li>
            <li><a href="funciones_especiales.php"><i class="fas fa-star"></i> <span>Funciones adicionales</span></a></li>
            <li><a href="/index"><i class="fas fa-backward"></i> <span>Log-out</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Gestionar horarios</h1>
        <p>Asignar horario a las materias</p>
    </div>

    <div class="main-content">
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <!-- Mostrar carreras con sus materias -->
        <?php foreach ($carreras as $carreraId => $carreraData): ?>
            <?php if (!isset($carreraData['nombre']) || !isset($carreraData['materias'])) continue; ?>
            <div class="career-section">
                <div class="career-header">
                    <h2 class="career-title"><?php echo htmlspecialchars($carreraData['nombre']); ?></h2>
                </div>
                
                <?php if (!empty($carreraData['materias'])): ?>
                    <div class="course-list">
                    <?php foreach ($carreraData['materias'] as $materia): ?>
                        <?php if (!isset($materia['id']) || !isset($materia['nombre'])) continue; ?>
                            <div class="course-item">
                                <h4><?php echo htmlspecialchars($materia['nombre']); ?></h4>
                                <div class="course-code"><?php echo htmlspecialchars($materia['id']); ?></div>
                                <div class="course-description"><?php echo htmlspecialchars($materia['descripcion']); ?></div>
                                
                                <?php if ($materia['horario']): ?>
                                    <div class="course-schedule">
                                        <strong>Horario asignado:</strong><br>
                                        Día: <?php echo htmlspecialchars($materia['horario']['dia_semana']); ?><br>
                                        Hora: <?php echo htmlspecialchars($materia['horario']['hora_inicio']); ?> - <?php echo htmlspecialchars($materia['horario']['hora_fin']); ?><br>
                                        Aula: <?php echo htmlspecialchars($materia['horario']['aula']); ?><br>
                                        Grupo: <?php echo htmlspecialchars($materia['horario']['grupo']); ?><br>
                                        Periodo: <?php echo htmlspecialchars($materia['horario']['periodo']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="course-schedule" style="background-color:--secondary-color; border-left-color: #e74c3c;">
                                        <strong>Sin horario asignado</strong>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="btn-group">
                                    <button class="btn btn-edit" onclick="openScheduleModal('<?php echo $materia['id']; ?>', '<?php echo htmlspecialchars(addslashes($materia['nombre'])); ?>')">
                                        <i class="fas fa-calendar-alt"></i> <?php echo $materia['horario'] ? 'Modificar' : 'Asignar'; ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No hay materias asignadas a esta carrera.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Modal para asignar/editar horario -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Asignar Horario</h3>
                <span class="close" onclick="closeScheduleModal()">&times;</span>
            </div>
            <form id="scheduleForm" action="definir_horarios.php" method="POST">
                <input type="hidden" name="action" value="guardar_horario">
                <input type="hidden" id="modalMateriaId" name="id_materia">
                
                <div class="form-group">
                    <label for="dia_semana">Día de la semana:</label>
                    <select id="dia_semana" name="dia_semana" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sábado">Sábado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="hora_inicio">Hora de inicio:</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" required>
                </div>
                
                <div class="form-group">
                    <label for="hora_fin">Hora de fin:</label>
                    <input type="time" id="hora_fin" name="hora_fin" required>
                </div>
                
                <div class="form-group">
                    <label for="aula">Aula:</label>
                    <input type="text" id="aula" name="aula" required>
                </div>
                
                <div class="form-group">
                    <label for="grupo">Grupo:</label>
                    <input type="text" id="grupo" name="grupo" required>
                </div>
                
                <div class="form-group">
                    <label for="periodo">Periodo escolar:</label>
                    <input type="text" id="periodo" name="periodo" placeholder="Ej: 2025-1" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-cancel" onclick="closeScheduleModal()">Cancelar</button>
                    <button type="submit" class="btn btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/scripts.js"></script>
    <script>
        // Función para abrir el modal de horario
        function openScheduleModal(materiaId, materiaNombre) {
            document.getElementById('modalMateriaId').value = materiaId;
            document.getElementById('modalTitle').textContent = 'Asignar horario: ' + materiaNombre;
            document.getElementById('scheduleModal').style.display = 'block';
        }
        
        // Función para cerrar el modal de horario
        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeScheduleModal();
            }
        }
        
        // Validación de horario antes de enviar
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            const horaInicio = document.getElementById('hora_inicio').value;
            const horaFin = document.getElementById('hora_fin').value;
            
            if (horaInicio >= horaFin) {
                e.preventDefault();
                alert('La hora de inicio debe ser anterior a la hora de fin');
                return false;
            }
            return true;
        });
    </script>
</body>
</html>