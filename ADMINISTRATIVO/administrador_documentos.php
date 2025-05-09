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
                    
                    <a href="#" class="btn">generar</a>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-redo"></i>
                    </div>
                    <h3>Reinscripción</h3>
                    
                    <a href="#" class="btn">Reinscribirse</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-minus-circle"></i>
                    </div>
                    <h3>Boleta de calificaciones</h3>
                    
                    <a href="#" class="btn">Solicitar baja</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Constancias</h3>
                    <p>Solicita constancias de estudios u otros documentos.</p>
                    <a href="#" class="btn">Solicitar</a>
                </div>
                
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3>Asesoría</h3>
                    <p>Agenda una cita con un asesor académico.</p>
                    <a href="#" class="btn">Agendar</a>
                </div>
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