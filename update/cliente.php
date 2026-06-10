<?php
// update_vacacion.php
// --------------------------
// Permite editar un periodo vacacional existente.
// --------------------------

include('../db.php');

// Obtener id desde GET; (int) protege contra inyección convirtiendo a entero
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
// Obtener datos actuales para mostrar en el formulario
$stmt = $conn->prepare('SELECT * FROM cliente WHERE id_cliente = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$vac = $result->fetch_assoc();
$stmt->close();

// Si se envía el formulario, procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Se sacan las variables
    $nombre_cliente = $_POST['nombre_cliente'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $apellido_materno = $_POST['apellido_materno'];
    $telefono = $_POST['telefono'];
    $correo_electronico = $_POST['correo_electronico'];
    $contrasenia = $_POST['contrasenia'];
    $contraseniaConfirmar = $_POST['contraseniaConfirmar'];
    $rfc = $_POST['rfc'];
    $razon_social = $_POST['razon_social'];
    $domicilio = $_POST['domicilio'];

    // Poner valores nulos a campos vacíos no obligatorios
    if (trim($apellido_materno) === '') { // Asignar valores nulos si están vacíos
        $apellido_materno = null;
    }
    if (trim($telefono) === '') { // Asignar valores nulos si están vacíos
        $telefono = null;
    }
    if (trim($rfc) === '') { // Asignar valores nulos si están vacíos
        $rfc = null;
    }
    if (trim($razon_social) === '') { // Asignar valores nulos si están vacíos
        $razon_social = null;
    }
    if (trim($domicilio) === '') { // Asignar valores nulos si están vacíos
        $domicilio = null;
    }
    if (trim($contrasenia) === '') { // Asignar valores nulos si están vacíos
        $contrasenia = null;
    }
    if (trim($contraseniaConfirmar) === '') { // Asignar valores nulos si están vacíos
        $contraseniaConfirmar = null;
    }

    // Sistema de validacion de correo
    $correoRepetido = false; // Se crea una variable para definir si el correo es repetido o no
    $result = $conn->query("SELECT * FROM cliente"); // Se busca en la base de datos
    while ($row = $result->fetch_assoc()) {
        if (trim($row['correo_electronico'])  === trim($correo_electronico) && trim($row['correo_electronico']) != null && trim($row['correo_electronico']) != $vac['correo_electronico']) { // Se valida si algún correo de la base de datos coincide con el que se quiere agregar
            $correoRepetido = true; // Se define el correo repetido como true si es que se encuentra en la base de datos
            break;
        }
    }
    
    // Validaciones
    if (trim($nombre_cliente) === '' || trim($apellido_paterno) === '' || trim($correo_electronico) === '') {  // Verificar que las lineas no estén vacías
        $error = 'Alguno de los campos obligatorios no contiene datos.';
    } elseif ($correo_electronico != null && !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) { // Comprobamos que el correo siga el formato correcto
        $error = 'El correo no cumple con el formato esperado.';
    } elseif ($correoRepetido) { // Comprobamos el que el correo no esté siendo usado ya
        $error = 'Este correo ya está siendo usado.';
    } elseif ($contrasenia != null && (strlen($contrasenia) < 8 || !preg_match('/[0-9]/', $contrasenia))) { // Comprobamos que la contraseña tenga al menos 8 caracteres y un número
        $error = 'La contraseña debe tener al menos 8 caracteres y un número.';
    } elseif ($contrasenia != null && ($contrasenia != $contraseniaConfirmar)) { // Comprobamos que la contraseña y la confirmación sean iguales
        $error = 'La contraseña no es igual a confirmar contraseña.';
    } elseif ($telefono != null && !preg_match("/^[0-9+\-()\s]+$/", $telefono)) { // Comprobamos que el teléfono solo tenga los caractéres permitidos
        $error = 'Caracteres inválidos en telefono.';
    } elseif (strlen($rfc) < 12 && $rfc != null) { // Validación de RFC
        $error = 'RFC con caracteres menores a 12.';
    } else {
      if ($contrasenia != null){
        $contraseniaHasheada = password_hash($contrasenia, PASSWORD_DEFAULT); // Hasheamos la contraseña para protejerla
        $update = 'UPDATE cliente SET nombre_cliente = ?, apellido_paterno = ?, apellido_materno = ?, telefono = ?, correo_electronico = ?, contrasenia = ?, rfc = ?, razon_social = ?, domicilio = ? WHERE id_cliente = ?';
        $stmt = $conn->prepare($update);
        $stmt->bind_param('sssssssssi', $nombre_cliente, $apellido_paterno, $apellido_materno, $telefono, $correo_electronico, $contraseniaHasheada, $rfc, $razon_social, $domicilio,  $id);
        if ($stmt->execute()) {
            header('Location: ../cliente.php?pagina=' . $pagina);
            exit();
        } else {
            $error = 'Error al actualizar: ' . $stmt->error;
        }
        $stmt->close();
      } else {
        $update = 'UPDATE cliente SET nombre_cliente = ?, apellido_paterno = ?, apellido_materno = ?, telefono = ?, correo_electronico = ?, rfc = ?, razon_social = ?, domicilio = ? WHERE id_cliente = ?';
        $stmt = $conn->prepare($update);
        $stmt->bind_param('ssssssssi', $nombre_cliente, $apellido_paterno, $apellido_materno, $telefono, $correo_electronico, $rfc, $razon_social, $domicilio,  $id);
        if ($stmt->execute()) {
            header('Location: ../cliente.php?pagina=' . $pagina);
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
  <title>Editar Cliente</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1>Editar Cliente: <?php echo "$vac[nombre_cliente] " . "$vac[apellido_paterno]"?> </h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
      <label class="form-label">Nombre:</label>
      <input type="text" name="nombre_cliente" maxlength="50" class="form-control" value="<?php echo htmlspecialchars($vac['nombre_cliente']); ?>" required>
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
      <input type="text" name="telefono" class="form-control" maxlength="30" pattern="[0-9+\-()\s]" value="<?php echo htmlspecialchars($vac['telefono']); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Correo Electrónico:</label>
      <input type="email" name="correo_electronico" maxlength="50" class="form-control" value="<?php echo htmlspecialchars($vac['correo_electronico']); ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Contraseña:</label>
      <input type="password" name="contrasenia" maxlength="50" class="form-control" placeholder="No agruegue la contraseña si no desea editarla">
      <p class="text-muted">
        La contraseña debe contener al menos 8 caracteres y un número.
      </p>
    </div>

    <div class="mb-3">
      <label class="form-label">Confirmar Contraseña:</label>
      <input type="password" name="contraseniaConfirmar" maxlength="50" class="form-control" placeholder="No agruegue la contraseña si no desea editarla">
      <p class="text-muted">
        Asegurse de recordar la contraseña.
      </p>
    </div>

    <div class="mb-3">
      <label class="form-label">RFC:</label>
      <input type="text" name="rfc" maxlength="13" class="form-control" value="<?php echo htmlspecialchars($vac['rfc']); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Razón Social:</label>
      <input type="text" name="razon_social" maxlength="100" class="form-control" value="<?php echo htmlspecialchars($vac['razon_social']); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Domicilio:</label>
      <input type="text" name="domicilio" maxlength="200" class="form-control" value="<?php echo htmlspecialchars($vac['domicilio']); ?>">
    </div>

    <button type="submit" class="btn btn-primary">Actualizar</button>
    <a href="../cliente.php" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
