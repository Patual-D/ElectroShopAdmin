<?php
include("db.php"); 

// Parámetros de paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

// Término de búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

// Base del FROM y JOINs
$fromJoin = "
FROM servicio S
JOIN cliente C ON S.id_cliente = C.id_cliente
LEFT JOIN empleado E ON S.id_empleado = E.id_empleado
JOIN tipo_servicio TS ON S.id_tipo_servicio = TS.id_tipo_servicio
";

// Construcción del WHERE dinámico
$where = "";
$params = [];
$types = "";

if ($busqueda !== "") {
    $where = "WHERE 
        CONCAT_WS(' ', C.nombre_cliente, C.apellido_paterno) LIKE ? OR
        CONCAT_WS(' ', E.nombre_empleado, E.apellido_paterno) LIKE ? OR
        TS.nombre_servicio LIKE ? OR
        S.estado_servicio LIKE ?";

    $like = "%{$busqueda}%";
    $params = [$like, $like, $like, $like];
    $types = "ssss";
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
    $sqlTotal = "SELECT COUNT(*) as total FROM servicio";
    $total_result = $conn->query($sqlTotal);
}

$total_filas = (int) $total_result->fetch_assoc()['total'];
$total_paginas = max(1, ceil($total_filas / $porPagina));

// Consulta principal con LIMIT y filtro
// Base del select
$select = "
SELECT
    S.id_servicio,
    S.id_cliente,
    S.id_empleado,
    S.id_tipo_servicio,
    S.fecha_inicio,
    S.fecha_termino,
    S.estado_servicio,
    S.descripcion,
    C.nombre_cliente,
    C.apellido_paterno AS apellido_cliente,
    E.nombre_empleado,
    E.apellido_paterno AS apellido_empleado,
    TS.nombre_servicio
";

if ($where) {
    $sql = "$select $fromJoin $where ORDER BY S.id_servicio LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $typesLimit = $types . "ii";
    $paramsWithLimit = array_merge($params, [$offset, $porPagina]);
    $stmt->bind_param($typesLimit, ...$paramsWithLimit);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "$select $fromJoin ORDER BY S.id_servicio LIMIT $offset, $porPagina";
    $result = $conn->query($sql);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Admin Panel - Servicios</title>
  <link href="img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

  <!-- Header general -->
  <div class="navbar navbar-dark bg-dark flex-column p-0">
    <div class="container-fluid w-100 py-2 d-flex justify-content-center">
      <a class="navbar-brand fw-semibold m-0" href="index.php">ADMIN PANEL | ELECTROSHOP</a>
    </div>
    <div class="container-fluid w-100 d-flex flex-wrap pb-2" style="gap: 5px;">
      <a href="cliente.php" class="btn btn-outline-light btn-sm flex-grow-1">Clientes</a>
      <a href="proveedor.php" class="btn btn-outline-light btn-sm flex-grow-1">Proveedores</a>
      <a href="empleado.php" class="btn btn-outline-light btn-sm flex-grow-1">Empleados</a>
      <a href="servicio.php" class="btn btn-primary btn-sm flex-grow-1">Servicios</a>
      <a href="producto.php" class="btn btn-outline-light btn-sm flex-grow-1">Productos</a>
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
      <h2 class="h4 m-0">Servicios</h2>
      <!-- Buscador -->
      <form class="d-flex" method="get" action="">
        <input type="hidden" name="pagina" value="1">
        <input class="form-control me-2" type="search" name="q" placeholder="Buscar por cliente, empleado, tipo o estado" value="<?php echo htmlspecialchars($busqueda); ?>">
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
          <th>Cliente</th>
          <th>Empleado</th>
          <th>Tipo de Servicio</th>
          <th>Descripcion</th>
          <th>Fecha de Inicio</th>
          <th>Fecha de Termino</th>
          <th>Estado del Servicio</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row['id_servicio']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_cliente'] . " " . $row['apellido_cliente']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_empleado'] . " " . $row['apellido_empleado']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_servicio']); ?></td>
              <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
              <td><?php echo htmlspecialchars($row['fecha_inicio']); ?></td>
              <td><?php echo htmlspecialchars($row['fecha_termino']); ?></td>
              <td><?php echo htmlspecialchars($row['estado_servicio']); ?></td>
              <td>
                <a href="update/servicio.php?id=<?php echo urlencode($row['id_servicio']); ?>&pagina=<?php echo ($pagina) ?>" class="btn btn-warning btn-sm">Editar</a>
                <a href="delete_admin.php?id=<?php echo urlencode($row['id_servicio']); ?>&table=servicio&pagina=<?php echo ($pagina) ?>" 
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('¿Estás seguro de querer eliminar este servicio?');">
                   Eliminar
                </a>
              </td>
            </tr>
          <?php } ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center text-muted">No se encontraron resultados.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- Paginación -->
    <?php 
        // Helper para querystring en paginación
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
        <a href="create/servicio.php" class="btn btn-primary">Registrar Servicio</a>
    </div>        
    <p class="text-muted">
      Mostrando <?php echo ($total_filas > 0) ? min($porPagina, max(0, $total_filas - $offset)) : 0; ?> de <?php echo $total_filas; ?> registros
      <?php if ($busqueda !== ""): ?>
        para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"
      <?php endif; ?>
    </p>
  </main>

</body>
</html>
