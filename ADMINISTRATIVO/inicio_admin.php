<?php
    session_start();
    if (!isset($_SESSION['idAdmin'])) {
        header("Location: ../index.php");
        exit();
    }
    
    require_once '../conexion.php';
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
        /* Estilos adicionales para el panel administrativo */
        .admin-dropdown {
            position: relative;
            display: inline-block;
            width: 100%;
            margin-bottom: 1.5rem;
        }
        
        .admin-dropbtn {
            width: 100%;
            padding: 1rem;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-dropbtn:hover {
            background-color: #2980b9;
        }
        
        .admin-dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--secondary-color);
            min-width: 100%;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 0 0 4px 4px;
            overflow: hidden;
        }
        
        .admin-dropdown-content a {
            color: var(--text-color);
            padding: 1rem;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .admin-dropdown-content a:hover {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--accent-color);
        }
        
        .admin-dropdown-content a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .show {
            display: block;
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
            <li><a href="administrarforo_administrador.php"><i class="fas fa-comments"></i> <span>Administrar Foros</span></a></li>
            <li><a href="administrador_documentos.php"><i class="fas fa-file-alt"></i> <span>Gestión Documental</span></a></li>
            <li><a href="admin_cursos.php"><i class="fas fa-book"></i> <span>Cursos</span></a></li>
            <li><a href="admin_anuncios.php"><i class="fas fa-comment-dots"></i> <span>Anuncios</span></a></li>
            <li><a href="definir_horarios.php"><i class="fas fa-star"></i> <span>Horarios</span></a></li>
            <li><a href="funciones_especiales.php"><i class="fas fa-star"></i> <span>Funciones adicionales</span></a></li>
            <li><a href="/index"><i class="fas fa-backward"></i> <span>Log-out</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Panel Administrativo</h1>
        <p>Gestión completa del sistema</p>
    </div>

    <script src="/scripts.js"></script>
</body>
</php>