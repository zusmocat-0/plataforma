<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

$alumno = $_SESSION['usuario'];

// Obtener anuncios recientes (últimos 5)
$query_anuncios = "SELECT * FROM anuncios ORDER BY fecha DESC LIMIT 5";
$result_anuncios = $conexion->query($query_anuncios);
$anuncios = [];

while ($row = $result_anuncios->fetch_assoc()) {
    $anuncios[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - <?php echo htmlspecialchars($alumno['nombre']); ?></title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .announcement-item {
            background-color: var(--secondary-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .announcement-title {
            font-weight: bold;
            color: var(--text-color);
        }
        
        .announcement-date {
            color: var(--dark-gray);
            font-size: 0.9em;
        }
        
        .announcement-content {
            margin-bottom: 10px;
            color: var(--text-color);
        }
        
        .announcement-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 4px;
            margin-top: 10px;
            display: block;
        }
        
        .dashboard-card {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            <li><a href="docente_calif.php"><i class="fas fa-check-circle"></i> <span>Calificaciones</span></a></li>
            <li><a href="docente_grupos.php"><i class="fas fa-user-group"></i> <span>Grupos</span></a></li>
            <li><a href="/logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Bienvenido, <?php echo htmlspecialchars($alumno['nombre']); ?></h1>
        <p><?php echo htmlspecialchars($alumno['carrera']); ?> - Semestre <?php echo htmlspecialchars($alumno['semestre']); ?></p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Anuncios Recientes</h3>
            
            <?php if (count($anuncios) > 0): ?>
                <?php foreach ($anuncios as $anuncio): ?>
                    <div class="announcement-item">
                        <div class="announcement-header">
                            <div class="announcement-title"><?php echo htmlspecialchars($anuncio['anuncio']); ?></div>
                            <div class="announcement-date"><?php echo date('d/m/Y H:i', strtotime($anuncio['fecha'])); ?></div>
                        </div>
                        <div class="announcement-content">
                            <?php if (!empty($anuncio['archivo_adjunto'])): ?>
                                <?php 
                                $extension = strtolower(pathinfo($anuncio['archivo_adjunto'], PATHINFO_EXTENSION));
                                $esImagen = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
                                ?>
                                <?php if ($esImagen): ?>
                                    <img src="../uploads/anuncios/<?php echo htmlspecialchars($anuncio['archivo_adjunto']); ?>"
                                         alt="Imagen del anuncio"
                                         class="announcement-image">
                                <?php else: ?>
                                    <a href="../uploads/anuncios/<?php echo htmlspecialchars($anuncio['archivo_adjunto']); ?>" 
                                       class="btn" 
                                       download
                                       style="margin-top: 10px;">
                                        <i class="fas fa-download"></i> Descargar archivo adjunto
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay anuncios recientes.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="/scripts.js"></script>
</body>
</html>