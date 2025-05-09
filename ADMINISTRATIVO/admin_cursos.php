<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../conexion.php';

// 1. Obtener todas las carreras
$carreras = [];
$queryCarreras = "SELECT c.idCarrera, c.Nombre FROM carrera c ORDER BY c.Nombre";
$resultCarreras = $conexion->query($queryCarreras);

while ($row = $resultCarreras->fetch_assoc()) {
    $carreras[$row['idCarrera']] = [
        'nombre' => $row['Nombre'],
        'materias' => []
    ];
}

// 2. Obtener todas las materias con su información completa (incluyendo unidades)
$queryMaterias = "SELECT 
    m.idMateria, 
    m.Nombre, 
    m.Descripcionmateria, 
    m.NumeroUnidades, 
    m.Unidades, 
    cm.Carrera AS nombre_carrera
FROM materia m
LEFT JOIN carrera_materia cm ON m.idMateria = cm.Materia
ORDER BY cm.Carrera, m.Nombre";
$resultMaterias = $conexion->query($queryMaterias);

// Organizar materias por carrera
// Modifica esta parte del código:
    while ($row = $resultMaterias->fetch_assoc()) {
        $unidades = json_decode($row['Unidades'], true) ?: [];
        
        $materia = [
            'id' => $row['idMateria'],
            'nombre' => $row['Nombre'],
            'descripcion' => $row['Descripcionmateria'],
            'unidades_totales' => $row['NumeroUnidades'],
            'unidades' => $unidades,
            'carrera_nombre' => $row['nombre_carrera'] // Cambiado de carrera_id a carrera_nombre
        ];
        
        // Si tiene carrera asignada
        if (!empty($row['nombre_carrera'])) {
            // Buscar la carrera por nombre en lugar de ID
            foreach ($carreras as &$carreraData) {
                if ($carreraData['nombre'] == $row['nombre_carrera']) {
                    $carreraData['materias'][] = $materia;
                    break;
                }
            }
        } else {
            // Materias sin carrera asignada
            if (!isset($materiasSinCarrera)) {
                $materiasSinCarrera = [];
            }
            $materiasSinCarrera[] = $materia;
        }
    }

// Procesar formularios de modificación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar contraseña para acciones críticas
    if (isset($_POST['action']) && in_array($_POST['action'], ['add_carrera', 'delete_materia'])) {
        $password = $_POST['admin_password'] ?? '';
        $query = "SELECT password FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $_SESSION['idAdmin']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = "Error: Administrador no encontrado";
            header("Location: admin_cursos.php");
            exit();
        }
        
        $admin = $result->fetch_assoc();
        
        // Verificación de contraseña SIN encriptación (ya que no están encriptadas)
        if ($password !== $admin['password']) {
            $_SESSION['error'] = "Contraseña incorrecta";
            header("Location: admin_cursos.php");
            exit();
        }
    }
    
    // Añadir nueva carrera
// Añadir nueva carrera
if (isset($_POST['action']) && $_POST['action'] == 'add_carrera') {
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $query = "INSERT INTO carrera (Nombre) VALUES ('$nombre')";
    if ($conexion->query($query)) {
        $_SESSION['success'] = "Carrera añadida correctamente";
    } else {
        $_SESSION['error'] = "Error al añadir carrera: " . $conexion->error;
    }
    header("Location: admin_cursos.php");
    exit();
}
    
    // Añadir nueva materia
