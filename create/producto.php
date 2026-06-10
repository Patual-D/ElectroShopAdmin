<?php
// create/producto.php
include('../db.php');

// Obtener categorías para el select
$result_categorias = $conn->query('SELECT id_categoria, nombre_categoria FROM categoria ORDER BY nombre_categoria');

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_producto = isset($_POST['nombre_producto']) ? trim($_POST['nombre_producto']) : '';
    $id_categoria = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '' ? (int)$_POST['id_categoria'] : null;
    $precio = isset($_POST['precio']) ? trim($_POST['precio']) : '';
    $stock = isset($_POST['stock']) ? trim($_POST['stock']) : '';
    $garantia = isset($_POST['garantia']) ? trim($_POST['garantia']) : '';

    // Validaciones básicas
    if ($nombre_producto === '' || $precio === '' || $stock === '' || $garantia === '') {
        $error = 'Alguno de los campos obligatorios no contiene datos.';
    } elseif (!is_numeric($precio) || floatval($precio) < 0) {
        $error = 'Precio inválido. Debe ser un número mayor o igual a 0.';
    } elseif (!preg_match('/^[0-9]+$/', $stock) || intval($stock) < 0) {
        $error = 'Stock inválido. Debe ser un entero mayor o igual a 0.';
    } elseif (!preg_match('/^[0-9]+$/', $garantia) || intval($garantia) < 0) {
        $error = 'Garantía inválida. Debe ser un entero (meses) mayor o igual a 0.';
    } else {
        // Si se proporcionó id_categoria, validar que exista
        if ($id_categoria !== null) {
            $catExists = false;
            $res = $conn->query("SELECT id_categoria FROM categoria WHERE id_categoria = " . intval($id_categoria));
            if ($res && $res->num_rows > 0) $catExists = true;
            if (!$catExists) {
                $error = 'La categoría seleccionada no existe.';
            }
        }
    }

    // Insertar si no hay error
    if (!isset($error)) {
        $stmt = $conn->prepare('INSERT INTO producto (nombre_producto, id_categoria, precio, stock, garantia) VALUES (?, ?, ?, ?, ?)');
        if ($stmt === false) {
            $error = 'Error al preparar la consulta: ' . $conn->error;
        } else {
            $precio_val = floatval($precio);
            $stock_val = intval($stock);
            $garantia_val = intval($garantia);
            $stmt->bind_param('sidii', $nombre_producto, $id_categoria, $precio_val, $stock_val, $garantia_val);
            if ($stmt->execute()) {
                header('Location: ../producto.php');
                exit();
            } else {
                $error = 'Error al insertar: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Producto</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h1>Registrar Producto</h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
      <label class="form-label">Nombre del Producto:</label>
      <input type="text" name="nombre_producto" class="form-control" value="<?php echo isset($nombre_producto) ? htmlspecialchars($nombre_producto) : ''; ?>" placeholder="Ej: Teclado Mecánico RGB" required>
    </div>
    
    <div class="mb-3">
      <label class="form-label">Categoría:</label>
      <select name="id_categoria" class="form-select">
        <option value="">-- Sin categoría --</option>
        <?php if ($result_categorias && $result_categorias->num_rows > 0): ?>
          <?php while ($opt = $result_categorias->fetch_assoc()): ?>
            <option value="<?php echo $opt['id_categoria']; ?>" <?php echo (isset($id_categoria) && $id_categoria == $opt['id_categoria']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($opt['nombre_categoria']); ?>
            </option>
          <?php endwhile; ?>
        <?php else: ?>
          <option value="">No hay categorías registradas</option>
        <?php endif; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Precio:</label>
      <input type="number" name="precio" step="0.01" min="0" class="form-control" value="<?php echo isset($precio) ? htmlspecialchars($precio) : ''; ?>" placeholder="Ej: 1500.50" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Stock:</label>
      <input type="number" name="stock" min="0" class="form-control" value="<?php echo isset($stock) ? htmlspecialchars($stock) : ''; ?>" placeholder="Ej: 50" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Garantía (en días):</label>
      <input type="number" name="garantia" min="0" class="form-control" value="<?php echo isset($garantia) ? htmlspecialchars($garantia) : '0'; ?>" placeholder="Ej: 12" required>
    </div>

    <button type="submit" class="btn btn-primary">Registrar Producto</button>
    <a href="../producto.php" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
