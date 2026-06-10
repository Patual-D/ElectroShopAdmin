<?php
include('../db.php');

// Obtener el ID de la compra desde la URL de forma segura
$id_compra = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_compra <= 0) {
    die('ID de compra no válido.');
}

// 1. OBTENER LOS DATOS GENERALES DE LA COMPRA (uniendo con cliente)
$stmt_compra = $conn->prepare("
    SELECT
        CO.id_compra,
        CO.fecha_compra,
        CO.metodo_pago,
        CO.total,
        CONCAT_WS(' ', CL.nombre_cliente, CL.apellido_paterno) AS nombre_completo_cliente
    FROM compra CO
    LEFT JOIN cliente CL ON CO.id_cliente = CL.id_cliente
    WHERE CO.id_compra = ?
");
$stmt_compra->bind_param('i', $id_compra);
$stmt_compra->execute();
$result_compra = $stmt_compra->get_result();
$compra = $result_compra->fetch_assoc();
$stmt_compra->close();

if (!$compra) {
    die('Compra no encontrada.');
}

// 2. OBTENER LOS PRODUCTOS ASOCIADOS A LA COMPRA (uniendo con producto)
$stmt_productos = $conn->prepare("
    SELECT 
        P.nombre_producto,
        CP.cantidad,
        CP.precio_unitario,
        (CP.cantidad * CP.precio_unitario) AS subtotal
    FROM compra_producto CP
    JOIN producto P ON CP.id_producto = P.id_producto
    WHERE CP.id_compra = ?
");
$stmt_productos->bind_param('i', $id_compra);
$stmt_productos->execute();
$result_productos = $stmt_productos->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Compra #<?php echo htmlspecialchars($compra['id_compra']); ?></title>
    <link href="../img/assets/ICONO.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h1 class="h4 m-0">Detalle de Compra</h1>
            <a href="../compra.php" class="btn btn-light btn-sm">← Volver al Listado</a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="h5">Información General</h2>
                    <p><strong>ID de Compra:</strong> <?php echo htmlspecialchars($compra['id_compra']); ?></p>
                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($compra['nombre_completo_cliente']); ?></p>
                    <p><strong>Fecha:</strong> <?php echo htmlspecialchars($compra['fecha_compra']); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h2 class="h5">Resumen Financiero</h2>
                    <p><strong>Método de Pago:</strong> <?php echo htmlspecialchars($compra['metodo_pago']); ?></p>
                    <p class="h4"><strong>Total: $<?php echo htmlspecialchars(number_format($compra['total'], 2)); ?></strong></p>
                </div>
            </div>

            <hr>

            <h2 class="h5 mt-4">Productos en esta Compra</h2>
            <table class="table table-bordered table-striped">
                <thead class="table-secondary">
                    <tr>
                        <th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Precio Unitario</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($producto = $result_productos->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                        <td class="text-end">$<?php echo htmlspecialchars(number_format($producto['precio_unitario'], 2)); ?></td>
                        <td class="text-end">$<?php echo htmlspecialchars(number_format($producto['subtotal'], 2)); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>