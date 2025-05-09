<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

$alumno = $_SESSION['usuario'];
require_once '../conexion.php';

// Obtener ID del foro desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: foro_estudiante.php");
    exit();
}

$idForo = $_GET['id'];

// Procesar nuevo comentario si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    $contenido = trim($_POST['comentario']);
    
    if (!empty($contenido)) {
        // El ID del autor es el número de control del alumno
        $idAutor = $alumno['id']; // Asumiendo que 'id' contiene el número de control
        
        // Insertar el comentario en la base de datos
        $query = "INSERT INTO comentario (idForo, idAutor, Contenido, Fecha) 
                 VALUES (?, ?, ?, NOW())";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("sss", $idForo, $idAutor, $contenido);
        
        if ($stmt->execute()) {
            // Recargar la página para mostrar el nuevo comentario
            header("Location: foro_detalle.php?id=" . $idForo);
            exit();
        } else {
            $error = "Error al publicar el comentario: " . $conexion->error;
        }
    } else {
        $error = "El comentario no puede estar vacío";
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
    header("Location: foro_estudiante.php");
    exit();
}

$foro = $resultForo->fetch_assoc();

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
    <title>MindBox - Detalles del Foro</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
    --comment-bg: #34495e;
    
    --primary-dark: #1c2c3f;
    --text-muted: #7f8c8d;
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
        .comment-form textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: 10px;
            resize: vertical;
            background-color: var(--comment-bg);
            color: var(--text-color);
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: var(--primary-dark);
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
        <h1>Foro: <?php echo htmlspecialchars($foro['nombreMateria']); ?></h1>
        <p>Participa en la discusión</p>
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
                        <p style="color: var(--text-muted);">No hay comentarios todavía. Sé el primero en participar.</p>
                    <?php endif; ?>
                </div>
                
                <div class="comment-form">
                    <h4 style="color: var(--primary-color); margin-top: 0;">Añadir comentario</h4>
                    <form method="POST" action="">
                        <textarea name="comentario" placeholder="Escribe tu comentario aquí..." required></textarea>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Publicar comentario
                        </button>
                    </form>
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