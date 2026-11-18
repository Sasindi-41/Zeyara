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

// Validate the session CSRF token.
$sessionToken = $_SESSION["admin_csrf_token"] ?? "";
$requestToken = $_POST["csrf_token"] ?? "";

if (
    !is_string($sessionToken) ||
    !is_string($requestToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $requestToken)
) {
    http_response_code(403);
    exit("Invalid security token. Reload the admin page.");
}

// Validate the requested user ID.
$uid = $_POST["u"] ?? "";

if (!is_string($uid) || !preg_match('/^[1-9][0-9]*$/', $uid)) {
    exit("Enter User ID");
}

$targetId = filter_var($uid, FILTER_VALIDATE_INT);

if ($targetId === false || $targetId < 1) {
    exit("Invalid User Id");
}

// Prevent an administrator from accidentally deactivating themselves.
if ($targetId === (int) $admin["id"]) {
    exit("You cannot change your own status");
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

    // Lock the target row to prevent concurrent status changes.
    $select = $connection->prepare(
        "SELECT `status` FROM `user` WHERE `id` = ? FOR UPDATE"
    );

    if (!$select) {
        throw new RuntimeException("User status query preparation failed.");
    }

    $select->bind_param("i", $targetId);
    $select->execute();
    $select->bind_result($currentStatus);

    $userFound = $select->fetch();
    $select->close();

    if (!$userFound) {
        $connection->commit();
        $transactionStarted = false;
        exit("Invalid User Id");
    }

    if ((string) $currentStatus === "0") {
        $newStatus = "1";
        $response = "activate";
    } elseif ((string) $currentStatus === "1") {
        $newStatus = "0";
        $response = "deactivate";
    } else {
        throw new RuntimeException("Unexpected user status value.");
    }

    $update = $connection->prepare(
        "UPDATE `user` SET `status` = ? WHERE `id` = ?"
    );

    if (!$update) {
        throw new RuntimeException("User status update preparation failed.");
    }

    $update->bind_param("si", $newStatus, $targetId);
    $update->execute();

    if ($update->affected_rows !== 1) {
        $update->close();
        throw new RuntimeException("User status update did not affect one row.");
    }

    $update->close();

    $connection->commit();
    $transactionStarted = false;

    echo $response;

} catch (Throwable $e) {
    if ($transactionStarted && $connection instanceof mysqli) {
        try {
            $connection->rollback();
        } catch (Throwable $rollbackError) {
            error_log("Zeyara status rollback error: " . $rollbackError->getMessage());
        }
    }

    error_log("Zeyara status update error: " . $e->getMessage());

    http_response_code(500);
    echo "Unable to update user status. Please try again.";
}