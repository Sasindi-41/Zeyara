<?php

http_response_code(410);
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

echo json_encode([
    "success" => false,
    "message" => "This payment endpoint has been retired. Please use the secure checkout process."
]);

exit;
