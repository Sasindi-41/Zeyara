<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

if (
    !isset($_SESSION["u"]["id"]) ||
    !is_numeric($_SESSION["u"]["id"])
) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Please sign in."]);
    exit;
}

if (
    !isset($_SESSION["payment_csrf"]) ||
    !is_string($_SESSION["payment_csrf"])
) {
    $_SESSION["payment_csrf"] = bin2hex(random_bytes(32));
}

echo json_encode([
    "success" => true,
    "csrf_token" => $_SESSION["payment_csrf"]
]);
