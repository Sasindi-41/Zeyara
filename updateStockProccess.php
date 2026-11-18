<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header("Content-Type: text/plain; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method Not Allowed");
}

// Require an authenticated, active administrator.
$admin = $_SESSION["a"] ?? null;

if (
    !is_array($admin) ||
    !isset($admin["id"], $admin["user_type_id"], $admin["status"]) ||
    !ctype_digit((string) $admin["id"]) ||
    (int) $admin["id"] < 1 ||
    (int) $admin["user_type_id"] !== 1 ||
    (string) $admin["status"] !== "1"
) {
    http_response_code(403);
    exit("Unauthorized");
}

// Validate CSRF token.
$sessionToken = $_SESSION["admin_csrf_token"] ?? "";
$requestToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($sessionToken) ||
    !is_string($requestToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $requestToken)
) {
    http_response_code(403);
    exit("Invalid security token. Reload the admin stock page.");
}

// Validate product ID.
$productInput = $_POST["pname"] ?? "";

if (
    !is_string($productInput) ||
    !preg_match('/^[1-9][0-9]*$/', $productInput)
) {
    exit("Please Select A Product");
}

$productId = filter_var($productInput, FILTER_VALIDATE_INT);

if ($productId === false || $productId < 1) {
    exit("Invalid Product");
}

// Validate quantity.
$quantityInput = $_POST["qty"] ?? "";

if (!is_string($quantityInput) || $quantityInput === "") {
    exit("Please Enter A Quantity");
}

if (strlen($quantityInput) > 10) {
    exit("Quantity Should Be Less Than 10 Digits");
}

if (!preg_match('/^[1-9][0-9]*$/', $quantityInput)) {
    exit("Please Enter A Valid Positive Whole Number");
}

$quantity = filter_var($quantityInput, FILTER_VALIDATE_INT);

if ($quantity === false || $quantity < 1 || $quantity > 2147483647) {
    exit("Invalid Quantity");
}

// Validate unit price. Maximum 10 characters, up to 2 decimal places.
$priceInput = $_POST["up"] ?? "";

if (!is_string($priceInput) || $priceInput === "") {
    exit("Please Enter Unit Price");
}

if (
    strlen($priceInput) > 10 ||
    !preg_match('/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/', $priceInput)
) {
    exit("Please Enter A Valid Unit Price");
}

$unitPrice = (float) $priceInput;

if (!is_finite($unitPrice) || $unitPrice <= 0) {
    exit("Unit Price Must Be Greater Than Zero");
}

require_once __DIR__ . "/connection.php";

$connection = null;
$transactionStarted = false;

try {
    Database::setupConnection();
    $connection = Database::$connection;

    if (!$connection instanceof mysqli) {
        throw new RuntimeException("Database connection unavailable.");
    }

    $connection->begin_transaction();
    $transactionStarted = true;

    // Confirm that the product exists.
    $productStmt = $connection->prepare(
        "SELECT `id` FROM `product` WHERE `id` = ? LIMIT 1"
    );

    if (!$productStmt) {
        throw new RuntimeException("Product query preparation failed.");
    }

    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $productStmt->store_result();

    $productExists = $productStmt->num_rows === 1;
    $productStmt->close();

    if (!$productExists) {
        $connection->rollback();
        $transactionStarted = false;
        exit("Invalid Product");
    }

    // Lock matching stock records before changing quantities.
    $stockStmt = $connection->prepare(
        "SELECT `stock_id`, `qty`, `status`
         FROM `stock`
         WHERE `product_id` = ? AND `price` = ?
         FOR UPDATE"
    );

    if (!$stockStmt) {
        throw new RuntimeException("Stock query preparation failed.");
    }

    $stockStmt->bind_param("id", $productId, $unitPrice);
    $stockStmt->execute();
    $stockStmt->bind_result($stockId, $currentQty, $existingStatus);

    $matchingRows = [];

    while ($stockStmt->fetch()) {
        $matchingRows[] = [
            "stock_id" => (int) $stockId,
            "qty" => (int) $currentQty,
            "status" => (string) $existingStatus
        ];
    }

    $stockStmt->close();

    if (count($matchingRows) > 1) {
        throw new RuntimeException("Multiple matching stock records found.");
    }

    if (count($matchingRows) === 1) {
        $existing = $matchingRows[0];

        if ($existing["status"] !== "available") {
            throw new RuntimeException("The matching stock record is not available.");
        }

        if (
            $existing["qty"] < 0 ||
            $existing["qty"] > 2147483647 - $quantity
        ) {
            throw new RuntimeException("Stock quantity limit exceeded.");
        }

        $updateStmt = $connection->prepare(
            "UPDATE `stock`
             SET `qty` = `qty` + ?
             WHERE `stock_id` = ?"
        );

        if (!$updateStmt) {
            throw new RuntimeException("Stock update preparation failed.");
        }

        $updateStmt->bind_param(
            "ii",
            $quantity,
            $existing["stock_id"]
        );

        $updateStmt->execute();

        if ($updateStmt->affected_rows !== 1) {
            $updateStmt->close();
            throw new RuntimeException("Stock update failed.");
        }

        $updateStmt->close();

        $response = "Stock Updated Successfull";

    } else {
        $insertStmt = $connection->prepare(
            "INSERT INTO `stock` (`price`, `qty`, `status`, `product_id`)
             VALUES (?, ?, 'available', ?)"
        );

        if (!$insertStmt) {
            throw new RuntimeException("Stock insert preparation failed.");
        }

        $insertStmt->bind_param(
            "dii",
            $unitPrice,
            $quantity,
            $productId
        );

        $insertStmt->execute();

        if ($insertStmt->affected_rows !== 1) {
            $insertStmt->close();
            throw new RuntimeException("New stock insertion failed.");
        }

        $insertStmt->close();

        $response = "New Stock Added Successfull";
    }

    $connection->commit();
    $transactionStarted = false;

    echo $response;

} catch (Throwable $e) {
    if ($transactionStarted && $connection instanceof mysqli) {
        try {
            $connection->rollback();
        } catch (Throwable $rollbackError) {
            error_log("Zeyara stock rollback error: " . $rollbackError->getMessage());
        }
    }

    error_log("Zeyara stock update error: " . $e->getMessage());

    http_response_code(500);
    echo "Unable to update stock. Please try again.";
}