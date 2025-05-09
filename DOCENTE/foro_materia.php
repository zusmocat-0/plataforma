<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['idDocente'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Obtener el ID de la materia desde la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: docente_foro.php");
    exit();
}

$idMateria = $_GET['id'];
$rfcDocente = $_SESSION['idDocente'];

// Obtener información de la materia
$queryMateria = "SELECT m.idMateria, m.Nombre, m.Descripcionmateria, m.NumeroUnidades, m.Unidades 
                 FROM materia m 
                 WHERE m.idMateria = ?";
$stmtMateria = $conexion->prepare($queryMateria);
$stmtMateria->bind_param("s", $idMateria);
$stmtMateria->execute();
$resultMateria = $stmtMateria->get_result();

if ($resultMateria->num_rows === 0) {
    header("Location: docente_foro.php");
    exit();
}

$materia = $resultMateria->fetch_assoc();
$unidades = json_decode($materia['Unidades'], true);

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $contenido = $_POST['contenido'] ?? '';
    $unidadSeleccionada = $_POST['unidad'] ?? null;
    
    // Validar los datos
    if (empty($titulo) || empty($contenido) || $unidadSeleccionada === null) {
        $error = "Todos los campos son obligatorios";
    } else {
        // Generar ID único para el foro
        $idForo = uniqid('FORO_');
        
        // Insertar el foro en la base de datos
        $queryInsert = "INSERT INTO foro (idForo, idMateria, idAutor, Tituloforo, Contenido, Unidad, Fecha) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmtInsert = $conexion->prepare($queryInsert);
        $stmtInsert->bind_param("sssssi", $idForo, $idMateria, $rfcDocente, $titulo, $contenido, $unidadSeleccionada);
        
        if ($stmtInsert->execute()) {
            $success = "Foro publicado exitosamente";
            // Limpiar los campos del formulario
            $titulo = $contenido = '';
            $unidadSeleccionada = null;
        } else {
            $error = "Error al publicar el foro: " . $conexion->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Publicar Foro</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .form-group input[type="text"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            width: 100%;
            height: 200px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            resize: vertical;
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-submit:hover {
            background-color: var(--primary-dark);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .toolbar {
            margin-bottom: 10px;
        }
        
        .toolbar button {
            background: none;
            border: 1px solid #ddd;
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
            border-radius: 3px;
        }
        
        .toolbar button:hover {
            background-color: #f0f0f0;
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
            <li><a href="docente_inicio.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li class="active"><a href="docente_foro.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="docente_anuncios.php"><i class="fas fa-bullhorn"></i> <span>Anuncios</span></a></li>
            <li><a href="docente_grupos.php"><i class="fas fa-user-group"></i> <span>Grupos</span></a></li>
            <li><a href="/logout.php"><i class="fas fa-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Publicar Foro - <?php echo htmlspecialchars($materia['Nombre']); ?></h1>
        <p>Crear un nuevo tema de discusión para esta materia</p>
    </div>
    
    <div class="main-content">
        <div class="dashboard-card">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="titulo">Título del Foro</label>
                        <input type="text" id="titulo" name="titulo" value="<?php echo isset($titulo) ? htmlspecialchars($titulo) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contenido">Contenido</label>
                        <div class="toolbar">
                            <button type="button" onclick="insertText('[b]', '[/b]')"><i class="fas fa-bold"></i></button>
                            <button type="button" onclick="insertText('[i]', '[/i]')"><i class="fas fa-italic"></i></button>
                            <button type="button" onclick="insertText('[url]', '[/url]')"><i class="fas fa-link"></i></button>
                        </div>
                        <textarea id="contenido" name="contenido" required><?php echo isset($contenido) ? htmlspecialchars($contenido) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="unidad">Unidad de la Materia</label>
                        <select id="unidad" name="unidad" required>
                            <option value="">Seleccione una unidad</option>
                            <?php foreach ($unidades as $index => $unidad): ?>
                                <option value="<?php echo $index + 1; ?>" <?php echo (isset($unidadSeleccionada) && $unidadSeleccionada == ($index + 1)) ? 'selected' : ''; ?>>
                                    Unidad <?php echo $index + 1; ?>: <?php echo htmlspecialchars($unidad['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Publicar Foro
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="/scripts.js"></script>
    <script>
        // Función para insertar texto en el área de contenido
        function insertText(prefix, suffix) {
            const textarea = document.getElementById('contenido');
            const startPos = textarea.selectionStart;
            const endPos = textarea.selectionEnd;
            const selectedText = textarea.value.substring(startPos, endPos);
            
            // Si hay texto seleccionado, lo envolvemos
            if (selectedText) {
                textarea.value = textarea.value.substring(0, startPos) + 
                                prefix + selectedText + suffix + 
                                textarea.value.substring(endPos);
            } else {
                // Si no hay texto seleccionado, insertamos los tags y colocamos el cursor entre ellos
                textarea.value = textarea.value.substring(0, startPos) + 
                                prefix + suffix + 
                                textarea.value.substring(endPos);
                // Colocar el cursor entre los tags
                textarea.selectionStart = startPos + prefix.length;
                textarea.selectionEnd = startPos + prefix.length;
            }
            
            // Enfocar el textarea de nuevo
            textarea.focus();
        }
        
        // Ejemplo de cómo podrías implementar un enlace
        function insertLink() {
            const url = prompt("Ingrese la URL:", "http://");
            if (url) {
                const text = prompt("Ingrese el texto del enlace:", "");
                insertText(`[url=${url}]`, `[/url]${text ? ` ${text}` : ''}`);
            }
        }
    </script>
</body>
</html>