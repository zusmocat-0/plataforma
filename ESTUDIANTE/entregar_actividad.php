<?php

session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit();
}

// Verificar que se haya proporcionado un ID de actividad
if (!isset($_GET['id'])) {
    header("Location: cursos_estudiante.php");
    exit();
}

require_once '../conexion.php';

$idActividad = $_GET['id'];
$idAlumno = $_SESSION['usuario']['id'];

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
    header("Location: cursos_estudiante.php?error=Actividad no encontrada");
    exit();
}

$actividad = $resultActividad->fetch_assoc();

// Verificar si hay un mensaje de error
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Obtener información de entrega del alumno
$entregas = $actividad['Entregas'] ? explode(",", $actividad['Entregas']) : [];
$archivos = $actividad['archivo_entregas'] ? explode(",", $actividad['archivo_entregas']) : [];
$indice = array_search($idAlumno, $entregas);
$haEntregado = $indice !== false;
$archivoEntrega = $haEntregado ? $archivos[$indice] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Ver Actividad</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        
        .activity-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .delivery-info {
            margin-top: 30px;
            padding: 15px;
            background-color: var(--light-bg);
            border-radius: 5px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 10px;
            margin-top: 10px;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
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
        
        .dates {
            display: flex;
            gap: 20px;
            margin: 15px 0;
        }
        
        .date-box {
            flex: 1;
            padding: 10px;
            background-color: var(--light-bg);
            border-radius: 5px;
        }
        
        /* Estilos para el área de drop */
        .drop-area {
            border: 2px dashed var(--accent-color);
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
            transition: all 0.3s;
        }
        
        .drop-area.highlight {
            background-color: rgba(0,0,0,0.05);
            border-color: var(--primary-dark);
        }
        
        .upload-btn {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .file-info {
            margin-top: 15px;
        }
        
        .file-info img {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
        }
        
        #fileInput {
            display: none;
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
        <h1><?php echo htmlspecialchars($actividad['TituloActividades']); ?></h1>
        <p><?php echo htmlspecialchars($actividad['nombre_materia']); ?></p>
    </div>
    
    <div class="main-content">
        <div class="activity-container">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="dates">
                <div class="date-box">
                    <strong><i class="far fa-calendar-alt"></i> Inicio:</strong>
                    <?php echo (new DateTime($actividad['fecha_inicio']))->format('d/m/Y H:i'); ?>
                </div>
                <div class="date-box">
                    <strong><i class="far fa-calendar-check"></i> Fin:</strong>
                    <?php echo (new DateTime($actividad['fecha_fin']))->format('d/m/Y H:i'); ?>
                </div>
            </div>
            
            <div class="activity-description">
                <h3>Descripción</h3>
                <p><?php echo nl2br(htmlspecialchars($actividad['Descripcion'])); ?></p>
            </div>
            
            <?php if ($actividad['ArchivoAdjunto']): ?>
                <div class="activity-attachment">
                    <h3>Material de apoyo</h3>
                    <a href="<?php echo htmlspecialchars($actividad['ArchivoAdjunto']); ?>" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-download"></i> Descargar
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="delivery-info">
                <h3>Tu entrega</h3>
                
                <?php if ($haEntregado): ?>
                    <p><strong>Estado:</strong> Entregado</p>
                    <?php if ($archivoEntrega && file_exists("../entregas/".$archivoEntrega)): ?>
                        <p><strong>Archivo:</strong> 
                            <a href="../entregas/<?php echo htmlspecialchars($archivoEntrega); ?>" target="_blank">
                                <i class="fas fa-file-download"></i> Ver entrega
                            </a>
                        </p>
                        <p><strong>Fecha de entrega:</strong> 
                            <?php echo date('d/m/Y H:i', filemtime("../entregas/".$archivoEntrega)); ?>
                        </p>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> El archivo de entrega no se encuentra disponible
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p><strong>Estado:</strong> No entregado</p>
                <?php endif; ?>
                
                <form id="uploadForm" action="procesar_entrega.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id_actividad" value="<?php echo $idActividad; ?>">
                    
                    <div class="drop-area" id="dropArea">
                        <i class="fas fa-cloud-upload-alt fa-3x"></i>
                        <p>Arrastra tu archivo aquí o haz clic para seleccionar</p>
                        <label for="fileInput" class="upload-btn">
                            <i class="fas fa-folder-open"></i> Seleccionar archivo
                        </label>
                        <input type="file" id="fileInput" name="archivo" required>
                        <div class="file-info" id="fileInfo"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> <?php echo $haEntregado ? 'Actualizar entrega' : 'Enviar entrega'; ?>
                    </button>
                </form>
            </div>
            
            <div class="activity-actions">
                <a href="inicio_curso.php?materia=<?php echo $actividad['idcurso']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a actividades
                </a>
            </div>
        </div>
    </div>
    
    <script src="/scripts.js"></script>
    <script>
        // Drag & Drop functionality
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const uploadForm = document.getElementById('uploadForm');
        
        // Evitar el comportamiento por defecto
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Resaltar el área de drop
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('highlight');
        }
        
        function unhighlight() {
            dropArea.classList.remove('highlight');
        }
        
        // Manejar archivos soltados
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            updateFileInfo(files[0]);
        }
        
        // Manejar archivos seleccionados
        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                updateFileInfo(this.files[0]);
            }
        });
        
        // Mostrar información del archivo
        function updateFileInfo(file) {
            fileInfo.innerHTML = `
                <p><strong>Archivo seleccionado:</strong> ${file.name}</p>
                <p><strong>Tamaño:</strong> ${formatFileSize(file.size)}</p>
                ${file.type.startsWith('image/') ? 
                    `<img src="${URL.createObjectURL(file)}" class="file-thumbnail">` : 
                    `<i class="fas fa-file fa-3x"></i>`}
            `;
        }
        
        // Formatear tamaño de archivo
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>