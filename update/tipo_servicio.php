<?php
// update/tipo_servicio.php
include('../db.php');

// Obtener id y página desde la URL de forma segura
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$error = null; // Inicializar variable de error

if ($id <= 0) {
    die('ID de servicio no válido.');
}

// 1. OBTENER DATOS ACTUALES PARA MOSTRAR EN EL FORMULARIO
$stmt = $conn->prepare('SELECT * FROM tipo_servicio WHERE id_tipo_servicio = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$tipo_servicio = $result->fetch_assoc();
$stmt->close();

if (!$tipo_servicio) {
    die('Tipo de servicio no encontrado.');
}

// 2. PROCESAR EL FORMULARIO SI SE ENVÍA (MÉTODO POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se recogen las variables del formulario
    $nombre_servicio = $_POST['nombre_servicio'] ?? '';
    $descripcion_servicio = $_POST['descripcion_servicio'] ?? '';
    $costo = $_POST['costo'] ?? null;

    // Convertir descripción vacía a NULL para la base de datos
    $descripcion_servicio = trim($descripcion_servicio) === '' ? null : $descripcion_servicio;
    
    // Validaciones
    if (trim($nombre_servicio) === '' || $costo === null || !is_numeric($costo) || $costo < 0) {
        $error = 'El nombre y el costo son campos obligatorios. El costo debe ser un número positivo.';
        // Para que el formulario muestre los datos erroneos que el usuario intentó enviar
        $tipo_servicio['nombre_servicio'] = $nombre_servicio;
        $tipo_servicio['descripcion_servicio'] = $descripcion_servicio;
        $tipo_servicio['costo'] = $costo;
    } else {
        // 3. ACTUALIZAR LA BASE DE DATOS
        $stmt = $conn->prepare('UPDATE tipo_servicio SET nombre_servicio = ?, descripcion_servicio = ?, costo = ? WHERE id_tipo_servicio = ?');
        // Los tipos son: string, string, double, integer
        $stmt->bind_param('ssdi', $nombre_servicio, $descripcion_servicio, $costo, $id);
        
        if ($stmt->execute()) {
            // 4. REDIRIGIR A LA LISTA
            header('Location: ../tipo_servicio.php?pagina=' . $pagina);
            exit();
        } else {
            $error = 'Error al actualizar el tipo de servicio: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar Tipo de Servicio</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-4">
  <h1>Editar Tipo de Servicio: <?php echo htmlspecialchars($tipo_servicio['nombre_servicio']); ?></h1>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="mb-3">
      <label for="nombre_servicio" class="form-label">Nombre del Servicio:</label>
      <input type="text" id="nombre_servicio" name="nombre_servicio" class="form-control" value="<?php echo htmlspecialchars($tipo_servicio['nombre_servicio']); ?>" required>
    </div>
    
    <div class="mb-3">
      <label for="descripcion_servicio" class="form-label">Descripción (Opcional):</label>
      <textarea id="descripcion_servicio" name="descripcion_servicio" class="form-control" rows="3"><?php echo htmlspecialchars($tipo_servicio['descripcion_servicio']); ?></textarea>
    </div>

    <div class="mb-3">
        <label for="costo" class="form-label">Costo:</label>
        <input type="number" id="costo" name="costo" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($tipo_servicio['costo']); ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Actualizar Servicio</button>
    <a href="../tipo_servicio.php?pagina=<?php echo $pagina; ?>" class="btn btn-secondary">Volver</a>
  </form>
</div>

</body>
</html>