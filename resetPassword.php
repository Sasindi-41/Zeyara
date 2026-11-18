<?php
$vcode = $_GET["vcode"] ?? "";

$validToken = is_string($vcode)
    && preg_match('/^[a-f0-9]{64}$/D', $vcode);

$safeToken = $validToken
    ? htmlspecialchars($vcode, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")
    : "";

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zeyara Perfumes</title>
    <link rel="stylesheet" href="bootstrap.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="icon" href="resources/icon/perfume.png">
</head>
<body class="signin_body">

    <div class="signIn_Box" id="signin_Box">

        <div>
            <h2 class="text-center">Reset Password</h2>
        </div>

        <div class="d-none">
            <input type="hidden" id="vcode" value="<?php echo $safeToken; ?>"/>
        </div>

        <?php if (!$validToken): ?>
            <div class="mt-4 mb-4">
                <div class="alert alert-danger">
                    This password reset link is invalid. Please request a new one.
                </div>
            </div>
        <?php else: ?>

            <div class="mt-4 mb-4">
                <label for="np">Password:</label>
                <input
                    type="password"
                    class="form-control"
                    id="np"
                    autocomplete="new-password"
                    maxlength="45"
                />
            </div>

            <div class="mt-4 mb-4">
                <label for="np2">Re-enter Password:</label>
                <input
                    type="password"
                    class="form-control"
                    id="np2"
                    autocomplete="new-password"
                    maxlength="45"
                />
            </div>

            <div class="d-none" id="msgDiv2">
                <div class="alert alert-danger" id="msg"></div>
            </div>

            <div class="mt-4">
                <button
                    type="button"
                    class="btn btn-secondary col-12"
                    onclick="resetPassword();"
                >
                    Update
                </button>
            </div>

        <?php endif; ?>

    </div>

    <script src="script.js"></script>
</body>
</html>