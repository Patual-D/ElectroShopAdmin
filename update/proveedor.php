<?php
// update/proveedor.php
include('../db.php');

// Obtener id desde GET; (int) protege contra inyección convirtiendo a entero
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Obtener datos actuales para mostrar en el formulario
$stmt = $conn->prepare('SELECT * FROM proveedor WHERE id_proveedor = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$proveedor = $result->fetch_assoc();
$stmt->close();

// Si no se encontró el proveedor, redirigir
if (!$proveedor) {
    header('Location: ../proveedor.php');
    exit();
}

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
    if (trim($nombre_proveedor) === '' || trim($apellido_paterno) === '' || $correo_electronico === '') {
        $error = 'Los campos de nombre, apellido paterno y correo son obligatorios.';
    } elseif (!filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo electrónico no es válido.';
    } elseif ($telefono != null && !preg_match("/^[0-9+\-()\s]+$/", $telefono)) {
        $error = 'Caracteres inválidos en telefono.';
    } elseif ($rfc != null && !in_array(strlen($rfc), [12,13])) {
        $error = 'RFC inválido. Debe contener 12 o 13 caracteres.';
    } else {
        // Verificar correo repetido (excluir al proveedor actual)
        $correoRepetido = false;
        $stmtCheck = $conn->prepare("SELECT id_proveedor FROM proveedor WHERE correo_electronico = ? AND id_proveedor != ?");
        $stmtCheck->bind_param('si', $correo_electronico, $id);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $correoRepetido = true;
        }
        $stmtCheck->close();
        
        if ($correoRepetido) {
            $error = 'Este correo electrónico ya está registrado por otro proveedor.';
        } else {
            $stmt = $conn->prepare('UPDATE proveedor SET nombre_proveedor = ?, apellido_paterno = ?, apellido_materno = ?, telefono = ?, correo_electronico = ?, rfc = ?, razon_social = ?, domicilio = ? WHERE id_proveedor = ?');
            $stmt->bind_param('ssssssssi', $nombre_proveedor, $apellido_paterno, $apellido_materno, $telefono, $correo_electronico, $rfc, $razon_social, $domicilio, $id);
            
            if ($stmt->execute()) {
                header('Location: ../proveedor.php?pagina=' . $pagina);
                exit();
            } else {
                $error = 'Error al actualizar el proveedor: ' . $stmt->error;
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
  <title>Editar Proveedor</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1>Editar Proveedor: <?php echo htmlspecialchars($proveedor['nombre_proveedor'] . ' ' . $proveedor['apellido_paterno']); ?></h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
      <label class="form-label">Nombre:</label>
      <input type="text" name="nombre_proveedor" maxlength="50" class="form-control" value="<?php echo htmlspecialchars(isset($nombre_proveedor) ? $nombre_proveedor : $proveedor['nombre_proveedor']); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Apellido Paterno:</label>
      <input type="text" name="apellido_paterno" maxlength="50" class="form-control" value="<?php echo htmlspecialchars(isset($apellido_paterno) ? $apellido_paterno : $proveedor['apellido_paterno']); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Apellido Materno:</label>
      <input type="text" name="apellido_materno" maxlength="50" class="form-control" value="<?php echo htmlspecialchars(isset($apellido_materno) ? $apellido_materno : $proveedor['apellido_materno']); ?>" placeholder="Opcional">
    </div>
    <div class="mb-3">
      <label class="form-label">Teléfono:</label>
      <input type="text" name="telefono" maxlength="30" class="form-control" pattern="[0-9+\-()\s]+" value="<?php echo htmlspecialchars(isset($telefono) ? $telefono : $proveedor['telefono']); ?>" placeholder="Opcional">
    </div>
    <div class="mb-3">
      <label class="form-label">Correo Electrónico:</label>
      <input type="email" name="correo_electronico" maxlength="50" class="form-control" value="<?php echo htmlspecialchars(isset($correo_electronico) ? $correo_electronico : $proveedor['correo_electronico']); ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">RFC:</label>
      <input type="text" name="rfc" maxlength="13" class="form-control" value="<?php echo htmlspecialchars(isset($rfc) ? $rfc : $proveedor['rfc']); ?>" placeholder="Opcional">
      <p class="text-muted">12 ó 13 caracteres si se proporciona.</p>
    </div>
    <div class="mb-3">
      <label class="form-label">Razón Social:</label>
      <input type="text" name="razon_social" maxlength="100" class="form-control" value="<?php echo htmlspecialchars(isset($razon_social) ? $razon_social : $proveedor['razon_social']); ?>" placeholder="Opcional">
    </div>
    <div class="mb-3">
      <label class="form-label">Domicilio:</label>
      <input type="text" name="domicilio" maxlength="200" class="form-control" value="<?php echo htmlspecialchars(isset($domicilio) ? $domicilio : $proveedor['domicilio']); ?>" placeholder="Opcional">
    </div>

    <button type="submit" class="btn btn-primary">Actualizar Proveedor</button>
    <a href="../proveedor.php?pagina=<?php echo $pagina; ?>" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
