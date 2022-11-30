<?php
$servername = "localhost";
$username = "root";
$password = "";
$db = "hows";
$mysql = new mysqli($servername, $username, $password, $db);

if (!$mysql) {
    die("Connection Error: " . mysqli_connect_error());
}
