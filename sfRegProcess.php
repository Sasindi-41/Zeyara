<?php

include "connection.php";

$sf = $_POST["c"];

if (empty($sf)) {
    echo("Enter Scent Family");
}elseif (strlen($sf) > 20) {
    echo("Characters Should Be Less than 20");
}else{
    $rs = Database::search("SELECT * FROM `scent_family`WHERE `sf_name` = '".$sf."' ");
    $num = $rs->num_rows;

    if ($num == 0) {
        Database::iud("INSERT INTO `scent_family` (`sf_name`) VALUES ('".$sf."')  ");
        echo("Success");
    } else {
        echo("Scent Family Already Exists");
    }
    
}


?>