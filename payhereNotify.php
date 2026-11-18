<?php

require_once __DIR__ . '/privateConfig.php';
require_once "connection.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function notifyFinish($status = 200)
{
    http_response_code($status);
    exit;
}

function notifyField($name)
{
    $value = $_POST[$name] ?? null;
    return is_string($value) ? trim($value) : "";
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    notifyFinish(405);
}

$merchantId = notifyField("merchant_id");
$orderId = notifyField("order_id");
$paymentId = notifyField("payment_id");
$amount = notifyField("payhere_amount");
$currency = notifyField("payhere_currency");
$statusCode = notifyField("status_code");
$receivedSignature = strtoupper(notifyField("md5sig"));

$expectedMerchantId = getenv("ZEYARA_PAYHERE_MERCHANT_ID");
$merchantSecret = getenv("ZEYARA_PAYHERE_MERCHANT_SECRET");

if ($expectedMerchantId === false || trim($expectedMerchantId) === "") {
    $expectedMerchantId = "1224066";
}

if (
    $merchantSecret === false ||
    trim($merchantSecret) === "" ||
    $merchantId === "" ||
    $orderId === "" ||
    $paymentId === "" ||
    $amount === "" ||
    $currency === "" ||
    $statusCode === "" ||
    $receivedSignature === ""
) {
    notifyFinish(400);
}

if (
    !preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/', $amount) ||
    !preg_match('/^[A-Z]{3}$/', $currency) ||
    !in_array($statusCode, ["2", "0", "-1", "-2", "-3"], true)
) {
    notifyFinish(400);
}

if (!hash_equals((string) $expectedMerchantId, $merchantId)) {
    notifyFinish(400);
}

$localSignature = strtoupper(
    md5(
        $merchantId .
        $orderId .
        $amount .
        $currency .
        $statusCode .
        strtoupper(md5($merchantSecret))
    )
);

if (!hash_equals($localSignature, $receivedSignature)) {
    error_log("PayHere notification rejected: invalid signature.");
    notifyFinish(400);
}

