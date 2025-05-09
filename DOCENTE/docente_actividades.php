<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['idDocente'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Verificar y agregar columnas faltantes si es necesario
$checkColumns = $conexion->query("SHOW COLUMNS FROM actividades LIKE 'unidad'");
if ($checkColumns->num_rows == 0) {
    $conexion->query("ALTER TABLE actividades
                     ADD COLUMN unidad INT NOT NULL DEFAULT 1 AFTER idcurso,
                     ADD COLUMN fecha_inicio DATETIME NOT NULL,
                     ADD COLUMN fecha_fin DATETIME NOT NULL,
                     ADD COLUMN calificacion_maxima INT NOT NULL");
}

// Obtener el ID de la materia desde la URL
$idMateria = isset($_GET['id']) ? $_GET['id'] : null;

if (!$idMateria) {
    header("Location: docente_grupos.php");
    exit();
}

// Obtener información de la materia
$queryMateria = "SELECT m.idMateria, m.Nombre, m.Descripcionmateria, m.NumeroUnidades, m.Unidades 
                 FROM materia m 
                 WHERE m.idMateria = ?";
$stmtMateria = $conexion->prepare($queryMateria);
$stmtMateria->bind_param("s", $idMateria);
$stmtMateria->execute();
$resultMateria = $stmtMateria->get_result();

if (!$materia = $resultMateria->fetch_assoc()) {
    header("Location: docente_grupos.php");
    exit();
}

// Decodificar las unidades desde JSON
$unidades = json_decode($materia['Unidades'], true) ?? [];

// Procesar formulario para nueva actividad
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que todos los campos requeridos están presentes
    if (!isset($_POST['titulo'], $_POST['descripcion'], $_POST['unidad'], 
        $_POST['fecha_inicio'], $_POST['fecha_fin'], $_POST['calificacion_maxima'])) {
        $_SESSION['error'] = "Faltan campos requeridos en el formulario";
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$idMateria);
        exit();
    }

    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $unidad = intval($_POST['unidad']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $calificacion_maxima = intval($_POST['calificacion_maxima']);
    
    // Validar datos básicos
    if (empty($titulo) || empty($descripcion) || $unidad < 1 || $calificacion_maxima < 1) {
        $_SESSION['error'] = "Por favor complete todos los campos requeridos correctamente";
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$idMateria);
        exit();
    }

    // Validar fechas
    if (empty($fecha_inicio) || empty($fecha_fin)) {
        $_SESSION['error'] = "Debe completar fechas de inicio y fin";
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$idMateria);
        exit();
    }

    // Validar que fecha fin sea posterior a fecha inicio
    if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
        $_SESSION['error'] = "La fecha de inicio no puede ser posterior a la fecha de fin";
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$idMateria);
        exit();
    }

    // Procesar archivo adjunto
    $archivoAdjunto = null;
    
    if (isset($_FILES['archivoAdjunto']) && $_FILES['archivoAdjunto']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = basename($_FILES['archivoAdjunto']['name']);
        $rutaTemporal = $_FILES['archivoAdjunto']['tmp_name'];
        $directorioDestino = '../uploads/actividades/';
        
        if (!file_exists($directorioDestino)) {
            mkdir($directorioDestino, 0777, true);
        }
        
        $rutaDestino = $directorioDestino . uniqid() . '_' . $nombreArchivo;
        
        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
            $archivoAdjunto = $rutaDestino;
        } else {
            $_SESSION['error'] = "Error al subir el archivo adjunto";
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$idMateria);
            exit();
        }
    }

    // Insertar en la base de datos
    $stmt = $conexion->prepare("INSERT INTO actividades 
                              (TituloActividades, Descripcion, ArchivoAdjunto, fecha_inicio, fecha_fin, idcurso, unidad, calificacion_maxima) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssssssii", $titulo, $descripcion, $archivoAdjunto, $fecha_inicio, $fecha_fin, $idMateria, $unidad, $calificacion_maxima);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "Actividad publicada con éxito";
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$idMateria);
            exit();
        } else {
            $_SESSION['error'] = "Error al publicar: " . $conexion->error;
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$idMateria);
            exit();
        }
    } else {
        $_SESSION['error'] = "Error al preparar la consulta: " . $conexion->error;
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$idMateria);
        exit();
    }
}

// Obtener actividades existentes
$queryActividades = "SELECT 
                        idActividades, 
                        TituloActividades, 
                        Descripcion, 
                        ArchivoAdjunto, 
                        fecha_inicio, 
                        fecha_fin, 
                        idcurso, 
                        unidad, 
                        calificacion_maxima
                     FROM actividades 
                     WHERE idcurso = ? 
                     ORDER BY unidad, fecha_inicio DESC";
$stmtActividades = $conexion->prepare($queryActividades);
$stmtActividades->bind_param("s", $idMateria);
$stmtActividades->execute();
$resultActividades = $stmtActividades->get_result();
$actividadesPorUnidad = [];

