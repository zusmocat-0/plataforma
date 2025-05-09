<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

$usuarioEncontrado = null;
$tipoUsuario = '';
$error_message = '';
$success_message = '';

// Procesar búsqueda
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['buscar'])) {
        $identificador = trim($_POST['identificador']);
        
        if (!empty($identificador)) {
            // Buscar en alumnos
            $query = "SELECT * FROM alumno WHERE idAlumno = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("s", $identificador);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $usuarioEncontrado = $result->fetch_assoc();
                $tipoUsuario = 'alumno';
            } else {
                // Buscar en docentes
                $query = "SELECT * FROM docente WHERE `idDocente(RFC)` = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("s", $identificador);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $usuarioEncontrado = $result->fetch_assoc();
                    $tipoUsuario = 'docente';
                } else {
                    // Buscar en administrativos
                    $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
                    $stmt = $conexion->prepare($query);
                    $stmt->bind_param("s", $identificador);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $usuarioEncontrado = $result->fetch_assoc();
                        $tipoUsuario = 'administrativo';
                    }
                }
            }
        }
    }
    // Procesar eliminación de usuario
    elseif (isset($_POST['eliminar_usuario'])) {
        $password = $_POST['admin_password'];
        $idUsuario = $_POST['id_usuario'];
        $tipoUsuario = $_POST['tipo_usuario'];
        
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
                // Eliminar usuario según su tipo
                switch ($tipoUsuario) {
                    case 'alumno':
                        $query = "DELETE FROM alumno WHERE idAlumno = ?";
                        break;
                    case 'docente':
                        $query = "DELETE FROM docente WHERE `idDocente(RFC)` = ?";
                        break;
                    case 'administrativo':
                        $query = "DELETE FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
                        break;
                }
                
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("s", $idUsuario);
                
                if ($stmt->execute()) {
                    $success_message = "Usuario eliminado correctamente";
                    $usuarioEncontrado = null; // Limpiar usuario mostrado
                } else {
                    $error_message = "Error al eliminar usuario: " . $conexion->error;
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
    <title>Buscar Usuario - MindBox</title>
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
        
        .btn-edit, .btn-delete {
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
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
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
        
        .btn-cancel, .btn-confirm {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-cancel {
            background-color: #95a5a6;
            color: white;
            border: none;
        }
        
        .btn-confirm {
            background-color: #e74c3c;
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="inicio_admin.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="administrador_gestionar usuarios.php"><i class="fas fa-user-plus"></i> <span>Registrar Usuario</span></a></li>
            <li class="active"><a href="#"><i class="fas fa-search"></i> <span>Buscar Usuario</span></a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Buscar Usuario</h1>
            <p>Busca perfiles de usuarios en el sistema</p>
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
                    <label for="identificador">Buscar por RFC (docentes/administrativos) o Número de Control (alumnos)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="identificador" name="identificador" required style="flex-grow: 1;">
                        <button type="submit" name="buscar" class="btn-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if ($usuarioEncontrado): ?>
                <div class="profile-header">
                    <div class="profile-photo">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-title"><?php echo htmlspecialchars($usuarioEncontrado['Nombre'] ?? 'Sin nombre'); ?></h2>
                        <span class="profile-type">
                            <?php 
                                echo $tipoUsuario === 'alumno' ? 'Alumno' : 
                                     ($tipoUsuario === 'docente' ? 'Docente' : 'Administrativo'); 
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="user-details">
                    <div class="detail-card">
                        <h4>Información Básica</h4>
                        <div class="detail-item">
                            <span class="detail-label"><?php echo $tipoUsuario === 'alumno' ? 'Número de Control' : 'RFC'; ?></span>
                            <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado[$tipoUsuario === 'alumno' ? 'idAlumno' : ($tipoUsuario === 'docente' ? 'idDocente(RFC)' : 'idAdministrativo(RFC)')]); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Nombre</span>
                            <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Nombre'] ?? ''); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Teléfono</span>
                            <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Telefono'] ?? ''); ?></span>
                        </div>
                        <?php if ($tipoUsuario === 'alumno'): ?>
                            <div class="detail-item">
                                <span class="detail-label">Carrera</span>
                                <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Carrera'] ?? ''); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Semestre</span>
                                <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Semestre'] ?? ''); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-card">
                        <h4>Datos Personales</h4>
                        <?php if ($tipoUsuario === 'alumno'): ?>
                            <div class="detail-item">
                                <span class="detail-label">Fecha de Nacimiento</span>
                                <span class="detail-value"><?php echo !empty($usuarioEncontrado['FechaNacimiento']) ? date('d/m/Y', strtotime($usuarioEncontrado['FechaNacimiento'])) : 'No especificada'; ?></span>
                            </div>
                        <?php else: ?>
                            <div class="detail-item">
                                <span class="detail-label">Fecha de Nacimiento</span>
                                <span class="detail-value"><?php echo !empty($usuarioEncontrado['FechasNacimiento']) ? date('d/m/Y', strtotime($usuarioEncontrado['FechasNacimiento'])) : 'No especificada'; ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <span class="detail-label">Sexo</span>
                            <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Sexo'] ?? 'No especificado'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Domicilio</span>
                            <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Domicilio'] ?? 'No especificado'); ?></span>
                        </div>
                        <?php if ($tipoUsuario === 'alumno'): ?>
                            <div class="detail-item">
                                <span class="detail-label">CURP</span>
                                <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Curp'] ?? 'No especificado'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="detail-card">
                        <h4>Información Académica</h4>
                        <?php if ($tipoUsuario === 'alumno'): ?>
                            <div class="detail-item">
                                <span class="detail-label">Cursos Inscritos</span>
                                <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Cursos'] ?? 'Ninguno'); ?></span>
                            </div>
                        <?php elseif ($tipoUsuario === 'docente'): ?>
                            <div class="detail-item">
                                <span class="detail-label">Cursos que Imparte</span>
                                <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Cursos'] ?? 'Ninguno'); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($tipoUsuario !== 'alumno'): ?>
                            <div class="detail-item">
                                <span class="detail-label">Correo Electrónico</span>
                                <span class="detail-value"><?php echo htmlspecialchars($usuarioEncontrado['Correo'] ?? 'No especificado'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Botones de acción al final -->
                <div class="action-buttons">
                    <a href="editar_usuario.php?tipo=<?php echo $tipoUsuario; ?>&id=<?php echo urlencode($usuarioEncontrado[$tipoUsuario === 'alumno' ? 'idAlumno' : ($tipoUsuario === 'docente' ? 'idDocente(RFC)' : 'idAdministrativo(RFC)')]); ?>" 
                       class="btn-edit">
                        <i class="fas fa-edit"></i> Editar Usuario
                    </a>
                    
                    <button class="btn-delete" onclick="openDeleteModal()">
                        <i class="fas fa-trash"></i> Eliminar Usuario
                    </button>
                </div>
                
                <!-- Modal para confirmar eliminación -->
                <div id="deleteModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Confirmar Eliminación</h3>
                            <span class="close" onclick="closeDeleteModal()">&times;</span>
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="tipo_usuario" value="<?php echo $tipoUsuario; ?>">
                            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($usuarioEncontrado[$tipoUsuario === 'alumno' ? 'idAlumno' : ($tipoUsuario === 'docente' ? 'idDocente(RFC)' : 'idAdministrativo(RFC)')]); ?>">
                            
                            <div class="form-group">
                                <p>¿Está seguro que desea eliminar permanentemente a <?php echo htmlspecialchars($usuarioEncontrado['Nombre'] ?? 'este usuario'); ?>?</p>
                                <p>Esta acción no se puede deshacer.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="admin_password">Ingrese su contraseña de administrador para confirmar:</label>
                                <input type="password" id="admin_password" name="admin_password" required>
                            </div>
                            
                            <div class="modal-actions">
                                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
                                <button type="submit" name="eliminar_usuario" class="btn-confirm">Confirmar Eliminación</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar'])): ?>
                <div class="message error">No se encontró ningún usuario con ese identificador</div>
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