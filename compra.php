<?php
include("db.php"); 

// Parámetros de paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

// Término de búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

// Se une la tabla 'compra' con 'cliente' para obtener el nombre del cliente
$fromJoin = "FROM compra CO LEFT JOIN cliente CL ON CO.id_cliente = CL.id_cliente";

// Construcción del WHERE dinámico
$where = "";
$params = [];
$types = "";

if ($busqueda !== "") {
    // La búsqueda funciona por nombre del cliente, método de pago o ID de la compra
    $where = "WHERE 
        CONCAT_WS(' ', CL.nombre_cliente, CL.apellido_paterno) LIKE ? OR
        CO.metodo_pago LIKE ? OR
        CO.id_compra = ?";

    $like = "%{$busqueda}%";
    $params = [$like, $like, $busqueda];
    $types = "ssi";
}

// Conteo total de registros
$sqlTotal = "SELECT COUNT(*) as total $fromJoin $where";
if ($where) {
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->bind_param($types, ...$params);
    $stmtTotal->execute();
    $total_result = $stmtTotal->get_result();
    $stmtTotal->close();
} else {
    $total_result = $conn->query("SELECT COUNT(*) as total FROM compra");
}
$total_filas = (int) $total_result->fetch_assoc()['total'];
$total_paginas = max(1, ceil($total_filas / $porPagina));

// Consulta principal para obtener los datos
$select = "
SELECT
    CO.id_compra,
    CO.fecha_compra,
    CO.metodo_pago,
    CO.total,
    CONCAT_WS(' ', CL.nombre_cliente, CL.apellido_paterno) AS nombre_completo_cliente
";

$sql = "$select $fromJoin $where ORDER BY CO.id_compra DESC LIMIT ?, ?";
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
  <title>Admin Panel - Compras</title>
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
        <a href="compra.php" class="btn btn-primary btn-sm flex-grow-1">Compras</a>
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
      <h2 class="h4 m-0">Gestión de Compras</h2>
      <form class="d-flex" method="get" action="">
        <input type="hidden" name="pagina" value="1">
        <input class="form-control me-2" type="search" name="q" placeholder="Buscar por cliente, método de pago o ID" value="<?php echo htmlspecialchars($busqueda); ?>">
        <button class="btn btn-outline-primary" type="submit">Buscar</button>
        <?php if ($busqueda !== ""): ?>
          <a href="?pagina=1" class="btn btn-link ms-2">Limpiar</a>
        <?php endif; ?>
      </form>
    </div>
    <table class="table table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>ID Compra</th>
          <th>Cliente</th>
          <th>Fecha</th>
          <th>Método de Pago</th>
          <th>Total</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row['id_compra']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_completo_cliente']); ?></td>
              <td><?php echo htmlspecialchars($row['fecha_compra']); ?></td>
              <td><?php echo htmlspecialchars($row['metodo_pago']); ?></td>
              <td>$<?php echo htmlspecialchars(number_format($row['total'], 2)); ?></td>
              <td>
                <a href="detail/compra.php?id=<?php echo urlencode($row['id_compra']); ?>" class="btn btn-info btn-sm">Ver Detalles</a>
              </td>
            </tr>
          <?php } ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center text-muted">No se encontraron compras.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    
    <div class="d-flex justify-content-between align-items-center">
      <nav>
        <ul class="pagination mb-0">
          <?php
            $qParam = $busqueda !== "" ? "&q=" . urlencode($busqueda) : "";
          ?>

          <?php if ($pagina > 1){ ?>
            <li class="page-item">
              <a class="page-link" href="?pagina=<?php echo $pagina - 1 . $qParam; ?>">
                <span>&laquo;</span>
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
                <span>&raquo;</span>
              </a>
            </li>
          <?php } ?>
        </ul>
      </nav>

      <p class="text-muted mb-0">
        Mostrando <?php echo $result->num_rows; ?> de <?php echo $total_filas; ?> registros
        <?php if ($busqueda !== ""): ?>
          para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"
        <?php endif; ?>
      </p>
    </div>
    
  </main>
</body>
</html>