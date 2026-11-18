<?php

require_once "connection.php";

$fname = trim($_POST["fn"] ?? "");
$lname = trim($_POST["ln"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($fname === "") {
    exit("Enter Your First Name");
} elseif (strlen($fname) > 20) {
    exit("Your First Name Should Be Less Than 20 Characters");
} elseif ($lname === "") {
    exit("Enter Your Last Name");
} elseif (strlen($lname) > 20) {
    exit("Your Last Name Should Be Less Than 20 Characters");
} elseif ($email === "" || strlen($email) > 100) {
    exit("Your Email Address Should Be Less Than 100 Characters");
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Your Email Address Is Invalid");
} elseif ($mobile === "") {
    exit("Please Enter Your Mobile Number");
} elseif (
    strlen($mobile) !== 10 ||
    !preg_match("/^07[01245678][0-9]{7}$/D", $mobile)
) {
    exit("Your Mobile Number Is Invalid");
} elseif ($username === "") {
    exit("Please Enter Your Username");
} elseif (strlen($username) > 20) {
    exit("Your Username Should Be Less Than 20 Characters");
} elseif ($password === "") {
    exit("Please Enter Your Password");
} elseif (strlen($password) < 5 || strlen($password) > 45) {
    exit("Your Password Must Contain 5 - 45 Characters");
}

try {
    Database::setupConnection();
    $connection = Database::$connection;

    $check = $connection->prepare(
        "SELECT `id` FROM `user`
         WHERE `email` = ? OR `username` = ? OR `mobile` = ?
         LIMIT 1"
    );

    if (!$check) {
        throw new RuntimeException("Signup validation query could not be prepared.");
    }

    $check->bind_param("sss", $email, $username, $mobile);
    $check->execute();

    $result = $check->get_result();
    $exists = $result->num_rows > 0;
    $check->close();

    if ($exists) {
        exit("Your Email OR Username OR Mobile Already Exists");
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($passwordHash === false) {
        throw new RuntimeException("Password hashing failed.");
    }

    $userType = 2;

    $insert = $connection->prepare(
        "INSERT INTO `user`
         (`fname`, `lname`, `email`, `mobile`, `username`, `password`, `user_type_id`)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$insert) {
        throw new RuntimeException("Signup insert query could not be prepared.");
    }

    $insert->bind_param(
        "ssssssi",
        $fname,
        $lname,
        $email,
        $mobile,
        $username,
        $passwordHash,
        $userType
    );

    if (!$insert->execute()) {
        $insert->close();
        throw new RuntimeException("Account registration failed.");
    }

    $insert->close();

    echo "Success";

} catch (Throwable $e) {
    error_log("Zeyara signup error: " . $e->getMessage());
    http_response_code(500);
    echo "Registration is temporarily unavailable. Please try again.";
}
?>