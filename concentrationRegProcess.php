<?php
include "connection.php";

$con = $_POST["c"];

if (empty($con)) {
    echo("Enter Concentration");
}elseif(strlen($con) > 20 ){
    echo("Characters Should Be less Than 20");
}else{
    $rs = Database::search("SELECT * FROM `concentration` WHERE `cname` = '".$con."' ");
    $num = $rs->num_rows;

    if ($num == 0) {
        Database::iud("INSERT INTO `concentration` (`cname`) VALUES ('".$con."') ");
        echo("Success");
    } else {
        echo("Concentration Already Exists");
    }
    
}

?>