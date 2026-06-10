<?php
// create/empleado.php
include('../db.php');

// Obtener puestos para el select
$result_puestos = $conn->query('SELECT id_puesto, nombre_puesto FROM puesto ORDER BY nombre_puesto');

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recolectar datos
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

    // Validación básica
    if (empty($id_puesto) || trim($nombre_empleado) === '' || trim($apellido_paterno) === '' || trim($correo_electronico) === '') {
        $error = 'Alguno de los campos obligatorios no contiene datos.';
    } elseif ($puestoInexistente) {
        $error = 'El puesto seleccionado no existe.';
    } elseif ($correo_electronico != null && !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no cumple con el formato esperado.';
    } else {
        // Verificar correo repetido
        $correoRepetido = false;
        if ($correo_electronico !== null) {
            $stmtCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM empleado WHERE correo_electronico = ?");
            $stmtCheck->bind_param('s', $correo_electronico);
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
            // Validación fecha_liquidacion: no puede ser futura
            if ($fecha_liquidacion !== null) {
                $ts_liq = strtotime($fecha_liquidacion);
                if ($ts_liq === false) {
                    $error = 'Formato de fecha de liquidación inválido.';
                } else {
                    $hoy = strtotime(date('Y-m-d'));
                    if ($ts_liq > $hoy) {
                        $error = 'La fecha de liquidación no puede ser mayor a la fecha actual.';
                    }
                }
            }
        }
    }

    // Si no hay errores, insertar
    if (!isset($error)) {
        $stmt = $conn->prepare('INSERT INTO empleado (id_puesto, nombre_empleado, apellido_paterno, apellido_materno, telefono, correo_electronico, fecha_liquidacion) VALUES (?, ?, ?, ?, ?, ?, ?)');
        if ($stmt === false) {
            $error = 'Error al preparar la consulta: ' . $conn->error;
        } else {
            // types: i = id_puesto, 6x s = strings
            $stmt->bind_param('issssss', $id_puesto, $nombre_empleado, $apellido_paterno, $apellido_materno, $telefono, $correo_electronico, $fecha_liquidacion);
            if ($stmt->execute()) {
                header('Location: ../empleado.php');
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
  <title>Registrar Empleado</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h1>Registrar Empleado</h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
      <label class="form-label">Puesto:</label>
      <select name="id_puesto" class="form-control" required>
        <option value="">-- Selecciona un puesto --</option>
        <?php if ($result_puestos && $result_puestos->num_rows > 0): ?>
          <?php while ($opt = $result_puestos->fetch_assoc()): ?>
            <option value="<?php echo $opt['id_puesto']; ?>">
              <?php echo htmlspecialchars($opt['nombre_puesto']); ?>
            </option>
          <?php endwhile; ?>
        <?php else: ?>
          <option value="">No hay puestos registrados</option>
        <?php endif; ?>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Nombre:</label>
      <input type="text" name="nombre_empleado" maxlength="50" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Apellido Paterno:</label>
      <input type="text" name="apellido_paterno" maxlength="50" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Apellido Materno:</label>
      <input type="text" name="apellido_materno" maxlength="50" class="form-control" placeholder="Opcional">
    </div>

    <div class="mb-3">
      <label class="form-label">Teléfono:</label>
      <input type="text" name="telefono" maxlength="30" class="form-control" pattern="[0-9+\-()\s]+" placeholder="+52 (55) 1234 5678 (Opcional)">
    </div>

    <div class="mb-3">
      <label class="form-label">Correo Electrónico:</label>
      <input type="email" name="correo_electronico" maxlength="50" class="form-control" required placeholder="empleado@correo.com">
    </div>

    <div class="mb-3">
      <label class="form-label">Fecha de Liquidación:</label>
      <input type="date" name="fecha_liquidacion" class="form-control">
      <p class="text-muted">Opcional. No puede ser mayor a la fecha actual.</p>
    </div>

    <button type="submit" class="btn btn-primary">Registrar</button>
    <a href="../empleado.php" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