// Añadir nueva materia (parte corregida)
// Añadir nueva materia (versión corregida)
if (isset($_POST['action']) && $_POST['action'] == 'add_materia') {
    // 1. Recoger y sanitizar datos del formulario
    $idMateria = $conexion->real_escape_string($_POST['codigo']);
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $carreraId = $conexion->real_escape_string($_POST['carrera_id']);
    $nombresUnidades = trim($conexion->real_escape_string($_POST['nombres_unidades']));
    
    // 2. Validar formato del código de materia
    if (!preg_match('/^[A-Za-z]{3}\d{3}$/', $idMateria)) {
        $_SESSION['error'] = "El código de materia debe tener 3 letras seguidas de 3 números (ej: ISC101)";
        header("Location: admin_cursos.php");
        exit();
    }
    
    // 3. Verificar si el ID de materia ya existe
    $queryVerificar = "SELECT idMateria FROM materia WHERE idMateria = '$idMateria'";
    $resultVerificar = $conexion->query($queryVerificar);
    
    if ($resultVerificar->num_rows > 0) {
        $_SESSION['error'] = "Error: El ID de materia ya existe";
        header("Location: admin_cursos.php");
        exit();
    }
    
    // 4. Procesar los nombres de las unidades (PARTE FALTANTE QUE CAUSABA EL ERROR)
    $unidadesArray = [];
    $unidadesInput = explode(',', $nombresUnidades);
    $numeroUnidades = 0;  // Inicializamos la variable que faltaba
    
    foreach ($unidadesInput as $nombreUnidad) {
        $nombreUnidad = trim($nombreUnidad);
        if (!empty($nombreUnidad)) {
            $numeroUnidades++;
            $unidadesArray[] = ['nombre' => "Unidad $numeroUnidades: $nombreUnidad"];
        }
    }
    
    // Validar que haya al menos una unidad
    if ($numeroUnidades === 0) {
        $_SESSION['error'] = "Debe ingresar al menos una unidad";
        header("Location: admin_cursos.php");
        exit();
    }
    
    $unidadesJson = json_encode($unidadesArray);  // Creamos el JSON que faltaba
    
    // 5. Obtener el NOMBRE de la carrera usando el ID
    $queryNombreCarrera = "SELECT Nombre FROM carrera WHERE idCarrera = '$carreraId'";
    $resultNombre = $conexion->query($queryNombreCarrera);
    
    if ($resultNombre->num_rows === 0) {
        $_SESSION['error'] = "Error: Carrera no encontrada";
        header("Location: admin_cursos.php");
        exit();
    }
    
    $carreraData = $resultNombre->fetch_assoc();
    $nombreCarrera = $carreraData['Nombre'];
    
    // 6. Insertar materia (AHORA CON LAS VARIABLES CORRECTAMENTE DEFINIDAS)
    $query = "INSERT INTO materia (idMateria, Nombre, Descripcionmateria, NumeroUnidades, Unidades) 
             VALUES ('$idMateria', '$nombre', '$descripcion', $numeroUnidades, '$unidadesJson')";
    
    if ($conexion->query($query)) {
        // 7. Insertar en carrera_materia usando el NOMBRE de la carrera
        $queryRelacion = "INSERT INTO carrera_materia (Carrera, Materia) VALUES (
                         '".$conexion->real_escape_string($nombreCarrera)."', 
                         '".$conexion->real_escape_string($idMateria)."')";
        
        if ($conexion->query($queryRelacion)) {
            $_SESSION['success'] = "Materia añadida correctamente a $nombreCarrera";
        } else {
            $_SESSION['error'] = "Error al relacionar con la carrera: " . $conexion->error;
        }
    } else {
        $_SESSION['error'] = "Error al añadir materia: " . $conexion->error;
    }
    header("Location: admin_cursos.php");
    exit();
}
    
    // Editar materia
    if (isset($_POST['action']) && $_POST['action'] == 'edit_materia') {
        $idMateria = $conexion->real_escape_string($_POST['id']);
        $nombre = $conexion->real_escape_string($_POST['nombre']);
        $descripcion = $conexion->real_escape_string($_POST['descripcion']);
        $unidades = intval($_POST['unidades']);
        $carreraId = intval($_POST['carrera_id']);
        
        // Obtener el nombre de la carrera desde el ID
    $queryNombreCarrera = "SELECT Nombre FROM carrera WHERE idCarrera = '$carreraId'";
    $resultNombre = $conexion->query($queryNombreCarrera);
    $carreraData = $resultNombre->fetch_assoc();
    $nombreCarrera = $carreraData['Nombre'];
    
    // Actualizar relación en carrera_materia usando el nombre
    $queryRelacion = "UPDATE carrera_materia SET Carrera = '$nombreCarrera' WHERE Materia = '$idMateria'";
        
        $unidadesJson = $materiaActual['Unidades'];
        if ($unidades != $materiaActual['NumeroUnidades']) {
            // Reconstruir unidades si cambia el número
            $unidadesActuales = json_decode($materiaActual['Unidades'], true) ?: [];
            $nuevasUnidades = [];
            
            for ($i = 0; $i < $unidades; $i++) {
                $nombreUnidad = $i < count($unidadesActuales) ? 
                    $unidadesActuales[$i]['nombre'] : "Unidad " . ($i + 1);
                $nuevasUnidades[] = ['nombre' => $nombreUnidad];
            }
            $unidadesJson = json_encode($nuevasUnidades);
        }
        
        $query = "UPDATE materia SET 
                 Nombre = '$nombre',
                 Descripcionmateria = '$descripcion',
                 NumeroUnidades = $unidades,
                 Unidades = '$unidadesJson'
                 WHERE idMateria = '$idMateria'";
        
        if ($conexion->query($query)) {
            // Actualizar relación con carrera si es necesario
            $queryRelacion = "UPDATE carrera_materia SET Carrera = $carreraId WHERE Materia = '$idMateria'";
            $conexion->query($queryRelacion);
            
            $_SESSION['success'] = "Materia actualizada correctamente";
        } else {
            $_SESSION['error'] = "Error al actualizar materia: " . $conexion->error;
        }
        header("Location: admin_cursos.php");
        exit();
    }
    
    // Eliminar materia
    if (isset($_POST['action']) && $_POST['action'] == 'delete_materia') {
        $idMateria = $conexion->real_escape_string($_POST['id']);
        $password = $_POST['admin_password'] ?? '';
        
        // Verificar contraseña de administrador
        $query = "SELECT password FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
        $stmt = $conexion->prepare($query);
        $stmt->bind_param("s", $_SESSION['idAdmin']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error'] = "Error: Administrador no encontrado";
            header("Location: admin_cursos.php");
            exit();
        }
        
        $admin = $result->fetch_assoc();
        
        // Verificar contraseña SIN password_verify()
        if ($password !== $admin['password']) {
            $_SESSION['error'] = "Contraseña incorrecta";
            header("Location: admin_cursos.php");
            exit();
        }
        
        // Si la contraseña es correcta, proceder con la eliminación
        // Eliminar relaciones primero
        $queryRelaciones = "DELETE FROM carrera_materia WHERE Materia = '$idMateria'";
        $conexion->query($queryRelaciones);
        
        // Luego eliminar la materia
        $query = "DELETE FROM materia WHERE idMateria = '$idMateria'";
        if ($conexion->query($query)) {
            $_SESSION['success'] = "Materia eliminada correctamente";
        } else {
            $_SESSION['error'] = "Error al eliminar materia: " . $conexion->error;
        }
        header("Location: admin_cursos.php");
        exit();
    }
    
    // Editar unidades
    if (isset($_POST['action']) && $_POST['action'] == 'edit_unidades') {
        $idMateria = $conexion->real_escape_string($_POST['id']);
        $unidadesData = [];
        
        foreach ($_POST['unidades'] as $unidad) {
            $nombre = $conexion->real_escape_string($unidad['nombre']);
            // Mantener el formato "Unidad X: Nombre"
            if (!preg_match('/^Unidad \d+: /', $nombre)) {
                // Si no tiene el formato, añadirlo
                $index = count($unidadesData) + 1;
                $nombre = "Unidad $index: " . $nombre;
            }
            $unidadesData[] = ['nombre' => $nombre];
        }
        
        $unidadesJson = json_encode($unidadesData);
        $numUnidades = count($unidadesData);
        
        $query = "UPDATE materia SET 
                 NumeroUnidades = $numUnidades,
                 Unidades = '$unidadesJson'
                 WHERE idMateria = '$idMateria'";
        
        if ($conexion->query($query)) {
            $_SESSION['success'] = "Unidades actualizadas correctamente";
        } else {
            $_SESSION['error'] = "Error al actualizar unidades: " . $conexion->error;
        }
        header("Location: admin_cursos.php");
        exit();
    }
}

