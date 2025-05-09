<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Verificar parámetros
if (!isset($_GET['tipo']) || !isset($_GET['id'])) {
    header("Location: buscar_usuario.php");
    exit();
}

$tipoUsuario = $_GET['tipo'];
$idUsuario = $_GET['id'];
$usuario = null;
$error = '';
$success = '';

// Obtener datos del usuario
$tabla = $tipoUsuario;
$campoId = $tipoUsuario === 'alumno' ? 'idAlumno' : 
          ($tipoUsuario === 'docente' ? 'idDocente(RFC)' : 'idAdministrativo(RFC)');

$query = "SELECT * FROM $tabla WHERE `$campoId` = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: buscar_usuario.php");
    exit();
}

$usuario = $result->fetch_assoc();

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $passwordAdmin = $_POST['password_admin'] ?? '';
    
    // Verificar contraseña del administrador
    $query = "SELECT password FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $_SESSION['idAdmin']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        if ($passwordAdmin === $admin['password']) {
            // Preparar la consulta según el tipo de usuario
            if ($tipoUsuario === 'alumno') {
                $query = "UPDATE alumno SET 
                          Nombre = ?, 
                          Telefono = ?, 
                          password = ?, 
                          FechaNacimiento = ?, 
                          Sexo = ?, 
                          Domicilio = ?, 
                          Curp = ?, 
                          Cursos = ?, 
                          Carrera = ?, 
                          Semestre = ? 
                          WHERE idAlumno = ?";
                
                $stmt = $conexion->prepare($query);
                $stmt->bind_param(
                    "sssssssssss",
                    $_POST['Nombre'],
                    $_POST['Telefono'],
                    $_POST['password'],
                    $_POST['FechaNacimiento'],
                    $_POST['Sexo'],
                    $_POST['Domicilio'],
                    $_POST['Curp'],
                    $_POST['Cursos'],
                    $_POST['Carrera'],
                    $_POST['Semestre'],
                    $idUsuario
                );
            } elseif ($tipoUsuario === 'docente') {
                $query = "UPDATE docente SET 
                          Nombre = ?, 
                          Telefono = ?, 
                          password = ?, 
                          Domicilio = ?, 
                          Sexo = ?, 
                          FechasNacimiento = ?, 
                          Cursos = ?, 
                          Correo = ? 
                          WHERE `idDocente(RFC)` = ?";
                
                $stmt = $conexion->prepare($query);
                $stmt->bind_param(
                    "sssssssss",
                    $_POST['Nombre'],
                    $_POST['Telefono'],
                    $_POST['password'],
                    $_POST['Domicilio'],
                    $_POST['Sexo'],
                    $_POST['FechasNacimiento'],
                    $_POST['Cursos'],
                    $_POST['Correo'],
                    $idUsuario
                );
            } else { // Administrativo
                $query = "UPDATE administrativo SET 
                          Nombre = ?, 
                          Telefono = ?, 
                          password = ?, 
                          Domicilio = ?, 
                          Sexo = ?, 
                          FechasNacimiento = ?, 
                          Correo = ? 
                          WHERE `idAdministrativo(RFC)` = ?";
                
                $stmt = $conexion->prepare($query);
                $stmt->bind_param(
                    "ssssssss",
                    $_POST['Nombre'],
                    $_POST['Telefono'],
                    $_POST['password'],
                    $_POST['Domicilio'],
                    $_POST['Sexo'],
                    $_POST['FechasNacimiento'],
                    $_POST['Correo'],
                    $idUsuario
                );
            }
            
            if ($stmt->execute()) {
                $success = "Datos actualizados correctamente";
                // Refrescar datos
                $query = "SELECT * FROM $tabla WHERE `$campoId` = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("s", $idUsuario);
                $stmt->execute();
                $result = $stmt->get_result();
                $usuario = $result->fetch_assoc();
            } else {
                $error = "Error al actualizar los datos: " . $stmt->error;
            }
        } else {
            $error = "Contraseña de administrador incorrecta";
        }
    } else {
        $error = "Error al verificar credenciales de administrador";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - MindBox</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--dark-gray);
            border-radius: 4px;
            background-color: var(--secondary-color);
            color: var(--text-color);
        }
        
        .btn-save {
            background-color: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-save:hover {
            background-color: var(--accent-dark);
        }
        
        .password-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .password-modal-content {
            background-color: var(--secondary-color);
            padding: 2rem;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="inicio_admin.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="registrar_usuario.php"><i class="fas fa-user-plus"></i> <span>Registrar Usuario</span></a></li>
            <li><a href="buscar_usuario.php"><i class="fas fa-search"></i> <span>Buscar Usuario</span></a></li>
        
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Editar Usuario</h1>
            <p>Modifica los datos del usuario seleccionado</p>
        </div>
        
        <div class="edit-container">
            <?php if ($success): ?>
                <div class="message success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label><?php echo $tipoUsuario === 'alumno' ? 'Número de Control' : 'RFC'; ?></label>
                    <input type="text" value="<?php echo htmlspecialchars($usuario[$campoId]); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="Nombre">Nombre Completo</label>
                    <input type="text" id="Nombre" name="Nombre" value="<?php echo htmlspecialchars($usuario['Nombre'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="Telefono">Teléfono</label>
                    <input type="tel" id="Telefono" name="Telefono" value="<?php echo htmlspecialchars($usuario['Telefono'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="text" id="password" name="password" value="<?php echo htmlspecialchars($usuario['password'] ?? ''); ?>" required>
                </div>
                
                <?php if ($tipoUsuario === 'alumno'): ?>
                    <div class="form-group">
                        <label for="FechaNacimiento">Fecha de Nacimiento</label>
                        <input type="date" id="FechaNacimiento" name="FechaNacimiento" value="<?php echo !empty($usuario['FechaNacimiento']) ? date('Y-m-d', strtotime($usuario['FechaNacimiento'])) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Sexo">Sexo</label>
                        <select id="Sexo" name="Sexo">
                            <option value="Masculino" <?php echo ($usuario['Sexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Femenino" <?php echo ($usuario['Sexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                            <option value="Otro" <?php echo ($usuario['Sexo'] ?? '') === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="Domicilio">Domicilio</label>
                        <input type="text" id="Domicilio" name="Domicilio" value="<?php echo htmlspecialchars($usuario['Domicilio'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Curp">CURP</label>
                        <input type="text" id="Curp" name="Curp" value="<?php echo htmlspecialchars($usuario['Curp'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Cursos">Cursos (separados por comas)</label>
                        <input type="text" id="Cursos" name="Cursos" value="<?php echo htmlspecialchars($usuario['Cursos'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Carrera">Carrera</label>
                        <input type="text" id="Carrera" name="Carrera" value="<?php echo htmlspecialchars($usuario['Carrera'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Semestre">Semestre</label>
                        <input type="text" id="Semestre" name="Semestre" value="<?php echo htmlspecialchars($usuario['Semestre'] ?? ''); ?>">
                    </div>
                <?php elseif ($tipoUsuario === 'docente'): ?>
                    <div class="form-group">
                        <label for="Domicilio">Domicilio</label>
                        <input type="text" id="Domicilio" name="Domicilio" value="<?php echo htmlspecialchars($usuario['Domicilio'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Sexo">Sexo</label>
                        <select id="Sexo" name="Sexo">
                            <option value="">Seleccionar...</option>
                            <option value="Masculino" <?php echo ($usuario['Sexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Femenino" <?php echo ($usuario['Sexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                            <option value="Otro" <?php echo ($usuario['Sexo'] ?? '') === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="FechasNacimiento">Fecha de Nacimiento</label>
                        <input type="date" id="FechasNacimiento" name="FechasNacimiento" value="<?php echo !empty($usuario['FechasNacimiento']) ? date('Y-m-d', strtotime($usuario['FechasNacimiento'])) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Cursos">Cursos que imparte (separados por comas)</label>
                        <input type="text" id="Cursos" name="Cursos" value="<?php echo htmlspecialchars($usuario['Cursos'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Correo">Correo Electrónico</label>
                        <input type="email" id="Correo" name="Correo" value="<?php echo htmlspecialchars($usuario['Correo'] ?? ''); ?>">
                    </div>
                <?php else: // Administrativo ?>
                    <div class="form-group">
                        <label for="Domicilio">Domicilio</label>
                        <input type="text" id="Domicilio" name="Domicilio" value="<?php echo htmlspecialchars($usuario['Domicilio'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Sexo">Sexo</label>
                        <select id="Sexo" name="Sexo">
                            <option value="">Seleccionar...</option>
                            <option value="Masculino" <?php echo ($usuario['Sexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Femenino" <?php echo ($usuario['Sexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                            <option value="Otro" <?php echo ($usuario['Sexo'] ?? '') === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="FechasNacimiento">Fecha de Nacimiento</label>
                        <input type="date" id="FechasNacimiento" name="FechasNacimiento" value="<?php echo !empty($usuario['FechasNacimiento']) ? date('Y-m-d', strtotime($usuario['FechasNacimiento'])) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Correo">Correo Electrónico</label>
                        <input type="email" id="Correo" name="Correo" value="<?php echo htmlspecialchars($usuario['Correo'] ?? ''); ?>">
                    </div>
                <?php endif; ?>
                
                <button type="button" id="btn-guardar" class="btn-save">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <a href="buscar_usuario.php" class="btn-save" style="background-color: var(--dark-gray); text-decoration: none;">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                
                <div id="password-modal" class="password-modal">
                    <div class="password-modal-content">
                        <h3>Confirmar Cambios</h3>
                        <p>Para guardar los cambios, ingrese su contraseña de administrador:</p>
                        <div class="form-group">
                            <label for="password_admin">Contraseña</label>
                            <input type="password" id="password_admin" name="password_admin" required>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 1rem;">
                            <button type="submit" name="guardar" class="btn-save">
                                <i class="fas fa-check"></i> Confirmar
                            </button>
                            <button type="button" id="btn-cancelar" class="btn-save" style="background-color: var(--dark-gray);">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mostrar modal de contraseña al hacer clic en Guardar
        document.getElementById('btn-guardar').addEventListener('click', function() {
            document.getElementById('password-modal').style.display = 'flex';
        });
        
        // Ocultar modal al hacer clic en Cancelar
        document.getElementById('btn-cancelar').addEventListener('click', function() {
            document.getElementById('password-modal').style.display = 'none';
        });
        
        // Cerrar modal al hacer clic fuera del contenido
        document.getElementById('password-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
</body>
</html>