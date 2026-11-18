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

function invoiceEscape($value)
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

$orderHistoryId = filter_input(INPUT_GET, "orderId", FILTER_VALIDATE_INT);
$userId = (int) $_SESSION["u"]["id"];

if (!$orderHistoryId || $orderHistoryId < 1) {
    header("Location: index.php");
    exit;
}

try {
    Database::setupConnection();
    $db = Database::$connection;

    $stmt = $db->prepare(
        "SELECT oh.*, u.fname, u.lname, u.mobile,
                u.no, u.line_1, u.line_2
         FROM order_history oh
         INNER JOIN user u ON u.id = oh.user_id
         WHERE oh.ohid = ? AND oh.user_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException("Unable to prepare invoice query.");
    }

    $stmt->bind_param("ii", $orderHistoryId, $userId);
    $stmt->execute();
    $orderResult = $stmt->get_result();
    $order = $orderResult->fetch_assoc();
    $stmt->close();

    if (
        !$order ||
        !in_array($order["payment_status"], ["PAID", "LEGACY"], true)
    ) {
        header("Location: orderHistory.php");
        exit;
    }

    $itemStmt = $db->prepare(
        "SELECT oi.oi_qty,
                COALESCE(oi.unit_price, s.price) AS unit_price,
                p.name,
                b.brand_name,
                c.cat_name,
                sf.sf_name,
                sz.size_name
         FROM order_item oi
         INNER JOIN stock s ON s.stock_id = oi.stock_stock_id
         INNER JOIN product p ON p.id = s.product_id
         INNER JOIN brand b ON b.brand_id = p.brand_id
         INNER JOIN scent_family sf ON sf.sf_id = p.sf_id
         INNER JOIN category c ON c.cat_id = p.cat_id
         INNER JOIN size sz ON sz.size_id = p.size_id
         WHERE oi.order_history_ohid = ?"
    );

    if (!$itemStmt) {
        throw new RuntimeException("Unable to prepare invoice items.");
    }

    $itemStmt->bind_param("i", $orderHistoryId);
    $itemStmt->execute();
    $itemsResult = $itemStmt->get_result();

    $items = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $items[] = $item;
    }
    $itemStmt->close();

} catch (Throwable $e) {
    http_response_code(500);
    exit("Unable to load this invoice. Please try again later.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="style.css">
    <title>Zeyara Perfumes - Invoice</title>
    <link rel="icon" href="resources/icon/perfume.png">
</head>
<body>

<div class="container text-end mt-2">
    <a href="index.php" class="btn btn-outline-dark btn-sm">Home</a>
</div>

<div class="container mt-2 mb-4">
    <div class="border border-3 border-black p-5 rounded-3" id="printinvoice">

        <div class="row">
            <div class="col-6">
                <h3>Order ID #<?= invoiceEscape($order["order_id"]) ?></h3>
                <h5><?= invoiceEscape($order["order_date"]) ?></h5>
                <p>
                    Payment status:
                    <strong><?= invoiceEscape($order["payment_status"]) ?></strong>
                </p>
            </div>

            <div class="container mt-5">
                <div class="row invoice-header align-items-center p-4">
                    <div class="col-md-6">
                        <h1 class="invoice-title text-start">I N V O I C E</h1>
                    </div>
                    <div class="col-md-6">
                        <div class="company-info text-end">
                            <h4>Zeyara Perfumes</h4>
                            <h5>No.254,</h5>
                            <h5>Anula Road,</h5>
                            <h5>Colombo</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mt-5">
            <div class="row justify-content-start">
                <div class="col-md-6">
                    <div class="user-info">
                        <h1 class="user-name">
                            <?= invoiceEscape($order["fname"]) ?>
                            <?= invoiceEscape($order["lname"]) ?>
                        </h1>
                        <div class="user-details">
                            <h4>Mobile: <?= invoiceEscape($order["mobile"]) ?></h4>
                            <h5>Address:</h5>
                            <h5>
                                <?= invoiceEscape($order["no"]) ?>,
                                <?= invoiceEscape($order["line_1"]) ?>
                            </h5>
                            <h5><?= invoiceEscape($order["line_2"]) ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ps-5 pe-5 mt-5">
            <div class="container mt-5">
                <div class="table-responsive">
                    <table class="table table-hover border border-black">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Brand Name</th>
                                <th>Category</th>
                                <th>Scent Family</th>
                                <th>Size</th>
                                <th>Qty</th>
                                <th>Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr class="table-row">
                                <td><?= invoiceEscape($item["name"]) ?></td>
                                <td><?= invoiceEscape($item["brand_name"]) ?></td>
                                <td><?= invoiceEscape($item["cat_name"]) ?></td>
                                <td><?= invoiceEscape($item["sf_name"]) ?></td>
                                <td><?= invoiceEscape($item["size_name"]) ?></td>
                                <td><?= (int) $item["oi_qty"] ?></td>
                                <td>
                                    Rs.
                                    <?= number_format(
                                        (float) $item["unit_price"] * (int) $item["oi_qty"],
                                        2
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($items) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    No order items found.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-end mt-5 pe-4">
                <h6>
                    Number of Items:
                    <span class="text-muted"><?= count($items) ?></span>
                </h6>
                <h5>
                    Delivery Fee:
                    <span class="text-muted">500.00</span>
                </h5>
                <h3>
                    Net Total:
                    <span class="text-muted">
                        Rs. <?= number_format((float) $order["amount"], 2) ?>
                    </span>
                </h3>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="mt-4 d-flex justify-content-end">
            <button class="btn btn-outline-dark col-2" onclick="printinvc();">
                Print
            </button>
        </div>
    </div>
</div>

<script src="script.js"></script>
<script src="bootstrap.bundle.min.js"></script>
</body>
</html>
