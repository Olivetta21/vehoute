<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods:POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$soma = null;

if (isset($_POST["soma"])) {
    $nums = json_decode($_POST["soma"], true);
    $num1 = $nums["num1"];
    $num2 = $nums["num2"];
    $soma = $num1 + $num2;
    echo json_encode(array("success" => $soma));
} else {
    echo json_encode(array("error"=> "invalid"));
}

exit;
?>