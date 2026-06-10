<?php
// create/proveedor.php
include('../db.php');

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_proveedor = isset($_POST['nombre_proveedor']) ? trim($_POST['nombre_proveedor']) : '';
    $apellido_paterno = isset($_POST['apellido_paterno']) ? trim($_POST['apellido_paterno']) : '';
    $apellido_materno = isset($_POST['apellido_materno']) ? trim($_POST['apellido_materno']) : null;
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
    $correo_electronico = isset($_POST['correo_electronico']) ? trim($_POST['correo_electronico']) : '';
    $rfc = isset($_POST['rfc']) ? trim($_POST['rfc']) : null;
    $razon_social = isset($_POST['razon_social']) ? trim($_POST['razon_social']) : null;
    $domicilio = isset($_POST['domicilio']) ? trim($_POST['domicilio']) : null;

    // Normalizar opcionales
    if ($apellido_materno === '') { $apellido_materno = null; }
    if ($telefono === '') { $telefono = null; }
    if ($rfc === '') { $rfc = null; }
    if ($razon_social === '') { $razon_social = null; }
    if ($domicilio === '') { $domicilio = null; }

    // Validaciones
    if (trim($nombre_proveedor) === '' || trim($apellido_paterno) === '' || trim($correo_electronico) === '') {
        $error = 'Los campos de nombre, apellido paterno y correo son obligatorios.';
    } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo electrónico no es válido.';
    } elseif ($telefono != null && !preg_match("/^[0-9+\-()\s]+$/", $telefono)) {
        $error = 'Caracteres inválidos en telefono.';
    } elseif ($rfc != null && !in_array(strlen($rfc), [12,13])) {
        $error = 'RFC inválido. Debe contener 12 o 13 caracteres.';
    } else {
        // Verificar correo repetido
        $correoRepetido = false;
        $stmtCheck = $conn->prepare("SELECT id_proveedor FROM proveedor WHERE correo_electronico = ?");
        $stmtCheck->bind_param('s', $correo_electronico);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $correoRepetido = true;
        }
        $stmtCheck->close();
        
        if ($correoRepetido) {
            $error = 'Este correo electrónico ya está registrado.';
        } else {
            $stmt = $conn->prepare('INSERT INTO proveedor (nombre_proveedor, apellido_paterno, apellido_materno, telefono, correo_electronico, rfc, razon_social, domicilio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssssss', $nombre_proveedor, $apellido_paterno, $apellido_materno, $telefono, $correo_electronico, $rfc, $razon_social, $domicilio);
            
            if ($stmt->execute()) {
                header('Location: ../proveedor.php');
                exit();
            } else {
                $error = 'Error al registrar al proveedor: ' . $stmt->error;
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
  <title>Registrar Proveedor</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1>Registrar Proveedor</h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
        <label class="form-label">Nombre:</label>
        <input type="text" name="nombre_proveedor" maxlength="50" class="form-control" value="<?php echo isset($nombre_proveedor) ? htmlspecialchars($nombre_proveedor) : ''; ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Apellido Paterno:</label>
        <input type="text" name="apellido_paterno" maxlength="50" class="form-control" value="<?php echo isset($apellido_paterno) ? htmlspecialchars($apellido_paterno) : ''; ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Apellido Materno:</label>
        <input type="text" name="apellido_materno" maxlength="50" class="form-control" value="<?php echo isset($apellido_materno) ? htmlspecialchars($apellido_materno) : ''; ?>" placeholder="Opcional">
    </div>
    <div class="mb-3">
        <label class="form-label">Teléfono:</label>
        <input type="text" name="telefono" maxlength="30" class="form-control" pattern="[0-9+\-()\s]+" value="<?php echo isset($telefono) ? htmlspecialchars($telefono) : ''; ?>" placeholder="+52 (55) 1234 5678 (Opcional)">
    </div>
    <div class="mb-3">
        <label class="form-label">Correo Electrónico:</label>
        <input type="email" name="correo_electronico" maxlength="50" class="form-control" value="<?php echo isset($correo_electronico) ? htmlspecialchars($correo_electronico) : ''; ?>" required>
    </div>
    <div class="mb-3">
        <label class="form-label">RFC:</label>
        <input type="text" name="rfc" maxlength="13" class="form-control" value="<?php echo isset($rfc) ? htmlspecialchars($rfc) : ''; ?>" placeholder="Opcional">
        <p class="text-muted">12 ó 13 caracteres si se proporciona.</p>
    </div>
    <div class="mb-3">
        <label class="form-label">Razón Social:</label>
        <input type="text" name="razon_social" maxlength="100" class="form-control" value="<?php echo isset($razon_social) ? htmlspecialchars($razon_social) : ''; ?>" placeholder="Opcional">
    </div>
    <div class="mb-3">
        <label class="form-label">Domicilio:</label>
        <input type="text" name="domicilio" maxlength="200" class="form-control" value="<?php echo isset($domicilio) ? htmlspecialchars($domicilio) : ''; ?>" placeholder="Opcional">
    </div>

    <button type="submit" class="btn btn-primary">Registrar Proveedor</button>
    <a href="../proveedor.php" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
