<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit();
}

// Obtener datos del estudiante desde la sesión
$alumno = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Información de <?php echo htmlspecialchars($alumno['nombre']); ?></title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
        <h1>Información de <?php echo htmlspecialchars($alumno['nombre']); ?></h1>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Datos Personales</h3>
            <div class="student-info">
                <div class="student-photo">
                    <img src="https://static.vecteezy.com/system/resources/previews/019/896/008/original/male-user-avatar-icon-in-flat-design-style-person-signs-illustration-png.png" alt="Foto del estudiante">
                </div>
                <div class="student-details">
                    <div class="student-field">
                        <label for="matricula">Matrícula</label>
                        <input type="text" id="matricula" value="<?php echo htmlspecialchars($alumno['id']); ?>" disabled>
                    </div>
                    <div class="student-field">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" value="<?php echo htmlspecialchars($alumno['nombre']); ?>" disabled>
                    </div>
                    <div class="student-field">
                        <label for="carrera">Carrera</label>
                        <input type="text" id="carrera" value="<?php echo htmlspecialchars($alumno['carrera']); ?>" disabled>
                    </div>
                    <div class="student-field">
                        <label for="semestre">Semestre</label>
                        <input type="text" id="semestre" value="<?php echo htmlspecialchars($alumno['semestre']); ?>" disabled>
                    </div>
                </div>
            </div>
            <button class="btn" onclick="generateID()">
                <i class="fas fa-file-pdf"></i> Generar Identificación PDF
            </button>
        </div>
        
        <div id="idCard" class="id-card" style="display:none;">
            <div class="id-header">
                <h2>Universidad MindBox</h2>
                <p>Identificación de Estudiante</p>
            </div>
            <div class="id-photo">
                <img src="https://via.placeholder.com/100x120" alt="Foto del estudiante">
            </div>
            <div class="id-details">
                <div class="id-row">
                    <div class="id-label">Matrícula:</div>
                    <div class="id-value"><?php echo htmlspecialchars($alumno['id']); ?></div>
                </div>
                <div class="id-row">
                    <div class="id-label">Nombre:</div>
                    <div class="id-value"><?php echo htmlspecialchars($alumno['nombre']); ?></div>
                </div>
                <div class="id-row">
                    <div class="id-label">Carrera:</div>
                    <div class="id-value"><?php echo htmlspecialchars($alumno['carrera']); ?></div>
                </div>
                <div class="id-row">
                    <div class="id-label">Semestre:</div>
                    <div class="id-value"><?php echo htmlspecialchars($alumno['semestre']); ?></div>
                </div>
            </div>
            <div class="id-footer">
                Válida hasta: <?php echo date('d/m/Y', strtotime('+1 year')); ?> | Esta identificación es propiedad de la Universidad MindBox
            </div>
        </div>
    </div>

    <script src="/scripts.js"></script>
    <script>
        function generateID() {
            const element = document.getElementById('idCard');
            const opt = {
                margin: 10,
                filename: 'identificacion_<?php echo $alumno["id"]; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Mostrar temporalmente la tarjeta para generarla
            element.style.display = 'block';
            html2pdf().set(opt).from(element).save();
            setTimeout(() => {
                element.style.display = 'none';
            }, 1000);
        }
    </script>
</body>
</html>