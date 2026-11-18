<?php
session_start();

require_once "connection.php";

$username = trim($_POST["u"] ?? "");
$password = $_POST["p"] ?? "";
$rememberme = $_POST["r"] ?? "";

if ($username === "") {
    exit("Enter Your Username");
}

if ($password === "") {
    exit("Enter Your Password");
}

try {
    Database::setupConnection();
    $connection = Database::$connection;

    $stmt = $connection->prepare(
        "SELECT * FROM `user` WHERE `username` = ? LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException("Login query could not be prepared.");
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        exit("Invalid Username OR Password");
    }

    $storedPassword = $user["password"];
    $passwordInfo = password_get_info($storedPassword);
    $isHashed = $passwordInfo["algo"] !== null;

    if ($isHashed) {
        $passwordValid = password_verify($password, $storedPassword);
    } else {
        $passwordValid = hash_equals($storedPassword, $password);
    }

    if (!$passwordValid) {
        exit("Invalid Username OR Password");
    }

    if ((int) $user["status"] !== 1) {
        exit("Inactive User");
    }

    if (
        !$isHashed ||
        password_needs_rehash($storedPassword, PASSWORD_DEFAULT)
    ) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);

        if ($newHash === false) {
            throw new RuntimeException("Password hashing failed.");
        }

        $update = $connection->prepare(
            "UPDATE `user` SET `password` = ? WHERE `username` = ?"
        );

        if (!$update) {
            throw new RuntimeException("Password update could not be prepared.");
        }

        $update->bind_param("ss", $newHash, $username);

        if (!$update->execute()) {
            $update->close();
            throw new RuntimeException("Password update failed.");
        }

        $update->close();
        $user["password"] = $newHash;
    }

    if (!session_regenerate_id(true)) {
        throw new RuntimeException("Session regeneration failed.");
    }

    unset($user["password"]);
    $_SESSION["u"] = $user;

    $cookieSecure = !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off";

    if ($rememberme === "true") {
        setcookie("username", $username, [
            "expires" => time() + (60 * 60 * 24 * 30),
            "path" => "/",
            "secure" => $cookieSecure,
            "httponly" => true,
            "samesite" => "Lax"
        ]);
    } else {
        setcookie("username", "", [
            "expires" => time() - 3600,
            "path" => "/",
            "secure" => $cookieSecure,
            "httponly" => true,
            "samesite" => "Lax"
        ]);
    }

    setcookie("password", "", [
        "expires" => time() - 3600,
        "path" => "/",
        "secure" => $cookieSecure,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    echo "Success";

} catch (Throwable $e) {
    error_log("Zeyara login error: " . $e->getMessage());
    http_response_code(500);
    echo "Login is temporarily unavailable. Please try again.";
}
?>
