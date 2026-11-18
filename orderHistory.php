<?php

session_start();

if (
    !isset($_SESSION["u"]) ||
    !isset($_SESSION["u"]["id"]) ||
    !is_numeric($_SESSION["u"]["id"])
) {
    header("Location: signin.php");
    exit;
}

require_once "connection.php";

function historyEscape($value)
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

$userId = (int) $_SESSION["u"]["id"];

try {
    Database::setupConnection();
    $db = Database::$connection;

    $orderStmt = $db->prepare(
        "SELECT oh.*
         FROM order_history oh
         WHERE oh.user_id = ?
         ORDER BY oh.order_date DESC, oh.ohid DESC"
    );

    if (!$orderStmt) {
        throw new RuntimeException("Unable to prepare order history query.");
    }

    $orderStmt->bind_param("i", $userId);
    $orderStmt->execute();
    $ordersResult = $orderStmt->get_result();

    $itemStmt = $db->prepare(
        "SELECT p.name,
                oi.oi_qty,
                COALESCE(oi.unit_price, s.price) AS unit_price
         FROM order_item oi
         INNER JOIN stock s ON s.stock_id = oi.stock_stock_id
         INNER JOIN product p ON p.id = s.product_id
         WHERE oi.order_history_ohid = ?"
    );

    if (!$itemStmt) {
        throw new RuntimeException("Unable to prepare order items query.");
    }

} catch (Throwable $e) {
    http_response_code(500);
    exit("Unable to load order history. Please try again later.");
}

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="style.css">
    <title>Zeyara Perfumes - Order History</title>
    <link rel="icon" href="resources/icon/perfume.png">
</head>

<body class="orderBody">

<?php include "navBar.php"; ?>

<div class="container mt-5">
    <div class="row mt-5" id="ordhid">

        <?php if ($ordersResult->num_rows > 0): ?>

            <div class="col-12 mt-4 mb-3">
                <h3>Order History</h3>
            </div>

            <?php while ($order = $ordersResult->fetch_assoc()): ?>

                <div class="col-12 mb-4">
                    <div class="card border-3 rounded-3 bg-body-secondary">

                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                Order ID
                                <span class="text-danger">
                                    #<?= historyEscape($order["order_id"]) ?>
                                </span>
                            </h5>

                            <p class="card-text mb-0">
                                Order Date &amp; Time:
                                <?= historyEscape($order["order_date"]) ?>
                            </p>

                            <p class="card-text mb-0">
                                Payment Status:
                                <strong>
                                    <?= historyEscape($order["payment_status"]) ?>
                                </strong>
                            </p>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    <?php
                                    $orderId = (int) $order["ohid"];
                                    $itemStmt->bind_param("i", $orderId);
                                    $itemStmt->execute();
                                    $itemsResult = $itemStmt->get_result();

                                    while ($item = $itemsResult->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?= historyEscape($item["name"]) ?></td>
                                            <td><?= (int) $item["oi_qty"] ?></td>
                                            <td>
                                                Rs.
                                                <?= number_format(
                                                    (float) $item["unit_price"] * (int) $item["oi_qty"],
                                                    2
                                                ) ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>

                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between mt-3">
                                <div>
                                    <h6>
                                        Delivery Fee:
                                        <span class="text-warning">500.00</span>
                                    </h6>
                                    <h4>
                                        Net Total:
                                        <span class="text-warning">
                                            Rs. <?= number_format((float) $order["amount"], 2) ?>
                                        </span>
                                    </h4>
                                </div>

                                <?php if (
                                    in_array(
                                        $order["payment_status"],
                                        ["PAID", "LEGACY"],
                                        true
                                    )
                                ): ?>
                                    <div class="align-self-end">
                                        <a
                                            class="btn btn-outline-dark"
                                            href="invoice.php?orderId=<?= (int) $order["ohid"] ?>"
                                        >
                                            View Invoice
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12 text-center mt-5">
                <h2>You have not ordered anything yet!</h2>
                <a href="index.php" class="btn btn-primary">Start Shopping</a>
            </div>

        <?php endif; ?>

    </div>

    <div class="row mt-3 justify-content-end">
        <div class="col-auto">
            <button class="btn btn-outline-light" onclick="printpurchhis();">
                Get A Print
            </button>
        </div>
    </div>
</div>

<?php
$itemStmt->close();
$orderStmt->close();
?>

<script src="script.js"></script>
<script src="bootstrap.bundle.min.js"></script>
</body>
</html>
