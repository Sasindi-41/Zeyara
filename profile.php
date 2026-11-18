<?php

session_start();

if (!isset($_SESSION["u"]["id"])) {
    header("Location: signin.php");
    exit;
}

require_once __DIR__ . "/connection.php";

function profileEscape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

try {
    Database::setupConnection();
    $connection = Database::$connection;

    $stmt = $connection->prepare(
        "SELECT `id`, `fname`, `lname`, `email`, `mobile`,
                `username`, `no`, `line_1`, `line_2`, `img_path`
         FROM `user`
         WHERE `id` = ?
         LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException("Profile query could not be prepared.");
    }

    $userId = (int) $_SESSION["u"]["id"];
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $d = $result->fetch_assoc();
    $stmt->close();

    if (!$d) {
        unset($_SESSION["u"]);
        header("Location: signin.php");
        exit;
    }

    $_SESSION["u"] = $d;

    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    $csrfToken = $_SESSION["csrf_token"];

} catch (Throwable $e) {
    error_log("Zeyara profile error: " . $e->getMessage());
    http_response_code(500);
    exit("Your profile is temporarily unavailable. Please try again.");
}

?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="style.css">
    <title>Zeyara Perfumes</title>
    <link rel="icon" href="resources/icon/perfume.png">
</head>

<body class="uprobody">

    <?php include "navBar.php"; ?>

    <div class="container mt-5">

        <input
            type="hidden"
            id="csrfToken"
            value="<?php echo profileEscape($csrfToken); ?>"
        >

        <div class="row justify-content-center">

            <div class="col-md-6 mb-4">

                <div class="card border-3 border-light bg-body-secondary">
                    <div class="card-body">

                        <h2 class="card-title text-center mb-4">
                            Profile Details
                        </h2>

                        <div class="row mb-3">
                            <div class="col">
                                <label for="fname" class="form-label">
                                    First Name
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?php echo profileEscape($d["fname"]); ?>"
                                    id="fname"
                                    maxlength="20"
                                >
                            </div>

                            <div class="col">
                                <label for="lname" class="form-label">
                                    Last Name
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="<?php echo profileEscape($d["lname"]); ?>"
                                    id="lname"
                                    maxlength="20"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                class="form-control"
                                value="<?php echo profileEscape($d["email"]); ?>"
                                id="email"
                                maxlength="100"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile</label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?php echo profileEscape($d["mobile"]); ?>"
                                id="mobile"
                                maxlength="10"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">
                                Username
                            </label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?php echo profileEscape($d["username"]); ?>"
                                id="username"
                                disabled
                            >
                        </div>

                        <h5 class="mt-3">Shipping Address</h5>

                        <div class="mb-3">
                            <label for="no" class="form-label">No.</label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?php echo profileEscape($d["no"]); ?>"
                                id="no"
                                maxlength="10"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="line1" class="form-label">Line 1</label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?php echo profileEscape($d["line_1"]); ?>"
                                id="line1"
                                maxlength="50"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="line2" class="form-label">Line 2</label>
                            <input
                                type="text"
                                class="form-control"
                                value="<?php echo profileEscape($d["line_2"]); ?>"
                                id="line2"
                                maxlength="50"
                            >
                        </div>

                        <div class="mb-3">
                            <button
                                type="button"
                                class="btn btn-warning col-12"
                                onclick="updateData();"
                            >
                                Update Profile
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Separate password change form -->

                <div class="card border-3 border-light bg-body-secondary mt-4">
                    <div class="card-body">

                        <h4 class="card-title text-center mb-4">
                            Change Password
                        </h4>

                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">
                                Current Password
                            </label>
                            <input
                                type="password"
                                class="form-control"
                                id="currentPassword"
                                autocomplete="current-password"
                                maxlength="45"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="newPassword" class="form-label">
                                New Password
                            </label>
                            <input
                                type="password"
                                class="form-control"
                                id="newPassword"
                                autocomplete="new-password"
                                maxlength="45"
                            >
                        </div>

                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">
                                Confirm New Password
                            </label>
                            <input
                                type="password"
                                class="form-control"
                                id="confirmPassword"
                                autocomplete="new-password"
                                maxlength="45"
                            >
                        </div>

                        <button
                            type="button"
                            class="btn btn-warning col-12"
                            onclick="changePassword();"
                        >
                            Change Password
                        </button>

                    </div>
                </div>

            </div>

            <div class="col-md-3 mb-4">

                <div class="card border-3 border-light bg-body-secondary">
                    <div class="card-body">

                        <div class="d-flex justify-content-center">
                            <img
                                src="<?php echo profileEscape(
                                    !empty($d["img_path"])
                                        ? $d["img_path"]
                                        : "resources/5856.png"
                                ); ?>"
                                height="150px"
                                id="i"
                                class="mt-5"
                                alt="Profile image"
                            >
                        </div>

                        <div class="text-center fw-bolder mt-4">
                            <label for="imgUploader">Profile Image</label>
                            <input
                                type="file"
                                class="form-control mt-2"
                                id="imgUploader"
                                accept="image/*"
                            >
                        </div>

                        <div class="mb-3 mt-3">
                            <button
                                type="button"
                                class="btn btn-warning col-12"
                                onclick="uploadImage();"
                            >
                                Upload
                            </button>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>

    <script src="script.js"></script>
    <script src="bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>