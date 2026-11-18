<?php
require_once __DIR__ . "/connection.php";
session_start();

function cartUpdateResponse($message, $status = 200) {
    http_response_code($status);
    header("Content-Type: text/plain; charset=UTF-8");
    echo $message;
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    cartUpdateResponse("Invalid request method.", 405);
}

if (!isset($_SESSION["u"]["id"])) {
    cartUpdateResponse("Signin First", 401);
}

$token = $_POST["csrf_token"] ?? null;
$sessionToken = $_SESSION["cart_csrf_token"] ?? "";

if (
    !is_string($token) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $token)
) {
    cartUpdateResponse("Invalid request. Please refresh the page and try again.", 403);
}

$cartId = filter_var($_POST["c"] ?? null, FILTER_VALIDATE_INT);
$newQty = filter_var($_POST["q"] ?? null, FILTER_VALIDATE_INT);
$userId = (int) $_SESSION["u"]["id"];

if (!$cartId || $cartId < 1 || !$newQty || $newQty < 1) {
    cartUpdateResponse("Please enter a valid quantity.", 400);
}

try {
    Database::setupConnection();
    $db = Database::$connection;
    $db->begin_transaction();

    $stmt = $db->prepare(
        "SELECT cart.cart_id, stock.qty, stock.status
         FROM cart
         INNER JOIN stock ON cart.stock_stock_id = stock.stock_id
         WHERE cart.cart_id = ? AND cart.user_id = ?
         FOR UPDATE"
    );

    if (!$stmt) {
        throw new RuntimeException("Database operation failed.");
    }

    $stmt->bind_param("ii", $cartId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if (!$item) {
        $db->rollback();
        cartUpdateResponse("Cart Item Not Found", 404);
    }

    if (strtolower(trim($item["status"])) !== "available") {
        $db->rollback();
        cartUpdateResponse("This product is currently unavailable.", 409);
    }

    if ($newQty > (int) $item["qty"]) {
        $db->rollback();
        cartUpdateResponse("Your Product Quantity Exeeded!", 409);
    }

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

    $db->commit();
    cartUpdateResponse("Success");

} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) {
        $db->rollback();
    }

    error_log("Cart quantity update failed: " . $e->getMessage());
    cartUpdateResponse("Unable to update your cart. Please try again.", 500);
}
