<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "chicken_grazing_monitoring";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>