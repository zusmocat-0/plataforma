<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

$adminEncontrado = null;
$error_message = '';
$success_message = '';
$modoEdicion = false;

// Procesar búsqueda
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['buscar'])) {
        $rfc = trim($_POST['rfc']);
        
        if (!empty($rfc)) {
            // Buscar en administrativos
            $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("s", $rfc);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $adminEncontrado = $result->fetch_assoc();
            } else {
                $error_message = "No se encontró ningún administrador con ese RFC";
            }
        }
    }
    // Activar modo edición
    elseif (isset($_POST['editar'])) {
        $rfc = $_POST['rfc'];
        
        $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $rfc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $adminEncontrado = $result->fetch_assoc();
            $modoEdicion = true;
        }
    }
    // Procesar actualización
    elseif (isset($_POST['actualizar'])) {
        $password = $_POST['admin_password'];
        $rfc = $_POST['rfc'];
        
        // Verificar contraseña de administrador actual
        $query = "SELECT password FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $_SESSION['idAdmin']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = "Error: Administrador no encontrado";
        } else {
            $admin = $result->fetch_assoc();
            
            if ($password === $admin['password']) {
                // Recoger datos del formulario
                $nombre = trim($_POST['nombre']);
                $telefono = trim($_POST['telefono']);
                $nueva_password = trim($_POST['nueva_password']);
                $domicilio = trim($_POST['domicilio']);
                $sexo = trim($_POST['sexo']);
                $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
                $correo = trim($_POST['correo']);
                $tipo = trim($_POST['tipo']);
                
                // Preparar la consulta de actualización
                if (!empty($nueva_password)) {
                    $query = "UPDATE administrativo SET 
                                Nombre = ?, 
                                Telefono = ?, 
                                password = ?, 
                                Domicilio = ?, 
                                Sexo = ?, 
                                FechasNacimiento = ?, 
                                Correo = ?, 
                                tipo = ? 
                              WHERE `idAdministrativo(RFC)` = ?";
                    $stmt = $conexion->prepare($query);
                    $stmt->bind_param("sssssssss", 
                        $nombre, $telefono, $nueva_password, $domicilio, 
                        $sexo, $fecha_nacimiento, $correo, $tipo, $rfc);
                } else {
                    $query = "UPDATE administrativo SET 
                                Nombre = ?, 
                                Telefono = ?, 
                                Domicilio = ?, 
                                Sexo = ?, 
                                FechasNacimiento = ?, 
                                Correo = ?, 
                                tipo = ? 
                              WHERE `idAdministrativo(RFC)` = ?";
                    $stmt = $conexion->prepare($query);
                    $stmt->bind_param("ssssssss", 
                        $nombre, $telefono, $domicilio, 
                        $sexo, $fecha_nacimiento, $correo, $tipo, $rfc);
                }
                
                if ($stmt->execute()) {
                    $success_message = "Administrador actualizado correctamente";
                    // Actualizar los datos mostrados
                    $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
                    $stmt = $conexion->prepare($query);
                    $stmt->bind_param("s", $rfc);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $adminEncontrado = $result->fetch_assoc();
                    $modoEdicion = false;
                } else {
                    $error_message = "Error al actualizar administrador: " . $conexion->error;
                    $modoEdicion = true;
                }
            } else {
                $error_message = "Contraseña incorrecta";
                $modoEdicion = true;
            }
        }
    }
    // Cancelar edición
    elseif (isset($_POST['cancelar'])) {
        $rfc = $_POST['rfc'];
        
        $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $rfc);
        $stmt->execute();
        $result = $stmt->get_result();
        $adminEncontrado = $result->fetch_assoc();
        $modoEdicion = false;
    }
    // Procesar eliminación
    elseif (isset($_POST['eliminar'])) {
        $password = $_POST['admin_password'];
        $rfc = $_POST['rfc'];
        
        // Verificar contraseña de administrador
        $query = "SELECT password FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $_SESSION['idAdmin']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error_message = "Error: Administrador no encontrado";
        } else {
            $admin = $result->fetch_assoc();
            
            if ($password === $admin['password']) {
                // Eliminar administrador
                $query = "DELETE FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("s", $rfc);
                
                if ($stmt->execute()) {
                    $success_message = "Administrador eliminado correctamente";
                    $adminEncontrado = null;
                } else {
                    $error_message = "Error al eliminar administrador: " . $conexion->error;
                }
            } else {
                $error_message = "Contraseña incorrecta";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Administrador - MindBox</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos para los botones de acción */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
            padding: 20px;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-edit, .btn-delete, .btn-save, .btn-cancel {
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit {
            background-color: #3498db;
            color: white;
        }
        
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .btn-save {
            background-color: #2ecc71;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .btn-cancel {
            background-color: #95a5a6;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-save:hover {
            background-color: #27ae60;
        }
        
        .btn-cancel:hover {
            background-color: #7f8c8d;
        }
        
        /* Estilos para el modal de confirmación */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: var(--secondary-color);
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-cancel-modal, .btn-confirm {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-cancel-modal {
            background-color: #95a5a6;
            color: white;
            border: none;
        }
        
        .btn-confirm {
            background-color: #e74c3c;
            color: white;
            border: none;
        }
        
        /* Estilos para campos deshabilitados */
        .readonly-input {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 4px;
            width: 100%;
            display: block;
        }
        
        .editable-input {
            background-color: white;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 4px;
            width: 100%;
            display: block;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="inicio_admin.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="administrador_gestionar_usuarios.php"><i class="fas fa-user-plus"></i> <span>Registrar Usuario</span></a></li>
            <li class="active"><a href="#"><i class="fas fa-search"></i> <span>Buscar Administrador</span></a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Buscar Administrador</h1>
            <p>Busca y gestiona administradores del sistema</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <div class="profile-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="rfc">Buscar por RFC</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="rfc" name="rfc" required style="flex-grow: 1;" 
                               value="<?php echo isset($_POST['rfc']) ? htmlspecialchars($_POST['rfc']) : ''; ?>">
                        <button type="submit" name="buscar" class="btn-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if ($adminEncontrado): ?>
                <form method="POST" action="">
                    <input type="hidden" name="rfc" value="<?php echo htmlspecialchars($adminEncontrado['idAdministrativo(RFC)']); ?>">
                    
                    <div class="profile-header">
                        <div class="profile-photo">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="profile-info">
                            <h2 class="profile-title">
                                <?php if ($modoEdicion): ?>
                                    <input type="text" name="nombre" class="editable-input" 
                                           value="<?php echo htmlspecialchars($adminEncontrado['Nombre'] ?? ''); ?>" required>
                                <?php else: ?>
                                    <span class="readonly-input"><?php echo htmlspecialchars($adminEncontrado['Nombre'] ?? ''); ?></span>
                                <?php endif; ?>
                            </h2>
                            <span class="profile-type">Administrador</span>
                        </div>
                    </div>
                    
                    <div class="user-details">
                        <div class="detail-card">
                            <h4>Información Básica</h4>
                            <div class="detail-item">
                                <span class="detail-label">RFC</span>
                                <span class="readonly-input"><?php echo htmlspecialchars($adminEncontrado['idAdministrativo(RFC)']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Teléfono</span>
                                <?php if ($modoEdicion): ?>
                                    <input type="text" name="telefono" class="editable-input" 
                                           value="<?php echo htmlspecialchars($adminEncontrado['Telefono'] ?? ''); ?>">
                                <?php else: ?>
                                    <span class="readonly-input"><?php echo htmlspecialchars($adminEncontrado['Telefono'] ?? ''); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($modoEdicion): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Nueva Contraseña</span>
                                    <input type="password" name="nueva_password" class="editable-input" 
                                           placeholder="Dejar en blanco para no cambiar">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="detail-card">
                            <h4>Datos Personales</h4>
                            <div class="detail-item">
                                <span class="detail-label">Fecha de Nacimiento</span>
                                <?php if ($modoEdicion): ?>
                                    <input type="date" name="fecha_nacimiento" class="editable-input" 
                                           value="<?php echo !empty($adminEncontrado['FechasNacimiento']) ? htmlspecialchars($adminEncontrado['FechasNacimiento']) : ''; ?>">
                                <?php else: ?>
                                    <span class="readonly-input">
                                        <?php echo !empty($adminEncontrado['FechasNacimiento']) ? date('d/m/Y', strtotime($adminEncontrado['FechasNacimiento'])) : 'No especificada'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Sexo</span>
                                <?php if ($modoEdicion): ?>
                                    <select name="sexo" class="editable-input">
                                        <option value="">Seleccionar...</option>
                                        <option value="Masculino" <?php echo ($adminEncontrado['Sexo'] ?? '') === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="Femenino" <?php echo ($adminEncontrado['Sexo'] ?? '') === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                                        <option value="Otro" <?php echo ($adminEncontrado['Sexo'] ?? '') === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                <?php else: ?>
                                    <span class="readonly-input"><?php echo htmlspecialchars($adminEncontrado['Sexo'] ?? 'No especificado'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Domicilio</span>
                                <?php if ($modoEdicion): ?>
                                    <input type="text" name="domicilio" class="editable-input" 
                                           value="<?php echo htmlspecialchars($adminEncontrado['Domicilio'] ?? ''); ?>">
                                <?php else: ?>
                                    <span class="readonly-input"><?php echo htmlspecialchars($adminEncontrado['Domicilio'] ?? 'No especificado'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-card">
                            <h4>Información de Cuenta</h4>
                            <div class="detail-item">
                                <span class="detail-label">Correo Electrónico</span>
                                <?php if ($modoEdicion): ?>
                                    <input type="email" name="correo" class="editable-input" 
                                           value="<?php echo htmlspecialchars($adminEncontrado['Correo'] ?? ''); ?>">
                                <?php else: ?>
                                    <span class="readonly-input"><?php echo htmlspecialchars($adminEncontrado['Correo'] ?? 'No especificado'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Tipo de Administrador</span>
                                <?php if ($modoEdicion): ?>
                                    <select name="tipo" class="editable-input" required>
                                        <option value="normal" <?php echo ($adminEncontrado['tipo'] ?? '') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="privilegiado" <?php echo ($adminEncontrado['tipo'] ?? '') === 'privilegiado' ? 'selected' : ''; ?>>Privilegiado</option>
                                    </select>
                                <?php else: ?>
                                    <span class="readonly-input">
                                        <?php echo ($adminEncontrado['tipo'] ?? '') === 'privilegiado' ? 'Privilegiado' : 'Normal'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="action-buttons">
                        <?php if ($modoEdicion): ?>
                            <!-- En modo edición: mostrar botones Guardar y Cancelar -->
                            <div class="password-confirm-section">
                                <h4><i class="fas fa-lock"></i> Confirmar Cambios</h4>
                                <p>Para guardar los cambios, ingrese su contraseña:</p>
                                <div class="form-group">
                                    <label for="admin_password">Contraseña:</label>
                                    <input type="password" id="admin_password" name="admin_password" class="editable-input" required>
                                </div>
                                
                                <button type="submit" name="actualizar" class="btn-save">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                                
                                <button type="submit" name="cancelar" class="btn-cancel">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- En modo visualización: mostrar botones Editar y Eliminar -->
                            <button type="submit" name="editar" class="btn-edit">
                                <i class="fas fa-edit"></i> Editar Administrador
                            </button>
                            
                            <button type="button" class="btn-delete" onclick="openDeleteModal()">
                                <i class="fas fa-trash"></i> Eliminar Administrador
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Modal para confirmar eliminación -->
                    <div id="deleteModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3>Confirmar Eliminación</h3>
                                <span class="close" onclick="closeDeleteModal()">&times;</span>
                            </div>
                            <p>¿Está seguro que desea eliminar permanentemente a <?php echo htmlspecialchars($adminEncontrado['Nombre'] ?? 'este administrador'); ?>?</p>
                            <p>Esta acción no se puede deshacer.</p>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="rfc" value="<?php echo htmlspecialchars($adminEncontrado['idAdministrativo(RFC)']); ?>">
                                <div class="form-group">
                                    <label for="admin_password_modal">Ingrese su contraseña de administrador para confirmar:</label>
                                    <input type="password" id="admin_password_modal" name="admin_password" required>
                                </div>
                                
                                <div class="modal-actions">
                                    <button type="button" class="btn-cancel-modal" onclick="closeDeleteModal()">Cancelar</button>
                                    <button type="submit" name="eliminar" class="btn-confirm">Confirmar Eliminación</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </form>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar'])): ?>
                <div class="message error">No se encontró ningún administrador con ese RFC</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Funciones para manejar el modal de eliminación
        function openDeleteModal() {
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>