<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

function statusResponse($success, $message, $status = null)
{
    $response = [
        "success" => $success,
        "message" => $message
    ];

    if ($status !== null) {
        $response["payment_status"] = $status;
    }

    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    statusResponse(false, "Invalid request method.");
}

if (
    !isset($_SESSION["u"]["id"]) ||
    !is_numeric($_SESSION["u"]["id"])
) {
    http_response_code(401);
    statusResponse(false, "Please sign in.");
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
    statusResponse(false, "Invalid session token.");
}

$orderId = $_POST["order_id"] ?? "";

if (
    !is_string($orderId) ||
    !preg_match('/^[a-f0-9]{32}$/', $orderId)
) {
    http_response_code(400);
    statusResponse(false, "Invalid order reference.");
}

try {
    require_once "connection.php";
    Database::setupConnection();

    $stmt = Database::$connection->prepare(
        "SELECT payment_status
         FROM order_history
         WHERE order_id = ? AND user_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException("Unable to prepare status query.");
    }

    $stmt->bind_param("si", $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        statusResponse(false, "Order not found.");
    }

    statusResponse(true, "Order status retrieved.", $order["payment_status"]);

} catch (Throwable $e) {
    error_log("Payment status lookup failed: " . $e->getMessage());
    http_response_code(500);
    statusResponse(false, "Unable to check payment status.");
}
