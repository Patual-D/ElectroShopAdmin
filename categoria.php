<?php
include("db.php"); 

// Parámetros de paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

// Término de búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

// No se necesitan JOINs
$from = "FROM categoria";

// Construcción del WHERE dinámico
$where = "";
$params = [];
$types = "";

if ($busqueda !== "") {
    $where = "WHERE nombre_categoria LIKE ?";
    $like = "%{$busqueda}%";
    $params = [$like];
    $types = "s";
}

// Conteo total de registros
$sqlTotal = "SELECT COUNT(*) as total $from $where";
if ($where) {
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bind_param($types, ...$params);
    $stmtTotal->execute();
    $total_result = $stmtTotal->get_result();
    $stmtTotal->close();
} else {
    $total_result = $conn->query($sqlTotal);
}
$total_filas = (int) $total_result->fetch_assoc()['total'];
$total_paginas = max(1, ceil($total_filas / $porPagina));


// Consulta principal para obtener los datos
$sql = "SELECT * $from $where ORDER BY id_categoria LIMIT ?, ?";
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
  <title>Admin Panel - Categorías</title>
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
        <a href="producto.php" class="btn btn-outline-light btn-sm flex-grow-1">Productos</a>
        <a href="compra.php" class="btn btn-outline-light btn-sm flex-grow-1">Compras</a>
        <a href="devolucion.php" class="btn btn-outline-light btn-sm flex-grow-1">Devoluciones</a>
        <a href="factura_venta.php" class="btn btn-outline-light btn-sm flex-grow-1">Facturas Ventas</a>
        <a href="factura_compra.php" class="btn btn-outline-light btn-sm flex-grow-1">Facturas Compras</a>
        <a href="tipo_servicio.php" class="btn btn-outline-light btn-sm flex-grow-1">Tipos Servicios</a>
        <a href="categoria.php" class="btn btn-primary btn-sm flex-grow-1">Categorías Productos</a>
        <a href="puesto.php" class="btn btn-outline-light btn-sm flex-grow-1">Puestos Empleados</a>
    </div>
  </div>


  <main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h4 m-0">Categorías de Productos</h2>
      <form class="d-flex" method="get" action="">
        <input type="hidden" name="pagina" value="1">
        <input class="form-control me-2" type="search" name="q" placeholder="Buscar por nombre de categoría" value="<?php echo htmlspecialchars($busqueda); ?>">
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
          <th>Nombre de la Categoría</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row['id_categoria']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_categoria']); ?></td>
              <td>
                <a href="update/categoria.php?id=<?php echo urlencode($row['id_categoria']); ?>&pagina=<?php echo $pagina; ?>" class="btn btn-warning btn-sm">Editar</a>
                <a href="delete_admin.php?id=<?php echo urlencode($row['id_categoria']); ?>&table=categoria&pagina=<?php echo $pagina; ?>" 
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('¿Estás seguro de querer eliminar esta categoría? Los productos asociados quedarán sin categoría.');">
                   Eliminar
                </a>
              </td>
            </tr>
          <?php } ?>
        <?php else: ?>
          <tr>
            <td colspan="3" class="text-center text-muted">No se encontraron categorías.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center">
      <nav>
        <ul class="pagination mb-0">
          <?php $qParam = $busqueda !== "" ? "&q=" . urlencode($busqueda) : ""; ?>
          <?php if ($pagina > 1){ ?>
            <li class="page-item">
              <a class="page-link" href="?pagina=<?php echo $pagina - 1 . $qParam; ?>"><span>&laquo;</span></a>
            </li>
          <?php } ?>
          <?php for ($i = 1; $i <= $total_paginas; $i++){ ?>
            <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
              <a class="page-link" href="?pagina=<?php echo $i . $qParam; ?>"><?php echo $i; ?></a>
            </li>
          <?php } ?>
          <?php if ($pagina < $total_paginas){ ?>
            <li class="page-item">
              <a class="page-link" href="?pagina=<?php echo $pagina + 1 . $qParam; ?>"><span>&raquo;</span></a>
            </li>
          <?php } ?>
        </ul>
      </nav>
      <a href="create/categoria.php" class="btn btn-primary">Registrar Categoría</a>
    </div> 
    <p class="text-muted mt-2">
      Mostrando <?php echo $result->num_rows; ?> de <?php echo $total_filas; ?> registros
      <?php if ($busqueda !== ""): ?>
        para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"
      <?php endif; ?>
    </p>

  </main>

</body>
</html>