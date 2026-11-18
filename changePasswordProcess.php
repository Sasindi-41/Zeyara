<?php

session_start();

require_once __DIR__ . "/connection.php";

if (!isset($_SESSION["u"]["id"])) {
    http_response_code(401);
    exit("Please sign in again.");
}

$csrfToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($csrfToken) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrfToken)
) {
    http_response_code(403);
    exit("Your request could not be verified. Refresh the page and try again.");
}

$currentPassword = $_POST["current_password"] ?? "";
$newPassword = $_POST["new_password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

if (
    !is_string($currentPassword) ||
    !is_string($newPassword) ||
    !is_string($confirmPassword) ||
    $currentPassword === "" ||
    $newPassword === "" ||
    $confirmPassword === ""
) {
    exit("Please complete all password fields.");
}

if ($newPassword !== $confirmPassword) {
    exit("New passwords do not match.");
}

if (strlen($newPassword) < 5 || strlen($newPassword) > 45) {
    exit("Your Password Must Contain 5 - 45 Characters");
}

if (hash_equals($currentPassword, $newPassword)) {
    exit("Your new password must be different from your current password.");
}

try {
    Database::setupConnection();
    $connection = Database::$connection;

    $userId = (int) $_SESSION["u"]["id"];

    $stmt = $connection->prepare(
        "SELECT `password` FROM `user` WHERE `id` = ? LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException("Password lookup could not be prepared.");
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        exit("Your account could not be found. Please sign in again.");
    }

    $storedPassword = $user["password"];
    $passwordInfo = password_get_info($storedPassword);

    $isHashed = isset($passwordInfo["algoName"])
        && $passwordInfo["algoName"] !== "unknown";

    $currentPasswordValid = $isHashed
        ? password_verify($currentPassword, $storedPassword)
        : hash_equals($storedPassword, $currentPassword);

    if (!$currentPasswordValid) {
        exit("Your current password is incorrect.");
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

    if ($newHash === false) {
        throw new RuntimeException("New password hashing failed.");
    }

    $update = $connection->prepare(
        "UPDATE `user` SET `password` = ? WHERE `id` = ?"
    );

    if (!$update) {
        throw new RuntimeException("Password update could not be prepared.");
    }

    $update->bind_param("si", $newHash, $userId);

    if (!$update->execute()) {
        $update->close();
        throw new RuntimeException("Password update failed.");
    }

    $update->close();

    echo "Password Changed Successfully";

} catch (Throwable $e) {
    error_log("Zeyara password change error: " . $e->getMessage());
    http_response_code(500);
    echo "Password change is temporarily unavailable. Please try again.";
}
?>