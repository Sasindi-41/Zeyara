<?php
require_once __DIR__ . "/connection.php";
session_start();

function cartResponse($message, $status = 200) {
    http_response_code($status);
    header("Content-Type: text/plain; charset=UTF-8");
    echo $message;
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    cartResponse("Invalid request method.", 405);
}

if (!isset($_SESSION["u"]["id"])) {
    cartResponse("Signin First", 401);
}

$token = $_POST["csrf_token"] ?? null;
$sessionToken = $_SESSION["cart_csrf_token"] ?? "";

if (
    !is_string($token) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $token)
) {
    cartResponse("Invalid request. Please refresh the page and try again.", 403);
}

$stockId = filter_var($_POST["s"] ?? null, FILTER_VALIDATE_INT);
$quantity = filter_var($_POST["q"] ?? null, FILTER_VALIDATE_INT);
$userId = (int) $_SESSION["u"]["id"];

if (!$stockId || $stockId < 1 || !$quantity || $quantity < 1) {
    cartResponse("Please enter a valid product and quantity.", 400);
}

try {
    Database::setupConnection();
    $db = Database::$connection;
    $db->begin_transaction();

    $stockStmt = $db->prepare(
        "SELECT qty, status FROM stock WHERE stock_id = ? FOR UPDATE"
    );

    if (!$stockStmt) {
        throw new RuntimeException("Database operation failed.");
    }

    $stockStmt->bind_param("i", $stockId);
    $stockStmt->execute();
    $stockResult = $stockStmt->get_result();
    $stock = $stockResult->fetch_assoc();
    $stockStmt->close();

    if (!$stock) {
        $db->rollback();
        cartResponse("Your Stock is not Found!", 404);
    }

    if (strtolower(trim($stock["status"])) !== "available") {
        $db->rollback();
        cartResponse("This product is currently unavailable.", 409);
    }

    $cartStmt = $db->prepare(
        "SELECT cart_id, cart_qty
         FROM cart
         WHERE user_id = ? AND stock_stock_id = ?
         ORDER BY cart_id ASC
         FOR UPDATE"
    );

    if (!$cartStmt) {
        throw new RuntimeException("Database operation failed.");
    }

    $cartStmt->bind_param("ii", $userId, $stockId);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();

    $cartRows = [];
    $existingQty = 0;

    while ($row = $cartResult->fetch_assoc()) {
        $cartRows[] = $row;
        $existingQty += (int) $row["cart_qty"];
    }

    $cartStmt->close();

    $newQty = $existingQty + $quantity;

    if ($newQty > (int) $stock["qty"]) {
        $db->rollback();
        cartResponse("Stock Quantity has been exceeded", 409);
    }

    if (count($cartRows) > 0) {
        $cartId = (int) $cartRows[0]["cart_id"];

        $updateStmt = $db->prepare(
            "UPDATE cart
             SET cart_qty = ?
             WHERE cart_id = ? AND user_id = ?"
        );

        if (!$updateStmt) {
            throw new RuntimeException("Database operation failed.");
        }

        $updateStmt->bind_param("iii", $newQty, $cartId, $userId);
        $updateStmt->execute();
        $updateStmt->close();

        // Merge any older duplicate rows into the first row.
        if (count($cartRows) > 1) {
            $deleteStmt = $db->prepare(
                "DELETE FROM cart
                 WHERE user_id = ? AND stock_stock_id = ? AND cart_id <> ?"
            );

            if (!$deleteStmt) {
                throw new RuntimeException("Database operation failed.");
            }

            $deleteStmt->bind_param("iii", $userId, $stockId, $cartId);
            $deleteStmt->execute();
            $deleteStmt->close();
        }

        $db->commit();
        cartResponse("Cart Item Updated Successfull");
    }

    $insertStmt = $db->prepare(
        "INSERT INTO cart (cart_qty, user_id, stock_stock_id)
         VALUES (?, ?, ?)"
    );

    if (!$insertStmt) {
        throw new RuntimeException("Database operation failed.");
    }

    $insertStmt->bind_param("iii", $quantity, $userId, $stockId);
    $insertStmt->execute();
    $insertStmt->close();

    $db->commit();
    cartResponse("Cart Item Added Successfull");

} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
    }

    error_log("Add to cart failed: " . $e->getMessage());
    cartResponse("Unable to update your cart. Please try again.", 500);
}
