<?php
// create/categoria.php
include('../db.php');

// Si se envía el formulario, procesar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_categoria = trim($_POST['nombre_categoria']);

    // Validaciones
    if ($nombre_categoria === '') {
        $error = 'El nombre de la categoría es un campo obligatorio.';
    } else {
        // Verificación de duplicados
        $stmtCheck = $conn->prepare("SELECT id_categoria FROM categoria WHERE nombre_categoria = ?");
        $stmtCheck->bind_param('s', $nombre_categoria);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        
        if ($stmtCheck->num_rows > 0) {
            $error = 'Esa categoría ya existe.';
        } else {
            // Si no hay errores, se procede a insertar
            $stmt = $conn->prepare('INSERT INTO categoria (nombre_categoria) VALUES (?)');
            $stmt->bind_param('s', $nombre_categoria);
            
            if ($stmt->execute()) {
                header('Location: ../categoria.php');
                exit();
            } else {
                $error = 'Error al registrar la categoría: ' . $stmt->error;
            }
            $stmt->close();
        }
        $stmtCheck->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Categoría</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-4">
  <h1>Registrar Nueva Categoría</h1>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="mb-3">
      <label for="nombre_categoria" class="form-label">Nombre de la Categoría:</label>
      <input type="text" id="nombre_categoria" name="nombre_categoria" class="form-control" placeholder="Ej: Laptops" required>
    </div>

    <button type="submit" class="btn btn-primary">Registrar Categoría</button>
    <a href="../categoria.php" class="btn btn-secondary">Volver</a>
  </form>
</div>

</body>
</html>