try {
    Database::setupConnection();
    $db = Database::$connection;

    $db->begin_transaction();

    $orderStmt = $db->prepare(
        "SELECT ohid, amount, currency, payment_status, payment_id
         FROM order_history
         WHERE order_id = ?
         LIMIT 1
         FOR UPDATE"
    );

    $orderStmt->bind_param("s", $orderId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        $db->rollback();
        notifyFinish(404);
    }

    $orderHistoryId = (int) $order["ohid"];
    $storedAmount = number_format((float) $order["amount"], 2, ".", "");

    if (
        $order["currency"] !== $currency ||
        $storedAmount !== number_format((float) $amount, 2, ".", "")
    ) {
        $db->rollback();
        error_log("PayHere notification rejected: order amount or currency mismatch.");
        notifyFinish(400);
    }

    $currentStatus = $order["payment_status"];
    $existingPaymentId = $order["payment_id"];

    // Handle chargebacks before the normal PAID idempotency check.
    // A chargeback must never automatically restore stock.
    if ($statusCode === "-3") {
        if (
            in_array($currentStatus, ["PAID", "CHARGEDBACK"], true) &&
            is_string($existingPaymentId) &&
            hash_equals($existingPaymentId, $paymentId)
        ) {
            if ($currentStatus === "PAID") {
                $chargebackStmt = $db->prepare(
                    "UPDATE order_history
                     SET payment_status = 'CHARGEDBACK'
                     WHERE ohid = ? AND payment_status = 'PAID'"
                );

                if (!$chargebackStmt) {
                    throw new RuntimeException("Unable to prepare chargeback update.");
                }

                $chargebackStmt->bind_param("i", $orderHistoryId);

                if (!$chargebackStmt->execute()) {
                    $chargebackStmt->close();
                    throw new RuntimeException("Unable to update chargeback status.");
                }

                $chargebackStmt->close();
            }

            $db->commit();
            notifyFinish(200);
        }

        if (
            in_array($currentStatus, ["PAID", "CHARGEDBACK"], true)
        ) {
            $db->rollback();
            error_log("PayHere chargeback rejected: payment ID mismatch.");
            notifyFinish(409);
        }

        // Ignore a chargeback notification for an order that was never confirmed paid.
        $db->commit();
        notifyFinish(200);
    }

    // An already confirmed payment must never be processed twice.
    if ($currentStatus === "PAID") {
        if (
            is_string($existingPaymentId) &&
            hash_equals($existingPaymentId, $paymentId)
        ) {
            $db->commit();
            notifyFinish(200);
        }

        $db->rollback();
        error_log("PayHere notification rejected: conflicting payment for paid order.");
        notifyFinish(409);
    }

    // Pending notifications do not change order or stock state.
    if ($statusCode === "0") {
        $db->commit();
        notifyFinish(200);
    }

    // Do not allow a later failure/cancellation to overwrite a final state.
    if ($currentStatus !== "PENDING") {
        $db->commit();
        notifyFinish(200);
    }

    if (in_array($statusCode, ["-1", "-2"], true)) {
        $newStatus = ($statusCode === "-1") ? "CANCELLED" : "FAILED";

        $releaseStmt = $db->prepare(
            "UPDATE stock_reservations
             SET reservation_status = 'RELEASED',
                 updated_at = UTC_TIMESTAMP()
             WHERE order_history_ohid = ?
               AND reservation_status = 'ACTIVE'"
        );
        $releaseStmt->bind_param("i", $orderHistoryId);
        $releaseStmt->execute();
        $releaseStmt->close();

        $failedStmt = $db->prepare(
            "UPDATE order_history
             SET payment_status = ?
             WHERE ohid = ? AND payment_status = 'PENDING'"
        );
        $failedStmt->bind_param("si", $newStatus, $orderHistoryId);
        $failedStmt->execute();
        $failedStmt->close();

        $db->commit();
        notifyFinish(200);
    }

    if ($statusCode !== "2") {
        $db->rollback();
        notifyFinish(400);
    }

    // Success: lock all reservations belonging to this order.
    $reservationStmt = $db->prepare(
        "SELECT reservation_id, stock_stock_id, quantity,
                reservation_status
         FROM stock_reservations
         WHERE order_history_ohid = ?
         ORDER BY stock_stock_id ASC
         FOR UPDATE"
    );

    $reservationStmt->bind_param("i", $orderHistoryId);
    $reservationStmt->execute();
    $reservationResult = $reservationStmt->get_result();

    $reservations = [];

    while ($reservation = $reservationResult->fetch_assoc()) {
        $reservations[] = $reservation;
    }

    $reservationStmt->close();

    $canFulfil = count($reservations) > 0;

    foreach ($reservations as $reservation) {
        if ($reservation["reservation_status"] !== "ACTIVE") {
            $canFulfil = false;
            break;
        }
    }

    $stockCheckStmt = $db->prepare(
        "SELECT qty
         FROM stock
         WHERE stock_id = ?
         FOR UPDATE"
    );

    $otherReservedStmt = $db->prepare(
        "SELECT COALESCE(SUM(quantity), 0) AS reserved_qty
         FROM stock_reservations
         WHERE stock_stock_id = ?
           AND reservation_status = 'ACTIVE'
           AND expires_at > UTC_TIMESTAMP()
           AND reservation_id <> ?"
    );

    $commitItems = [];

    if ($canFulfil) {
        foreach ($reservations as $reservation) {
            $stockId = (int) $reservation["stock_stock_id"];
            $quantity = (int) $reservation["quantity"];
            $reservationId = (int) $reservation["reservation_id"];

            $stockCheckStmt->bind_param("i", $stockId);
            $stockCheckStmt->execute();
            $stock = $stockCheckStmt->get_result()->fetch_assoc();

            $otherReservedStmt->bind_param("ii", $stockId, $reservationId);
            $otherReservedStmt->execute();
            $otherReserved = $otherReservedStmt->get_result()->fetch_assoc();

            if (
                !$stock ||
                $quantity < 1 ||
                ((int) $stock["qty"] - (int) $otherReserved["reserved_qty"]) < $quantity
            ) {
                $canFulfil = false;
                break;
            }

            $commitItems[] = [
                "stock_id" => $stockId,
                "quantity" => $quantity,
                "reservation_id" => $reservationId
            ];
        }
    }

    $stockCheckStmt->close();
    $otherReservedStmt->close();

    if (!$canFulfil) {
        // Funds may have been captured, but stock cannot safely be fulfilled.
        // Preserve the payment reference for manual review/refund.
        $reviewStmt = $db->prepare(
            "UPDATE order_history
             SET payment_status = 'REVIEW',
                 payment_id = ?,
                 paid_at = UTC_TIMESTAMP()
             WHERE ohid = ? AND payment_status = 'PENDING'"
        );
        $reviewStmt->bind_param("si", $paymentId, $orderHistoryId);
        $reviewStmt->execute();
        $reviewStmt->close();

        $releaseStmt = $db->prepare(
            "UPDATE stock_reservations
             SET reservation_status = 'RELEASED',
                 updated_at = UTC_TIMESTAMP()
             WHERE order_history_ohid = ?
               AND reservation_status = 'ACTIVE'"
        );
        $releaseStmt->bind_param("i", $orderHistoryId);
        $releaseStmt->execute();
        $releaseStmt->close();

        $db->commit();
        error_log("PayHere payment requires manual review: stock unavailable.");
        notifyFinish(200);
    }

    // Deduct stock only after every item has passed validation.
    $deductStmt = $db->prepare(
        "UPDATE stock
         SET qty = qty - ?
         WHERE stock_id = ? AND qty >= ?"
    );

    $commitReservationStmt = $db->prepare(
        "UPDATE stock_reservations
         SET reservation_status = 'COMMITTED',
             updated_at = UTC_TIMESTAMP()
         WHERE reservation_id = ?
           AND reservation_status = 'ACTIVE'"
    );

    foreach ($commitItems as $item) {
        $quantity = $item["quantity"];
        $stockId = $item["stock_id"];

        $deductStmt->bind_param("iii", $quantity, $stockId, $quantity);
        $deductStmt->execute();

        if ($deductStmt->affected_rows !== 1) {
            throw new RuntimeException("Stock deduction failed.");
        }

        $reservationId = $item["reservation_id"];
        $commitReservationStmt->bind_param("i", $reservationId);
        $commitReservationStmt->execute();

        if ($commitReservationStmt->affected_rows !== 1) {
            throw new RuntimeException("Reservation commit failed.");
        }
    }

    $deductStmt->close();
    $commitReservationStmt->close();

    $paidStmt = $db->prepare(
        "UPDATE order_history
         SET payment_status = 'PAID',
             payment_id = ?,
             paid_at = UTC_TIMESTAMP()
         WHERE ohid = ? AND payment_status = 'PENDING'"
    );

    $paidStmt->bind_param("si", $paymentId, $orderHistoryId);
    $paidStmt->execute();

    if ($paidStmt->affected_rows !== 1) {
        throw new RuntimeException("Order payment update failed.");
    }

    $paidStmt->close();

    // Cart deletion is limited to this customer and the purchased stock IDs.
    $deleteCartStmt = $db->prepare(
        "DELETE FROM cart
         WHERE user_id = ? AND stock_stock_id = ?"
    );

    $cartUserId = null;

    // The order owner is obtained from the order itself.
    $ownerStmt = $db->prepare(
        "SELECT user_id FROM order_history WHERE ohid = ?"
    );
    $ownerStmt->bind_param("i", $orderHistoryId);
    $ownerStmt->execute();
    $owner = $ownerStmt->get_result()->fetch_assoc();
    $ownerStmt->close();

    $cartUserId = (int) $owner["user_id"];

    foreach ($commitItems as $item) {
        $stockId = $item["stock_id"];
        $deleteCartStmt->bind_param("ii", $cartUserId, $stockId);
        $deleteCartStmt->execute();
    }

    $deleteCartStmt->close();

    $db->commit();
    notifyFinish(200);

} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) {
        try {
            $db->rollback();
        } catch (Throwable $ignored) {
        }
    }

    error_log("PayHere notification processing failed: " . $e->getMessage());
    notifyFinish(500);
}

