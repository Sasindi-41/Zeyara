<?php

session_start();

require_once __DIR__ . "/connection.php";

header("Cache-Control: no-store");

function uploadResponse($message, $status = 400)
{
    http_response_code($status);
    echo $message;
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    uploadResponse("Invalid request.", 405);
}

if (
    empty($_SESSION["u"]["id"]) ||
    !is_numeric($_SESSION["u"]["id"])
) {
    uploadResponse("Please sign in again.", 401);
}

$csrf = $_POST["csrf_token"] ?? "";

if (
    !is_string($csrf) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $csrf)
) {
    uploadResponse("Security validation failed. Refresh the page.", 403);
}

if (!isset($_FILES["i"])) {
    uploadResponse("Empty");
}

$file = $_FILES["i"];

if ($file["error"] === UPLOAD_ERR_NO_FILE) {
    uploadResponse("Empty");
}

if ($file["error"] !== UPLOAD_ERR_OK) {
    uploadResponse("Image upload failed.");
}

if (
    !is_uploaded_file($file["tmp_name"]) ||
    $file["size"] <= 0 ||
    $file["size"] > 5 * 1024 * 1024
) {
    uploadResponse("Image must be smaller than 5 MB.");
}

if (!extension_loaded("gd") || !class_exists("finfo")) {
    uploadResponse("Image processing is unavailable.", 500);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file["tmp_name"]);

$allowedMimes = [
    "image/jpeg",
    "image/png",
    "image/webp"
];

if (!in_array($mime, $allowedMimes, true)) {
    uploadResponse("Only JPG, PNG and WebP images are allowed.");
}

$imageInfo = @getimagesize($file["tmp_name"]);

if (
    $imageInfo === false ||
    $imageInfo[0] < 1 ||
    $imageInfo[1] < 1 ||
    $imageInfo[0] > 5000 ||
    $imageInfo[1] > 5000
) {
    uploadResponse("Invalid image dimensions.");
}

$imageData = @file_get_contents($file["tmp_name"]);
$image = $imageData !== false ? @imagecreatefromstring($imageData) : false;

if ($image === false) {
    uploadResponse("The uploaded image could not be processed.");
}

$directory = __DIR__ . "/resources/profileImg";

if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
    imagedestroy($image);
    uploadResponse("Image storage is unavailable.", 500);
}

if (!is_writable($directory)) {
    imagedestroy($image);
    uploadResponse("Image storage is unavailable.", 500);
}

$filename = bin2hex(random_bytes(20)) . ".png";
$absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
$relativePath = "resources/profileImg/" . $filename;

if (!imagepng($image, $absolutePath, 6)) {
    imagedestroy($image);
    uploadResponse("Could not save the image.", 500);
}

imagedestroy($image);

try {
    Database::setupConnection();

    $db = Database::$connection;
    $userId = (int) $_SESSION["u"]["id"];

    $select = $db->prepare(
        "SELECT img_path FROM `user` WHERE id = ? LIMIT 1"
    );

    if (!$select) {
        throw new RuntimeException("Profile lookup failed.");
    }

    $select->bind_param("i", $userId);
    $select->execute();
    $result = $select->get_result();
    $user = $result->fetch_assoc();
    $select->close();

    if (!$user) {
        throw new RuntimeException("Profile not found.");
    }

    $update = $db->prepare(
        "UPDATE `user` SET img_path = ? WHERE id = ?"
    );

    if (!$update) {
        throw new RuntimeException("Profile update failed.");
    }

    $update->bind_param("si", $relativePath, $userId);

    if (!$update->execute()) {
        throw new RuntimeException("Profile update failed.");
    }

    $update->close();

    $oldPath = $user["img_path"] ?? "";

    if (
        is_string($oldPath) &&
        preg_match(
            '#\Aresources/profileImg/[a-zA-Z0-9_-]+\.(?:png|jpg|jpeg|webp)\z#i',
            $oldPath
        )
    ) {
        $oldAbsolutePath = __DIR__ . "/" . $oldPath;

        if (
            is_file($oldAbsolutePath) &&
            realpath(dirname($oldAbsolutePath)) === realpath($directory)
        ) {
            @unlink($oldAbsolutePath);
        }
    }

    echo $relativePath;

} catch (Throwable $e) {
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }

    error_log("Zeyara profile image upload failed: " . $e->getMessage());
    uploadResponse("Unable to update your profile image.", 500);
}