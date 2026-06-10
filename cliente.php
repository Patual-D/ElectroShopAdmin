<?php
include("db.php"); 

// Parámetros
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1; // Es el número de página en el que nos encontramos
$porPagina = 10; // Es el límite de datos que se mostrarán por página
$offset = ($pagina - 1) * $porPagina; // Sirve para saber desde donde empezar la consulta 

// Término de búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : ''; // Se saca el valor de la varialbe de busqueda

// WHERE dinámico
$where = ""; // La sentencia del where como tal
$params = []; // Los parametrso que se usarán
$types = ""; // Los tipos de los parametros

if ($busqueda !== "") {
    // Buscamos en nombre y apellidos concatenados, correo, rfc, etc. (ajusta a tu gusto)
    // CONCAT_WS sirve para concatenar cadenas usando como separador el primer argumento
    $where = "WHERE CONCAT_WS(' ', nombre_cliente, apellido_paterno, apellido_materno) LIKE ? 
              OR correo_electronico LIKE ? 
              OR rfc LIKE ?";
    $like = "%{$busqueda}%";
    $params = [$like, $like, $like];
    $types = "sss";
}

// Total de registros
if ($where) {
    $stmtTotal = $conn->prepare("SELECT COUNT(*) as total FROM cliente $where");
    $stmtTotal->bind_param($types, ...$params); // los ... sirve para sacar todos los elementos del array
    $stmtTotal->execute();
    $total_result = $stmtTotal->get_result();
    $stmtTotal->close();
} else {
    $total_result = $conn->query("SELECT COUNT(*) as total FROM cliente");
}

$total_filas = $total_result->fetch_assoc()['total']; // Se saca el total de la consulta 
$total_paginas = max(1, ceil($total_filas / $porPagina)); // Esto se pagina dividiendolo por el límite por página
// max() sirve para determinar el valor más alto dentro de múltiples elementos

// Consulta principal con LIMIT y filtro
if ($where) {
    $sql = "SELECT * FROM cliente $where ORDER BY id_cliente LIMIT ?, ?";
    $stmt = $conn->prepare($sql);

    $typesLimit = $types . "ii"; // Se agregan los tiposde offset y porPagina a los tipos ya establecidos
    $paramsWithLimit = array_merge($params, [$offset, $porPagina]); // array_merge sirve para combinar arrays en uno solo
    $stmt->bind_param($typesLimit, ...$paramsWithLimit); // Se llama a la consulta usando los tipos y parametros anteriormente definidos
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM cliente ORDER BY id_cliente LIMIT $offset, $porPagina"); // Se llama a la consulta como normalmente se haría
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Admin Panel - Clientes</title>
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
      <a href="cliente.php" class="btn btn-primary btn-sm flex-grow-1">Clientes</a>
      <a href="proveedor.php" class="btn btn-outline-light btn-sm flex-grow-1">Proveedores</a>
      <a href="empleado.php" class="btn btn-outline-light btn-sm flex-grow-1">Empleados</a>
      <a href="servicio.php" class="btn btn-outline-light btn-sm flex-grow-1">Servicios</a>
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





  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h4 m-0">Clientes</h2>
      <!-- Formulario de búsqueda -->
      <form class="d-flex" method="get" action="">
        <input type="hidden" name="pagina" value="1"> <!-- Un input oculto que siempre tendrá la pagina 1 como argumento, esto es para que también se envíe como argumento la página a la hora de buscar -->
        <input class="form-control me-2" type="search" name="q" placeholder="Buscar por nombre, correo o RFC" value="<?php echo htmlspecialchars($busqueda); ?>"> <!-- Se pregunta por lo que quiere buscar -->
        <button class="btn btn-outline-primary" type="submit">Buscar</button>
        <?php if ($busqueda !== ""){ ?>
          <a href="?pagina=1" class="btn btn-link ms-2">Limpiar</a> <!-- Si hay una busqueda activa se agrega el botón de limpiar -->
        <?php } ?>
      </form>
    </div>

    <table class="table table-striped align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Nombre Completo</th>
          <th>Teléfono</th>
          <th>Correo Electrónico</th>
          <th>RFC</th>
          <th>Razón Social</th>
          <th>Domicilio</th>
          <th>Fecha de Registro</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0){ ?>
          <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row['id_cliente']); ?></td>
              <td><?php echo htmlspecialchars($row['nombre_cliente'] . " " . $row['apellido_paterno'] . " " . $row['apellido_materno']); ?></td>
              <td><?php echo htmlspecialchars($row['telefono']); ?></td>
              <td><?php echo htmlspecialchars($row['correo_electronico']); ?></td>
              <td><?php echo htmlspecialchars($row['rfc']); ?></td>
              <td><?php echo htmlspecialchars($row['razon_social']); ?></td>
              <td><?php echo htmlspecialchars($row['domicilio']); ?></td>
              <td><?php echo htmlspecialchars($row['fecha_registro']); ?></td>
              <td>
                <a href="update/cliente.php?id=<?php echo urlencode($row['id_cliente']); ?>&pagina=<?php echo ($pagina) ?>" class="btn btn-warning btn-sm">Editar</a>
                <a href="toggle_status.php?id=<?php echo urlencode($row['id_cliente']); ?>&table=cliente&estado=<?php echo ($row['estado'] == 'activo') ? 'inactivo' : 'activo'; ?>&pagina=<?php echo ($pagina) ?>" 
                  class="btn btn-<?php echo ($row['estado'] == 'activo') ? 'danger' : 'success'; ?> btn-sm"
                  onclick="return confirm('¿Estás seguro de querer <?php echo ($row['estado'] == 'activo') ? 'deshabilitar' : 'habilitar'; ?> este cliente?');">
                  <?php echo ($row['estado'] == 'activo') ? 'Deshabilitar' : 'Habilitar'; ?>
                </a>
              </td>
            </tr>
          <?php } ?>
        <?php } else { ?>
          <tr>
            <td colspan="9" class="text-center text-muted">No se encontraron resultados.</td> <!-- Si no se encuentran resultados esto se mostrará en toda la fila de la tabla -->
          </tr>
        <?php } ?>
      </tbody>
    </table>
    <div class="d-flex justify-content-between align-items-center">
      <!-- Paginación -->
      <nav>
        <ul class="pagination">
          <?php
            // En caso de haber una busqueda, está también se agregará a la paginación
            $qParam = $busqueda !== "" ? "&q=" . urlencode($busqueda) : "";
          ?>

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

      <a href="create/cliente.php" class="btn btn-primary">Registrar Cliente</a>
    </div>

    <p class="text-muted">
      Mostrando <?php echo ($total_filas > 0) ? min($porPagina, max(0, $total_filas - $offset)) : 0; ?> de <?php echo $total_filas; ?> registros
      <?php if ($busqueda !== ""): // Si hay busqueda se nmostrará otro mensaje?>
        para "<strong><?php echo htmlspecialchars($busqueda); ?></strong>"
      <?php endif; ?>
    </p>
  </div>
</body>
</html>
