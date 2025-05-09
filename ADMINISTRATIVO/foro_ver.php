<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../conexion.php';

// Obtener el ID del foro desde la URL
$idForo = isset($_GET['id']) ? $_GET['id'] : null;

if (!$idForo) {
    header("Location: administrarforo_administrador.php");
    exit();
}

// Consulta para obtener los detalles del foro
$queryForo = "SELECT f.idForo, f.idMateria, f.idAutor, f.Tituloforo, f.Contenido, f.Unidad, f.Fecha, 
                m.Nombre AS nombreMateria, 
                CASE 
                    WHEN d.Nombre IS NOT NULL THEN d.Nombre
                    WHEN a.Nombre IS NOT NULL THEN a.Nombre
                    ELSE 'Usuario desconocido'
                END AS nombreAutor
                FROM foro f
                LEFT JOIN materia m ON f.idMateria = m.idMateria
                LEFT JOIN docente d ON f.idAutor = d.`idDocente(RFC)`
                LEFT JOIN administrativo a ON f.idAutor = a.`idAdministrativo(RFC)`
                WHERE f.idForo = ?";
$stmtForo = $conexion->prepare($queryForo);
$stmtForo->bind_param("s", $idForo);
$stmtForo->execute();
$resultForo = $stmtForo->get_result();

if ($resultForo->num_rows === 0) {
    header("Location: administrarforo_administrador.php");
    exit();
}

$foro = $resultForo->fetch_assoc();

// Consulta para obtener los comentarios del foro
$queryComentarios = "SELECT c.idAutor, c.Contenido, c.Fecha,
                    CASE 
                        WHEN d.Nombre IS NOT NULL THEN d.Nombre
                        WHEN a.Nombre IS NOT NULL THEN a.Nombre
                        WHEN al.Nombre IS NOT NULL THEN al.Nombre
                        ELSE 'Usuario desconocido'
                    END AS nombreAutor
                    FROM comentario c
                    LEFT JOIN docente d ON c.idAutor = d.`idDocente(RFC)`
                    LEFT JOIN administrativo a ON c.idAutor = a.`idAdministrativo(RFC)`
                    LEFT JOIN alumno al ON c.idAutor = al.idAlumno
                    WHERE c.idForo = ?
                    ORDER BY c.Fecha ASC";
$stmtComentarios = $conexion->prepare($queryComentarios);
$stmtComentarios->bind_param("s", $idForo);
$stmtComentarios->execute();
$resultComentarios = $stmtComentarios->get_result();
$comentarios = [];

while ($row = $resultComentarios->fetch_assoc()) {
    $comentarios[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Ver Foro</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .forum-container {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .forum-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .forum-title {
            color: var(--accent-color);
            margin: 0;
        }
        
        .forum-meta {
            display: flex;
            flex-direction: column;
            font-size: 0.9em;
            color: var(--text-muted);
        }
        
        .forum-content {
            padding: 15px;
            background-color: var(--secondary-color);
            border-radius: 5px;
            margin-bottom: 20px;
            white-space: pre-wrap;
        }
        
        .comments-section {
            margin-top: 30px;
        }
        
        .comment {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        
        .comment-author {
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .comment-date {
            color: var(--text-muted);
        }
        
        .comment-content {
            white-space: pre-wrap;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            background-color: var(--dark-gray);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background-color: var(--text-muted);
        }
        
        .back-button i {
            margin-right: 8px;
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
            <li><a href="/ADMINISTRATIVO/inicio_admin.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="administrador_gestionar usuarios.php"><i class="fas fa-user-plus"></i> <span>Gestionar Usuarios</span></a></li>
            <li class="active"><a href="administrarforo_administrador.php"><i class="fas fa-comments"></i> <span>Administrar Foros</span></a></li>
            <li><a href="administrador_documentos.php"><i class="fas fa-file-alt"></i> <span>Gestión Documental</span></a></li>
            <li><a href="admin_cursos.php"><i class="fas fa-book"></i> <span>Cursos</span></a></li>
            <li><a href="admin_anuncios.php"><i class="fas fa-comment-dots"></i> <span>Anuncios</span></a></li>
            <li><a href="/index"><i class="fas fa-backward"></i> <span>Log-out</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Visualización de Foro</h1>
        <p>Detalles del foro seleccionado</p>
    </div>
    
    <div class="main-content">
        <a href="administrarforo_administrador.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver a la lista de foros
        </a>
        
        <div class="forum-container">
            <div class="forum-header">
                <h2 class="forum-title"><?php echo htmlspecialchars($foro['Tituloforo']); ?></h2>
                <div class="forum-meta">
                    <span><strong>Materia:</strong> <?php echo htmlspecialchars($foro['nombreMateria']); ?> (<?php echo htmlspecialchars($foro['idMateria']); ?>)</span>
                    <span><strong>Unidad:</strong> <?php echo htmlspecialchars($foro['Unidad']); ?></span>
                    <span><strong>Autor:</strong> <?php echo htmlspecialchars($foro['nombreAutor']); ?> (<?php echo htmlspecialchars($foro['idAutor']); ?>)</span>
                    <span><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($foro['Fecha'])); ?></span>
                </div>
            </div>
            
            <div class="forum-content">
                <?php echo nl2br(htmlspecialchars($foro['Contenido'])); ?>
            </div>
        </div>
        
        <div class="comments-section">
            <h3>Comentarios</h3>
            
            <?php if (count($comentarios) > 0): ?>
                <?php foreach ($comentarios as $comentario): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <span class="comment-author"><?php echo htmlspecialchars($comentario['nombreAutor']); ?></span>
                            <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comentario['Fecha'])); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comentario['Contenido'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay comentarios en este foro.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="/scripts.js"></script>
</body>
</html>