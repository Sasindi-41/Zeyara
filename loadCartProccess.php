<?php

require_once __DIR__ . "/connection.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Require an authenticated user.
if (
    !isset($_SESSION["u"]) ||
    !is_array($_SESSION["u"]) ||
    !isset($_SESSION["u"]["id"]) ||
    !filter_var($_SESSION["u"]["id"], FILTER_VALIDATE_INT) ||
    (int) $_SESSION["u"]["id"] <= 0
) {
    http_response_code(401);
    exit;
}

$userId = (int) $_SESSION["u"]["id"];
$netTotal = 0.0;

// Escape all values displayed in HTML.
function cartEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

try {
    Database::setupConnection();

    $db = Database::$connection;

    if (!$db instanceof mysqli) {
        throw new RuntimeException("Database connection unavailable.");
    }

    // Fetch only the authenticated user's cart items.
    $sql = "
        SELECT
            c.cart_id,
            c.cart_qty,
            p.name,
            p.path,
            st.price,
            sf.sf_name,
            sz.size_name
        FROM cart AS c
        INNER JOIN stock AS st
            ON c.stock_stock_id = st.stock_id
        INNER JOIN product AS p
            ON st.product_id = p.id
        INNER JOIN scent_family AS sf
            ON p.sf_id = sf.sf_id
        INNER JOIN size AS sz
            ON p.size_id = sz.size_id
        WHERE c.user_id = ?
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException("Cart query preparation failed.");
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $rs = $stmt->get_result();
    $num = $rs->num_rows;

} catch (Throwable $e) {
    error_log("Cart loading error: " . $e->getMessage());
    http_response_code(500);
    echo "Unable to load your cart. Please try again.";
    exit;
}

if ($num > 0) {
?>

    <div class="mb-4 mt-4 justify-content-center d-flex">
        <h3>Shopping Cart</h3>
    </div>

    <?php while ($d = $rs->fetch_assoc()) {

        $cartId = (int) $d["cart_id"];
        $quantity = max(0, (int) $d["cart_qty"]);
        $price = (float) $d["price"];

        $total = $price * $quantity;
        $netTotal += $total;

        // Safe values for HTML and JavaScript.
        $imagePath = cartEscape($d["path"]);
        $productName = cartEscape($d["name"]);
        $scentFamily = cartEscape($d["sf_name"]);
        $sizeName = cartEscape($d["size_name"]);

    ?>

        <div class="col-md-8 col-lg-6 mx-auto mb-4">
            <div class="card border border-3 border-light rounded-5 p-3">
                <div class="row">

                    <div class="col-md-4">
                        <img
                            src="<?php echo $imagePath; ?>"
                            class="card-img-top rounded-4"
                            alt="Product Image"
                        >
                    </div>

                    <div class="col-md-8">
                        <div class="card-body">

                            <h5 class="card-title">
                                <?php echo $productName; ?>
                            </h5>

                            <p class="card-text mb-1">
                                <small class="text-muted">
                                    Scent Famliy: <?php echo $scentFamily; ?>
                                </small>
                            </p>

                            <p class="card-text mb-3">
                                <small class="text-muted">
                                    Size: <?php echo $sizeName; ?>
                                </small>
                            </p>

                            <h6 class="card-text text-warning">
                                Price: Rs <?php echo number_format($price, 2); ?>
                            </h6>

                            <div class="d-flex justify-content-between align-items-center mt-3">

                                <div class="d-flex align-items-center">

                                    <button
                                        class="btn btn-light btn-sm me-2"
                                        onclick="decrementCartQty(<?php echo $cartId; ?>);"
                                    >-</button>

                                    <input
                                        type="number"
                                        id="qty<?php echo $cartId; ?>"
                                        class="form-control form-control-sm text-center"
                                        style="max-width: 100px;"
                                        value="<?php echo $quantity; ?>"
                                        disabled
                                    >

                                    <button
                                        class="btn btn-light btn-sm ms-2"
                                        onclick="incrementCartQty(<?php echo $cartId; ?>);"
                                    >+</button>

                                </div>

                                <div class="d-flex align-items-center">
                                    <button
                                        class="btn btn-danger btn-sm"
                                        onclick="removeCart(<?php echo $cartId; ?>);"
                                    >Remove</button>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    <?php } ?>

    <div class="col-12 mt-4">
        <hr />
    </div>

    <!-- Checkout -->
    <div class="d-flex flex-column align-items-center justify-content-center mt-3">

        <h6>
            Number of Items:
            <span class="text-warning"><?php echo $num; ?></span>
        </h6>

        <h5>
            Delivery Fees:
            <span class="text-warning">Rs:500</span>
        </h5>

        <h3>
            Net Total:
            <span class="text-warning">
                Rs: <?php echo number_format($netTotal + 500, 2); ?>
            </span>
        </h3>

        <button
            class="btn btn-outline-warning col-3 mt-3 mb-4"
            onclick="checkout();"
        >CHECKOUT</button>

    </div>
    <!-- Checkout -->

<?php

    $stmt->close();

} else {
    $stmt->close();
?>

    <!-- Empty cart -->
    <div class="col-12 text-center mt-5">
        <h2 class="mt-5 mb-5">Your Cart is Empty!</h2>

        <a href="index.php">
            <button class="btn btn-outline-warning mt-2">
                Start Shopping
            </button>
        </a>
    </div>
    <!-- Empty cart -->

<?php
}

?>
