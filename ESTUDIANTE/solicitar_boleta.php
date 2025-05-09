<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

$alumno = $_SESSION['usuario'];
$error = '';
$success = '';
$periodos_disponibles = [];
$boleta_html = '';

// Obtener lista de periodos disponibles
$directorio_base = '../uploads/historial_academico/';

if (is_dir($directorio_base)) {
    $archivos = scandir($directorio_base);
    
    foreach ($archivos as $archivo) {
        if ($archivo != '.' && $archivo != '..' && is_dir($directorio_base . $archivo)) {
            // Verificar si existe la boleta para este alumno en el periodo
            $nombre_archivo = $alumno['id'] . '_' . str_replace('-', '_', $archivo) . '.html';
            $ruta_archivo = $directorio_base . $archivo . '/' . $nombre_archivo;
            
            if (file_exists($ruta_archivo)) {
                $periodos_disponibles[] = [
                    'nombre' => str_replace('_', '-', $archivo),
                    'ruta' => $ruta_archivo
                ];
            }
        }
    }
}

// Ordenar periodos de más reciente a más antiguo
usort($periodos_disponibles, function($a, $b) {
    return strtotime(str_replace('-', '-01-', $b['nombre'])) - strtotime(str_replace('-', '-01-', $a['nombre']));
});

// Procesar solicitud para ver boleta
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['periodo'])) {
    $periodo_seleccionado = $_GET['periodo'];
    $encontrado = false;
    
    foreach ($periodos_disponibles as $periodo) {
        if ($periodo['nombre'] === $periodo_seleccionado) {
            $boleta_html = file_get_contents($periodo['ruta']);
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        $error = "No se encontró la boleta para el periodo seleccionado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Solicitar Boletas</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>

        .boletas-container {
            max-width: 1000px;
            margin: 30px auto;
            background-color: var(--secondary-color);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .boletas-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .materia-header {
    background-color: --secondary-color;
    padding: 10px;
    border-radius: 3px;
}
        .boletas-header h2 {
            color: var(--accent-color);
            margin-bottom: 10px;
        }
        
        .periodos-list {
            margin-bottom: 30px;
        }
        
        .periodo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .periodo-item:hover {
            border-color: var(--accent-color);
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .periodo-info {
            flex-grow: 1;
        }
        
        .periodo-info h4 {
            margin: 0 0 5px 0;
            color: var(--text-color);
        }
        
        .periodo-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-view {
            padding: 8px 15px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-view:hover {
            background-color: #0056b3;
        }
        
        .btn-download {
            padding: 8px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-download:hover {
            background-color: #218838;
        }
        
        .boleta-preview {
            margin-top: 30px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
            background-color: white;
            position: relative;
        }
        
        .boleta-actions {
            position: sticky;
            top: 0;
            background-color: --primary-color;
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: center;
            gap: 10px;
            z-index: 10;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 6px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 6px solid #c3e6cb;
        }
        
        .no-boletas {
            text-align: center;
            padding: 30px;
            color: var(--dark-gray);
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
            <li><a href="inicio_estudiante.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="cursos_estudiante.php"><i class="fas fa-book"></i> <span>Cursos</span></a></li>
            <li><a href="calendario_estudiante.php"><i class="fas fa-calendar-alt"></i> <span>Calendario</span></a></li>
            <li><a href="avance_reticular.php"><i class="fas fa-tasks"></i> <span>Avance Reticular</span></a></li>
            <li><a href="foro_estudiante.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li class="active"><a href="solicitar_boletas.php"><i class="fas fa-file-alt"></i> <span>Solicitar Boletas</span></a></li>
            <li><a href="servicios_estudiante.php"><i class="fas fa-school"></i> <span>Servicios Escolares</span></a></li>
            <li><a href="info_estudiante.php"><i class="fas fa-user"></i> <span>Información personal</span></a></li>
            <li><a href="\logout.php"><i class="fas fa-right-from-bracket"></i> <span>Cerrar sesión</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Solicitar Boletas de Calificaciones</h1>
        <p>Consulta y descarga tus boletas de periodos anteriores</p>
    </div>
    
    <div class="main-content">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="boletas-container">
            <div class="boletas-header">
                <h2><i class="fas fa-file-alt"></i> Boletas Disponibles</h2>
                <p>Selecciona un periodo para ver o descargar tu boleta</p>
            </div>
            
            <?php if (empty($periodos_disponibles)): ?>
                <div class="no-boletas">
                    <h3><i class="fas fa-info-circle"></i> No hay boletas disponibles</h3>
                    <p>No se encontraron boletas de calificaciones para tu número de control.</p>
                    <p>Si crees que esto es un error, por favor contacta a Servicios Escolares.</p>
                </div>
            <?php else: ?>
                <div class="periodos-list">
                    <?php foreach ($periodos_disponibles as $periodo): ?>
                        <div class="periodo-item">
                            <div class="periodo-info">
                                <h4>Periodo: <?php echo htmlspecialchars($periodo['nombre']); ?></h4>
                            </div>
                            <div class="periodo-actions">
                                <a href="?periodo=<?php echo urlencode($periodo['nombre']); ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <button class="btn-download" onclick="generatePDF('<?php echo $alumno['id']; ?>', '<?php echo $periodo['nombre']; ?>', '<?php echo $periodo['ruta']; ?>')">
                                    <i class="fas fa-download"></i> PDF
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($boleta_html)): ?>
                    <div class="boleta-preview">
                        <div class="boleta-actions">
                            <button class="btn" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <button class="btn" onclick="generatePDF('<?php echo $alumno['id']; ?>', '<?php echo htmlspecialchars($_GET['periodo']); ?>', '<?php echo $periodos_disponibles[array_search($_GET['periodo'], array_column($periodos_disponibles, 'nombre'))]['ruta']; ?>')" style="margin-left: 10px;">
                                <i class="fas fa-file-pdf"></i> Descargar PDF
                            </button>
                        </div>
                        <?php echo $boleta_html; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="/scripts.js"></script>
    <script>
        function generatePDF(idAlumno, periodo, rutaHtml) {
            // Mostrar carga mientras se genera el PDF
            const loading = document.createElement('div');
            loading.style.position = 'fixed';
            loading.style.top = '0';
            loading.style.left = '0';
            loading.style.width = '100%';
            loading.style.height = '100%';
            loading.style.backgroundColor = 'rgba(0,0,0,0.5)';
            loading.style.display = 'flex';
            loading.style.justifyContent = 'center';
            loading.style.alignItems = 'center';
            loading.style.zIndex = '1000';
            loading.innerHTML = '<div style="background: white; padding: 20px; border-radius: 5px;"><i class="fas fa-spinner fa-spin"></i> Generando PDF, por favor espere...</div>';
            document.body.appendChild(loading);
            
            // Obtener el contenido HTML
            fetch(rutaHtml)
                .then(response => response.text())
                .then(html => {
                    const element = document.createElement('div');
                    element.innerHTML = html;
                    
                    const opt = {
                        margin: 10,
                        filename: `boleta_${idAlumno}_${periodo.replace(/ /g, '_')}.pdf`,
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: { 
                            scale: 2,
                            scrollY: 0,
                            allowTaint: true,
                            useCORS: true
                        },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                    };
                    
                    // Generar y descargar PDF
                    html2pdf().set(opt).from(element).save().then(() => {
                        document.body.removeChild(loading);
                    });
                })
                .catch(error => {
                    console.error('Error al generar PDF:', error);
                    document.body.removeChild(loading);
                    alert('Error al generar el PDF. Por favor intente nuevamente.');
                });
        }
    </script>
</body>
</html>