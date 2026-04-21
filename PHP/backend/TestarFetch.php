<?php
require __DIR__ ."/include_me.php";

$soma = null;

if (isset($_POST["soma"])) {
    $nums = json_decode($_POST["soma"], true);
    $num1 = $nums["num1"];
    $num2 = $nums["num2"];
    $soma = $num1 + $num2;
    returnJson(["success" => $soma]);
} else {
    returnJson(["error" => "invalid"]);
}

exit;
?>