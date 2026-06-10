<?php
// toggle_status.php
include('db.php');

// Validar parámetros
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$table = isset($_GET['table']) ? $_GET['table'] : '';
$nuevoEstado = isset($_GET['estado']) ? $_GET['estado'] : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

$tablasPermitidas = ['cliente', 'proveedor', 'empleado', 'servicio', 'producto', 'tipo_servicio'];

// Validaciones
if ($id <= 0 || !in_array($table, $tablasPermitidas) || !in_array($nuevoEstado, ['activo', 'inactivo'])) {
    die('Parámetros inválidos');
}

$idField = 'id'; // valor por defecto
if ($table === 'cliente') {
    $idField = 'id_cliente';
} elseif ($table === 'proveedor') {
    $idField = 'id_proveedor';
} elseif ($table === 'empleado') {
    $idField = 'id_empleado';
} elseif ($table === 'servicio') {
    $idField = 'id_servicio';
} elseif ($table === 'producto') {
    $idField = 'id_producto';
} elseif ($table === 'tipo_servicio') {
    $idField = 'id_tipo_servicio';
}

// Actualizar el estado
$sql = "UPDATE $table SET estado = ? WHERE $idField = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $nuevoEstado, $id);

if ($stmt->execute()) {
    // Redirigir de vuelta con mensaje de éxito
    if ($table === 'cliente') {
        header('Location: cliente.php?pagina='.$pagina);
    } elseif ($table === 'proveedor') {
        header('Location: proveedor.php?pagina='.$pagina);
    } elseif ($table === 'empleado') {
        header('Location: empleado.php?pagina='.$pagina);
    } elseif ($table === 'servicio') {
        header('Location: servicio.php?pagina='.$pagina);
    } elseif ($table === 'producto') {
        header('Location: producto.php?pagina='.$pagina);
    } elseif ($table === 'tipo_servicio') {
        header('Location: tipo_servicio.php?pagina='.$pagina);
    } else {
        header('Location: index.php');
    }
    exit();
} else {
    echo 'Error al actualizar: ' . $stmt->error;
}

$stmt->close();
?>