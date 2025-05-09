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
    } elseif (isset($_POST['fecha_inicio_curso']) && isset($_POST['fecha_fin_curso']) && 
              isset($_POST['fecha_inicio_inscripcion']) && isset($_POST['fecha_fin_inscripcion']) && 
              isset($_POST['fecha_inicio_seleccion']) && isset($_POST['fecha_fin_seleccion'])) {
        
        // Verificar nuevamente la contraseña antes de guardar
        $password = $_POST['confirm_password'];
        $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ? AND password = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("ss", $adminId, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Procesar fechas de curso
            $fecha_inicio_curso = $_POST['fecha_inicio_curso'];
            $fecha_fin_curso = $_POST['fecha_fin_curso'];
            
            // Eliminar registros anteriores del mismo tipo
            $deleteQuery = "DELETE FROM fechas WHERE tipo = 'curso'";
            $conexion->query($deleteQuery);
            
            // Insertar nuevo registro
            $insertQuery = "INSERT INTO fechas (tipo, fecha_inicio, fecha_fin, descripcion, creado_por) 
                           VALUES ('curso', ?, ?, 'Periodo escolar', ?)";
            $stmt = $conexion->prepare($insertQuery);
            $stmt->bind_param("sss", $fecha_inicio_curso, $fecha_fin_curso, $adminId);
            $stmt->execute();
            
            // Procesar fechas de inscripción
            $fecha_inicio_inscripcion = $_POST['fecha_inicio_inscripcion'];
            $fecha_fin_inscripcion = $_POST['fecha_fin_inscripcion'];
            
            $deleteQuery = "DELETE FROM fechas WHERE tipo = 'inscripcion'";
            $conexion->query($deleteQuery);
            
            $insertQuery = "INSERT INTO fechas (tipo, fecha_inicio, fecha_fin, descripcion, creado_por) 
                           VALUES ('inscripcion', ?, ?, 'Periodo de inscripciones', ?)";
            $stmt = $conexion->prepare($insertQuery);
            $stmt->bind_param("sss", $fecha_inicio_inscripcion, $fecha_fin_inscripcion, $adminId);
            $stmt->execute();
            
            // Procesar fechas de selección de materias
            $fecha_inicio_seleccion = $_POST['fecha_inicio_seleccion'];
            $fecha_fin_seleccion = $_POST['fecha_fin_seleccion'];
            
            $deleteQuery = "DELETE FROM fechas WHERE tipo = 'seleccion_materias'";
            $conexion->query($deleteQuery);
            
            $insertQuery = "INSERT INTO fechas (tipo, fecha_inicio, fecha_fin, descripcion, creado_por) 
                           VALUES ('seleccion_materias', ?, ?, 'Periodo de selección de materias', ?)";
            $stmt = $conexion->prepare($insertQuery);
            $stmt->bind_param("sss", $fecha_inicio_seleccion, $fecha_fin_seleccion, $adminId);
            $stmt->execute();
            
            $successMessage = "Fechas configuradas correctamente.";
        } else {
            $errorMessage = "Contraseña incorrecta. No se guardaron los cambios.";
        }
    }
}

// Si no se ha verificado la contraseña, mostrar formulario de verificación
if (!isset($_SESSION['privileged_auth']) || !$_SESSION['privileged_auth']) {
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

// Obtener fechas actuales de la base de datos
$fechas = [
    'curso' => null,
    'inscripcion' => null,
    'seleccion_materias' => null
];

$query = "SELECT tipo, fecha_inicio, fecha_fin FROM fechas";
$result = $conexion->query($query);

while ($row = $result->fetch_assoc()) {
    $fechas[$row['tipo']] = [
        'inicio' => $row['fecha_inicio'],
        'fin' => $row['fecha_fin']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Configurar Fechas</title>
    
    
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
         .content {
        margin-left: 250px; /* Igual al ancho del sidebar */
        padding: 100px;
        width: calc(100% - 500px); /* Resta el ancho del sidebar */
        box-sizing: border-box;
    }
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
        
        /* Estilos específicos para el formulario de fechas */
        .date-form-container {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .date-form-section {
            flex: 1;
            min-width: 300px;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .date-form-section h3 {
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
        
        .form-group input[type="date"] {
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
    </style>
</head>
<body>
    <div class="notification-icon">
        <i class="fas fa-bell" onclick="toggleNotifications()"></i>
    
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
        <h1>Configurar Fechas Académicas</h1>
        <p>Defina los periodos escolares, de inscripción y selección de materias</p>
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

        <form method="POST" action="configurar_fechas.php">
            <div class="date-form-container">
                <!-- Sección de Periodo Escolar -->
                <div class="date-form-section">
                    <h3><i class="fas fa-calendar-alt"></i> Periodo Escolar</h3>
                    <div class="form-group">
                        <label for="fecha_inicio_curso">Fecha de Inicio:</label>
                        <input type="date" id="fecha_inicio_curso" name="fecha_inicio_curso" 
                               value="<?php echo isset($fechas['curso']['inicio']) ? htmlspecialchars($fechas['curso']['inicio']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_fin_curso">Fecha de Fin:</label>
                        <input type="date" id="fecha_fin_curso" name="fecha_fin_curso" 
                               value="<?php echo isset($fechas['curso']['fin']) ? htmlspecialchars($fechas['curso']['fin']) : ''; ?>" required>
                    </div>
                </div>

                <!-- Sección de Inscripciones -->
                <div class="date-form-section">
                    <h3><i class="fas fa-user-plus"></i> Periodo de Inscripciones</h3>
                    <div class="form-group">
                        <label for="fecha_inicio_inscripcion">Fecha de Inicio:</label>
                        <input type="date" id="fecha_inicio_inscripcion" name="fecha_inicio_inscripcion" 
                               value="<?php echo isset($fechas['inscripcion']['inicio']) ? htmlspecialchars($fechas['inscripcion']['inicio']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_fin_inscripcion">Fecha de Fin:</label>
                        <input type="date" id="fecha_fin_inscripcion" name="fecha_fin_inscripcion" 
                               value="<?php echo isset($fechas['inscripcion']['fin']) ? htmlspecialchars($fechas['inscripcion']['fin']) : ''; ?>" required>
                    </div>
                </div>

                <!-- Sección de Selección de Materias -->
                <div class="date-form-section">
                    <h3><i class="fas fa-book"></i> Periodo de Selección de Materias</h3>
                    <div class="form-group">
                        <label for="fecha_inicio_seleccion">Fecha de Inicio:</label>
                        <input type="date" id="fecha_inicio_seleccion" name="fecha_inicio_seleccion" 
                               value="<?php echo isset($fechas['seleccion_materias']['inicio']) ? htmlspecialchars($fechas['seleccion_materias']['inicio']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_fin_seleccion">Fecha de Fin:</label>
                        <input type="date" id="fecha_fin_seleccion" name="fecha_fin_seleccion" 
                               value="<?php echo isset($fechas['seleccion_materias']['fin']) ? htmlspecialchars($fechas['seleccion_materias']['fin']) : ''; ?>" required>
                    </div>
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
                    <i class="fas fa-save"></i> Guardar Configuración
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
    </script>
</body>
</html>