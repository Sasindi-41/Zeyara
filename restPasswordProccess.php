<?php

require_once __DIR__ . "/connection.php";

$vcode = $_POST["vcode"] ?? "";
$np = $_POST["np"] ?? "";
$np2 = $_POST["np2"] ?? "";

if (!is_string($vcode) || !preg_match('/^[a-f0-9]{64}$/D', $vcode)) {
    exit("Invalid or expired password reset link. Please request a new one.");
}

if (!is_string($np) || !is_string($np2) || $np === "") {
    exit("Please Enter Your Password");
}

if ($np !== $np2) {
    exit("Password Doesn't Match");
}

if (strlen($np) < 5 || strlen($np) > 45) {
    exit("Your Password Must Contain 5 - 45 Characters");
}

$passwordHash = password_hash($np, PASSWORD_DEFAULT);

if ($passwordHash === false) {
    http_response_code(500);
    exit("Password update is temporarily unavailable. Please try again.");
}

$tokenHash = hash("sha256", $vcode);

try {
    Database::setupConnection();
    $connection = Database::$connection;

    $connection->begin_transaction();

    $stmt = $connection->prepare(
        "SELECT `id`
         FROM `user`
         WHERE `v_code` = ?
           AND `v_code_expires` IS NOT NULL
           AND `v_code_expires` > UTC_TIMESTAMP()
         LIMIT 1
         FOR UPDATE"
    );

    if (!$stmt) {
        throw new RuntimeException("Reset token query could not be prepared.");
    }

    $stmt->bind_param("s", $tokenHash);

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException("Reset token lookup failed.");
    }

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $connection->rollback();
        exit("Invalid or expired password reset link. Please request a new one.");
    }

    $userId = (int) $user["id"];

    $update = $connection->prepare(
        "UPDATE `user`
         SET `password` = ?,
             `v_code` = NULL,
             `v_code_expires` = NULL
         WHERE `id` = ?
           AND `v_code` = ?"
    );

    if (!$update) {
        throw new RuntimeException("Password update query could not be prepared.");
    }

    $update->bind_param("sis", $passwordHash, $userId, $tokenHash);

    if (!$update->execute() || $update->affected_rows !== 1) {
        $update->close();
        throw new RuntimeException("Password update failed.");
    }

    $update->close();

    $connection->commit();

    echo "Success";

} catch (Throwable $e) {
    if (isset($connection) && $connection instanceof mysqli) {
        try {
            $connection->rollback();
        } catch (Throwable $rollbackError) {
            error_log("Zeyara password reset rollback failed.");
        }
    }

    error_log("Zeyara password reset error: " . $e->getMessage());

    http_response_code(500);
    echo "Password update is temporarily unavailable. Please try again.";
}
?>