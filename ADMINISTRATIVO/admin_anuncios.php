<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

// Incluir el archivo de conexión - Asegúrate de que la ruta sea correcta
require_once('../conexion.php');

// Verificar si la conexión se estableció correctamente
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Procesar el formulario de anuncio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcementText'])) {
    $contenido = trim($_POST['announcementText']);
    $idAdmin = $_SESSION['idAdmin'];
    
    // Procesar archivo adjunto si existe
    $archivoAdjunto = null;
    if (isset($_FILES['archivoAdjunto']) && $_FILES['archivoAdjunto']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = basename($_FILES['archivoAdjunto']['name']);
        $rutaTemporal = $_FILES['archivoAdjunto']['tmp_name'];
        $directorioDestino = '../uploads/anuncios/';
        
        // Crear directorio si no existe
        if (!file_exists($directorioDestino)) {
            mkdir($directorioDestino, 0777, true);
        }
        
        $rutaDestino = $directorioDestino . uniqid() . '_' . $nombreArchivo;
        
        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
            $archivoAdjunto = $rutaDestino;
        }
    }
    
    // Insertar el anuncio en la base de datos
    $stmt = $conexion->prepare("INSERT INTO anuncios (idAdministrativo_RFC, anuncio, archivo_adjunto) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $idAdmin, $contenido, $archivoAdjunto);
    
    if ($stmt->execute()) {
        $mensaje = "Anuncio publicado con éxito";
    } else {
        $error = "Error al publicar el anuncio: " . $conexion->error;
    }
}

// Obtener todos los anuncios para mostrar
$query = "SELECT a.*, ad.Nombre FROM anuncios a 
          JOIN administrativo ad ON a.idAdministrativo_RFC = ad.`idAdministrativo(RFC)` 
          ORDER BY a.fecha DESC";
$result = $conexion->query($query);

// Verificar si la consulta fue exitosa
if (!$result) {
    die("Error en la consulta: " . $conexion->error);
}

$anuncios = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Administración de Anuncios</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        <h1>Anuncios</h1>
        <p>Gestión de comunicados institucionales</p>
    </div>
    
    <div class="main-content">
        <div class="announcement-container">
            <!-- Sección para crear nuevo anuncio -->
            <div class="create-announcement dashboard-card">
                <h3><i class="fas fa-plus-circle"></i> Crear Nuevo Anuncio</h3>
                <?php if (isset($mensaje)): ?>
                    <div class="alert alert-success"><?php echo $mensaje; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form id="announcementForm" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="announcementText">Contenido del anuncio</label>
                        <textarea id="announcementText" name="announcementText" placeholder="Escribe aquí tu anuncio..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="archivoAdjunto">Archivo adjunto (opcional)</label>
                        <input type="file" id="archivoAdjunto" name="archivoAdjunto">
                    </div>
                    <button type="submit" class="btn btn-publish">
                        <i class="fas fa-paper-plane"></i> Publicar Anuncio
                    </button>
                </form>
            </div>
            
            <!-- Sección para administrar anuncios anteriores -->
            <div class="announcement-history dashboard-card">
                <h3><i class="fas fa-history"></i> Anuncios Anteriores</h3>
                <div class="announcement-list" id="announcementList">
                    <?php if (empty($anuncios)): ?>
                        <p>No hay anuncios publicados aún.</p>
                    <?php else: ?>
                        <?php foreach ($anuncios as $anuncio): ?>
                            <div class="announcement-item">
                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($anuncio['anuncio'])); ?>
                                </div>
                                <?php if ($anuncio['archivo_adjunto']): ?>
                                    <div class="announcement-attachment">
                                        <i class="fas fa-paperclip"></i> 
                                        <a href="<?php echo $anuncio['archivo_adjunto']; ?>" target="_blank">Archivo adjunto</a>
                                    </div>
                                <?php endif; ?>
                                <div class="announcement-meta">
                                    <span><i class="far fa-calendar-alt"></i> Publicado: <?php echo date('d/m/Y H:i', strtotime($anuncio['fecha'])); ?></span>
                                    <span><i class="fas fa-user"></i> Por: <?php echo htmlspecialchars($anuncio['Nombre']); ?></span>
                                </div>
                                <div class="announcement-actions">
                                    <button class="btn btn-action" onclick="editarAnuncio(<?php echo $anuncio['idAnuncio']; ?>)">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="btn btn-action btn-danger" onclick="eliminarAnuncio(<?php echo $anuncio['idAnuncio']; ?>)">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para manejar el envío del formulario
        document.getElementById('announcementForm').addEventListener('submit', function(e) {
            const announcementText = document.getElementById('announcementText').value;
            
            if (announcementText.trim() === '') {
                alert('Por favor escribe un anuncio antes de publicar');
                e.preventDefault();
            }
        });
        
        // Funciones para el tema claro/oscuro
        function toggleTheme() {
            const html = document.documentElement;
            const themeToggle = document.querySelector('.theme-toggle i');
            
            if (html.classList.contains('light-mode')) {
                html.classList.remove('light-mode');
                themeToggle.classList.remove('fa-sun');
                themeToggle.classList.add('fa-moon');
                localStorage.setItem('theme', 'dark');
            } else {
                html.classList.add('light-mode');
                themeToggle.classList.remove('fa-moon');
                themeToggle.classList.add('fa-sun');
                localStorage.setItem('theme', 'light');
            }
        }
        
        // Verificar el tema al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeToggle = document.querySelector('.theme-toggle i');
            
            if (savedTheme === 'light') {
                document.documentElement.classList.add('light-mode');
                themeToggle.classList.remove('fa-moon');
                themeToggle.classList.add('fa-sun');
            }
        });
        
        // Funciones para editar y eliminar anuncios
        function editarAnuncio(idAnuncio) {
            if (confirm('¿Deseas editar este anuncio?')) {
                // Aquí iría la lógica para editar el anuncio
                // Podrías abrir un modal con el formulario de edición
                alert('Funcionalidad de edición en desarrollo para el anuncio ID: ' + idAnuncio);
            }
        }
        
        function eliminarAnuncio(idAnuncio) {
            if (confirm('¿Estás seguro de que deseas eliminar este anuncio?')) {
                // Enviar solicitud AJAX para eliminar el anuncio
                fetch('eliminar_anuncio.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'idAnuncio=' + idAnuncio
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Anuncio eliminado con éxito');
                        location.reload();
                    } else {
                        alert('Error al eliminar el anuncio: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el anuncio');
                });
            }
        }
    </script>
    <script src="/scripts.js"></script>
</body>
</html>