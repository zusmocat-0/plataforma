<?php
session_start();

// Verificar sesión de docente
if (!isset($_SESSION['idDocente'])) {
    header("Location: /index.php");
    exit();
}

$docenteRFC = $_SESSION['idDocente'];
require_once '../conexion.php';

// Obtener ID del foro desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: docente_foro.php");
    exit();
}

$idForo = $_GET['id'];

// Procesar eliminación de comentario si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_comentario'])) {
    $idComentario = $_POST['id_comentario'];
    
    // Verificar que el docente es el autor del foro o tiene permisos
    $queryVerificar = "SELECT f.idAutor FROM foro f WHERE f.idForo = ?";
    $stmtVerificar = $conexion->prepare($queryVerificar);
    $stmtVerificar->bind_param("s", $idForo);
    $stmtVerificar->execute();
    $resultVerificar = $stmtVerificar->get_result();
    
    if ($foro = $resultVerificar->fetch_assoc()) {
        if ($foro['idAutor'] == $docenteRFC) {
            // Eliminar el comentario
            $queryEliminar = "DELETE FROM comentario WHERE idForo = ? AND Fecha = ?";
            $stmtEliminar = $conexion->prepare($queryEliminar);
            $stmtEliminar->bind_param("ss", $idForo, $idComentario);
            
            if ($stmtEliminar->execute()) {
                // Recargar la página para actualizar los comentarios
                header("Location: detalle_foro_docente.php?id=" . $idForo);
                exit();
            } else {
                $error = "Error al eliminar el comentario: " . $conexion->error;
            }
        } else {
            $error = "No tienes permiso para eliminar comentarios de este foro";
        }
    } else {
        $error = "Foro no encontrado";
    }
}

// Obtener información del foro
$queryForo = "SELECT f.idForo, f.Tituloforo, f.Contenido, f.Fecha, f.Unidad,
                     m.Nombre AS nombreMateria, m.idMateria,
                     d.Nombre AS nombreDocente, d.`idDocente(RFC)` AS rfcDocente
              FROM foro f
              JOIN materia m ON f.idMateria = m.idMateria
              JOIN docente d ON f.idAutor = d.`idDocente(RFC)`
              WHERE f.idForo = ?";
$stmtForo = $conexion->prepare($queryForo);
$stmtForo->bind_param("s", $idForo);
$stmtForo->execute();
$resultForo = $stmtForo->get_result();

if ($resultForo->num_rows === 0) {
    header("Location: docente_foro.php");
    exit();
}

$foro = $resultForo->fetch_assoc();

// Verificar que el docente es el autor del foro
$esAutor = ($foro['rfcDocente'] == $docenteRFC);

// Obtener todos los comentarios del foro
$queryComentarios = "SELECT c.idForo, c.idAutor, c.Contenido, c.Fecha,
                            IFNULL(a.Nombre, d.Nombre) AS nombreAutor,
                            IF(a.idAlumno IS NOT NULL, 'Estudiante', 'Docente') AS tipoAutor
                     FROM comentario c
                     LEFT JOIN alumno a ON c.idAutor = a.idAlumno AND a.idAlumno IS NOT NULL
                     LEFT JOIN docente d ON c.idAutor = d.`idDocente(RFC)` AND d.`idDocente(RFC)` IS NOT NULL
                     WHERE c.idForo = ?
                     ORDER BY c.Fecha ASC";
$stmtComentarios = $conexion->prepare($queryComentarios);
$stmtComentarios->bind_param("s", $idForo);
$stmtComentarios->execute();
$resultComentarios = $stmtComentarios->get_result();
$comentarios = $resultComentarios->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Detalles del Foro (Docente)</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
        --comment-bg: #34495e;
        --primary-dark: #1c2c3f;
        --text-muted: #7f8c8d;
        --danger-color: #e74c3c;
    }
     
    .comments-section {
        margin-top: 30px;
    }
    
    .comment-list {
        margin-top: 20px;
    }
    
    .comment-item {
        background-color: var(--comment-bg);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid var(--border-color);
        position: relative;
    }
    
    .comment-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 0.85em;
        color: var(--text-muted);
    }
    
    .comment-author {
        font-weight: bold;
        color: var(--accent-color);
    }
    
    .comment-type {
        background-color: var(--primary-dark);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8em;
        margin-left: 5px;
    }
    
    .comment-actions {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    
    .btn-delete {
        background-color: var(--danger-color);
        color: white;
        border: none;
        padding: 3px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.8em;
    }
    
    .btn-delete:hover {
        background-color: #c0392b;
    }
    
    .alert {
        padding: 10px 15px;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Estilos para el modo oscuro */
    body.dark-mode .alert-error {
        background-color: #4a1c24;
        color: #f8d7da;
        border-color: #5c2c34;
    }
    
    body.dark-mode .btn-delete {
        background-color: #c0392b;
    }
    
    body.dark-mode .btn-delete:hover {
        background-color: #a5281b;
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
            <li class="active"><a href="docente_foro.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="docente_anuncios.php"><i class="fas fa-bullhorn"></i> <span>Anuncios</span></a></li>
            <li><a href="docente_grupos.php"><i class="fas fa-user-group"></i> <span>Grupos</span></a></li>
            <li><a href="/logout.php"><i class="fas fa-right-from-bracket"></i> <span>Cerrar sesión</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Foro: <?php echo htmlspecialchars($foro['nombreMateria']); ?></h1>
        <p>Gestión de discusión - Vista docente</p>
    </div>
    
    <div class="main-content">
        <div class="forum-container">
            <div class="forum-header">
                <h2 class="forum-title"><?php echo htmlspecialchars($foro['Tituloforo']); ?></h2>
                <div class="forum-meta">
                    <span>Publicado por: <?php echo htmlspecialchars($foro['nombreDocente']); ?> (Docente)</span>
                    <span>Unidad <?php echo htmlspecialchars($foro['Unidad']); ?> • <?php echo date('d/m/Y H:i', strtotime($foro['Fecha'])); ?></span>
                </div>
                <div class="forum-content">
                    <?php echo nl2br(htmlspecialchars($foro['Contenido'])); ?>
                </div>
            </div>
            
            <div class="comments-section">
                <h3>Comentarios</h3>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="comment-list">
                    <?php if (!empty($comentarios)): ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="comment-item">
                                <?php if ($esAutor): ?>
                                <div class="comment-actions">
                                    <form method="POST" action="" onsubmit="return confirm('¿Estás seguro de eliminar este comentario?');">
                                        <input type="hidden" name="id_comentario" value="<?php echo htmlspecialchars($comentario['Fecha']); ?>">
                                        <button type="submit" name="eliminar_comentario" class="btn-delete" title="Eliminar comentario">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                                
                                <div class="comment-meta">
                                    <div>
                                        <span class="comment-author"><?php echo htmlspecialchars($comentario['nombreAutor']); ?></span>
                                        <span class="comment-type"><?php echo htmlspecialchars($comentario['tipoAutor']); ?></span>
                                    </div>
                                    <span><?php echo date('d/m/Y H:i', strtotime($comentario['Fecha'])); ?></span>
                                </div>
                                <div class="comment-content">
                                    <?php echo nl2br(htmlspecialchars($comentario['Contenido'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-muted);">No hay comentarios en este foro.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/scripts.js"></script>
    <script>
        // Función para cambiar entre modo claro/oscuro
        function toggleTheme() {
            document.body.classList.toggle('dark-mode');
            
            // Guardar preferencia en localStorage
            const isDarkMode = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDarkMode);
        }
        
        // Aplicar tema al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
            }
        });
    </script>
</body>
</html>