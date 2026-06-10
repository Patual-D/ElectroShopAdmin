<?php include("../db.php"); ?>

<?php
  header('Location: cliente.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Tienda Electrónica - Admin Panel</title>
  <link href="img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <!-- Header general -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand fw-semibold" href="../admin_panel">Admin Panel</a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="adminNav">
        <div class="ms-auto d-flex gap-2">
          <a href="cliente.php" class="btn btn-outline-light btn-sm">Clientes</a>
          <a href="proveedor.php" class="btn btn-outline-light btn-sm">Proveedores</a>
          <a href="empleado.php" class="btn btn-outline-light btn-sm">Empleados</a>
          <a href="servicio.php" class="btn btn-outline-light btn-sm">Servicios</a>
          <a href="producto.php" class="btn btn-outline-light btn-sm">Productos</a>
        </div>
      </div>
    </div>
  </nav>


</body>
</html>
