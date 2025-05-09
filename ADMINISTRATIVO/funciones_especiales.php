<?php
session_start();

require_once '../conexion.php';

// 1. Primero verificar si hay sesión de administrador
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

// 2. Verificar si el usuario actual es privilegiado
$adminId = $_SESSION['idAdmin'];
$query = "SELECT tipo FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No se encontró el administrador
    header("Location: ../index.php");
    exit();
}

$adminData = $result->fetch_assoc();
$esPrivilegiado = ($adminData['tipo'] === 'privilegiado');

// 3. Si no es privilegiado, mostrar mensaje con botón de regresar
if (!$esPrivilegiado) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado</title>
        <link rel="stylesheet" href="/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            
            .denied-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: var(--background-color);
                text-align: center;
            }

            .denied-box {
                background-color: var(--secondary-color);
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
            }

            .denied-box h2 {
                color: #e74c3c;
                margin-top: 0;
            }

            .denied-box p {
                margin-bottom: 1.5rem;
            }

            .btn-back {
                display: inline-block;
                background-color: var(--accent-color);
                color: white;
                padding: 0.75rem 1.5rem;
                text-decoration: none;
                border-radius: 4px;
                transition: background-color 0.3s;
            }

            .btn-back:hover {
                background-color: #2980b9;
            }

            .denied-icon {
                font-size: 3rem;
                color: #e74c3c;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="denied-container">
            <div class="denied-box">
                <div class="denied-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h2>Acceso Denegado</h2>
                <p>No tienes los privilegios necesarios para acceder a esta sección.</p>
                <a href="/ADMINISTRATIVO/inicio_admin.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// 4. Verificación de contraseña adicional para funciones sensibles
if (!isset($_SESSION['privileged_auth'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
        $password = $_POST['admin_password'];

        $query = "SELECT * FROM administrativo
                  WHERE `idAdministrativo(RFC)` = ?
                  AND password = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("ss", $adminId, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['privileged_auth'] = true;
        } else {
            $error_message = "Contraseña incorrecta. Intente nuevamente.";
        }
    }

    // Mostrar formulario de autenticación
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verificación Requerida</title>
        <link rel="stylesheet" href="/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            .content {
        margin-left: 500px; /* Igual al ancho del sidebar */
        padding: 100px;
        width: calc(500px); /* Resta el ancho del sidebar */
        box-sizing: border-box;
    }
            .auth-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: var(--background-color);
            }

            .auth-box {
                background-color: var(--secondary-color);
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
                text-align: center;
            }

            .auth-box h2 {
                margin-top: 0;
                color: var(--accent-color);
            }

            .message.error {
                color: #e74c3c;
                margin: 1rem 0;
            }

            .form-group {
                margin-bottom: 1.5rem;
                text-align: left;
            }

            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                font-weight: bold;
            }

            .form-group input {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 1rem;
            }

            .btn-submit {
                background-color: var(--accent-color);
                color: white;
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: 4px;
                cursor: pointer;
                font-size: 1rem;
                width: 100%;
            }

            .btn-submit:hover {
                background-color: #2980b9;
            }
        </style>
    </head>
    <body>
        <div class="auth-container">
            <div class="auth-box">
                <h2>Verificación de Seguridad</h2>
                <p>Por favor ingrese su contraseña para continuar:</p>

                <?php if (isset($error_message)): ?>
                    <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="admin_password">Contraseña:</label>
                        <input type="password" id="admin_password" name="admin_password" required>
                    </div>
                    <button type="submit" class="btn-submit">Verificar</button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// 5. Si llegamos aquí, el usuario está autenticado y es privilegiado
