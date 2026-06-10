<?php
// create/tipo_servicio.php
include('../db.php');

$error = null; // Inicializar la variable de error

// Si se envía el formulario, procesar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se usa el operador '??' para evitar warnings si los campos no se envían
    $nombre_servicio = $_POST['nombre_servicio'] ?? '';
    $descripcion_servicio = $_POST['descripcion_servicio'] ?? '';
    $costo = $_POST['costo'] ?? null;

    // Convertir descripción opcional vacía a NULL
    $descripcion_servicio = trim($descripcion_servicio) === '' ? null : $descripcion_servicio;
    
    // Validaciones
    if (trim($nombre_servicio) === '' || $costo === null || !is_numeric($costo) || $costo < 0) {
        $error = 'El nombre y el costo son campos obligatorios. El costo debe ser un número positivo.';
    } else {
        $stmt = $conn->prepare('INSERT INTO tipo_servicio (nombre_servicio, descripcion_servicio, costo) VALUES (?, ?, ?)');
        // La 'd' en el bind_param es para tipo double/decimal
        $stmt->bind_param('ssd', $nombre_servicio, $descripcion_servicio, $costo);
        
        if ($stmt->execute()) {
            header('Location: ../tipo_servicio.php');
            exit();
        } else {
            $error = 'Error al registrar el tipo de servicio: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Tipo de Servicio</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-4">
  <h1>Registrar Tipo de Servicio</h1>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="mb-3">
      <label for="nombre_servicio" class="form-label">Nombre del Servicio:</label>
      <input type="text" id="nombre_servicio" name="nombre_servicio" class="form-control" placeholder="Ej: Reparación de Smartphone" value="<?php echo htmlspecialchars($_POST['nombre_servicio'] ?? ''); ?>" required>
    </div>
    
    <div class="mb-3">
      <label for="descripcion_servicio" class="form-label">Descripción (Opcional):</label>
      <textarea id="descripcion_servicio" name="descripcion_servicio" class="form-control" rows="3" placeholder="Ej: Cambio de pantalla, batería, etc."><?php echo htmlspecialchars($_POST['descripcion_servicio'] ?? ''); ?></textarea>
    </div>

    <div class="mb-3">
        <label for="costo" class="form-label">Costo:</label>
        <input type="number" id="costo" name="costo" class="form-control" step="0.01" min="0" placeholder="Ej: 250.50" value="<?php echo htmlspecialchars($_POST['costo'] ?? ''); ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Registrar Servicio</button>
    <a href="../tipo_servicio.php" class="btn btn-secondary">Volver</a>
  </form>
</div>

</body>
</html>