<?php

include "connection.php";

$brand = $_POST["m"];

if (empty($brand)) {
    echo ("Please Enter Brand");
}else if(strlen($brand) > 20 ) {
    echo("Characters Should Be Less Than 20");
} else {
    $rs = Database::search("SELECT * FROM `brand` WHERE `brand_name` = '" . $brand . "' ");
    $num = $rs->num_rows;
    if ($num == 0) {
        Database::iud("INSERT INTO `brand` (`brand_name`) VALUES ('" . $brand . "')  ");
        echo ("Success");
    } else {
        echo ("Brand Already Exists");
    }
}
