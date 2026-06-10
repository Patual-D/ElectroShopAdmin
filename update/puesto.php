<?php
// update/puesto.php
include('../db.php');

// Obtener id y página desde la URL de forma segura
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

if ($id <= 0) {
    die('ID de puesto no válido.');
}

// 1. OBTENER DATOS ACTUALES PARA MOSTRAR EN EL FORMULARIO
$stmt = $conn->prepare('SELECT * FROM puesto WHERE id_puesto = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$puesto = $result->fetch_assoc();
$stmt->close();

if (!$puesto) {
    die('Puesto no encontrado.');
}

// 2. PROCESAR EL FORMULARIO SI SE ENVÍA (MÉTODO POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_puesto = trim($_POST['nombre_puesto']);
    $descripcion_puesto = trim($_POST['descripcion_puesto']);

    // Convertir descripción vacía a NULL
    $descripcion_puesto = $descripcion_puesto === '' ? null : $descripcion_puesto;
    
    // Validaciones
    if ($nombre_puesto === '') {
        $error = 'El nombre del puesto es obligatorio.';
    } else {
        // 3. ACTUALIZAR LA BASE DE DATOS
        $stmt = $conn->prepare('UPDATE puesto SET nombre_puesto = ?, descripcion_puesto = ? WHERE id_puesto = ?');
        $stmt->bind_param('ssi', $nombre_puesto, $descripcion_puesto, $id);
        
        if ($stmt->execute()) {
            // 4. REDIRIGIR A LA LISTA
            header('Location: ../puesto.php?pagina=' . $pagina);
            exit();
        } else {
            $error = 'Error al actualizar el puesto: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar Puesto</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-4">
  <h1>Editar Puesto: <?php echo htmlspecialchars($puesto['nombre_puesto']); ?></h1>

  <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="mb-3">
      <label for="nombre_puesto" class="form-label">Nombre del Puesto:</label>
      <input type="text" id="nombre_puesto" name="nombre_puesto" class="form-control" value="<?php echo htmlspecialchars($puesto['nombre_puesto']); ?>" required>
    </div>
    
    <div class="mb-3">
      <label for="descripcion_puesto" class="form-label">Descripción (Opcional):</label>
      <textarea id="descripcion_puesto" name="descripcion_puesto" class="form-control" rows="3"><?php echo htmlspecialchars($puesto['descripcion_puesto']); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Actualizar Puesto</button>
    <a href="../puesto.php?pagina=<?php echo $pagina; ?>" class="btn btn-secondary">Volver</a>
  </form>
</div>

</body>
</html>