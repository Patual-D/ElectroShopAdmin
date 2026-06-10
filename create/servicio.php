<?php
// update_vacacion.php
// --------------------------
// Permite editar un periodo vacacional existente.
// --------------------------

include('../db.php');

// Consulta para sacar los tipos de servicio para el select del form
$result_tipo_servicio = $conn->query('SELECT id_tipo_servicio, nombre_servicio FROM tipo_servicio ORDER BY nombre_servicio');

// Si se envía el formulario, procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se sacan las variables
    $id_cliente = (int)$_POST['id_cliente'];
    $id_empleado = $_POST['id_empleado'];
    $id_tipo_servicio = (int)$_POST['id_tipo_servicio'];
    $descripcion = $_POST['descripcion'];
    $fecha_termino = $_POST['fecha_termino'];
    $estado_servicio = $_POST['estado_servicio'];

    // Poner valores nulos a campos vacíos no obligatorios
    if (trim($id_empleado) === '') { // Asignar valores nulos si están vacíos
        $id_empleado = null;
    }else{
        $id_empleado = (int)$id_empleado;
    }


    if (trim($fecha_termino) === '') { // Asignar valores nulos si están vacíos
        $fecha_termino = null;
    }
    if (trim($descripcion) === '') { // Asignar valores nulos si están vacíos
        $descripcion = null;
    }

    //Validación de ID existente
    $idClienteInexistente = true; // Se crea una variable para definir si el correo es repetido o no
    $result = $conn->query("SELECT id_cliente FROM cliente"); // Se busca en la base de datos
    while ($row = $result->fetch_assoc()) {
        if ($row['id_cliente'] == $id_cliente) { // Se busca hasta encontrar un ID que sea el mismo
            $idClienteInexistente = false; // Si se encuentra se define el id como existente
            break;
        }
    }

    $idEmpleadoInexistente = false; // Se crea una variable para definir si el correo es repetido o no

    if ($id_empleado != null) {
      $idEmpleadoInexistente = true; // Se crea una variable para definir si el correo es repetido o no
      $result = $conn->query("SELECT id_empleado FROM empleado"); // Se busca en la base de datos
      while ($row = $result->fetch_assoc()) {
          if ($row['id_empleado'] == $id_empleado) { // Se busca hasta encontrar un ID que sea el mismo
              $idEmpleadoInexistente = false; // Si se encuentra se define el id como existente
              break;
          }
      }
    }


    // Validaciones
    if (trim($id_cliente) === '' || trim($id_tipo_servicio) === '' || trim($estado_servicio) === '') {  // Verificar que las lineas no estén vacías
        $error = 'Alguno de los campos obligatorios no contiene datos.';
    } elseif($idClienteInexistente || $idEmpleadoInexistente){
        $error = 'Alguno de los ID ingresados es inexistente.';    
    } elseif ($id_cliente != null && !preg_match("/^[0-9]+$/", $id_cliente) || $id_empleado != null && !preg_match("/^[0-9]+$/", $id_empleado)) { // Comprobamos que el input solo tenga los caractéres permitidos
        $error = 'Caracteres inválidos en alguno de los inputs de numeros enteros.';
    } elseif (strtotime($fecha_termino) > strtotime(date('Ymd'))-1) { // Comprobamos que la fecha de nacimiento no sea mayor a la fecha actual
            $error = 'La fecha de nacimiento no puede ser mayor a la fecha actual.';
    } else {
        $stmt = $conn->prepare('INSERT INTO servicio (id_cliente, id_empleado, id_tipo_servicio, fecha_termino, estado_servicio, descripcion) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iiisss', $id_cliente, $id_empleado, $id_tipo_servicio, $fecha_termino, $estado_servicio, $descripcion);
        if ($stmt->execute()) {
            header('Location: ../servicio.php');
            exit();
        } else {
            $error = 'Error al actualizar: ' . $stmt->error;
        }
        $stmt->close();
    }
}



?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Registrar Servicio</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1>Registrar Servicio</h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
      <label class="form-label">ID del Cliente:</label>
      <input type="number" step="1" name="id_cliente" class="form-control" placeholder="Ej. 1" required>
    </div>
    <p class="text-muted">
      Se recomienda asegurarse que es el ID correcto antes de registrarlo
      <a href="../cliente.php" target="_blank" class="text-muted">
        Click aquí para consultar los clientes.
      </a>
    </p>

    <div class="mb-3">
      <label class="form-label">ID del Empleado:</label>
      <input type="number" step="1" name="id_empleado" class="form-control" placeholder="Ej. 2">
    </div>
    <p class="text-muted">
      Se recomienda asegurarse que es el ID correcto antes de registrarlo
      <a href="../cliente.php" target="_blank" class="text-muted">
        Click aquí para consultar los empleados.
      </a>
    </p>

    <div class="mb-3">
      <label class="form-label">Descripción:</label>
      <input type="text" name="descripcion" class="form-control" placeholder="Opcional">
    </div>

    <div class="mb-3">
      <label class="form-label">Tipo de Servicio:</label>
      <select name="id_tipo_servicio" class="form-control" required>
          <?php while ($option = $result_tipo_servicio->fetch_assoc()){ ?> <!-- El primer echo sirve para obtener el id de cada una de las opciones usando array_keys -->
            <option value="<?php echo $option['id_tipo_servicio']; ?>"   >
              <?php echo $option['nombre_servicio']; ?> <!-- Se define el texto que se mostrará en el select -->
            </option>
          <?php } ?>
        </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Fecha de Termino:</label>
      <input type="date" name="fecha_termino" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Estado del Servicio:</label>
      <select name="estado_servicio" class="form-control" required>
        <option value="pendiente">Pendiente</option>
        <option value="en_proceso">En Proceso</option>
        <option value="concluido">Concluido</option>
        <option value="cancelado">Cancelado</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Registrar</button>
    <a href="../servicio.php" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
