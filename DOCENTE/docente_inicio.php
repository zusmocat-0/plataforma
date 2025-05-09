<?php
session_start();

// Verificar si el usuario está logueado (usando las variables que ya estableces)
if (!isset($_SESSION['idDocente'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Obtener información del docente desde la sesión
$rfcDocente = $_SESSION['idDocente'];
$nombreDocente = $_SESSION['Nombre'] ?? 'Profesor';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Profesor</title>
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
            <li><a href="docente_inicio.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="docente_foro.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="docente_anuncios.php"><i class="fas fa-bullhorn"></i> <span>Anuncios</span></a></li>
            <li><a href="docente_grupos.php"><i class="fas fa-user-group"></i> <span>Grupos</span></a></li>
            <li><a href="/logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>MindBox</h1>
        <p>Bienvenido, <?php echo htmlspecialchars($nombreDocente); ?></p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Bienvenido, Profesor</h3>
            <p>Aquí puedes administrar tus grupos, publicar anuncios y gestionar las calificaciones de tus estudiantes.</p>
            <a href="docente_grupos.php" class="btn">Ver mis grupos</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Actividades pendientes</h3>
            <ul class="task-list">
                <li>Calificar exámenes</li>
                <li>Publicar material para la próxima clase</li>
                <li>Responder consultas en el foro</li>
            </ul>
            <a href="#" class="btn">Ver calendario</a>
        </div>
        
        <div class="dashboard-card">
            <h3>Anuncios recientes</h3>
            <div class="announcement">
                <h4>Reunión de profesores</h4>
                <p>Próxima reunión de departamento.</p>
            </div>
            <div class="announcement">
                <h4>Nuevo material disponible</h4>
                <p>Recursos actualizados para tus clases.</p>
            </div>
            <a href="docente_anuncios.php" class="btn">Ver todos los anuncios</a>
        </div>
    </div>

    <script src="/scripts.js"></script>
</body>
</html>