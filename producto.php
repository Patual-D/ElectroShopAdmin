<?php
include("db.php");

// Parámetros de paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

// Término de búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

// FROM + JOIN (categoria es opcional -> LEFT JOIN)
$fromJoin = "FROM producto P LEFT JOIN categoria C ON P.id_categoria = C.id_categoria";

// Construcción del WHERE dinámico
$where = "";
$params = [];
$types = "";

if ($busqueda !== "") {
    $where = "WHERE P.nombre_producto LIKE ? OR C.nombre_categoria LIKE ? OR P.estado LIKE ?";
    $like = "%{$busqueda}%";
    $params = [$like, $like, $like];
    $types = "sss";
}

// Total de registros
if ($where) {
    $sqlTotal = "SELECT COUNT(*) as total $fromJoin $where";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bind_param($types, ...$params);
    $stmtTotal->execute();
    $total_result = $stmtTotal->get_result();
    $stmtTotal->close();
} else {
    $sqlTotal = "SELECT COUNT(*) as total FROM producto";
    $total_result = $conn->query($sqlTotal);
}

$total_filas = (int) $total_result->fetch_assoc()['total'];
$total_paginas = max(1, ceil($total_filas / $porPagina));

// Consulta principal con LIMIT y filtro
$select = "
SELECT
    P.id_producto,
    P.nombre_producto,
    P.precio,
    P.stock,
    P.garantia,
    P.id_categoria,
    C.nombre_categoria,
    P.estado
";

$sql = "$select $fromJoin $where ORDER BY P.id_producto LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if ($where) {
    $typesLimit = $types . "ii";
    $paramsWithLimit = array_merge($params, [$offset, $porPagina]);
    $stmt->bind_param($typesLimit, ...$paramsWithLimit);
} else {
    $stmt->bind_param("ii", $offset, $porPagina);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Admin Panel - Productos</title>
  <link href="img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="navbar navbar-dark bg-dark flex-column p-0">
    <div class="container-fluid w-100 py-2 d-flex justify-content-center">
      <a class="navbar-brand fw-semibold m-0" href="index.php">ADMIN PANEL | ELECTROSHOP</a>
    </div>
    <div class="container-fluid w-100 d-flex flex-wrap pb-2" style="gap: 5px;">
      <a href="cliente.php" class="btn btn-outline-light btn-sm flex-grow-1">Clientes</a>
      <a href="proveedor.php" class="btn btn-outline-light btn-sm flex-grow-1">Proveedores</a>
      <a href="empleado.php" class="btn btn-outline-light btn-sm flex-grow-1">Empleados</a>
      <a href="servicio.php" class="btn btn-outline-light btn-sm flex-grow-1">Servicios</a>
      <a href="producto.php" class="btn btn-primary btn-sm flex-grow-1">Productos</a>
      <a href="compra.php" class="btn btn-outline-light btn-sm flex-grow-1">Compras</a>
      <a href="devolucion.php" class="btn btn-outline-light btn-sm flex-grow-1">Devoluciones</a>
      <a href="factura_venta.php" class="btn btn-outline-light btn-sm flex-grow-1">Facturas Ventas</a>
      <a href="factura_compra.php" class="btn btn-outline-light btn-sm flex-grow-1">Facturas Compras</a>
      <a href="tipo_servicio.php" class="btn btn-outline-light btn-sm flex-grow-1">Tipos Servicios</a>
      <a href="categoria.php" class="btn btn-outline-light btn-sm flex-grow-1">Categorías Productos</a>
      <a href="puesto.php" class="btn btn-outline-light btn-sm flex-grow-1">Puestos Empleados</a>
    </div>
  </div>

  <main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h4 m-0">Productos</h2>
      <form class="d-flex" method="get" action="">
        <input type="hidden" name="pagina" value="1">
        <input class="form-control me-2" type="search" name="q" placeholder="Buscar por nombre, categoría o estado" value="<?php echo htmlspecialchars($busqueda); ?>">
        <button class="btn btn-outline-primary" type="submit">Buscar</button>
        <?php if ($busqueda !== ""): ?>
          <a href="?pagina=1" class="btn btn-link ms-2">Limpiar</a>
        <?php endif; ?>
      </form>
    </div>

    <table class="table table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Garantía</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row['id_producto']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_producto']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_categoria'] ?? 'Sin categoría'); ?></td>
              <td>$<?php echo htmlspecialchars(number_format($row['precio'], 2)); ?></td>
              <td><?php echo htmlspecialchars($row['stock']); ?></td>
              <td><?php echo htmlspecialchars($row['garantia']); ?> días</td>
              <td>
                <a href="update/producto.php?id=<?php echo urlencode($row['id_producto']); ?>&pagina=<?php echo $pagina; ?>" class="btn btn-warning btn-sm">Editar</a>
                
                <a href="toggle_status.php?id=<?php echo urlencode($row['id_producto']); ?>&table=producto&estado=<?php echo ($row['estado'] == 'activo') ? 'inactivo' : 'activo'; ?>&pagina=<?php echo $pagina; ?>" 
                   class="btn btn-<?php echo ($row['estado'] == 'activo') ? 'danger' : 'success'; ?> btn-sm"
                   onclick="return confirm('¿Estás seguro de querer <?php echo ($row['estado'] == 'activo') ? 'deshabilitar' : 'habilitar'; ?> este producto?');">
                   <?php echo ($row['estado'] == 'activo') ? 'Deshabilitar' : 'Habilitar'; ?>
                </a>
              </td>
            </tr>
          <?php } ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center text-muted">No se encontraron resultados.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php 
        $qParam = $busqueda !== "" ? "&q=" . urlencode($busqueda) : "";
    ?>

    <div class="d-flex justify-content-between align-items-center">
      <nav>
        <ul class="pagination">
          <?php if ($pagina > 1){ ?>
            <li class="page-item">
              <a class="page-link" href="?pagina=<?php echo $pagina - 1 . $qParam; ?>">
                <span>«</span>
              </a>
            </li>
          <?php } ?>

          <?php for ($i = 1; $i <= $total_paginas; $i++){ ?>
            <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
              <a class="page-link" href="?pagina=<?php echo $i . $qParam; ?>"><?php echo $i; ?></a>
            </li>
          <?php } ?>

          <?php if ($pagina < $total_paginas){ ?>
            <li class="page-item">
              <a class="page-link" href="?pagina=<?php echo $pagina + 1 . $qParam; ?>">
                <span>»</span>
              </a>
            </li>
          <?php } ?>
        </ul>
      </nav>

      <a href="create/producto.php" class="btn btn-primary">Registrar Producto</a>
    </div>        
    <p class="text-muted">
      Mostrando <?php echo ($result ? $result->num_rows : 0); ?> de <?php echo $total_filas; ?> registros
      <?php if ($busqueda !== ""): ?>
        para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"
      <?php endif; ?>
    </p>
  </main>

</body>
</html>
