<?php
// update/empleado.php
include('../db.php');

// Obtener id desde GET; (int) protege contra inyección convirtiendo a entero
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Obtener datos actuales para mostrar en el formulario
$stmt = $conn->prepare('SELECT * FROM empleado WHERE id_empleado = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$vac = $result->fetch_assoc();
$stmt->close();

if (!$vac) {
    // Si no existe, redirigir de vuelta
    header('Location: ../empleado.php');
    exit();
}

// Consulta para sacar los puestos para el select del form
$result_puestos = $conn->query('SELECT id_puesto, nombre_puesto FROM puesto ORDER BY nombre_puesto');

// Si se envía el formulario, procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se sacan las variables
    $id_puesto = isset($_POST['id_puesto']) ? (int)$_POST['id_puesto'] : null;
    $nombre_empleado = isset($_POST['nombre_empleado']) ? trim($_POST['nombre_empleado']) : '';
    $apellido_paterno = isset($_POST['apellido_paterno']) ? trim($_POST['apellido_paterno']) : '';
    $apellido_materno = isset($_POST['apellido_materno']) ? trim($_POST['apellido_materno']) : null;
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $correo_electronico = isset($_POST['correo_electronico']) ? trim($_POST['correo_electronico']) : '';
    $fecha_liquidacion = isset($_POST['fecha_liquidacion']) ? trim($_POST['fecha_liquidacion']) : null;

    // Normalizar valores opcionales
    if ($apellido_materno === '') { $apellido_materno = null; }
    if ($telefono === '') { $telefono = null; }
    if ($fecha_liquidacion === '') { $fecha_liquidacion = null; }
    if ($correo_electronico === '') { $correo_electronico = null; }

    // Validar existencia del puesto
    $puestoInexistente = true;
    $res = $conn->query("SELECT id_puesto FROM puesto");
    while ($r = $res->fetch_assoc()) {
        if ($r['id_puesto'] == $id_puesto) {
            $puestoInexistente = false;
            break;
        }
    }

    // Validaciones básicas
    if (empty($id_puesto) || trim($nombre_empleado) === '' || trim($apellido_paterno) === '' || trim($correo_electronico) === '') {
        $error = 'Alguno de los campos obligatorios no contiene datos.';
    } elseif ($puestoInexistente) {
        $error = 'El puesto seleccionado no existe.';
    } elseif ($correo_electronico != null && !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no cumple con el formato esperado.';
    } else {
        // Verificar correo repetido (excluir al empleado actual)
        $correoRepetido = false;
        if ($correo_electronico !== null) {
            $stmtCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM empleado WHERE correo_electronico = ? AND id_empleado <> ?");
            $stmtCheck->bind_param('si', $correo_electronico, $id);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            if ($resCheck) {
                $rowCheck = $resCheck->fetch_assoc();
                $correoRepetido = ($rowCheck['cnt'] > 0);
            }
            $stmtCheck->close();
        }

        if ($correoRepetido) {
            $error = 'Este correo ya está siendo usado por otro empleado.';
        } elseif ($telefono != null && !preg_match("/^[0-9+\-()\s]+$/", $telefono)) {
            $error = 'Caracteres inválidos en teléfono.';
        } else {
            // Validación de fechas: tomar fecha_contratacion actual desde DB para comparar
            $fecha_contratacion = $vac['fecha_contratacion']; // formato YYYY-mm-dd

            if ($fecha_liquidacion !== null) {
                $ts_liq = strtotime($fecha_liquidacion);
                $ts_contrat = strtotime($fecha_contratacion);
                if ($ts_liq === false) {
                    $error = 'Formato de fecha de liquidación inválido.';
                } else {
                    $hoy = strtotime(date('Y-m-d'));
                    if ($ts_liq > $hoy) {
                        $error = 'La fecha de liquidación no puede ser mayor a la fecha actual.';
                    } elseif ($ts_liq < $ts_contrat) {
                        $error = 'La fecha de liquidación no puede ser anterior a la fecha de contratación.';
                    }
                }
            }
        }
    }

    // Si no hay errores, actualizar
    if (!isset($error)) {
        $stmt = $conn->prepare('UPDATE empleado SET id_puesto = ?, nombre_empleado = ?, apellido_paterno = ?, apellido_materno = ?, telefono = ?, correo_electronico = ?, fecha_liquidacion = ? WHERE id_empleado = ?');
        if ($stmt === false) {
            $error = 'Error al preparar la consulta: ' . $conn->error;
        } else {
            // types: i = id_puesto, 6x s = strings, final i = id_empleado
            $stmt->bind_param('issssssi', $id_puesto, $nombre_empleado, $apellido_paterno, $apellido_materno, $telefono, $correo_electronico, $fecha_liquidacion, $id);
            if ($stmt->execute()) {
                header('Location: ../empleado.php?pagina=' . $pagina);
                exit();
            } else {
                $error = 'Error al actualizar: ' . $stmt->error;
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
  <title>Editar Empleado</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1>Editar Empleado: <?php echo htmlspecialchars($vac['nombre_empleado'] . ' ' . $vac['apellido_paterno']); ?> </h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
      <label class="form-label">Puesto:</label>
      <select name="id_puesto" class="form-control" required>
        <option value="">-- Selecciona un puesto --</option>
        <?php
          // Rewind result set if posible (si ya fue iterado). Si no, volver a consultar.
          if ($result_puestos && $result_puestos->num_rows > 0) {
              // Para mostrar el selected correctamente, no usamos fetch_assoc antes de este loop
              $result_puestos->data_seek(0);
              while ($opt = $result_puestos->fetch_assoc()):
        ?>
            <option value="<?php echo $opt['id_puesto']; ?>" <?php echo ($opt['id_puesto'] == $vac['id_puesto']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($opt['nombre_puesto']); ?>
            </option>
        <?php
              endwhile;
          } else {
        ?>
            <option value="">No hay puestos registrados</option>
        <?php } ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Nombre:</label>
      <input type="text" name="nombre_empleado" maxlength="50" class="form-control" value="<?php echo htmlspecialchars($vac['nombre_empleado']); ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Apellido Paterno:</label>
      <input type="text" name="apellido_paterno" maxlength="50" class="form-control" value="<?php echo htmlspecialchars($vac['apellido_paterno']); ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Apellido Materno:</label>
      <input type="text" name="apellido_materno" maxlength="50" class="form-control" value="<?php echo htmlspecialchars($vac['apellido_materno']); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Teléfono:</label>
      <input type="text" name="telefono" maxlength="30" class="form-control" pattern="[0-9+\-()\s]+" value="<?php echo htmlspecialchars($vac['telefono']); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Correo Electrónico:</label>
      <input type="email" name="correo_electronico" maxlength="50" class="form-control" value="<?php echo htmlspecialchars($vac['correo_electronico']); ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Fecha de Contratación:</label>
      <input type="date" name="fecha_contratacion" class="form-control" value="<?php echo htmlspecialchars($vac['fecha_contratacion']); ?>" disabled>
    </div>

    <div class="mb-3">
      <label class="form-label">Fecha de Liquidación:</label>
      <input type="date" name="fecha_liquidacion" class="form-control" value="<?php echo htmlspecialchars($vac['fecha_liquidacion']); ?>">
      <p class="text-muted">Opcional. No puede ser mayor a la fecha actual ni anterior a la fecha de contratación.</p>
    </div>

    <button type="submit" class="btn btn-primary">Actualizar</button>
    <a href="../empleado.php?pagina=<?php echo $pagina; ?>" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
