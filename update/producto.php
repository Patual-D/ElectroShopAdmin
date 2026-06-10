<?php
// update/producto.php
include('../db.php');

// Obtener id desde GET; (int) protege contra inyección convirtiendo a entero
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Obtener datos actuales del producto
$stmt = $conn->prepare('SELECT * FROM producto WHERE id_producto = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto) {
    // Si no existe, redirigir de vuelta
    header('Location: ../producto.php');
    exit();
}

// Obtener la lista de categorías para el menú desplegable
$categorias_result = $conn->query('SELECT * FROM categoria ORDER BY nombre_categoria');

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

    // Si no hay error, actualizar
    if (!isset($error)) {
        $stmt = $conn->prepare('UPDATE producto SET nombre_producto = ?, id_categoria = ?, precio = ?, stock = ?, garantia = ? WHERE id_producto = ?');
        $precio_val = floatval($precio);
        $stock_val = intval($stock);
        $garantia_val = intval($garantia);
        $stmt->bind_param('sidiii', $nombre_producto, $id_categoria, $precio_val, $stock_val, $garantia_val, $id);
        
        if ($stmt->execute()) {
            header('Location: ../producto.php?pagina=' . $pagina);
            exit();
        } else {
            $error = 'Error al actualizar el producto: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar Producto</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1>Editar Producto: <?php echo htmlspecialchars($producto['nombre_producto']); ?></h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
      <label class="form-label">Nombre del Producto:</label>
      <input type="text" name="nombre_producto" maxlength="100" class="form-control" value="<?php echo htmlspecialchars(isset($nombre_producto) ? $nombre_producto : $producto['nombre_producto']); ?>" required>
    </div>
    
    <div class="mb-3">
      <label class="form-label">Categoría:</label>
      <select name="id_categoria" class="form-select">
        <option value="">-- Sin categoría --</option>
        <?php 
          // mover puntero a inicio por si ya se iteró
          if ($categorias_result) $categorias_result->data_seek(0);
          while($categoria = $categorias_result->fetch_assoc()): 
        ?>
          <?php $selected = (isset($id_categoria) ? $id_categoria : $producto['id_categoria']); ?>
          <option value="<?php echo $categoria['id_categoria']; ?>" <?php if($categoria['id_categoria'] == $selected) echo 'selected'; ?>>
            <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
          </option>
        <?php endwhile; ?>
      </select>
      <p class="text-muted">Opcional. Puedes dejarlo vacío si el producto no tiene categoría.</p>
    </div>

    <div class="mb-3">
      <label class="form-label">Precio:</label>
      <input type="number" name="precio" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars(isset($precio) ? $precio : $producto['precio']); ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Stock:</label>
      <input type="number" name="stock" min="0" class="form-control" value="<?php echo htmlspecialchars(isset($stock) ? $stock : $producto['stock']); ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Garantía (en días):</label>
      <input type="number" name="garantia" min="0" class="form-control" value="<?php echo htmlspecialchars(isset($garantia) ? $garantia : $producto['garantia']); ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Actualizar Producto</button>
    <a href="../producto.php?pagina=<?php echo $pagina; ?>" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
