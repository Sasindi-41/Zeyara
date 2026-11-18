<?php

require_once __DIR__ . '/privateConfig.php';
session_start();

header("Content-Type: application/json; charset=UTF-8");

function paymentResponse($success, $message, $data = [])
{
    echo json_encode(
        array_merge([
            "success" => $success,
            "message" => $message
        ], $data),
        JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    paymentResponse(false, "Invalid request method.");
}

if (
    !isset($_SESSION["u"]["id"]) ||
    !is_numeric($_SESSION["u"]["id"])
) {
    http_response_code(401);
    paymentResponse(false, "Please sign in before checkout.");
}

$userId = (int) $_SESSION["u"]["id"];

$csrf = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["payment_csrf"] ?? "";

if (
    !is_string($csrf) ||
    !is_string($sessionToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $csrf)
) {
    http_response_code(403);
    paymentResponse(false, "Your session expired. Refresh the page and try again.");
}

$merchantId = getenv("ZEYARA_PAYHERE_MERCHANT_ID");
$merchantSecret = getenv("ZEYARA_PAYHERE_MERCHANT_SECRET");
$baseUrl = getenv("ZEYARA_BASE_URL");
$sandboxSetting = getenv("ZEYARA_PAYHERE_SANDBOX");

if ($merchantId === false || trim($merchantId) === "") {
    $merchantId = "1224066";
}

if (
    $merchantSecret === false ||
    trim($merchantSecret) === "" ||
    $baseUrl === false ||
    trim($baseUrl) === ""
) {
    http_response_code(503);
    paymentResponse(false, "Payment service configuration is incomplete.");
}

$baseUrl = rtrim(trim($baseUrl), "/");
$urlParts = parse_url($baseUrl);

if (
    !$urlParts ||
    empty($urlParts["scheme"]) ||
    empty($urlParts["host"]) ||
    !in_array($urlParts["scheme"], ["https", "http"], true) ||
    (
        $urlParts["scheme"] === "http" &&
        !in_array(
            strtolower($urlParts["host"]),
            ["localhost", "127.0.0.1"]
        )
    )
) {
    http_response_code(503);
    paymentResponse(false, "Payment website URL must use HTTPS.");
}

$cartFlag = $_POST["cart"] ?? null;

if (!is_string($cartFlag) || !in_array($cartFlag, ["true", "false"], true)) {
    http_response_code(400);
    paymentResponse(false, "Invalid checkout request.");
}

$requestedItems = [];

if ($cartFlag === "true") {
    // Cart quantities are aggregated in case duplicate rows exist.
    try {
        require_once "connection.php";
        Database::setupConnection();
        $db = Database::$connection;

        $cartStmt = $db->prepare(
            "SELECT stock_stock_id, SUM(cart_qty) AS total_qty
             FROM cart
             WHERE user_id = ?
             GROUP BY stock_stock_id
             ORDER BY stock_stock_id ASC"
        );

        if (!$cartStmt) {
            throw new RuntimeException("Cart query preparation failed.");
        }

        $cartStmt->bind_param("i", $userId);
        $cartStmt->execute();
        $cartResult = $cartStmt->get_result();

        while ($row = $cartResult->fetch_assoc()) {
            $stockId = (int) $row["stock_stock_id"];
            $quantity = filter_var(
                $row["total_qty"],
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]]
            );

            if ($stockId < 1 || $quantity === false) {
                throw new RuntimeException("Invalid cart quantity.");
            }

            $requestedItems[$stockId] = $quantity;
        }

        $cartStmt->close();

    } catch (Throwable $e) {
        http_response_code(400);
        paymentResponse(false, "Unable to read your cart.");
    }

} else {
    $stockId = filter_var(
        $_POST["stockId"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    $quantity = filter_var(
        $_POST["qty"] ?? null,
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($stockId === false || $quantity === false) {
        http_response_code(400);
        paymentResponse(false, "Invalid product or quantity.");
    }

    $requestedItems[$stockId] = $quantity;
}

if (count($requestedItems) === 0) {
    http_response_code(400);
    paymentResponse(false, "Your cart is empty.");
}

ksort($requestedItems, SORT_NUMERIC);

try {
    require_once "connection.php";
    Database::setupConnection();
    $db = Database::$connection;

    // Read customer details from the database, not session-supplied POST data.
    $customerStmt = $db->prepare(
        "SELECT fname, lname, email, mobile, no, line_1, line_2
         FROM user
         WHERE id = ?
         LIMIT 1"
    );

    if (!$customerStmt) {
        throw new RuntimeException("Customer query preparation failed.");
    }

    $customerStmt->bind_param("i", $userId);
    $customerStmt->execute();
    $customer = $customerStmt->get_result()->fetch_assoc();
    $customerStmt->close();

    if (
        !$customer ||
        !filter_var($customer["email"], FILTER_VALIDATE_EMAIL) ||
        !preg_match('/^[0-9]{10}$/', (string) $customer["mobile"])
    ) {
        paymentResponse(false, "Please update your email and mobile number before checkout.");
    }

    $db->begin_transaction();

    $stockStmt = $db->prepare(
        "SELECT s.stock_id, s.qty, s.price, s.status, p.name
         FROM stock s
         INNER JOIN product p ON p.id = s.product_id
         WHERE s.stock_id = ?
         FOR UPDATE"
    );

    $reservedStmt = $db->prepare(
        "SELECT COALESCE(SUM(quantity), 0) AS reserved_qty
         FROM stock_reservations
         WHERE stock_stock_id = ?
           AND reservation_status = 'ACTIVE'
           AND expires_at > UTC_TIMESTAMP()"
    );

    if (!$stockStmt || !$reservedStmt) {
        throw new RuntimeException("Stock query preparation failed.");
    }

    $validatedItems = [];
    $subtotalCents = 0;
    $itemNames = [];

    foreach ($requestedItems as $stockId => $quantity) {
        $stockStmt->bind_param("i", $stockId);
        $stockStmt->execute();
        $stock = $stockStmt->get_result()->fetch_assoc();

        if (!$stock || strtolower($stock["status"]) !== "available") {
            throw new RuntimeException("A product is no longer available.");
        }

        $reservedStmt->bind_param("i", $stockId);
        $reservedStmt->execute();
        $reserved = $reservedStmt->get_result()->fetch_assoc();

        $availableQty =
            (int) $stock["qty"] - (int) $reserved["reserved_qty"];

        if ($quantity > $availableQty) {
            throw new RuntimeException(
                "Insufficient available stock for " . $stock["name"] . "."
            );
        }

        $unitPriceCents = (int) round((float) $stock["price"] * 100);

        if ($unitPriceCents < 0) {
            throw new RuntimeException("Invalid product price.");
        }

        $subtotalCents += $unitPriceCents * $quantity;

        $validatedItems[] = [
            "stock_id" => (int) $stockId,
            "quantity" => (int) $quantity,
            "unit_price" => number_format($unitPriceCents / 100, 2, ".", ""),
            "name" => $stock["name"]
        ];

        $itemNames[] = $stock["name"];
    }

    $stockStmt->close();
    $reservedStmt->close();

    // Existing delivery charge.
    $totalCents = $subtotalCents + 50000;
    $totalAmount = number_format($totalCents / 100, 2, ".", "");

    $orderId = bin2hex(random_bytes(16));

    $orderStmt = $db->prepare(
        "INSERT INTO order_history
            (order_id, order_date, amount, user_id, payment_status, currency)
         VALUES (?, UTC_TIMESTAMP(), ?, ?, 'PENDING', 'LKR')"
    );

    if (!$orderStmt) {
        throw new RuntimeException("Order query preparation failed.");
    }

    $orderStmt->bind_param("sdi", $orderId, $totalAmount, $userId);
    $orderStmt->execute();
    $orderHistoryId = (int) $db->insert_id;
    $orderStmt->close();

    $itemStmt = $db->prepare(
        "INSERT INTO order_item
            (oi_qty, order_history_ohid, stock_stock_id, unit_price)
         VALUES (?, ?, ?, ?)"
    );

    $reservationStmt = $db->prepare(
        "INSERT INTO stock_reservations
            (order_history_ohid, stock_stock_id, quantity,
             reservation_status, expires_at)
         VALUES (?, ?, ?, 'ACTIVE', DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE))"
    );

    if (!$itemStmt || !$reservationStmt) {
        throw new RuntimeException("Order item preparation failed.");
    }

    foreach ($validatedItems as $item) {
        $itemQty = $item["quantity"];
        $itemStockId = $item["stock_id"];
        $unitPrice = $item["unit_price"];

        $itemStmt->bind_param(
            "iiis",
            $itemQty,
            $orderHistoryId,
            $itemStockId,
            $unitPrice
        );
        $itemStmt->execute();

        $reservationStmt->bind_param(
            "iii",
            $orderHistoryId,
            $itemStockId,
            $itemQty
        );
        $reservationStmt->execute();
    }

    $itemStmt->close();
    $reservationStmt->close();

    $db->commit();

    $currency = "LKR";

    $hash = strtoupper(
        md5(
            $merchantId .
            $orderId .
            $totalAmount .
            $currency .
            strtoupper(md5($merchantSecret))
        )
    );

    $addressParts = array_filter([
        trim((string) $customer["no"]),
        trim((string) $customer["line_1"])
    ]);

    $payment = [
        "sandbox" => !in_array(
            strtolower((string) $sandboxSetting),
            ["false", "0", "no"],
            true
        ),
        "merchant_id" => $merchantId,
        "return_url" => $baseUrl . "/index.php",
        "cancel_url" => $baseUrl . "/index.php",
        "notify_url" => $baseUrl . "/payhereNotify.php",
        "first_name" => $customer["fname"],
        "last_name" => $customer["lname"],
        "email" => $customer["email"],
        "phone" => $customer["mobile"],
        "address" => implode(", ", $addressParts) ?: "Not provided",
        "city" => trim((string) $customer["line_2"]) ?: "Colombo",
        "country" => "Sri Lanka",
        "order_id" => $orderId,
        "order_history_id" => $orderHistoryId,
        "items" => implode(", ", array_slice($itemNames, 0, 5)),
        "currency" => $currency,
        "amount" => $totalAmount,
        "hash" => $hash
    ];

    paymentResponse(true, "Order prepared.", [
        "payment" => $payment,
        "order_id" => $orderId,
        "order_history_id" => $orderHistoryId
    ]);

} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) {
        try {
            if ($db->errno === 0 || $db->errno !== 0) {
                $db->rollback();
            }
        } catch (Throwable $ignored) {
        }
    }

    http_response_code(400);
    paymentResponse(false, "Unable to prepare checkout. Please refresh your cart and try again.");
}

