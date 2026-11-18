<?php
session_start();

require_once "connection.php";

$username = trim($_POST["u"] ?? "");
$password = $_POST["p"] ?? "";

if ($username === "") {
    exit("Enter Your Admin Username");
}

if ($password === "") {
    exit("Enter Your Admin Password");
}

try {
    Database::setupConnection();
    $connection = Database::$connection;

    $stmt = $connection->prepare(
        "SELECT * FROM `user` WHERE `username` = ? LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException("Admin login query could not be prepared.");
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        exit("Invalid Username OR Password");
    }

    if ((int) $user["user_type_id"] !== 1) {
        exit("You Don't Have An Admin Account");
    }

    if ((int) $user["status"] !== 1) {
        exit("Inactive User");
    }

    $storedPassword = $user["password"];
    $passwordInfo = password_get_info($storedPassword);

    $isHashed = isset($passwordInfo["algoName"])
        && $passwordInfo["algoName"] !== "unknown";

    if ($isHashed) {
        $passwordValid = password_verify($password, $storedPassword);
    } else {
        $passwordValid = hash_equals($storedPassword, $password);
    }

    if (!$passwordValid) {
        exit("Invalid Username OR Password");
    }

    if (
        !$isHashed ||
        password_needs_rehash($storedPassword, PASSWORD_DEFAULT)
    ) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);

        if ($newHash === false) {
            throw new RuntimeException("Admin password hashing failed.");
        }

        $update = $connection->prepare(
            "UPDATE `user` SET `password` = ? WHERE `username` = ?"
        );

        if (!$update) {
            throw new RuntimeException("Admin password update could not be prepared.");
        }

        $update->bind_param("ss", $newHash, $username);

        if (!$update->execute()) {
            $update->close();
            throw new RuntimeException("Admin password update failed.");
        }

        $update->close();
    }

    if (!session_regenerate_id(true)) {
        throw new RuntimeException("Admin session regeneration failed.");
    }

    unset($user["password"]);
    $_SESSION["a"] = $user;

    echo "Success";

} catch (Throwable $e) {
    error_log("Zeyara admin login error: " . $e->getMessage());
    http_response_code(500);
    echo "Admin login is temporarily unavailable. Please try again.";
}
?>