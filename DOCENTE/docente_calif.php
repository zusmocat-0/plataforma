<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Calificaciones</title>
    <link rel="stylesheet" href="styles.css">
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
            <li><a href="docente_calif.php"><i class="fas fa-check-circle"></i> <span>Calificaciones</span></a></li>
            <li><a href="docente_grupos.php"><i class="fas fa-user-group"></i> <span>Grupos</span></a></li>
            <li><a href="/logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Calificaciones</h1>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Gestor de Calificaciones</h3>
            <p>Registra y administra las calificaciones de tus estudiantes.</p>
            <!-- Contenido de calificaciones puede ser añadido aquí -->
        </div>
    </div>

    <script src="scripts.js"></script>
</body>
</html>