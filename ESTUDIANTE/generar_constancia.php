<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Obtener información del alumno
$alumno = $_SESSION['usuario'];

// Obtener periodo escolar actual
$query_periodo = "SELECT fecha_inicio, fecha_fin FROM fechas WHERE tipo = 'curso' ORDER BY id DESC LIMIT 1";
$result_periodo = $conexion->query($query_periodo);
$periodo = $result_periodo->fetch_assoc();

// Formatear fechas para mostrar solo meses
$meses = [
    '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
    '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
    '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
];

$fecha_inicio = new DateTime($periodo['fecha_inicio']);
$fecha_fin = new DateTime($periodo['fecha_fin']);

$mes_inicio = $meses[$fecha_inicio->format('m')];
$mes_fin = $meses[$fecha_fin->format('m')];
$anio = $fecha_inicio->format('Y');

$periodo_escolar = "$mes_inicio - $mes_fin $anio";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ModuleBox - Constancia de Estudios</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .constancia-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .constancia-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .constancia-logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        
        .constancia-title {
            font-size: 24px;
            font-weight: bold;
            color:rgb(0, 0, 0);
            margin-bottom: 10px;
        }
        
        .constancia-subtitle {
            font-size: 18px;
            color:rgb(0, 0, 0);
            margin-bottom: 30px;
        }
        
        .constancia-body {
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 40px;
            text-align: justify;
            color:rgb(0, 0, 0);
        }
        
        .constancia-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        
        .constancia-signature {
            width: 200px;
            border-top: 1px solid #333;
            text-align: center;
            padding-top: 10px;
        }
        
        .constancia-actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .watermark {
            position: absolute;
            opacity: 0.1;
            font-size: 120px;
            color: #3498db;
            transform: rotate(-30deg);
            top: 30%;
            left: 20%;
            z-index: 0;
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
        <h1>Constancia de Estudios</h1>
        <p>Documento oficial de inscripción</p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <div class="constancia-container">
                <div class="watermark">ModuleBox</div>
                
                <div class="constancia-header">
                    
                    <div class="constancia-title">CONSTANCIA DE INSCRIPCIÓN</div>
                    <div class="constancia-subtitle">INSTITUCIÓN EDUCATIVA ModuleBox</div>
                </div>
                
                <div class="constancia-body">
                    <p>La Dirección de la Institución Educativa ModuleBox hace constar que el(la) alumno(a) <strong><?php echo htmlspecialchars($alumno['nombre']); ?></strong>, con matrícula <strong><?php echo htmlspecialchars($alumno['id']); ?></strong>, se encuentra inscrito(a) satisfactoriamente en el periodo escolar <strong><?php echo htmlspecialchars($periodo_escolar); ?></strong>, cursando el <strong><?php echo htmlspecialchars($alumno['semestre']); ?> semestre</strong> de la carrera de <strong><?php echo htmlspecialchars($alumno['carrera']); ?></strong>.</p>
                    
                    <p>Se extiende la presente constancia a solicitud del interesado(a) para los fines que estime convenientes.</p>
                    
                    <p>En la ciudad de <?php echo date('d'); ?> de <?php echo $meses[date('m')]; ?> del <?php echo date('Y'); ?>.</p>
                </div>
                
                <div class="constancia-footer">
                    <div></div>
                    <div class="constancia-signature">
                        <strong>ATENTAMENTE</strong><br>
                        <span>Dirección Académica</span><br>
                        <span>ModuleBox</span>
                    </div>
                </div>
                
                <div class="constancia-actions">
                    <button class="btn" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir Constancia
                    </button>
                    <button class="btn" onclick="generatePDF()" style="margin-left: 10px;">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function generatePDF() {
            const element = document.querySelector('.constancia-container');
            const opt = {
                margin: 10,
                filename: 'constancia_estudios_<?php echo $alumno["id"]; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save();
        }
        
        // Función para cambiar entre modo claro y oscuro
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
    </script>
</body>
</html>