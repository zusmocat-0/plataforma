<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../conexion.php';

// Consulta para obtener todos los foros existentes
$queryForos = "SELECT f.idForo, f.idMateria, f.idAutor, f.Tituloforo, f.Contenido, f.Unidad, f.Fecha, 
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
                ORDER BY f.Fecha DESC";
$resultForos = $conexion->query($queryForos);
$foros = [];

if ($resultForos) {
    while ($row = $resultForos->fetch_assoc()) {
        $foros[] = [
            'id' => $row['idForo'],
            'materia' => $row['idMateria'],
            'nombreMateria' => $row['nombreMateria'],
            'autor' => $row['idAutor'],
            'nombreAutor' => $row['nombreAutor'],
            'titulo' => $row['Tituloforo'],
            'contenido' => $row['Contenido'],
            'unidad' => $row['Unidad'],
            'fecha' => $row['Fecha']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Administrar Foros</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .forum-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .forum-item {
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .forum-item:hover {
            transform: translateY(-5px);
        }
        
        .forum-item h4 {
            margin-top: 0;
            color: var(--accent-color);
        }
        
        .forum-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        
        .forum-author {
            color: var(--text-muted);
            font-style: italic;
        }
        
        .forum-content {
            margin-bottom: 15px;
            padding: 10px;
            background-color: var(--secondary-color);
            border-radius: 5px;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .btn-foro {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn-foro:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
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
            <li class="active"><a href="definir_horarios.php"><i class="fas fa-calendar"></i> <span>Definir Horarios</span></a></li>
            <li><a href="funciones_especiales.php"><i class="fas fa-star"></i> <span>Funciones adicionales</span></a></li>
            <li><a href="/index"><i class="fas fa-backward"></i> <span>Log-out</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Administrar Foros</h1>
        <p>Gestiona todos los foros de discusión de la plataforma</p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Foros Existentes</h3>
            <p>Listado de todos los foros creados en la plataforma.</p>
            
            <?php if (count($foros) > 0): ?>
                <div class="forum-list">
                    <?php foreach ($foros as $foro): ?>
                        <div class="forum-item">
                            <h4><?php echo htmlspecialchars($foro['titulo']); ?></h4>
                            <div class="forum-meta">
                                <span><strong>Materia:</strong> <?php echo htmlspecialchars($foro['nombreMateria']); ?> (<?php echo htmlspecialchars($foro['materia']); ?>)</span>
                                <span><strong>Unidad:</strong> <?php echo htmlspecialchars($foro['unidad']); ?></span>
                            </div>
                            <div class="forum-meta">
                                <span class="forum-author">Creado por: <?php echo htmlspecialchars($foro['nombreAutor']); ?> (<?php echo htmlspecialchars($foro['autor']); ?>)</span>
                                <span><?php echo date('d/m/Y H:i', strtotime($foro['fecha'])); ?></span>
                            </div>
                            <div class="forum-content">
                                <?php echo nl2br(htmlspecialchars(substr($foro['contenido'], 0, 150) . (strlen($foro['contenido']) > 150 ? '...' : ''))); ?>
                            </div>
                            <div class="actions">
                                <a href="foro_ver.php?id=<?php echo $foro['id']; ?>" class="btn-foro">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <a href="foro_eliminar.php?id=<?php echo $foro['id']; ?>" class="btn-foro btn-danger" onclick="return confirm('¿Estás seguro de eliminar este foro?');">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No hay foros creados actualmente en la plataforma.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="/scripts.js"></script>
</body>
</html>