// Mostrar mensajes de éxito/error
if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Administrar Grupos</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        <h1>Administrar Grupos</h1>
        <p>Gestiona los grupos de materias por carrera</p>
    </div>

    <div class="main-content">
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <!-- Botón para añadir nueva carrera -->
        <div style="margin-bottom: 20px;">
           
            <button class="btn-add" onclick="openAddMateriaModal()" style="margin-left: 10px;">
                <i class="fas fa-plus"></i> Añadir Nueva Materia
            </button>
        </div>
        
        <!-- Mostrar materias agrupadas por carrera -->
        <?php foreach ($carreras as $carreraId => $carreraData): ?>
            <div class="career-section">
                <div class="career-header">
                    <h2 class="career-title"><?php echo htmlspecialchars($carreraData['nombre']); ?></h2>
                </div>
                
                <?php if (!empty($carreraData['materias'])): ?>
                    <div class="course-list">
                        <?php foreach ($carreraData['materias'] as $materia): ?>
                            <div class="course-item">
                                <h4><?php echo htmlspecialchars($materia['nombre']); ?></h4>
                                <div class="course-code"><?php echo htmlspecialchars($materia['id']); ?></div>
                                <div class="course-description"><?php echo htmlspecialchars($materia['descripcion']); ?></div>
                                
                                <!-- Mostrar unidades -->
                                <div class="unidades-list">
                                    <strong>Unidades:</strong>
                                    <ul>
                                        <?php foreach ($materia['unidades'] as $unidad): ?>
                                            <li><?php echo htmlspecialchars($unidad['nombre']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                
                                <div class="btn-group">
                                <a href="#" class="btn-edit" onclick="openEditModal(
    '<?php echo $materia['id']; ?>', 
    '<?php echo htmlspecialchars(addslashes($materia['nombre'])); ?>', 
    '<?php echo htmlspecialchars(addslashes($materia['descripcion'])); ?>', 
    <?php echo $materia['unidades_totales']; ?>,
    '<?php echo isset($materia['carrera_nombre']) ? htmlspecialchars(addslashes($materia['carrera_nombre'])) : ''; ?>'
)">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="#" class="btn" onclick="openUnidadesModal('<?php echo $materia['id']; ?>', <?php echo htmlspecialchars(json_encode($materia['unidades'])); ?>)">
                                        <i class="fas fa-list-ol"></i> Unidades
                                    </a>
                                    <a href="#" class="btn-delete" onclick="confirmDelete('<?php echo $materia['id']; ?>', '<?php echo htmlspecialchars(addslashes($materia['nombre'])); ?>')">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No hay materias asignadas a esta carrera.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
        <!-- Mostrar materias sin carrera asignada -->
        <?php if (!empty($materiasSinCarrera)): ?>
            <div class="career-section">
                <div class="career-header">
                    <h2 class="career-title">Materias sin carrera asignada</h2>
                </div>
                
                <div class="course-list">
                    <?php foreach ($materiasSinCarrera as $materia): ?>
                        <div class="course-item">
                            <h4><?php echo htmlspecialchars($materia['nombre']); ?></h4>
                            <div class="course-code"><?php echo htmlspecialchars($materia['id']); ?></div>
                            <div class="course-description"><?php echo htmlspecialchars($materia['descripcion']); ?></div>
                            
                            <!-- Mostrar unidades -->
                            <div class="unidades-list">
                                <strong>Unidades:</strong>
                                <ul>
                                    <?php foreach ($materia['unidades'] as $unidad): ?>
                                        <li><?php echo htmlspecialchars($unidad['nombre']); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="btn-group">
                                <a href="#" class="btn-edit" onclick="openEditModal(
                                    '<?php echo $materia['id']; ?>', 
                                    '<?php echo htmlspecialchars(addslashes($materia['nombre'])); ?>', 
                                    '<?php echo htmlspecialchars(addslashes($materia['descripcion'])); ?>', 
                                    <?php echo $materia['unidades_totales']; ?>,
                                    null
                                )">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <a href="#" class="btn-unidades" onclick="openUnidadesModal('<?php echo $materia['id']; ?>', <?php echo htmlspecialchars(json_encode($materia['unidades'])); ?>)">
                                    <i class="fas fa-list-ol"></i> Unidades
                                </a>
                                <a href="#" class="btn-delete" onclick="confirmDelete('<?php echo $materia['id']; ?>', '<?php echo htmlspecialchars(addslashes($materia['nombre'])); ?>')">
                                    <i class="fas fa-trash"></i> Eliminar
                                </a>
                                <a href="asignar_carrera.php?id=<?php echo $materia['id']; ?>" class="btn-edit">
                                    <i class="fas fa-link"></i> Asignar Carrera
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Career Modal -->
    <div id="addCareerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Añadir Nueva Carrera</h3>
                <span class="close" onclick="closeAddCareerModal()">&times;</span>
            </div>
            <form id="addCareerForm" action="admin_cursos.php" method="POST">
                <input type="hidden" name="action" value="add_carrera">
                <div class="form-group">
                    <label for="nombre_carrera">Nombre de la Carrera:</label>
                    <input type="text" id="nombre_carrera" name="nombre" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddCareerModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div id="addMateriaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Añadir Nueva Materia</h3>
                <span class="close" onclick="closeAddMateriaModal()">&times;</span>
            </div>
            <form id="addMateriaForm" action="admin_cursos.php" method="POST">
                <input type="hidden" name="action" value="add_materia">
                <div class="form-group">
                    <label for="add_carrera_id">Carrera:</label>
                    <select id="add_carrera_id" name="carrera_id" required>
                        <option value="">Seleccione una carrera</option>
                        <?php foreach ($carreras as $carreraId => $carreraData): ?>
                            <option value="<?php echo $carreraId; ?>"><?php echo htmlspecialchars($carreraData['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="codigo">Código de Materia:</label>
                    <input type="text" id="codigo" name="codigo" required>
                    <small class="text-muted">Este ID debe ser único</small>
                </div>
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion" required></textarea>
                </div>
                <div class="form-group">
                    <label for="nombres_unidades">Nombres de Unidades (separados por comas):</label>
                    <textarea id="nombres_unidades" name="nombres_unidades" required placeholder="Ejemplo: Introducción, Variables, Funciones, Arreglos"></textarea>
                    <small class="text-muted">Ingrese los nombres de las unidades separados por comas. Se convertirán automáticamente a "Unidad 1: Introducción", etc.</small>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddMateriaModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Course Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Materia</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editForm" action="admin_cursos.php" method="POST">
                <input type="hidden" name="action" value="edit_materia">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_carrera_id">Carrera:</label>
                    <select id="edit_carrera_id" name="carrera_id" required>
                        <option value="">Seleccione una carrera</option>
                        <?php foreach ($carreras as $carreraId => $carreraData): ?>
                            <option value="<?php echo $carreraId; ?>"><?php echo htmlspecialchars($carreraData['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_codigo">Código de Materia:</label>
                    <input type="text" id="edit_codigo" name="codigo" readonly>
                </div>
                <div class="form-group">
                    <label for="edit_nombre">Nombre:</label>
                    <input type="text" id="edit_nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="edit_descripcion">Descripción:</label>
                    <textarea id="edit_descripcion" name="descripcion" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_unidades">Número de Unidades:</label>
                    <input type="number" id="edit_unidades" name="unidades" min="1" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Unidades Modal -->
    <div id="unidadesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Unidades</h3>
                <span class="close" onclick="closeUnidadesModal()">&times;</span>
            </div>
            <form id="unidadesForm" action="admin_cursos.php" method="POST">
                <input type="hidden" name="action" value="edit_unidades">
                <input type="hidden" id="unidades_id" name="id">
                <div id="unidades-container">
                    <!-- Las unidades se agregarán aquí dinámicamente -->
                </div>
                <button type="button" class="btn-add-unidad" onclick="addUnidadField()">
                    <i class="fas fa-plus"></i> Añadir Unidad
                </button>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeUnidadesModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Eliminación</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <form id="deleteForm" action="admin_cursos.php" method="POST">
                <input type="hidden" name="action" value="delete_materia">
                <input type="hidden" id="delete_id" name="id">
                <div class="form-group">
                    <p id="delete-message">¿Estás seguro de que deseas eliminar esta materia?</p>
                </div>
                <div class="form-group">
                    <label for="delete_password">Contraseña de Administrador:</label>
                    <input type="password" id="delete_password" name="admin_password" required autocomplete="current-password">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancelar</button>
                    <button type="submit" class="btn-submit">Confirmar Eliminación</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/scripts.js"></script>
    <script>
        // Función para validar ID único antes de enviar el formulario
        document.getElementById('addMateriaForm').addEventListener('submit', function(e) {
            const codigo = document.getElementById('codigo').value;
            
            // Validación de ID único (puedes hacerlo más robusto con AJAX)
            if (!codigo) {
                e.preventDefault();
                alert('El código de materia es requerido');
                return;
            }
        });

        // Función para abrir el modal de añadir carrera
        function openAddCareerModal() {
            document.getElementById('addCareerModal').style.display = 'block';
        }
        
        // Función para cerrar el modal de añadir carrera
        function closeAddCareerModal() {
            document.getElementById('addCareerModal').style.display = 'none';
        }
        
        // Función para abrir el modal de añadir materia
        function openAddMateriaModal() {
            // Limpiar formulario al abrir
            document.getElementById('addMateriaForm').reset();
            document.getElementById('addMateriaModal').style.display = 'block';
        }
        
        // Función para cerrar el modal de añadir materia
        function closeAddMateriaModal() {
            document.getElementById('addMateriaModal').style.display = 'none';
        }
        
        // Función para abrir el modal de editar materia
// Modifica la función openEditModal
function openEditModal(materiaId, nombre, descripcion, unidades, carreraNombre) {
    document.getElementById('edit_id').value = materiaId;
    document.getElementById('edit_codigo').value = materiaId;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_descripcion').value = descripcion;
    document.getElementById('edit_unidades').value = unidades;
    
    if (carreraNombre) {
        var select = document.getElementById('edit_carrera_id');
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].text === carreraNombre) {
                select.selectedIndex = i;
                break;
            }
        }
    }
    
    document.getElementById('editModal').style.display = 'block';
}
        
        // Función para cerrar el modal de editar materia
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Función para abrir el modal de unidades
        function openUnidadesModal(materiaId, unidades) {
            document.getElementById('unidades_id').value = materiaId;
            const container = document.getElementById('unidades-container');
            container.innerHTML = '';
            
            unidades.forEach((unidad, index) => {
                addUnidadField(unidad.nombre);
            });
            
            document.getElementById('unidadesModal').style.display = 'block';
        }
        
        // Función para cerrar el modal de unidades
        function closeUnidadesModal() {
            document.getElementById('unidadesModal').style.display = 'none';
        }
        
        // Función para añadir un campo de unidad
        function addUnidadField(nombre = '') {
            const container = document.getElementById('unidades-container');
            const index = container.children.length;
            
            const group = document.createElement('div');
            group.className = 'unidad-input-group';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.name = `unidades[${index}][nombre]`;
            input.placeholder = `Nombre de la Unidad ${index + 1}`;
            input.value = nombre;
            input.required = true;
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-remove-unidad';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.onclick = function() {
                container.removeChild(group);
                // Renumerar los índices restantes
                Array.from(container.children).forEach((child, i) => {
                    child.querySelector('input').name = `unidades[${i}][nombre]`;
                    child.querySelector('input').placeholder = `Nombre de la Unidad ${i + 1}`;
                });
            };
            
            group.appendChild(input);
            group.appendChild(removeBtn);
            container.appendChild(group);
        }
        
        // Función para confirmar eliminación
        function confirmDelete(materiaId, nombreMateria) {
            document.getElementById('delete_id').value = materiaId;
            document.getElementById('delete-message').textContent = `¿Estás seguro de que deseas eliminar la materia "${nombreMateria}"?`;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Función para cerrar el modal de confirmación de eliminación
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Cerrar modales al hacer clic fuera
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                document.getElementById('addCareerModal').style.display = 'none';
                document.getElementById('addMateriaModal').style.display = 'none';
                document.getElementById('editModal').style.display = 'none';
                document.getElementById('unidadesModal').style.display = 'none';
                document.getElementById('deleteModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>