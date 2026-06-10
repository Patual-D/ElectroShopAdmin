<?php
// update_vacacion.php
// --------------------------
// Permite editar un periodo vacacional existente.
// --------------------------

include('../db.php');

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

    /* // Sistema de validacion de correo
    $correoRepetido = false; // Se crea una variable para definir si el correo es repetido o no
    $result = $conn->query("SELECT * FROM cliente"); // Se busca en la base de datos
    while ($row = $result->fetch_assoc()) {
        if (trim($row['correo_electronico'])  === trim($correo_electronico) && trim($row['correo_electronico']) != null ) { // Se valida si algún correo de la base de datos coincide con el que se quiere agregar
            $correoRepetido = true; // Se define el correo repetido como true si es que se encuentra en la base de datos
            break;
        }
    } */

    // Aplicamos trim al correo
    $correo_electronico = trim($correo_electronico);
    if ($correo_electronico === '') {
        $correo_electronico = null;
    }

    // Sistema para encontrar correo ya ingresado
    $correoRepetido = false;
    if ($correo_electronico !== null) {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM cliente WHERE correo_electronico = ?");
        $stmtCheck->bind_param('s', $correo_electronico);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        if ($resCheck) {
            $rowCheck = $resCheck->fetch_assoc();
            $correoRepetido = ($rowCheck['cnt'] > 0);
        }
        $stmtCheck->close();
    }

    
    // Validaciones
    if (trim($nombre_cliente) === '' || trim($apellido_paterno) === '' || trim($correo_electronico) === ''  || trim($contrasenia) === '' || trim($contraseniaConfirmar) === '') {  // Verificar que las lineas no estén vacías
        $error = 'Alguno de los campos obligatorios no contiene datos.';
    } elseif ($correo_electronico != null && !filter_var($correo_electronico, FILTER_VALIDATE_EMAIL)) { // Comprobamos que el correo siga el formato correcto
        $error = 'El correo no cumple con el formato esperado.';
    } elseif ($correoRepetido) { // Comprobamos el que el correo no esté siendo usado ya
        $error = 'Este correo ya está siendo usado.';
    } elseif (strlen($contrasenia) < 8 || !preg_match('/[0-9]/', $contrasenia)) { // Comprobamos que la contraseña tenga al menos 8 caracteres y un número
        $error = 'La contraseña debe tener al menos 8 caracteres y un número.';
    } elseif ($contrasenia != $contraseniaConfirmar) { // Comprobamos que la contraseña y la confirmación sean iguales
        $error = 'La contraseña no es igual a confirmar contraseña.';
    } elseif ($telefono != null && !preg_match("/^[0-9+\-()\s]+$/", $telefono)) { // Comprobamos que el teléfono solo tenga los caractéres permitidos
        $error = 'Caracteres inválidos en telefono.';
    } elseif (strlen($rfc) < 12 && $rfc != null) { // Validación de RFC
        $error = 'RFC con caracteres menores a 12.';
    } else {
        $contraseniaHasheada = password_hash($contrasenia, PASSWORD_DEFAULT); // Hasheamos la contraseña para protejerla
        $stmt = $conn->prepare('INSERT INTO cliente (nombre_cliente, apellido_paterno, apellido_materno, telefono, correo_electronico, contrasenia, rfc, razon_social, domicilio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sssssssss', $nombre_cliente, $apellido_paterno, $apellido_materno, $telefono, $correo_electronico, $contraseniaHasheada, $rfc, $razon_social, $domicilio);
        if ($stmt->execute()) {
            header('Location: ../cliente.php');
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
  <title>Registrar Cliente</title>
  <link href="../img/assets/ICONO.png" rel="icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1>Registrar Cliente</h1>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } ?>

  <form method="post" action="">
    <div class="mb-3">
      <label class="form-label">Nombre:</label>
      <input type="text" name="nombre_cliente" placeholder="Carlos" maxlength="50" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Apellido Paterno:</label>
      <input type="text" name="apellido_paterno" placeholder="Ramírez" maxlength="50" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Apellido Materno:</label>
      <input type="text" name="apellido_materno" placeholder="López (Opcional)" maxlength="50" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Teléfono:</label>
      <input type="text" name="telefono" class="form-control" placeholder="+55 (512) 34 56 27 (Opcional)" maxlength="30" pattern="[0-9+\-()\s]">
    </div>

    <div class="mb-3">
      <label class="form-label">Correo Electrónico:</label>
      <input type="email" name="correo_electronico" placeholder="carlos.ramirez@mail.com" maxlength="50" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Contraseña:</label>
      <input type="password" name="contrasenia" placeholder="Contrasenia1" maxlength="50" class="form-control" required>
      <p class="text-muted">
        La contraseña debe contener al menos 8 caracteres y un número.
      </p>
    </div>

    <div class="mb-3">
      <label class="form-label">Confirmar Contraseña:</label>
      <input type="password" name="contraseniaConfirmar" placeholder="Contrasenia1" maxlength="50" class="form-control" required>
      <p class="text-muted">
        Asegurse de recordar la contraseña.
      </p>
    </div>

    <div class="mb-3">
      <label class="form-label">RFC:</label>
      <input type="text" name="rfc" placeholder="Hola" maxlength="RAMC800101XXX (Opcional)" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Razón Social:</label>
      <input type="text" name="razon_social" placeholder="ElectroMundoGamer (Opcional)" maxlength="100" class="form-control">
    </div>

    <div class="mb-3">
      <label class="form-label">Domicilio:</label>
      <input type="text" name="domicilio" placeholder="Av. Reforma 123, CDMX (Opcional)" maxlength="200" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Registrar</button>
    <a href="../cliente.php" class="btn btn-secondary">Volver</a>
  </form>
</body>
</html>
