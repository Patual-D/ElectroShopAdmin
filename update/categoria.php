<?php
// update/categoria.php
include('../db.php');

// Obtener id y página desde la URL de forma segura
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$error = null; // Inicializar la variable de error

if ($id <= 0) {
    die('ID de categoría no válido.');
}

// 1. OBTENER DATOS ACTUALES PARA MOSTRAR EN EL FORMULARIO
$stmt = $conn->prepare('SELECT * FROM categoria WHERE id_categoria = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$categoria = $result->fetch_assoc();
$stmt->close();

// Si no se encuentra la categoría, detener el script
if (!$categoria) {
    die('Categoría no encontrada.');
}

// 2. PROCESAR EL FORMULARIO SI SE ENVÍA (MÉTODO POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se recogen las variables del formulario
    $nombre_categoria = $_POST['nombre_categoria'] ?? '';
    
    // Validaciones
    if (trim($nombre_categoria) === '') {
        $error = 'El nombre de la categoría es obligatorio.';
        // Para que el formulario muestre el dato erróneo que el usuario intentó enviar
        $categoria['nombre_categoria'] = $nombre_categoria;
    } else {
        // 3. ACTUALIZAR LA BASE DE DATOS
        $stmt = $conn->prepare('UPDATE categoria SET nombre_categoria = ? WHERE id_categoria = ?');
        // Los tipos son: string, integer
        $stmt->bind_param('si', $nombre_categoria, $id);
        
        if ($stmt->execute()) {
            // 4. REDIRIGIR A LA LISTA, MANTENIENDO LA PÁGINA
            header('Location: ../categoria.php?pagina=' . $pagina);
            exit();
        } else {
            $error = 'Error al actualizar la categoría: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar Categoría</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-4">
  <h1>Editar Categoría: <?php echo htmlspecialchars($categoria['nombre_categoria']); ?></h1>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" action="">
    <div class="mb-3">
      <label for="nombre_categoria" class="form-label">Nombre de la Categoría:</label>
      <input type="text" id="nombre_categoria" name="nombre_categoria" class="form-control" value="<?php echo htmlspecialchars($categoria['nombre_categoria']); ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Actualizar Categoría</button>
    <a href="../categoria.php?pagina=<?php echo $pagina; ?>" class="btn btn-secondary">Volver</a>
  </form>
</div>

</body>
</html>