<?php
require_once __DIR__ . "/connection.php";
session_start();

function cartRemoveResponse($message, $status = 200) {
    http_response_code($status);
    header("Content-Type: text/plain; charset=UTF-8");
    echo $message;
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    cartRemoveResponse("Invalid request method.", 405);
}

if (!isset($_SESSION["u"]["id"])) {
    cartRemoveResponse("Signin First", 401);
}

$token = $_POST["csrf_token"] ?? null;
$sessionToken = $_SESSION["cart_csrf_token"] ?? "";

if (
    !is_string($token) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $token)
) {
    cartRemoveResponse("Invalid request. Please refresh the page and try again.", 403);
}

$cartId = filter_var($_POST["c"] ?? null, FILTER_VALIDATE_INT);
$userId = (int) $_SESSION["u"]["id"];

if (!$cartId || $cartId < 1) {
    cartRemoveResponse("Cart Item Not Found", 400);
}

try {
    Database::setupConnection();
    $db = Database::$connection;

    $stmt = $db->prepare(
        "DELETE FROM cart WHERE cart_id = ? AND user_id = ?"
    );

    if (!$stmt) {
        throw new RuntimeException("Database operation failed.");
    }

    $stmt->bind_param("ii", $cartId, $userId);
    $stmt->execute();

    $removed = $stmt->affected_rows;
    $stmt->close();

    if ($removed > 0) {
        cartRemoveResponse("Item Removed Succeddfull");
    }

    cartRemoveResponse("Cart Item Not Found", 404);

} catch (Throwable $e) {
    error_log("Cart item removal failed: " . $e->getMessage());
    cartRemoveResponse("Unable to remove this cart item. Please try again.", 500);
}
