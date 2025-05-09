<?php
session_start();

require_once '../conexion.php';

// 1. Verificar sesión de administrador
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

// 2. Verificar si el usuario es privilegiado
$adminId = $_SESSION['idAdmin'];
$query = "SELECT tipo FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit();
}

$adminData = $result->fetch_assoc();
$esPrivilegiado = ($adminData['tipo'] === 'privilegiado');

// 3. Si no es privilegiado, mostrar mensaje de acceso denegado
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

// 4. Verificación de contraseña adicional
$passwordVerified = false;
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['admin_password'])) {
        // Verificar contraseña
        $password = $_POST['admin_password'];
        $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ? AND password = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("ss", $adminId, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $passwordVerified = true;
            $_SESSION['privileged_auth'] = true;
        } else {
            $errorMessage = "Contraseña incorrecta. Intente nuevamente.";
        }
    } elseif (isset($_POST['nombre_carrera']) && isset($_POST['confirm_password'])) {
        
        // Verificar nuevamente la contraseña antes de guardar
        $password = $_POST['confirm_password'];
        $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ? AND password = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("ss", $adminId, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Procesar nueva carrera
            $nombreCarrera = trim($_POST['nombre_carrera']);
            
            // Validar que el nombre no esté vacío
            if (empty($nombreCarrera)) {
                $errorMessage = "El nombre de la carrera no puede estar vacío.";
            } else {
                // Insertar nueva carrera
                $insertQuery = "INSERT INTO carrera (Nombre) VALUES (?)";
                $stmt = $conexion->prepare($insertQuery);
                $stmt->bind_param("s", $nombreCarrera);
                
                if ($stmt->execute()) {
                    $successMessage = "Carrera agregada correctamente.";
                } else {
                    $errorMessage = "Error al guardar la carrera: " . $conexion->error;
                }
            }
        } else {
            $errorMessage = "Contraseña incorrecta. No se guardaron los cambios.";
        }
    }
}

// Si no se ha verificado la contraseña, mostrar formulario de verificación
if (!isset($_SESSION['privileged_auth'])) {
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

                <?php if (!empty($errorMessage)): ?>
                    <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
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

// Obtener carreras existentes
$query = "SELECT idCarrera, Nombre FROM carrera ORDER BY Nombre";
$carreras = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Añadir Carrera</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos adicionales para el panel administrativo */
        .content {
            margin-left: 250px; /* Igual al ancho del sidebar */
            padding: 100px;
            width: calc(100% - 500px); /* Resta el ancho del sidebar */
            box-sizing: border-box;
        }
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
        
        /* Estilos específicos para el formulario de carreras */
        .carreras-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .form-section {
            flex: 1;
            min-width: 300px;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .form-section h3 {
            color: var(--accent-color);
            margin-top: 0;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .password-confirm-section {
            width: 100%;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .btn-submit {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-submit:hover {
            background-color: #2980b9;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Estilos para la lista de carreras */
        .lista-carreras {
            width: 100%;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .lista-carreras h3 {
            color: var(--accent-color);
            margin-top: 0;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .carrera-item {
            padding: 1rem;
            border-bottom: 1px solid var(--dark-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .carrera-item:last-child {
            border-bottom: none;
        }
        
        .carrera-info {
            flex: 1;
        }
        
        .carrera-nombre {
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .btn-eliminar {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .btn-eliminar:hover {
            background-color: #c0392b;
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
        <h1>Gestión de Carreras</h1>
        <p>Agregar nuevas carreras al sistema</p>
    </div>

    <div class="content">
        <?php if (isset($successMessage)): ?>
            <div class="message success">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php elseif (isset($errorMessage) && !empty($errorMessage)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="anadir_carrera.php">
            <div class="carreras-container">
                <!-- Formulario para agregar carreras -->
                <div class="form-section">
                    <h3><i class="fas fa-graduation-cap"></i> Agregar Nueva Carrera</h3>
                    <div class="form-group">
                        <label for="nombre_carrera">Nombre de la Carrera:</label>
                        <input type="text" id="nombre_carrera" name="nombre_carrera" required>
                    </div>
                </div>

                <!-- Lista de carreras existentes -->
                <div class="lista-carreras">
                    <h3><i class="fas fa-list"></i> Carreras Existentes</h3>
                    
                    <?php if ($carreras->num_rows > 0): ?>
                        <?php while ($carrera = $carreras->fetch_assoc()): ?>
                            <div class="carrera-item">
                                <div class="carrera-info">
                                    <div class="carrera-nombre">
                                        <?php echo htmlspecialchars($carrera['Nombre']); ?>
                                    </div>
                                </div>
                                <button type="button" class="btn-eliminar" onclick="eliminarCarrera(<?php echo $carrera['idCarrera']; ?>)">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No hay carreras registradas en el sistema.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Confirmación de contraseña -->
            <div class="password-confirm-section">
                <h3><i class="fas fa-lock"></i> Confirmar Cambios</h3>
                <p>Para guardar los cambios, por favor ingrese su contraseña nuevamente:</p>
                <div class="form-group">
                    <label for="confirm_password">Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Guardar Carrera
                </button>
            </div>
        </form>
    </div>

    <script src="/scripts.js"></script>
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

        // Función para eliminar una carrera
        function eliminarCarrera(id) {
            if (confirm('¿Está seguro que desea eliminar esta carrera? Esta acción no se puede deshacer.')) {
                fetch('eliminar_carrera.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Carrera eliminada correctamente');
                        location.reload();
                    } else {
                        alert('Error al eliminar la carrera: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error al eliminar la carrera');
                });
            }
        }
    </script>
</body>
</html>