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

// This endpoint must enforce admin authorization independently of the page.
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

$sessionToken = $_SESSION["admin_csrf_token"] ?? "";
$requestToken = $_POST["csrf_token"] ?? "";
if (
    !is_string($sessionToken) ||
    !is_string($requestToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $requestToken)
) {
    http_response_code(403);
    exit("Invalid security token. Reload the admin stock page.");
}

$pname = $_POST["pname"] ?? "";
$brandInput = $_POST["brand"] ?? "";
$categoryInput = $_POST["cat"] ?? "";
$sfInput = $_POST["sf"] ?? "";
$sizeInput = $_POST["size"] ?? "";
$conInput = $_POST["con"] ?? "";
$description = $_POST["desc"] ?? "";

if (!is_string($pname) || !is_string($description)) {
    exit("Invalid product details.");
}

$pname = trim($pname);
$description = trim($description);

// Product names may contain spaces and common punctuation (e.g. Calvin Klein One).
if ($pname === "") {
    exit("Please Enter Product Name");
}
if (
    !preg_match("/^[\\p{L}\\p{N}][\\p{L}\\p{N}\\s&'’().,+\\-\/#]*$/u", $pname) ||
    preg_match("/\\p{C}/u", $pname)
) {
    exit("Please Enter A Valid Product Name");
}
$nameLength = preg_match_all("/./us", $pname);
if ($nameLength === false || $nameLength > 30) {
    exit("Product Name Should Be 30 Characters Or Less");
}

if ($description === "") {
    exit("Please Enter A Description");
}
$descriptionLength = preg_match_all("/./us", $description);
if ($descriptionLength === false || $descriptionLength > 200) {
    exit("Description Should Be 200 Characters Or Less");
}

function zeyaraPositiveId($value): ?int
{
    if (!is_string($value) || !preg_match('/^[1-9][0-9]{0,9}$/', $value)) {
        return null;
    }
    $validated = filter_var($value, FILTER_VALIDATE_INT);
    return ($validated !== false && $validated > 0) ? $validated : null;
}

$brandId = zeyaraPositiveId($brandInput);
$categoryId = zeyaraPositiveId($categoryInput);
$sfId = zeyaraPositiveId($sfInput);
$sizeId = zeyaraPositiveId($sizeInput);
$concentrationId = zeyaraPositiveId($conInput);

if ($brandId === null) exit("Please Select A Brand");
if ($categoryId === null) exit("Please Select A Category");
if ($sfId === null) exit("Please Select A Scent Family");
if ($sizeId === null) exit("Please Select A Size");
if ($concentrationId === null) exit("Please Select A Concentration");

$image = $_FILES["image"] ?? null;
if (!is_array($image) || !isset($image["error"]) || $image["error"] !== UPLOAD_ERR_OK) {
    exit("Please Select A Valid Product Image");
}
if (
    !isset($image["tmp_name"], $image["size"]) ||
    !is_uploaded_file($image["tmp_name"]) ||
    (int) $image["size"] < 1 ||
    (int) $image["size"] > 5 * 1024 * 1024
) {
    exit("Product Image Must Be 5 MB Or Smaller");
}

$allowedMimeExtensions = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

try {
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($image["tmp_name"]);
    if (!is_string($mimeType) || !isset($allowedMimeExtensions[$mimeType])) {
        exit("Only JPG, PNG Or WEBP Product Images Are Allowed");
    }

    $imageInfo = @getimagesize($image["tmp_name"]);
    if (
        $imageInfo === false ||
        !isset($imageInfo[0], $imageInfo[1], $imageInfo["mime"]) ||
        $imageInfo["mime"] !== $mimeType ||
        $imageInfo[0] < 1 || $imageInfo[1] < 1 ||
        $imageInfo[0] > 5000 || $imageInfo[1] > 5000
    ) {
        exit("The Uploaded File Is Not A Valid Product Image");
    }

    require_once __DIR__ . "/connection.php";
    Database::setupConnection();
    $connection = Database::$connection;

    if (!$connection instanceof mysqli) {
        throw new RuntimeException("Database connection unavailable.");
    }

    // Confirm all selected lookup values exist; never trust browser-supplied IDs.
    $lookupChecks = [
        ["brand", "brand_id", $brandId, "Brand"],
        ["category", "cat_id", $categoryId, "Category"],
        ["scent_family", "sf_id", $sfId, "Scent Family"],
        ["size", "size_id", $sizeId, "Size"],
        ["concentration", "id", $concentrationId, "Concentration"]
    ];

    foreach ($lookupChecks as [$table, $column, $id, $label]) {
        $lookup = $connection->prepare("SELECT 1 FROM `$table` WHERE `$column` = ? LIMIT 1");
        if (!$lookup) {
            throw new RuntimeException("Lookup query preparation failed.");
        }
        $lookup->bind_param("i", $id);
        $lookup->execute();
        $lookup->store_result();
        $exists = $lookup->num_rows === 1;
        $lookup->close();
        if (!$exists) {
            exit("Please Select A Valid " . $label);
        }
    }

    $duplicate = $connection->prepare("SELECT `id` FROM `product` WHERE `name` = ? LIMIT 1");
    if (!$duplicate) {
        throw new RuntimeException("Duplicate check preparation failed.");
    }
    $duplicate->bind_param("s", $pname);
    $duplicate->execute();
    $duplicate->store_result();
    $alreadyExists = $duplicate->num_rows > 0;
    $duplicate->close();

    if ($alreadyExists) {
        exit("Product Has Been Already Registered");
    }

    $uploadDirectory = __DIR__ . "/resources/ProductImg";
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException("Product image directory is unavailable.");
    }

    $extension = $allowedMimeExtensions[$mimeType];
    $fileName = bin2hex(random_bytes(24)) . "." . $extension;
    $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
    $relativePath = "resources/ProductImg/" . $fileName;

    if (!move_uploaded_file($image["tmp_name"], $absolutePath)) {
        throw new RuntimeException("Product image could not be saved.");
    }

    $insert = $connection->prepare(
        "INSERT INTO `product`
         (`name`, `description`, `path`, `size_id`, `cat_id`, `sf_id`, `brand_id`, `concentration_id`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$insert) {
        @unlink($absolutePath);
        throw new RuntimeException("Product insert preparation failed.");
    }

    $insert->bind_param(
        "sssiiiii",
        $pname,
        $description,
        $relativePath,
        $sizeId,
        $categoryId,
        $sfId,
        $brandId,
        $concentrationId
    );

    if (!$insert->execute()) {
        $insert->close();
        @unlink($absolutePath);
        if ($connection->errno === 1062) {
            exit("Product Has Been Already Registered");
        }
        throw new RuntimeException("Product insert failed.");
    }

    $insert->close();
    echo "Success";

} catch (Throwable $e) {
    error_log("Zeyara product registration error: " . $e->getMessage());
    http_response_code(500);
    echo "Product registration is temporarily unavailable. Please try again.";
}
