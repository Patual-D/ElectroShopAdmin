<?php
include('../db.php');

// Obtener el ID de la factura desde la URL de forma segura
$id_factura = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_factura <= 0) {
    die('ID de factura no válido.');
}

// 1) OBTENER DATOS GENERALES DE LA FACTURA (cliente o proveedor según tipo)
$stmt_factura = $conn->prepare("
    SELECT 
        F.id_factura,
        F.tipo_factura,
        F.fecha_factura,
        F.metodo_pago,
        F.total,
        F.id_cliente,
        F.id_proveedor,
        CONCAT_WS(' ', C.nombre_cliente, C.apellido_paterno) AS nombre_cliente,
        CONCAT_WS(' ', P.nombre_proveedor, P.apellido_paterno) AS nombre_proveedor
    FROM factura F
    LEFT JOIN cliente C   ON F.id_cliente = C.id_cliente
    LEFT JOIN proveedor P ON F.id_proveedor = P.id_proveedor
    WHERE F.id_factura = ?
");
$stmt_factura->bind_param('i', $id_factura);
$stmt_factura->execute();
$result_factura = $stmt_factura->get_result();
$factura = $result_factura->fetch_assoc();
$stmt_factura->close();

if (!$factura) {
    die('Factura no encontrada.');
}

// 2) SI ES FACTURA DE VENTA, OBTENER COMPRAS ASOCIADAS Y SUS PRODUCTOS
$compras = [];
$productos_por_compra = [];

if ($factura['tipo_factura'] === 'venta') {
    // 2.1) Compras asociadas a la factura
    $stmt_fc = $conn->prepare("
        SELECT 
            CO.id_compra,
            CO.fecha_compra,
            CO.metodo_pago AS metodo_pago_compra,
            CO.total AS total_compra
        FROM factura_compra FC
        JOIN compra CO ON CO.id_compra = FC.id_compra
        WHERE FC.id_factura = ?
        ORDER BY CO.id_compra ASC
    ");
    $stmt_fc->bind_param('i', $id_factura);
    $stmt_fc->execute();
    $result_fc = $stmt_fc->get_result();
    while ($row = $result_fc->fetch_assoc()) {
        $compras[] = $row;
    }
    $stmt_fc->close();

    // 2.2) Por cada compra, obtener productos
    if (!empty($compras)) {
        $stmt_prod = $conn->prepare("
            SELECT 
                CP.id_compra,
                P.nombre_producto,
                CP.cantidad,
                CP.precio_unitario,
                (CP.cantidad * CP.precio_unitario) AS subtotal
            FROM compra_producto CP
            JOIN producto P ON P.id_producto = CP.id_producto
            WHERE CP.id_compra = ?
        ");

        foreach ($compras as $c) {
            $productos_por_compra[$c['id_compra']] = [];
            $id_c = (int)$c['id_compra'];
            $stmt_prod->bind_param('i', $id_c);
            $stmt_prod->execute();
            $res_p = $stmt_prod->get_result();
            while ($row_p = $res_p->fetch_assoc()) {
                $productos_por_compra[$c['id_compra']][] = $row_p;
            }
        }
        $stmt_prod->close();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Factura #<?php echo htmlspecialchars($factura['id_factura']); ?></title>
    <link href="../img/assets/ICONO.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="card">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h1 class="h4 m-0">Detalle de Factura</h1>
            <a href="../factura_venta.php" class="btn btn-light btn-sm">← Volver al Listado</a>
        </div>
        <div class="card-body">
            <!-- Encabezado -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h2 class="h5">Información General</h2>
                    <p><strong>ID de Factura:</strong> <?php echo htmlspecialchars($factura['id_factura']); ?></p>
                    <p><strong>Tipo:</strong> <?php echo htmlspecialchars(ucfirst($factura['tipo_factura'])); ?></p>

                    <?php if ($factura['tipo_factura'] === 'venta'): ?>
                        <p><strong>Cliente:</strong> 
                            <?php echo htmlspecialchars($factura['nombre_cliente'] ?: 'Sin cliente'); ?>
                        </p>
                    <?php else: ?>
                        <p><strong>Proveedor:</strong> 
                            <?php echo htmlspecialchars($factura['nombre_proveedor'] ?: 'Sin proveedor'); ?>
                        </p>
                    <?php endif; ?>

                    <p><strong>Fecha:</strong> <?php echo htmlspecialchars($factura['fecha_factura']); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h2 class="h5">Resumen Financiero</h2>
                    <p><strong>Método de Pago:</strong> <?php echo htmlspecialchars($factura['metodo_pago']); ?></p>
                    <p class="h4"><strong>Total: $<?php echo htmlspecialchars(number_format($factura['total'], 2)); ?></strong></p>
                </div>
            </div>

            <hr>

            <!-- Detalle para FACTURA DE VENTA -->
            <?php if ($factura['tipo_factura'] === 'venta'): ?>
                <h2 class="h5 mt-3">Compras asociadas a esta Factura</h2>

                <?php if (empty($compras)): ?>
                    <div class="alert alert-warning my-3">
                        No hay compras asociadas a esta factura.
                    </div>
                <?php else: ?>
                    <?php foreach ($compras as $c): ?>
                        <div class="card mb-4 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between flex-wrap">
                                    <div class="mb-2">
                                        <h3 class="h6 mb-1">Compra #<?php echo htmlspecialchars($c['id_compra']); ?></h3>
                                        <div><strong>Fecha:</strong> <?php echo htmlspecialchars($c['fecha_compra']); ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div><strong>Método pago compra:</strong> <?php echo htmlspecialchars($c['metodo_pago_compra']); ?></div>
                                        <div class="h6 mb-0"><strong>Total compra:</strong> $<?php echo htmlspecialchars(number_format($c['total_compra'], 2)); ?></div>
                                    </div>
                                </div>

                                <?php
                                $productos = $productos_por_compra[$c['id_compra']] ?? [];
                                ?>

                                <?php if (!empty($productos)): ?>
                                    <div class="table-responsive mt-3">
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
                                                <?php foreach ($productos as $p): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($p['nombre_producto']); ?></td>
                                                        <td class="text-center"><?php echo htmlspecialchars($p['cantidad']); ?></td>
                                                        <td class="text-end">$<?php echo htmlspecialchars(number_format($p['precio_unitario'], 2)); ?></td>
                                                        <td class="text-end">$<?php echo htmlspecialchars(number_format($p['subtotal'], 2)); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-light mt-3 mb-0">
                                        Esta compra no tiene productos registrados.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <!-- Detalle para FACTURA DE COMPRA (proveedor) -->
            <?php else: ?>
                <h2 class="h5 mt-3">Detalle</h2>
                <div class="alert alert-info">
                    Esta factura es de tipo "compra" (proveedor). En el modelo actual no hay tabla de detalle 
                    ligada a factura de compra, por lo que se muestra únicamente la información general y el total.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