// Mostrar el contenido normal de la página
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Funciones Privilegiadas</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
       
    /* Estilos generales para el layout */
    
    .content {
        margin-left: 250px; /* Igual al ancho del sidebar */
        padding: 100px;
        width: calc(100% - 500px); /* Resta el ancho del sidebar */
        box-sizing: border-box;
    }

    /* Estilos para el dropdown de administrador */
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

    /* Notificación de privilegios */
    .privilege-notice {
        background-color: #f8f9fa;
        padding: 1rem;
        border-left: 4px solid var(--accent-color);
        margin-bottom: 2rem;
    }

    /* Contenedor y recuadros flotantes */
    .floating-box-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
        width: 100%;
    }

    .floating-box {
        background-color: var(--secondary-color);
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
        width: 100%;
        box-sizing: border-box;
        transition: transform 0.3s ease;
    }

    .floating-box:hover {
        transform: translateY(-5px);
    }

    .floating-box h3 {
        color: var(--accent-color);
        margin-top: 0;
        margin-bottom: 1rem;
    }

    .floating-box p {
        margin-bottom: 1rem;
    }

    .floating-box a {
        display: inline-block;
        background-color: var(--button-color);
        color: white;
        padding: 0.75rem 1.25rem;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .floating-box a:hover {
        background-color: var(--button-hover-color);
    }

    /* Media queries para responsividad */
    @media (max-width: 1024px) {
        .floating-box-container {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .header,
        .content {
            margin-left: 0;
            width: 100%;
            padding: 15px;
        }
    }

    @media (max-width: 480px) {
        .floating-box-container {
            grid-template-columns: 1fr;
        }
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
        <h1>Funciones adicionales</h1>
        
    </div>

    <div class="content">
    <div class="floating-box-container">
        <div class="floating-box">
            <h3>Definir Fechas de Inscripciones y Reinscripciones</h3>
            <p>Configura los periodos en los que los alumnos pueden inscribirse o reinscribirse a los cursos.</p>
            <button class="btn" onclick="window.location.href='configurar_fechas.php'">Configurar Fechas</button>
        </div>

        <div class="floating-box">
            <h3>Definir Días Inhábiles</h3>
            <p>Establece los días del calendario que no serán considerados hábiles para actividades académicas.</p>
            <button class="btn" onclick="window.location.href='dias_inhabiles.php'">Gestionar Días Inhábiles</button>
        </div>



        <div class="floating-box">
            <h3>Añadir Administrador</h3>
            <p>Gestiona la lista de usuarios con permisos de administrador dentro del sistema.</p>
            <button class="btn" onclick="window.location.href='agregar_administrador.php'">Gestionar Administradores</button>
        </div>

        <div class="floating-box">
            <h3>Añadir o Eliminar Carrera</h3>
            <p>Administra las diferentes carreras o programas académicos ofrecidos por la institución.</p>
            <button class="btn" onclick="window.location.href='anadir_carrera.php'">Gestionar Carreras</button>
        </div>

        <div class="floating-box">
            <h3>Gestionar usuarios</h3>
            <p>Gestionar a todos los usuarios del sistema.</p>
            <button class="btn" onclick="window.location.href='buscar_admins.php'">Gestionar Usuarios</button>
        </div>
        <div class="floating-box">
    <h3>Limpiar Base de Datos</h3>
    <p>Elimina todos los datos de actividades, foros y comentarios (acción irreversible).</p>
    <button class="btn btn-danger" onclick="window.location.href='limpiar_bd.php'">
        <i class="fas fa-database"></i> Limpiar BD
    </button>
</div>
    </div>
</div>

    <script>
        function toggleAdminDropdown(id) {
            document.getElementById(id).classList.toggle("show");
            // Cerrar otros dropdowns cuando se abre uno
            var dropdowns = document.getElementsByClassName("admin-dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show') && openDropdown.id !== id) {
                    openDropdown.classList.remove('show');
                }
            }
        }

        // Cerrar el dropdown si el usuario hace clic fuera de él
        window.onclick = function(event) {
            if (!event.target.matches('.admin-dropbtn')) {
                var dropdowns = document.getElementsByClassName("admin-dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
    <script src="/scripts.js"></script>
</body>
</html>