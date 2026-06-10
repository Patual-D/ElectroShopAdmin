<?php
// create/puesto.php
include('../db.php');

$error = null;

// Si se envía el formulario, procesar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_puesto = $_POST['nombre_puesto'] ?? '';
    $descripcion_puesto = $_POST['descripcion_puesto'] ?? '';

    // Convertir descripción opcional vacía a NULL
    $descripcion_puesto = trim($descripcion_puesto) === '' ? null : $descripcion_puesto;
    
    // Validar que el nombre no esté vacío
    if (trim($nombre_puesto) === '') {
        $error = 'El nombre del puesto es un campo obligatorio.';
    } else {
        // Insertar en la base de datos
        $stmt = $conn->prepare('INSERT INTO puesto (nombre_puesto, descripcion_puesto) VALUES (?, ?)');
        $stmt->bind_param('ss', $nombre_puesto, $descripcion_puesto);
        
        if ($stmt->execute()) {
            header('Location: ../puesto.php');
            exit();
        } else {
            $error = 'Error al registrar el puesto: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Puesto</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-4">
  <h1>Registrar Nuevo Puesto</h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="mb-3">
      <label for="nombre_puesto" class="form-label">Nombre del Puesto:</label>
      <input type="text" id="nombre_puesto" name="nombre_puesto" class="form-control" placeholder="Ej: Gerente de Ventas" value="<?php echo htmlspecialchars($_POST['nombre_puesto'] ?? ''); ?>" required>
    </div>
    
    <div class="mb-3">
      <label for="descripcion_puesto" class="form-label">Descripción (Opcional):</label>
      <textarea id="descripcion_puesto" name="descripcion_puesto" class="form-control" rows="3" placeholder="Ej: Responsable del equipo de ventas y estrategias comerciales."><?php echo htmlspecialchars($_POST['descripcion_puesto'] ?? ''); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Registrar Puesto</button>
    <a href="../puesto.php" class="btn btn-secondary">Volver</a>
  </form>
</div>

</body>
</html>