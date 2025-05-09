<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Documentos</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <li><a href="documentos_estudiante.php"><i class="fas fa-file-alt"></i> <span>Documentos</span></a></li>
            <li><a href="actividades_estudiante.php"><i class="fas fa-tasks"></i> <span>Actividades</span></a></li>
            <li><a href="foro_estudiante.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="servicios_estudiante.php"><i class="fas fa-school"></i> <span>Servicios Escolares</span></a></li>
            <li><a href="info_estudiante.php"><i class="fas fa-user"></i> <span>Información personal</span></a></li>
            <li><a href="\logout.php"><i class="fas fa-right-from-bracket"></i> <span>Cerrar sesión</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Documentos</h1>
        <p>Accede a tus documentos académicos</p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <h3>Mis Documentos</h3>
            <div class="document-list">
                <div class="document-item">
                    <i class="fas fa-file-pdf"></i>
                    <span>Syllabus Programación Web.pdf</span>
                    <a href="#" class="btn">Descargar</a>
                </div>
                <div class="document-item">
                    <i class="fas fa-file-word"></i>
                    <span>Guía Bases de Datos.docx</span>
                    <a href="#" class="btn">Descargar</a>
                </div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <h3>Subir Documento</h3>
            <form>
                <div class="form-group">
                    <label for="document-upload">Seleccionar archivo</label>
                    <input type="file" id="document-upload">
                </div>
                <button type="submit" class="btn">Subir Documento</button>
            </form>
        </div>
    </div>

    <script src="scripts.js"></script>
</body>
</html>