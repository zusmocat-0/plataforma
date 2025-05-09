<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Servicios</title>
    <link rel="stylesheet" href="/styles.css">
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
            <li><a href="avance_reticular.php"><i class="fas fa-tasks"></i> <span>Avance Reticular</span></a></li>
            <li><a href="foro_estudiante.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="servicios_estudiante.php"><i class="fas fa-school"></i> <span>Servicios Escolares</span></a></li>
            <li><a href="info_estudiante.php"><i class="fas fa-user"></i> <span>Información personal</span></a></li>
            <li><a href="\logout.php"><i class="fas fa-right-from-bracket"></i> <span>Cerrar sesión</span></a></li>
        </ul>
    </div>
    
    <div class="header">
    <h1>Servicios escolares</h1>
    <p>Gestiona tus trámites académicos</p>
</div>

<div class="main-content">
    <div class="dashboard-card">
        <h3>Documentos disponibles</h3>
        <p>Selecciona el servicio que necesitas realizar:</p>

        <div class="services-container">
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3>Constancia</h3>
                <a href="generar_constancia.php" class="btn">Solicitar</a>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-redo"></i>
                </div>
                <h3>Reinscripción</h3>
                <a href="reeinscribirse.php" class="btn">Reinscribirse</a>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-minus-circle"></i>
                </div>
                <h3>Boleta de calificaciones</h3>
                <a href="solicitar_boleta.php" class="btn">Solicitar boleta</a>
            </div>

            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-list-ul"></i> </div>
                <h3>Selección de materias</h3> <a href="selec_materias.php" class="btn">Seleccionar</a> </div>
        </div>
    </div>
</div>
    
    <script>
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
    <script src="/scripts.js"></script>
</body>
</html>