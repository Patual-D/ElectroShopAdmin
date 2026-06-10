<?php
// delete_vacacion.php
// --------------------------
// Elimina un periodo vacacional dado su id (DELETE del CRUD).
// --------------------------

include('db.php');

// Obtener id y tabla desde GET y convertir a entero y string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$table = isset($_GET['table']) ? $_GET['table'] : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

$tablasPermitidas = ['cliente', 'proveedor', 'empleado', 'servicio', 'producto', 'categoria'];

if ($id <= 0 || !in_array($table, $tablasPermitidas)) {
    die ('Parámetros inválidos');
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
} elseif ($table === 'categoria') {
    $idField = 'id_categoria';
}

$sql = "DELETE FROM $table WHERE $idField = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Error preparando la consulta: ' . $conn->error);
}

$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // Redirigir a la página correspondiente según la tabla
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
    } elseif ($table === 'categoria') {
        header('Location: categoria.php?pagina='.$pagina);
    } else {
        header('Location: index.php');
    }
    exit();
} else {
    echo 'Error al eliminar: ' . $stmt->error;
}

$stmt->close();
?>