while ($actividad = $resultActividades->fetch_assoc()) {
    $unidad = $actividad['unidad'];
    if (!isset($actividadesPorUnidad[$unidad])) {
        $actividadesPorUnidad[$unidad] = [];
    }
    $actividadesPorUnidad[$unidad][] = $actividad;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Gestión de <?php echo htmlspecialchars($materia['Nombre']); ?></title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .unit-container {
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            background-color: var(--card-bg);
        }
        
        .unit-title {
            font-size: 1.3em;
            margin-bottom: 15px;
            color: var(--accent-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-list {
            margin-top: 15px;
        }
        
        .activity-item {
            padding: 15px;
            margin-bottom: 10px;
            background-color: var(--bg-color);
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
        
        .btn-action {
            padding: 5px 10px;
            margin-right: 5px;
            font-size: 0.9em;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: var(--card-bg);
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
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
            <li><a href="docente_foro.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="docente_anuncios.php"><i class="fas fa-bullhorn"></i> <span>Anuncios</span></a></li>
            <li class="active"><a href="docente_grupos.php"><i class="fas fa-user-group"></i> <span>Grupos</span></a></li>
            <li><a href="/logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1><?php echo htmlspecialchars($materia['Nombre']); ?></h1>
        <p><?php echo htmlspecialchars($materia['Descripcionmateria']); ?></p>
    </div>
    
    <div class="main-content">
        <?php if (isset($mensaje)): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <button class="btn btn-primary" onclick="openModal()">
            <i class="fas fa-plus"></i> Nueva Actividad
        </button>
        
        <div class="dashboard-card">
            <h3>Unidades de la Materia</h3>
            
            <?php for ($i = 1; $i <= $materia['NumeroUnidades']; $i++): ?>
                <?php 
                $nombreUnidad = $unidades[$i-1]['nombre'] ?? "Unidad $i";
                $actividadesUnidad = $actividadesPorUnidad[$i] ?? [];
                ?>
                <div class="unit-container">
                    <div class="unit-title">
                        <span><?php echo htmlspecialchars($nombreUnidad); ?></span>
                        <button class="btn btn-sm btn-secondary" onclick="openModalForUnit(<?php echo $i; ?>)">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                    
                    <div class="activity-list">
                        <?php if (empty($actividadesUnidad)): ?>
                            <p>No hay actividades en esta unidad.</p>
                        <?php else: ?>
                            <?php foreach ($actividadesUnidad as $actividad): ?>
                                <div class="activity-item">
                                    <div class="activity-title">
                                        <?php echo htmlspecialchars($actividad['TituloActividades']); ?>
                                    </div>
                                    <div class="activity-description">
                                        <?php echo nl2br(htmlspecialchars($actividad['Descripcion'])); ?>
                                    </div>
                                    
                                    <div class="activity-meta">
                                        <span><i class="far fa-calendar-alt"></i> Inicio: <?php echo date('d/m/Y H:i', strtotime($actividad['fecha_inicio'])); ?></span>
                                        <span><i class="far fa-calendar-check"></i> Fin: <?php echo date('d/m/Y H:i', strtotime($actividad['fecha_fin'])); ?></span>
                                        <span><i class="fas fa-star"></i> Calificación máxima: <?php echo $actividad['calificacion_maxima']; ?></span>
                                    </div>
                                    
                                    <?php if ($actividad['ArchivoAdjunto']): ?>
                                        <div class="activity-attachment">
                                            <i class="fas fa-paperclip"></i> 
                                            <a href="<?php echo $actividad['ArchivoAdjunto']; ?>" target="_blank">Archivo adjunto</a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="activity-actions">
                                        <button class="btn btn-action btn-primary" onclick="editActivity(<?php echo $actividad['idActividades']; ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <button class="btn btn-action btn-danger" onclick="deleteActivity(<?php echo $actividad['idActividades']; ?>)">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                        <a href="ver_entregas.php?id=<?php echo $actividad['idActividades']; ?>" class="btn btn-action btn-primary">
        <i class="fas fa-eye"></i> Ver Entregas
    </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Modal para nueva actividad -->
    <div id="activityModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Nueva Actividad</h3>
            
            <form id="activityForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="unidad" id="formUnidad" value="1">
                
                <div class="form-group">
                    <label for="titulo">Título de la actividad:</label>
                    <input type="text" id="titulo" name="titulo" required>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de inicio:</label>
                    <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_fin">Fecha de finalización:</label>
                    <input type="datetime-local" id="fecha_fin" name="fecha_fin" required>
                </div>
                
                <div class="form-group">
                    <label for="calificacion_maxima">Calificación máxima:</label>
                    <input type="number" id="calificacion_maxima" name="calificacion_maxima" min="1" max="100" value="10" required>
                </div>
                
                <div class="form-group">
                    <label for="archivoAdjunto">Archivo adjunto (opcional):</label>
                    <input type="file" id="archivoAdjunto" name="archivoAdjunto">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('activityForm').addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo').value;
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            
            if (titulo.trim() === '') {
                alert('Por favor ingrese un título');
                e.preventDefault();
                return;
            }
            
            if (!fechaInicio || !fechaFin) {
                alert('Debe especificar fechas de inicio y fin');
                e.preventDefault();
                return;
            }
            
            if (new Date(fechaInicio) > new Date(fechaFin)) {
                alert('La fecha de inicio no puede ser posterior a la fecha de fin');
                e.preventDefault();
                return;
            }
        });
        
        function openModal() {
            document.getElementById('activityModal').style.display = 'block';
            document.getElementById('formUnidad').value = "1";
            document.getElementById('modalTitle').innerText = "Nueva Actividad";
            document.getElementById('activityForm').reset();
        }
        
        function openModalForUnit(unitNumber) {
            openModal();
            document.getElementById('formUnidad').value = unitNumber;
            document.getElementById('modalTitle').innerText = "Nueva Actividad para Unidad " + unitNumber;
        }
        
        function closeModal() {
            document.getElementById('activityModal').style.display = 'none';
        }
        
        function editActivity(id) {
            alert('Editar actividad con ID: ' + id + ' (funcionalidad en desarrollo)');
        }
        
        function deleteActivity(id) {
            if (confirm('¿Estás seguro de que deseas eliminar esta actividad?')) {
                fetch('eliminar_actividad.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Eliminado con éxito');
                            location.reload();
                        } else {
                            alert('Error al eliminar: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar');
                    });
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('activityModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
    <script src="/scripts.js"></script>
</body>
</html>