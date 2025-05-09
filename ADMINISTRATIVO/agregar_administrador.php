<?php
session_start();

require_once '../conexion.php';

// Verificar sesión y privilegios
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

// Verificar si el usuario es privilegiado
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

// Si no es privilegiado, mostrar mensaje de acceso denegado
if (!$esPrivilegiado) {
    header("Location: acceso_denegado.php");
    exit();
}

// Procesar el formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_password'])) {
    // Verificar contraseña del administrador actual
    $password = $_POST['confirm_password'];
    $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ? AND password = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ss", $adminId, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Validar y sanitizar datos
        $rfc = trim($_POST['rfc']);
        $nombre = trim($_POST['nombre']);
        $telefono = trim($_POST['telefono']);
        $password_nuevo = trim($_POST['password']);
        $domicilio = trim($_POST['domicilio']);
        $sexo = trim($_POST['sexo']);
        $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
        $correo = trim($_POST['correo']);
        $tipo = trim($_POST['tipo']);

        // Validaciones básicas
        if (empty($rfc) || empty($nombre) || empty($password_nuevo)) {
            $error = "RFC, nombre y contraseña son campos obligatorios";
        } else {
            // Verificar si el RFC ya existe
            $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("s", $rfc);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "El RFC ya está registrado";
            } else {
                // Insertar nuevo administrador
                $query = "INSERT INTO administrativo (
                    `idAdministrativo(RFC)`, 
                    `Nombre`, 
                    `Telefono`, 
                    `password`, 
                    `Domicilio`, 
                    `Sexo`, 
                    `FechasNacimiento`, 
                    `Correo`, 
                    `tipo`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conexion->prepare($query);
                $stmt->bind_param(
                    "sssssssss", 
                    $rfc, 
                    $nombre, 
                    $telefono, 
                    $password_nuevo, 
                    $domicilio, 
                    $sexo, 
                    $fecha_nacimiento, 
                    $correo, 
                    $tipo
                );

                if ($stmt->execute()) {
                    $success = "Administrador agregado correctamente";
                } else {
                    $error = "Error al agregar administrador: " . $conexion->error;
                }
            }
        }
    } else {
        $error = "Contraseña incorrecta";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Agregar Administrador</title>
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
        <h1>Agregar Nuevo Administrador</h1>
        <p>Complete el formulario para registrar un nuevo administrador</p>
    </div>

    <div class="content">
        <?php if (!empty($error)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="agregar_administrador.php">
            <div class="form-section">
                <h3><i class="fas fa-user-shield"></i> Información Básica</h3>
                
                <div class="form-group">
                    <label for="rfc">RFC (ID):</label>
                    <input type="text" id="rfc" name="rfc" required>
                </div>
                
                <div class="form-group">
                    <label for="nombre">Nombre completo:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono:</label>
                    <input type="tel" id="telefono" name="telefono">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            
            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Información Adicional</h3>
                
                <div class="form-group">
                    <label for="domicilio">Domicilio:</label>
                    <input type="text" id="domicilio" name="domicilio">
                </div>
                
                <div class="form-group">
                    <label for="sexo">Sexo:</label>
                    <select id="sexo" name="sexo">
                        <option value="">Seleccionar...</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
                </div>
                
                <div class="form-group">
                    <label for="correo">Correo electrónico:</label>
                    <input type="email" id="correo" name="correo">
                </div>
                
                <div class="form-group">
                    <label for="tipo">Tipo de administrador:</label>
                    <select id="tipo" name="tipo" required>
                        <option value="">Seleccionar...</option>
                        <option value="normal">Normal</option>
                        <option value="privilegiado">Privilegiado</option>
                    </select>
                </div>
            </div>
            
            <div class="password-confirm-section">
                <h3><i class="fas fa-lock"></i> Confirmar Cambios</h3>
                <p>Para registrar el nuevo administrador, ingrese su contraseña:</p>
                <div class="form-group">
                    <label for="confirm_password">Su contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Registrar Administrador
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