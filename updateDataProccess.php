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

$fields = ["f", "l", "e", "m", "n", "l1", "l2"];

foreach ($fields as $field) {
    if (!isset($_POST[$field]) || !is_string($_POST[$field])) {
        exit("Invalid profile information.");
    }
}

$fname = trim($_POST["f"]);
$lname = trim($_POST["l"]);
$email = trim($_POST["e"]);
$mobile = trim($_POST["m"]);
$no = trim($_POST["n"]);
$line1 = trim($_POST["l1"]);
$line2 = trim($_POST["l2"]);

if ($fname === "") {
    exit("Enter Your First Name");
} elseif (strlen($fname) > 20) {
    exit("Your First Name should be less than 20 Characters");
} elseif ($lname === "") {
    exit("Enter Your Last Name");
} elseif (strlen($lname) > 20) {
    exit("Your Last Name should be less than 20 Characters");
} elseif ($email === "" || strlen($email) > 100) {
    exit("Your Email Address should be less than 100 Characters");
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Your Email Address is Invalid");
} elseif (!preg_match('/^07[01245678][0-9]{7}$/D', $mobile)) {
    exit("Your Mobile Number is Invalid");
} elseif ($no === "") {
    exit("Enter Your Address Number");
} elseif (strlen($no) > 10) {
    exit("Your Address Number Should Be Less Than 10 Characters");
} elseif ($line1 === "") {
    exit("Enter Your Address Line 1");
} elseif (strlen($line1) > 50) {
    exit("Your Address Line 1 Should Be Less Than 50 Characters");
} elseif ($line2 === "") {
    exit("Enter Your Address Line 2");
} elseif (strlen($line2) > 50) {
    exit("Your Address Line 2 Should Be Less Than 50 Characters");
}

try {
    Database::setupConnection();
    $connection = Database::$connection;

    $userId = (int) $_SESSION["u"]["id"];

    $check = $connection->prepare(
        "SELECT `id`
         FROM `user`
         WHERE (`email` = ? OR `mobile` = ?)
           AND `id` <> ?
         LIMIT 1"
    );

    if (!$check) {
        throw new RuntimeException("Profile validation query could not be prepared.");
    }

    $check->bind_param("ssi", $email, $mobile, $userId);
    $check->execute();

    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        exit("Your Email OR Mobile Number Already Exists");
    }

    $stmt = $connection->prepare(
        "UPDATE `user`
         SET `fname` = ?,
             `lname` = ?,
             `email` = ?,
             `mobile` = ?,
             `no` = ?,
             `line_1` = ?,
             `line_2` = ?
         WHERE `id` = ?"
    );

    if (!$stmt) {
        throw new RuntimeException("Profile update query could not be prepared.");
    }

    $stmt->bind_param(
        "sssssssi",
        $fname,
        $lname,
        $email,
        $mobile,
        $no,
        $line1,
        $line2,
        $userId
    );

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException("Profile update failed.");
    }

    $stmt->close();

    $refresh = $connection->prepare(
        "SELECT `id`, `fname`, `lname`, `email`, `mobile`,
                `username`, `no`, `line_1`, `line_2`, `img_path`
         FROM `user`
         WHERE `id` = ?
         LIMIT 1"
    );

    if (!$refresh) {
        throw new RuntimeException("Updated profile could not be loaded.");
    }

    $refresh->bind_param("i", $userId);
    $refresh->execute();

    $updatedUser = $refresh->get_result()->fetch_assoc();
    $refresh->close();

    if (!$updatedUser) {
        throw new RuntimeException("Updated profile could not be found.");
    }

    $_SESSION["u"] = $updatedUser;

    echo "Update Successfully Completed";

} catch (Throwable $e) {
    error_log("Zeyara profile update error: " . $e->getMessage());
    http_response_code(500);
    echo "Profile update is temporarily unavailable. Please try again.";
}
?>