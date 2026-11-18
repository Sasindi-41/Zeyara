<?php

require_once __DIR__ . '/privateConfig.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/connection.php";
require_once __DIR__ . "/Exception.php";
require_once __DIR__ . "/PHPMailer.php";
require_once __DIR__ . "/SMTP.php";

function finishPasswordResetRequest(): void
{
    echo "Success";
    exit;
}

$email = trim($_POST["e"] ?? "");

if (
    $email === "" ||
    strlen($email) > 100 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    finishPasswordResetRequest();
}

$smtpHost = getenv("ZEYARA_SMTP_HOST");
$smtpUsername = getenv("ZEYARA_SMTP_USERNAME");
$smtpPassword = getenv("ZEYARA_SMTP_PASSWORD");
$smtpPort = getenv("ZEYARA_SMTP_PORT");
$baseUrl = rtrim((string) getenv("ZEYARA_BASE_URL"), "/");

if (
    !$smtpHost ||
    !$smtpUsername ||
    !$smtpPassword ||
    !$baseUrl ||
    !filter_var($baseUrl, FILTER_VALIDATE_URL)
) {
    error_log("Zeyara password reset: mail or base URL configuration is missing.");
    finishPasswordResetRequest();
}

$urlParts = parse_url($baseUrl);
$isHttps = isset($urlParts["scheme"]) && strtolower($urlParts["scheme"]) === "https";
$isLocalhost = isset($urlParts["host"]) &&
    in_array(strtolower($urlParts["host"]), ["localhost", "127.0.0.1"], true);

if (!$isHttps && !$isLocalhost) {
    error_log("Zeyara password reset: public reset URL must use HTTPS.");
    finishPasswordResetRequest();
}

try {
    Database::setupConnection();
    $connection = Database::$connection;

    $stmt = $connection->prepare(
        "SELECT `id`, `email` FROM `user` WHERE `email` = ? LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException("Reset account lookup could not be prepared.");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Do not reveal whether an email address is registered.
    if (!$user) {
        finishPasswordResetRequest();
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);

    $update = $connection->prepare(
        "UPDATE `user`
         SET `v_code` = ?, `v_code_expires` = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 MINUTE)
         WHERE `id` = ?"
    );

    if (!$update) {
        throw new RuntimeException("Reset token query could not be prepared.");
    }

    $userId = (int) $user["id"];
    $update->bind_param("si", $tokenHash, $userId);

    if (!$update->execute()) {
        $update->close();
        throw new RuntimeException("Reset token could not be saved.");
    }

    $update->close();

    $resetUrl = $baseUrl . "/resetPassword.php?vcode=" . rawurlencode($token);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = ($smtpPort !== false && ctype_digit((string) $smtpPort))
        ? (int) $smtpPort
        : 465;
    $mail->CharSet = "UTF-8";

    $fromAddress = getenv("ZEYARA_SMTP_FROM") ?: $smtpUsername;
    $mail->setFrom($fromAddress, "Zeyara Perfumes");
    $mail->addAddress($user["email"]);

    $safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES, "UTF-8");

    $mail->isHTML(true);
    $mail->Subject = "Reset your Zeyara account password";
    $mail->Body = '
        <table style="width:100%;font-family:Arial,sans-serif;color:#222;">
            <tr>
                <td align="center">
                    <table style="max-width:600px;width:100%;padding:24px;">
                        <tr>
                            <td align="center">
                                <h1>Zeyara Perfumes</h1>
                                <hr>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h2>Reset Your Password</h2>
                                <p>You requested to change your account password.</p>
                                <p>
                                    <a href="' . $safeResetUrl . '"
                                       style="display:inline-block;padding:14px 22px;
                                       background:#26342d;color:#fff;text-decoration:none;
                                       border-radius:8px;">
                                        Reset Password
                                    </a>
                                </p>
                                <p>This link expires in 30 minutes and can only be used once.</p>
                                <p>If you did not request this change, you can ignore this email.</p>
                                <hr>
                                <p>&copy; 2026 Zeyara Perfumes. All rights reserved.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>';

    $mail->AltBody =
        "Reset your Zeyara password using this link: " .
        $resetUrl .
        "\nThis link expires in 30 minutes.";

    $mail->send();

    finishPasswordResetRequest();

} catch (Throwable $e) {
    error_log("Zeyara password reset request error: " . $e->getMessage());

    // Invalidate a token if the email could not be sent.
    if (isset($connection, $userId) && $connection instanceof mysqli) {
        $clear = $connection->prepare(
            "UPDATE `user`
             SET `v_code` = NULL, `v_code_expires` = NULL
             WHERE `id` = ?"
        );

        if ($clear) {
            $clear->bind_param("i", $userId);
            $clear->execute();
            $clear->close();
        }
    }

    finishPasswordResetRequest();
}
?>